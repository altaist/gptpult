// TMA Debug Helper
// Временный файл для диагностики проблем с Telegram Mini App

window.TMADebug = {
    // Логирование состояния TMA
    logTMAState() {
        console.group('🔍 TMA Debug: Состояние системы')
        
        console.log('📱 Telegram WebApp:', {
            available: !!(window.Telegram?.WebApp),
            version: window.Telegram?.WebApp?.version,
            platform: window.Telegram?.WebApp?.platform,
            ready: window.Telegram?.WebApp?.isReady,
            expanded: window.Telegram?.WebApp?.isExpanded
        })
        
        if (window.Telegram?.WebApp?.initDataUnsafe) {
            console.log('👤 User Data:', {
                hasUser: !!(window.Telegram.WebApp.initDataUnsafe.user),
                userId: window.Telegram.WebApp.initDataUnsafe.user?.id,
                username: window.Telegram.WebApp.initDataUnsafe.user?.username,
                firstName: window.Telegram.WebApp.initDataUnsafe.user?.first_name,
                isPremium: window.Telegram.WebApp.initDataUnsafe.user?.is_premium
            })
            
            console.log('🔑 Init Data Length:', window.Telegram.WebApp.initData?.length || 0)
        }
        
        console.log('💾 LocalStorage:', {
            authUserId: localStorage.getItem('telegram_auth_user_id'),
            authTimestamp: localStorage.getItem('telegram_auth_timestamp'),
            autoAuthToken: localStorage.getItem('auto_auth_token')
        })
        
        console.log('🍪 Cookies:', {
            all: document.cookie,
            telegramCookies: document.cookie.split(';')
                .map(c => c.trim())
                .filter(c => c.startsWith('telegram_auth_user_'))
        })
        
        console.log('🌐 Page Info:', {
            pathname: window.location.pathname,
            userAgent: navigator.userAgent,
            authenticated: document.querySelector('meta[name="user-authenticated"]')?.content
        })
        
        console.groupEnd()
    },
    
    // Тест аутентификации
    async testAuth() {
        if (!window.Telegram?.WebApp?.initData) {
            console.error('❌ Нет данных Telegram для тестирования')
            return
        }
        
        console.group('🧪 TMA Debug: Тест аутентификации')
        
        try {
            const headers = {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Telegram-Init-Data': window.Telegram.WebApp.initData,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
            
            console.log('📤 Отправляем запрос на /telegram/auth')
            
            const response = await fetch('/telegram/auth', {
                method: 'POST',
                headers,
                credentials: 'include',
                body: JSON.stringify({
                    init_data: window.Telegram.WebApp.initData,
                    tgWebAppData: window.Telegram.WebApp.initData
                })
            })
            
            console.log('📥 Ответ сервера:', {
                status: response.status,
                ok: response.ok,
                headers: Object.fromEntries(response.headers.entries())
            })
            
            if (response.ok) {
                const data = await response.json()
                console.log('✅ Данные ответа:', data)
            } else {
                const error = await response.text()
                console.error('❌ Ошибка ответа:', error)
            }
            
        } catch (error) {
            console.error('❌ Ошибка запроса:', error)
        }
        
        console.groupEnd()
    },
    
    // Очистить все данные аутентификации
    clearAuth() {
        console.log('🧹 Очистка данных аутентификации')
        
        // LocalStorage
        localStorage.removeItem('telegram_auth_user_id')
        localStorage.removeItem('telegram_auth_timestamp')
        localStorage.removeItem('auto_auth_token')
        
        // Cookies
        document.cookie.split(';').forEach(cookie => {
            const eqPos = cookie.indexOf('=')
            const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim()
            if (name.startsWith('telegram_auth_user_')) {
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`
            }
        })
        
        console.log('✅ Данные очищены')
    },
    
    // Проверить сессию
    async checkSession() {
        console.group('🔍 TMA Debug: Проверка сессии')
        
        try {
            const response = await fetch('/lk', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            
            console.log('📊 Статус сессии:', {
                status: response.status,
                ok: response.ok,
                redirectHeader: response.headers.get('X-Telegram-Redirect')
            })
            
        } catch (error) {
            console.error('❌ Ошибка проверки сессии:', error)
        }
        
        console.groupEnd()
    },
    
    // Запустить полную диагностику
    async fullDiagnosis() {
        console.log('🏥 TMA Debug: Запуск полной диагностики')
        
        this.logTMAState()
        await this.checkSession()
        
        if (window.Telegram?.WebApp?.initData) {
            await this.testAuth()
        }
        
        console.log('✅ Диагностика завершена')
    }
}

// Автоматически запускаем диагностику при загрузке в dev режиме
if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('🔧 TMA Debug доступен через window.TMADebug')
        console.log('🔧 Команды: logTMAState(), testAuth(), clearAuth(), checkSession(), fullDiagnosis()')
        
        // Показываем состояние через 2 секунды после загрузки
        setTimeout(() => {
            window.TMADebug.logTMAState()
        }, 2000)
    })
} 