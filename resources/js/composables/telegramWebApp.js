/**
 * Composable для работы с Telegram WebApp
 */
export function useTelegramWebApp() {
    /**
     * Проверяет, работает ли приложение в Telegram WebApp
     */
    const isTelegramWebApp = () => {
        return typeof window !== 'undefined' && window.Telegram?.WebApp?.initData;
    };

    /**
     * Получает объект Telegram WebApp
     */
    const getTelegramWebApp = () => {
        return window.Telegram?.WebApp;
    };

    /**
     * Скачать файл с учетом особенностей Telegram WebApp
     */
    const downloadFile = (url, filename) => {
        if (isTelegramWebApp()) {
            // В Telegram WebApp используем openLink для скачивания файла
            // Это откроет файл в браузере системы
            window.Telegram.WebApp.openLink(url);
        } else {
            // В обычном браузере используем стандартное скачивание
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    /**
     * Скачать файл документа (для API endpoint)
     */
    const downloadDocumentFile = async (documentId) => {
        const url = `/documents/${documentId}/download-word`;
        
        if (isTelegramWebApp()) {
            // В Telegram WebApp отправляем POST запрос и обрабатываем JSON ответ
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                if (data.telegram_sent) {
                    return { success: true, telegram_sent: true, message: data.message };
                } else {
                    // Если не отправлено в Telegram, открываем ссылку
                    window.Telegram.WebApp.openLink(data.url);
                    return { success: true, telegram_sent: false, message: data.message };
                }
            } catch (error) {
                throw error;
            }
        } else {
            // В обычном браузере делаем прямой запрос для скачивания файла
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    // Если ошибка, пытаемся получить JSON ответ с ошибкой
                    const errorData = await response.json().catch(() => null);
                    throw new Error(errorData?.message || `HTTP error! status: ${response.status}`);
                }

                // Проверяем, получили ли мы файл или JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    // Получили JSON ответ (вероятно Telegram)
                    const data = await response.json();
                    return { success: true, telegram_sent: data.telegram_sent, message: data.message };
                } else {
                    // Получили файл - создаем blob и скачиваем
                    const blob = await response.blob();
                    const contentDisposition = response.headers.get('content-disposition');
                    let filename = 'document.docx';
                    
                    if (contentDisposition) {
                        const matches = /filename="([^"]*)"/.exec(contentDisposition);
                        if (matches && matches[1]) {
                            filename = matches[1];
                        }
                    }

                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    return { success: true, telegram_sent: false, message: 'Документ успешно скачан' };
                }
            } catch (error) {
                throw error;
            }
        }
    };

    /**
     * Показать главную кнопку в Telegram WebApp
     */
    const showMainButton = (text, onClick) => {
        if (isTelegramWebApp()) {
            const webApp = getTelegramWebApp();
            webApp.MainButton.setText(text);
            webApp.MainButton.show();
            webApp.MainButton.onClick(onClick);
        }
    };

    /**
     * Скрыть главную кнопку в Telegram WebApp
     */
    const hideMainButton = () => {
        if (isTelegramWebApp()) {
            const webApp = getTelegramWebApp();
            webApp.MainButton.hide();
        }
    };

    /**
     * Показать кнопку "Назад" в Telegram WebApp
     */
    const showBackButton = (onClick) => {
        if (isTelegramWebApp()) {
            const webApp = getTelegramWebApp();
            webApp.BackButton.show();
            webApp.BackButton.onClick(onClick);
        }
    };

    /**
     * Скрыть кнопку "Назад" в Telegram WebApp
     */
    const hideBackButton = () => {
        if (isTelegramWebApp()) {
            const webApp = getTelegramWebApp();
            webApp.BackButton.hide();
        }
    };

    /**
     * Установить высоту WebApp
     */
    const expand = () => {
        if (isTelegramWebApp()) {
            getTelegramWebApp().expand();
        }
    };

    /**
     * Закрыть WebApp
     */
    const close = () => {
        if (isTelegramWebApp()) {
            getTelegramWebApp().close();
        }
    };

    /**
     * Получить данные пользователя из Telegram
     */
    const getUserData = () => {
        if (isTelegramWebApp()) {
            const webApp = getTelegramWebApp();
            return webApp.initDataUnsafe?.user || null;
        }
        return null;
    };

    /**
     * Получить тему оформления Telegram
     */
    const getThemeParams = () => {
        if (isTelegramWebApp()) {
            return getTelegramWebApp().themeParams;
        }
        return null;
    };

    return {
        isTelegramWebApp,
        getTelegramWebApp,
        downloadFile,
        downloadDocumentFile,
        showMainButton,
        hideMainButton,
        showBackButton,
        hideBackButton,
        expand,
        close,
        getUserData,
        getThemeParams
    };
} 