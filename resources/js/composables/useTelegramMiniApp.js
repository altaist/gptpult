import { ref, onMounted } from 'vue'

export function useTelegramMiniApp() {
  const isInitialized = ref(false)
  const isTelegramMiniApp = ref(false)
  const telegramData = ref(null)

  // Функция для добавления Telegram заголовков
  const addTelegramHeaders = (headers = {}) => {
    // Проверяем localStorage
    const storedUserId = localStorage.getItem('telegram_auth_user_id')
    const storedTimestamp = localStorage.getItem('telegram_auth_timestamp')
    
    if (storedUserId && storedTimestamp) {
      headers['X-Telegram-Auth-User-Id'] = storedUserId
      headers['X-Telegram-Auth-Timestamp'] = storedTimestamp
    }
    
    // Проверяем токен предыдущего временного пользователя для переноса документов
    const autoAuthToken = localStorage.getItem('auto_auth_token')
    if (autoAuthToken) {
      headers['X-Auto-Auth-Token'] = autoAuthToken
      // console.log('useTelegramMiniApp: Found auto_auth_token for document transfer')
    }
    
    // Проверяем куки Telegram
    if (document.cookie) {
      document.cookie.split(';').forEach(cookie => {
        const trimmed = cookie.trim()
        if (trimmed.startsWith('telegram_auth_user_')) {
          const [name, value] = trimmed.split('=')
          if (name && value) {
            headers['X-Telegram-Cookie-' + name] = value
          }
        }
      })
    }
    
    return headers
  }

  // Функция для установки куки
  const setCookie = (name, value, days) => {
    let expires = ""
    if (days) {
      const date = new Date()
      date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000))
      expires = "; expires=" + date.toUTCString()
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/; Secure; SameSite=None"
  }

  // Флаг для предотвращения множественных редиректов
  let isRedirecting = false

  onMounted(() => {
    // console.log('useTelegramMiniApp: Initializing...', { 
    //   hasTelegram: !!window.Telegram,
    //   hasWebApp: !!(window.Telegram && window.Telegram.WebApp),
    //   cookies: document.cookie,
    //   userAgent: navigator.userAgent
    // })

    if (window.Telegram && window.Telegram.WebApp) {
      isTelegramMiniApp.value = true
      const tg = window.Telegram.WebApp

      // console.log('useTelegramMiniApp: Telegram WebApp detected', {
      //   initData: tg.initData,
      //   initDataUnsafe: tg.initDataUnsafe,
      //   version: tg.version
      // })

      // Получаем данные пользователя
      if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
        telegramData.value = tg.initDataUnsafe.user
        
        // console.log('useTelegramMiniApp: User data found', {
        //   user: tg.initDataUnsafe.user,
        //   initData: tg.initData
        // })
        
        // Проверяем localStorage на наличие данных авторизации
        const storedUserId = localStorage.getItem('telegram_auth_user_id')
        const storedTimestamp = localStorage.getItem('telegram_auth_timestamp')
        
        // Если есть сохраненные данные, отправляем их с заголовками
        if (storedUserId && storedTimestamp && !isRedirecting) {
          // console.log('useTelegramMiniApp: Found stored auth data, sending with headers')
          
          // Отправляем запрос с заголовками для восстановления сессии
          const headers = addTelegramHeaders({
            'X-Requested-With': 'XMLHttpRequest'
          })
          
          fetch(window.location.href, {
            method: 'GET',
            headers,
            credentials: 'include' // Важно для передачи куки
          }).then(response => {
            // console.log('useTelegramMiniApp: Auth restoration response:', response.status)
            
            // Проверяем специальный заголовок перенаправления
            const redirectUrl = response.headers.get('X-Telegram-Redirect')
            if (redirectUrl && !isRedirecting) {
              // console.log('useTelegramMiniApp: Received redirect header:', redirectUrl)
              isRedirecting = true
              window.location.href = redirectUrl
              return
            }
            
            if (response.ok && window.location.pathname === '/login' && !isRedirecting) {
              // Если успешно и мы на странице логина, перенаправляем
              isRedirecting = true
              window.location.href = '/lk'
            }
          }).catch(error => {
            // console.error('useTelegramMiniApp: Auth restoration failed:', error)
          })
        }
        
        // Проверяем куки на наличие авторизации
        const telegramAuthCookies = document.cookie.split(';')
          .map(cookie => cookie.trim())
          .filter(cookie => cookie.startsWith('telegram_auth_user_'))
        
        if (telegramAuthCookies.length > 0 && !isRedirecting) {
          // console.log('useTelegramMiniApp: Found Telegram auth cookies:', telegramAuthCookies)
          
          // Если мы на странице логина и есть куки авторизации, попробуем перенаправить
          if (window.location.pathname === '/login') {
            // console.log('useTelegramMiniApp: User has auth cookies but on login page, attempting redirect')
            
            const headers = addTelegramHeaders({
              'X-Requested-With': 'XMLHttpRequest'
            })
            
            fetch('/lk', {
              method: 'GET',
              headers,
              credentials: 'include'
            }).then(response => {
              if (response.ok && !isRedirecting) {
                // console.log('useTelegramMiniApp: Redirect to /lk successful')
                isRedirecting = true
                window.location.href = '/lk'
              } else {
                // console.log('useTelegramMiniApp: /lk not accessible, staying on login')
              }
            }).catch(error => {
              // console.error('useTelegramMiniApp: /lk check failed:', error)
            })
          }
        }
        
        // Проверяем, авторизован ли пользователь уже (через Inertia props)
        const isAlreadyAuthenticated = window?.Laravel?.auth?.user || document.querySelector('meta[name="user-authenticated"]')?.content === 'true'
        
        if (isAlreadyAuthenticated) {
          // console.log('useTelegramMiniApp: User already authenticated, skipping data sending')
          
          // Если мы на странице логина и пользователь авторизован, принудительно перенаправляем
          if (window.location.pathname === '/login' && !isRedirecting) {
            // console.log('useTelegramMiniApp: User is on login page but authenticated, redirecting to /lk')
            isRedirecting = true
            window.location.href = '/lk'
            return
          }
        } else {
          // console.log('useTelegramMiniApp: User not authenticated, sending data to server')
          // Отправляем данные для автологина только если не перенаправляемся
          if (!isRedirecting) {
            sendTelegramDataToServer(tg.initData)
          }
        }
      } else {
        // console.log('useTelegramMiniApp: No user data in initDataUnsafe')
      }

      // Настраиваем Mini App
      tg.ready()
      tg.expand()
      
      // Настраиваем кнопку "Назад" если нужно
      if (tg.BackButton) {
        tg.BackButton.hide()
      }

      // Настраиваем главную кнопку если нужно
      if (tg.MainButton) {
        tg.MainButton.hide()
      }

      isInitialized.value = true
    } else {
      // console.log('useTelegramMiniApp: Not running in Telegram WebApp')
    }
  })

  // Отправить данные Telegram на сервер для автологина
  const sendTelegramDataToServer = async (initData) => {
    // Проверяем, не происходит ли уже редирект
    if (isRedirecting) {
      return
    }

    // console.log('useTelegramMiniApp: Sending data to server', {
    //   initData: initData,
    //   url: window.location.href
    // })

    try {
      // Способ 1: Отправляем данные как заголовок к текущей странице с правильными параметрами
      const headers1 = addTelegramHeaders({
        'X-Telegram-Init-Data': initData,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      })
      
      // Создаем URL с параметрами для GET запроса
      const currentUrl = new URL(window.location.href)
      currentUrl.searchParams.set('tgWebAppData', initData)
      
      const response1 = await fetch(currentUrl.toString(), {
        method: 'GET',
        headers: headers1,
        credentials: 'include'
      })
      
      // console.log('useTelegramMiniApp: Header method response', {
      //   status: response1.status,
      //   ok: response1.ok
      // })

      // Проверяем специальный заголовок перенаправления
      const redirectUrl = response1.headers.get('X-Telegram-Redirect')
      if (redirectUrl && !isRedirecting) {
        // console.log('useTelegramMiniApp: Received redirect header from main request:', redirectUrl)
        isRedirecting = true
        window.location.href = redirectUrl
        return
      }

      // Способ 2: Отправляем через специальный API эндпоинт для Telegram WebApp
      try {
        const headers2 = addTelegramHeaders({
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Telegram-Init-Data': initData,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        })
        
        const response2 = await fetch('/telegram/auth', {
          method: 'POST',
          headers: headers2,
          credentials: 'include',
          body: JSON.stringify({
            init_data: initData,
            tgWebAppData: initData
          })
        })
        
        // console.log('useTelegramMiniApp: Telegram auth API response', {
        //   status: response2.status,
        //   ok: response2.ok
        // })
        
        if (response2.ok) {
          const data = await response2.json()
          // console.log('useTelegramMiniApp: Telegram auth API response data', data)
          
          if (data.success && data.user && !isRedirecting) {
            // console.log('useTelegramMiniApp: User authenticated via Telegram WebApp API')
            
            // Сохраняем информацию об авторизации в localStorage для Telegram WebApp
            localStorage.setItem('telegram_auth_user_id', data.user.id)
            localStorage.setItem('telegram_auth_timestamp', Date.now())
            
            // Также сохраняем в куки для дублирования
            setCookie(`telegram_auth_user_${data.user.id}`, data.user.id, 7)
            
            // Очищаем токен временного пользователя, если он был использован
            const hadAutoAuthToken = localStorage.getItem('auto_auth_token')
            if (hadAutoAuthToken) {
              localStorage.removeItem('auto_auth_token')
              // console.log('useTelegramMiniApp: Cleared auto_auth_token after successful Telegram auth')
            }
            
            // Перенаправляем в ЛК если мы на странице логина
            if (window.location.pathname === '/login') {
              isRedirecting = true
              window.location.href = '/lk'
            }
            return
          }
        }
      } catch (apiError) {
        // console.error('useTelegramMiniApp: Telegram auth API method failed', apiError)
      }

      // Способ 3: Используем обычный автологин API как fallback
      try {
        const headers3 = addTelegramHeaders({
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Telegram-Init-Data': initData,
          'X-Requested-With': 'XMLHttpRequest'
        })
        
        const response3 = await fetch('/login/auto', {
          method: 'POST',
          headers: headers3,
          credentials: 'include',
          body: JSON.stringify({
            telegram_init_data: initData
          })
        })
        
        // console.log('useTelegramMiniApp: Auto login API response', {
        //   status: response3.status,
        //   ok: response3.ok
        // })
        
        if (response3.ok) {
          const data = await response3.json()
          // console.log('useTelegramMiniApp: Auto login API response data', data)
          
          if (data.success && data.user && !isRedirecting) {
            // console.log('useTelegramMiniApp: User authenticated via auto login API')
            
            // Сохраняем информацию об авторизации в localStorage для Telegram WebApp
            localStorage.setItem('telegram_auth_user_id', data.user.id)
            localStorage.setItem('telegram_auth_timestamp', Date.now())
            
            // Также сохраняем в куки для дублирования
            setCookie(`telegram_auth_user_${data.user.id}`, data.user.id, 7)
            
            // Очищаем токен временного пользователя, если он был использован
            const hadAutoAuthToken = localStorage.getItem('auto_auth_token')
            if (hadAutoAuthToken) {
              localStorage.removeItem('auto_auth_token')
              // console.log('useTelegramMiniApp: Cleared auto_auth_token after successful Telegram auth')
            }
            
            // УБИРАЕМ window.location.reload() и заменяем на редирект в ЛК
            if (window.location.pathname === '/login') {
              isRedirecting = true
              window.location.href = '/lk'
            }
            return
          }
        }
      } catch (autoLoginError) {
        // console.error('useTelegramMiniApp: Auto login API method failed', autoLoginError)
      }
      
      if (response1.ok) {
        // console.log('Telegram data sent successfully via header method')
        // Попробуем получить ответ
        const responseText = await response1.text()
        // console.log('Response body length:', responseText.length)
      } else {
        // console.error('Server responded with error:', response1.status, response1.statusText)
      }
    } catch (error) {
      // console.error('Error sending Telegram data:', error)
    }
  }

  // Показать главную кнопку
  const showMainButton = (text, onClick) => {
    if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.MainButton) {
      const mainButton = window.Telegram.WebApp.MainButton
      mainButton.text = text
      mainButton.show()
      mainButton.onClick(onClick)
    }
  }

  // Скрыть главную кнопку
  const hideMainButton = () => {
    if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.MainButton) {
      window.Telegram.WebApp.MainButton.hide()
    }
  }

  // Показать кнопку "Назад"
  const showBackButton = (onClick) => {
    if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.BackButton) {
      const backButton = window.Telegram.WebApp.BackButton
      backButton.show()
      backButton.onClick(onClick)
    }
  }

  // Скрыть кнопку "Назад"
  const hideBackButton = () => {
    if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.BackButton) {
      window.Telegram.WebApp.BackButton.hide()
    }
  }

  // Закрыть Mini App
  const closeMiniApp = () => {
    if (window.Telegram && window.Telegram.WebApp) {
      window.Telegram.WebApp.close()
    }
  }

  return {
    isInitialized,
    isTelegramMiniApp,
    telegramData,
    showMainButton,
    hideMainButton,
    showBackButton,
    hideBackButton,
    closeMiniApp
  }
} 