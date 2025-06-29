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