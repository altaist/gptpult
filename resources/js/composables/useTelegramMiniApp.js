import { ref, onMounted } from 'vue'

export function useTelegramMiniApp() {
  const isInitialized = ref(false)
  const isTelegramMiniApp = ref(false)
  const telegramData = ref(null)
  
  // Глобальный флаг для предотвращения множественных редиректов
  let isRedirecting = false
  
  // Функция добавления Telegram заголовков
  const addTelegramHeaders = (headers = {}) => {
    // Добавляем куки для Telegram WebApp
    const telegramCookies = document.cookie.split(';')
      .map(cookie => cookie.trim())
      .filter(cookie => cookie.startsWith('telegram_auth_user_'))
    
    if (telegramCookies.length > 0) {
      telegramCookies.forEach(cookie => {
        const [key, value] = cookie.split('=')
        headers[`X-Telegram-Cookie-${key}`] = value
      })
    }
    
    // Добавляем данные из localStorage
    const storedUserId = localStorage.getItem('telegram_auth_user_id')
    const storedTimestamp = localStorage.getItem('telegram_auth_timestamp')
    
    if (storedUserId && storedTimestamp) {
      headers['X-Telegram-Auth-User-Id'] = storedUserId
      headers['X-Telegram-Auth-Timestamp'] = storedTimestamp
    }
    
    return headers
  }
  
  // Функция сохранения куки
  const setCookie = (name, value, days) => {
    const expires = new Date()
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000))
    document.cookie = `${name}=${value}; expires=${expires.toUTCString()}; path=/; secure; samesite=none`
  }

  onMounted(() => {
    console.log('useTelegramMiniApp: Инициализация...', { 
      hasTelegram: !!window.Telegram,
      hasWebApp: !!(window.Telegram && window.Telegram.WebApp),
      userAgent: navigator.userAgent,
      currentPath: window.location.pathname
    })

    if (window.Telegram && window.Telegram.WebApp) {
      isTelegramMiniApp.value = true
      const tg = window.Telegram.WebApp

      console.log('useTelegramMiniApp: Telegram WebApp обнаружен', {
        initDataLength: tg.initData ? tg.initData.length : 0,
        hasUser: !!(tg.initDataUnsafe && tg.initDataUnsafe.user),
        version: tg.version
      })

      // Получаем данные пользователя
      if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
        telegramData.value = tg.initDataUnsafe.user
        
        console.log('useTelegramMiniApp: Данные пользователя найдены', {
          userId: tg.initDataUnsafe.user.id,
          username: tg.initDataUnsafe.user.username
        })
        
        // Запускаем процесс аутентификации с задержкой для стабильности
        setTimeout(() => {
          if (!isRedirecting) {
            authenticateUser(tg.initData, tg.initDataUnsafe.user)
          }
        }, 100)
      } else {
        console.log('useTelegramMiniApp: Данные пользователя не найдены в initDataUnsafe')
      }

      // Настраиваем Mini App
      tg.ready()
      tg.expand()
      
      // Настраиваем кнопки
      if (tg.BackButton) {
        tg.BackButton.hide()
      }
      if (tg.MainButton) {
        tg.MainButton.hide()
      }

      isInitialized.value = true
    } else {
      console.log('useTelegramMiniApp: Не работает в Telegram WebApp')
    }
  })

  // Основная функция аутентификации
  const authenticateUser = async (initData, userData) => {
    if (isRedirecting) {
      console.log('useTelegramMiniApp: Редирект уже в процессе, пропускаем аутентификацию')
      return
    }

    console.log('useTelegramMiniApp: Начинаем аутентификацию пользователя')

    try {
      // Шаг 1: Проверяем сохраненную сессию
      const storedUserId = localStorage.getItem('telegram_auth_user_id')
      const storedTimestamp = localStorage.getItem('telegram_auth_timestamp')
      
      if (storedUserId && storedTimestamp) {
        const timestampDiff = Date.now() - parseInt(storedTimestamp)
        
        // Если данные не старше 24 часов
        if (timestampDiff < 24 * 60 * 60 * 1000) {
          console.log('useTelegramMiniApp: Найдена сохраненная сессия, проверяем валидность')
          
          const isValid = await checkStoredSession()
          if (isValid) {
            console.log('useTelegramMiniApp: Сохраненная сессия валидна')
            if (window.location.pathname === '/login') {
              redirectToIntended()
            }
            return
          }
        }
      }

      // Шаг 2: Аутентификация через Telegram данные
      console.log('useTelegramMiniApp: Отправляем данные Telegram для аутентификации')
      const authResult = await sendTelegramAuth(initData)
      
      if (authResult.success) {
        console.log('useTelegramMiniApp: Аутентификация успешна')
        
        // Сохраняем данные сессии
        if (authResult.user) {
          localStorage.setItem('telegram_auth_user_id', authResult.user.id)
          localStorage.setItem('telegram_auth_timestamp', Date.now().toString())
          setCookie(`telegram_auth_user_${authResult.user.id}`, authResult.user.id, 7)
        }
        
        // Перенаправляем если на странице логина
        if (window.location.pathname === '/login') {
          redirectToIntended()
        }
      } else {
        console.warn('useTelegramMiniApp: Аутентификация неуспешна:', authResult.error)
      }
      
    } catch (error) {
      console.error('useTelegramMiniApp: Ошибка аутентификации:', error)
    }
  }

  // Проверка сохраненной сессии
  const checkStoredSession = async () => {
    try {
      const headers = addTelegramHeaders({
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      })
      
      const response = await fetch('/lk', {
        method: 'GET',
        headers,
        credentials: 'include'
      })
      
      return response.ok
    } catch (error) {
      console.error('useTelegramMiniApp: Ошибка проверки сессии:', error)
      return false
    }
  }

  // Отправка данных Telegram для аутентификации
  const sendTelegramAuth = async (initData) => {
    try {
      const headers = addTelegramHeaders({
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'X-Telegram-Init-Data': initData,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      })
      
      const response = await fetch('/telegram/auth', {
        method: 'POST',
        headers,
        credentials: 'include',
        body: JSON.stringify({
          init_data: initData,
          tgWebAppData: initData
        })
      })
      
      if (response.ok) {
        const data = await response.json()
        return {
          success: true,
          user: data.user,
          data: data
        }
      } else {
        const errorData = await response.json().catch(() => ({}))
        return {
          success: false,
          error: errorData.error || `HTTP ${response.status}`
        }
      }
    } catch (error) {
      return {
        success: false,
        error: error.message
      }
    }
  }

  // Функция для получения intended URL
  const getIntendedUrl = () => {
    const intendedUrl = localStorage.getItem('intended_url')
    if (intendedUrl) {
      localStorage.removeItem('intended_url')
      
      // Список разрешенных маршрутов для безопасности
      const allowedRoutes = ['/lk', '/new', '/documents', '/profile']
      
      // Проверяем, что URL начинается с / и входит в разрешенные
      if (intendedUrl.startsWith('/') && allowedRoutes.some(route => intendedUrl.startsWith(route))) {
        return intendedUrl
      }
    }
    
    return '/lk' // По умолчанию ЛК
  }

  // Редирект в ЛК или на intended URL
  const redirectToIntended = () => {
    if (isRedirecting) return
    
    const intendedUrl = getIntendedUrl()
    console.log('useTelegramMiniApp: Перенаправляем на intended URL:', intendedUrl)
    isRedirecting = true
    
    // Небольшая задержка для завершения всех операций
    setTimeout(() => {
      window.location.href = intendedUrl
    }, 100)
  }

  const showBackButton = (callback) => {
    if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.BackButton) {
      window.Telegram.WebApp.BackButton.show()
      window.Telegram.WebApp.BackButton.onClick(callback)
    }
  }

  const hideBackButton = () => {
    if (window.Telegram && window.Telegram.WebApp && window.Telegram.WebApp.BackButton) {
      window.Telegram.WebApp.BackButton.hide()
    }
  }

  return {
    isInitialized,
    isTelegramMiniApp,
    telegramData,
    showBackButton,
    hideBackButton,
    addTelegramHeaders
  }
} 