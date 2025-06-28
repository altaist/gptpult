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
        
        // Отправляем данные для автологина
        sendTelegramDataToServer(tg.initData)
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
      // Отправляем данные как заголовок
      const response = await fetch(window.location.href, {
        method: 'GET',
        headers: {
          'X-Telegram-Init-Data': initData,
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      console.log('useTelegramMiniApp: Server response', {
        status: response.status,
        ok: response.ok,
        headers: Object.fromEntries(response.headers.entries())
      })
      
      if (response.ok) {
        console.log('Telegram data sent successfully')
        // Попробуем получить ответ
        const responseText = await response.text()
        console.log('Response body length:', responseText.length)
      } else {
        console.error('Server responded with error:', response.status, response.statusText)
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