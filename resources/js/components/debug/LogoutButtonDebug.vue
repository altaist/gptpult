<template>
    <div class="logout-debug-panel" v-if="isDevelopment">
        <h3>🔍 Отладка кнопки выхода</h3>
        <div class="debug-content">
            <div class="debug-row">
                <strong>Показать кнопку:</strong> 
                <span :class="debugInfo.shouldShow ? 'text-success' : 'text-warning'">
                    {{ debugInfo.shouldShow ? '✅ ДА' : '❌ НЕТ' }}
                </span>
            </div>
            
            <div class="debug-row">
                <strong>Причина:</strong> {{ debugInfo.reason }}
            </div>
            
            <div class="debug-criteria" v-if="debugInfo.criteria && debugInfo.criteria.length > 0">
                <strong>Проверенные критерии:</strong>
                <ul>
                    <li v-for="criterion in debugInfo.criteria" :key="criterion">
                        {{ criterion }}
                    </li>
                </ul>
            </div>
            
            <div class="debug-user-info">
                <strong>Данные пользователя:</strong>
                <pre>{{ JSON.stringify(userInfo, null, 2) }}</pre>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { user, isAuthenticated, debugLogoutButtonCriteria } from '@/composables/auth';

const props = defineProps({
    documentsCount: {
        type: Number,
        default: 0
    },
    balance: {
        type: Number,
        default: 0
    }
});

// Проверяем, запущен ли development режим
const isDevelopment = computed(() => {
    return import.meta.env.DEV || 
           window.location.hostname === 'localhost' || 
           window.location.search.includes('debug=1');
});

// Получаем информацию для отладки
const debugInfo = computed(() => {
    return debugLogoutButtonCriteria(props.documentsCount, props.balance);
});

// Подготавливаем информацию о пользователе для отображения
const userInfo = computed(() => {
    if (!isAuthenticated.value || !user.value) {
        return { message: 'Пользователь не авторизован' };
    }
    
    return {
        id: user.value.id,
        name: user.value.name,
        email: user.value.email,
        balance_rub: user.value.balance_rub,
        created_at: user.value.created_at,
        telegram_id: user.value.telegram_id,
        telegram_linked_at: user.value.telegram_linked_at,
        privacy_consent: user.value.privacy_consent,
        hasAutoEmail: user.value.email?.endsWith('@auto.user') || user.value.email?.endsWith('@linked.user'),
        localStorage: {
            auto_auth_token: localStorage.getItem('auto_auth_token') ? '***скрыт***' : null,
            telegram_auth_user_id: localStorage.getItem('telegram_auth_user_id'),
            settings: localStorage.getItem('settings'),
            lang: localStorage.getItem('lang') || localStorage.getItem('locale'),
            shop: localStorage.getItem('shop.main') ? 'есть данные' : null
        }
    };
});
</script>

<style scoped>
.logout-debug-panel {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 400px;
    max-height: 500px;
    overflow-y: auto;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 16px;
    font-size: 12px;
    z-index: 9999;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.logout-debug-panel h3 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #495057;
}

.debug-row {
    margin-bottom: 8px;
    padding: 4px 0;
    border-bottom: 1px solid #e9ecef;
}

.debug-criteria {
    margin: 12px 0;
}

.debug-criteria ul {
    margin: 4px 0;
    padding-left: 20px;
}

.debug-criteria li {
    margin: 2px 0;
    font-size: 11px;
}

.debug-user-info {
    margin-top: 12px;
}

.debug-user-info pre {
    background: #ffffff;
    padding: 8px;
    border-radius: 4px;
    font-size: 10px;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
}

.text-success {
    color: #28a745;
    font-weight: bold;
}

.text-warning {
    color: #ffc107;
    font-weight: bold;
}

@media (max-width: 768px) {
    .logout-debug-panel {
        width: calc(100vw - 40px);
        bottom: 10px;
        right: 10px;
        left: 10px;
    }
}
</style> 