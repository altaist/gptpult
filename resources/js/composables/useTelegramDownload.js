import { ref } from 'vue'

/**
 * Композабл для работы с загрузкой файлов в Telegram Web App
 */
export function useTelegramDownload() {
    const isDownloading = ref(false)

    /**
     * Универсальная функция для загрузки файлов
     * @param {string} url - URL файла
     * @param {string} filename - Имя файла (опционально)
     * @param {Object} options - Дополнительные опции
     */
    const downloadFile = async (url, filename = null, options = {}) => {
        try {
            isDownloading.value = true
            
            // Проверяем, работаем ли в Telegram Web App
            const isTelegramWebApp = window.Telegram?.WebApp?.initData
            
            if (isTelegramWebApp) {
                // В Telegram Web App используем openLink для загрузки файлов
                console.log('useTelegramDownload: Opening file in Telegram Web App:', url)
                window.Telegram.WebApp.openLink(url)
                
                // Показываем уведомление пользователю
                if (options.onSuccess) {
                    options.onSuccess('Файл открыт для скачивания в браузере')
                }
                
                return true
            } else {
                // В обычном браузере используем стандартный метод
                console.log('useTelegramDownload: Using standard download method:', url)
                
                const link = document.createElement('a')
                link.href = url
                if (filename) {
                    link.download = filename
                }
                link.target = '_blank' // Открываем в новой вкладке для надежности
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                
                if (options.onSuccess) {
                    options.onSuccess('Файл успешно скачан')
                }
                
                return true
            }
        } catch (error) {
            console.error('useTelegramDownload: Error downloading file:', error)
            
            if (options.onError) {
                options.onError(error.message || 'Ошибка при скачивании файла')
            }
            
            return false
        } finally {
            isDownloading.value = false
        }
    }

    /**
     * Загрузка файла через API запрос с последующим скачиванием
     * @param {string} apiUrl - URL API для получения файла  
     * @param {Object} requestOptions - Опции для fetch запроса
     * @param {Object} downloadOptions - Опции для скачивания
     */
    const downloadFromApi = async (apiUrl, requestOptions = {}, downloadOptions = {}) => {
        try {
            isDownloading.value = true
            
            console.log('useTelegramDownload: Making API request:', apiUrl)
            
            // Проверяем, работаем ли в Telegram Web App
            const isTelegramWebApp = window.Telegram?.WebApp?.initData
            
            if (isTelegramWebApp) {
                // В Telegram Web App используем прямую ссылку на загрузку
                // Заменяем API endpoint на direct endpoint
                const directUrl = apiUrl.replace('/download-word', '/download-word-direct')
                console.log('useTelegramDownload: Using direct download for Telegram Web App:', directUrl)
                
                // Используем openLink для прямой загрузки
                window.Telegram.WebApp.openLink(directUrl)
                
                if (downloadOptions.onSuccess) {
                    downloadOptions.onSuccess('Файл открыт для скачивания в браузере')
                }
                
                return true
            } else {
                // В обычном браузере используем API + JSON ответ
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        ...requestOptions.headers
                    },
                    ...requestOptions
                })
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`)
                }
                
                const data = await response.json()
                
                if (!data.url) {
                    throw new Error('URL файла не получен от сервера')
                }
                
                // Скачиваем файл
                return await downloadFile(data.url, data.filename, downloadOptions)
            }
            
        } catch (error) {
            console.error('useTelegramDownload: Error in API download:', error)
            
            if (downloadOptions.onError) {
                downloadOptions.onError(error.message || 'Ошибка при получении файла')
            }
            
            return false
        }
    }

    return {
        isDownloading,
        downloadFile,
        downloadFromApi
    }
} 