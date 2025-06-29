import { ref } from 'vue';

// Глобальное состояние уведомлений
const notifications = ref([]);
let notificationId = 0;

// Функция для получения иконки по умолчанию
const getDefaultIcon = (type) => {
    const icons = {
        positive: 'check_circle',
        negative: 'error',
        warning: 'warning',
        info: 'info'
    };
    return icons[type] || 'info';
};

// Функция для удаления уведомления
const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index > -1) {
        notifications.value.splice(index, 1);
    }
};

// Функция для показа современного уведомления
export const showModernNotification = (options) => {
    const id = ++notificationId;
    const notification = {
        id,
        type: options.type || 'info', // positive, negative, warning, info
        title: options.title || '',
        message: options.message || '',
        icon: options.icon || getDefaultIcon(options.type),
        timeout: options.timeout || 4000,
        position: options.position || 'top-right',
        showProgress: options.showProgress !== false,
        createdAt: Date.now()
    };

    notifications.value.push(notification);

    // Автоматическое удаление через timeout
    if (notification.timeout > 0) {
        setTimeout(() => {
            removeNotification(id);
        }, notification.timeout);
    }

    return id;
};

// Экспортируем состояние уведомлений для использования в компонентах
export const useModernNotifications = () => {
    return {
        notifications,
        removeNotification,
        showModernNotification
    };
}; 