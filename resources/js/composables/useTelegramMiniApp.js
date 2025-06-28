import { ref, onMounted } from 'vue'

export function useTelegramMiniApp() {
  const isInitialized = ref(false)
  const isTelegramMiniApp = ref(false)
  const telegramData = ref(null)

  onMounted(() => {
    console.log('useTelegramMiniApp: Initializing...', { 
      hasTelegram: !!window.Telegram,
      hasWebApp: !!(window.Telegram && window.Telegram.WebApp)
    })

    if (window.Telegram && window.Telegram.WebApp) {
      isTelegramMiniApp.value = true
      const tg = window.Telegram.WebApp

      console.log('useTelegramMiniApp: Telegram WebApp detected', {
        initData: tg.initData,
        initDataUnsafe: tg.initDataUnsafe,
        version: tg.version
      })

      // Получаем данные пользователя
      if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
        telegramData.value = tg.initDataUnsafe.user
        
        console.log('useTelegramMiniApp: User data found', {
          user: tg.initDataUnsafe.user,
          initData: tg.initData
        })
        
        // Проверяем localStorage на наличие данных авторизации
        const storedUserId = localStorage.getItem('telegram_auth_user_id')
        const storedTimestamp = localStorage.getItem('telegram_auth_timestamp')
        
        // Если есть сохраненные данные, отправляем их с заголовками
        if (storedUserId && storedTimestamp) {
          console.log('useTelegramMiniApp: Found stored auth data, sending with headers')
          
          // Отправляем запрос с заголовками для восстановления сессии
          fetch(window.location.href, {
            method: 'GET',
            headers: {
              'X-Telegram-Auth-User-Id': storedUserId,
              'X-Telegram-Auth-Timestamp': storedTimestamp,
              'X-Requested-With': 'XMLHttpRequest'
            }
          }).then(response => {
            console.log('useTelegramMiniApp: Auth restoration response:', response.status)
            if (response.ok && window.location.pathname === '/login') {
              // Если успешно и мы на странице логина, перенаправляем
              window.location.href = '/lk'
            }
          }).catch(error => {
            console.error('useTelegramMiniApp: Auth restoration failed:', error)
          })
        }
        
        // Проверяем, авторизован ли пользователь уже (через Inertia props)
        const isAlreadyAuthenticated = window?.Laravel?.auth?.user || document.querySelector('meta[name="user-authenticated"]')?.content === 'true'
        
        if (isAlreadyAuthenticated) {
          console.log('useTelegramMiniApp: User already authenticated, skipping data sending')
          
          // Если мы на странице логина и пользователь авторизован, принудительно перенаправляем
          if (window.location.pathname === '/login') {
            console.log('useTelegramMiniApp: User is on login page but authenticated, redirecting to /lk')
            window.location.href = '/lk'
            return
          }
        } else {
          console.log('useTelegramMiniApp: User not authenticated, sending data to server')
          // Отправляем данные для автологина
          sendTelegramDataToServer(tg.initData)
        }
      } else {
        console.log('useTelegramMiniApp: No user data in initDataUnsafe')
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
      console.log('useTelegramMiniApp: Not running in Telegram WebApp')
    }
  })

  // Отправить данные Telegram на сервер для автологина
  const sendTelegramDataToServer = async (initData) => {
    console.log('useTelegramMiniApp: Sending data to server', {
      initData: initData,
      url: window.location.href
    })

    try {
      // Пробуем несколько способов отправки данных
      
      // Способ 1: Отправляем данные как заголовок к текущей странице
      const response1 = await fetch(window.location.href, {
        method: 'GET',
        headers: {
          'X-Telegram-Init-Data': initData,
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      console.log('useTelegramMiniApp: Header method response', {
        status: response1.status,
        ok: response1.ok
      })

      // Способ 2: Отправляем через API эндпоинт для автологина
      try {
        const response2 = await fetch('/login/auto', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Telegram-Init-Data': initData,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            telegram_init_data: initData
          })
        })
        
        console.log('useTelegramMiniApp: API method response', {
          status: response2.status,
          ok: response2.ok
        })
        
        if (response2.ok) {
          const data = await response2.json()
          console.log('useTelegramMiniApp: API response data', data)
          
          if (data.success && data.user) {
            console.log('useTelegramMiniApp: User authenticated via API')
            
            // Сохраняем информацию об авторизации в localStorage для Telegram WebApp
            localStorage.setItem('telegram_auth_user_id', data.user.id)
            localStorage.setItem('telegram_auth_timestamp', Date.now())
            
            // Перезагружаем страницу для применения авторизации
            window.location.reload()
            return
          }
        }
      } catch (apiError) {
        console.error('useTelegramMiniApp: API method failed', apiError)
      }
      
      if (response1.ok) {
        console.log('Telegram data sent successfully via header method')
        // Попробуем получить ответ
        const responseText = await response1.text()
        console.log('Response body length:', responseText.length)
      } else {
        console.error('Server responded with error:', response1.status, response1.statusText)
      }
    } catch (error) {
      console.error('Error sending Telegram data:', error)
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