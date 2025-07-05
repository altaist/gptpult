<template>
    <page-layout :auto-auth="true">
        <div class="payment-waiting-container">
            <div class="payment-waiting-card">
                <!-- Иконка загрузки -->
                <div class="loading-icon">
                    <q-circular-progress
                        indeterminate
                        size="120px"
                        :thickness="0.2"
                        color="primary"
                        track-color="grey-3"
                        class="q-ma-md"
                    />
                    <div class="loading-inner-icon">
                        <q-icon name="payment" size="48px" color="primary" />
                    </div>
                </div>

                <!-- Заголовок -->
                <div class="payment-waiting-title">
                    Ожидаем подтверждение оплаты
                </div>

                <!-- Описание -->
                <div class="payment-waiting-description">
                    {{ getStatusMessage() }}
                </div>

                <!-- Информация о заказе -->
                <div v-if="orderInfo" class="order-info">
                    <div class="order-item">
                        <span class="order-label">Номер заказа:</span>
                        <span class="order-value">#{{ orderInfo.id }}</span>
                    </div>
                    <div class="order-item">
                        <span class="order-label">Сумма:</span>
                        <span class="order-value">{{ formatPrice(orderInfo.amount) }} ₽</span>
                    </div>
                </div>

                <!-- Статус платежа -->
                <div class="payment-status">
                    <q-icon 
                        :name="getStatusIcon()" 
                        :color="getStatusColor()" 
                        size="24px" 
                        class="status-icon"
                    />
                    <span :class="`status-text status-${paymentStatus}`">
                        {{ getPaymentStatusText() }}
                    </span>
                </div>

                <!-- Кнопки действий -->
                <div class="action-buttons">
                    <q-btn
                        v-if="paymentStatus === 'completed'"
                        label="Продолжить"
                        color="primary"
                        size="lg"
                        @click="handleSuccess"
                        class="action-btn"
                        unelevated
                        no-caps
                    />
                    
                    <q-btn
                        v-else-if="paymentStatus === 'failed'"
                        label="Попробовать снова"
                        color="negative"
                        size="lg"
                        @click="handleRetry"
                        class="action-btn"
                        unelevated
                        no-caps
                    />
                </div>

                <!-- Кнопка обращения в поддержку -->
                <div class="support-section">
                    <q-btn
                        label="Обратиться в поддержку"
                        color="grey-7"
                        size="md"
                        @click="contactSupport"
                        class="support-btn"
                        outline
                        no-caps
                    />
                </div>

                <!-- Кнопка возврата -->
                <div class="back-button">
                    <q-btn
                        label="Вернуться в личный кабинет"
                        color="grey-7"
                        size="md"
                        @click="goToDashboard"
                        flat
                        no-caps
                    />
                </div>
            </div>
        </div>
    </page-layout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useQuasar } from 'quasar';
import PageLayout from '@/components/shared/PageLayout.vue';
import { apiClient } from '@/composables/api';

const $q = useQuasar();

const props = defineProps({
    orderId: {
        type: Number,
        required: true
    },
    orderInfo: {
        type: Object,
        default: null
    },
    isDocument: {
        type: Boolean,
        default: false
    },
    documentId: {
        type: Number,
        default: null
    }
});

const paymentStatus = ref('pending');
let statusCheckInterval = null;

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price);
};

const getStatusMessage = () => {
    switch (paymentStatus.value) {
        case 'pending':
            return 'Платёж обрабатывается банком. Это может занять несколько минут.';
        case 'completed':
            return 'Оплата успешно завершена!';
        case 'failed':
            return 'Платёж не был завершён. Попробуйте ещё раз.';
        default:
            return 'Проверяем статус вашего платежа...';
    }
};

const getStatusIcon = () => {
    switch (paymentStatus.value) {
        case 'pending':
            return 'schedule';
        case 'completed':
            return 'check_circle';
        case 'failed':
            return 'error';
        default:
            return 'help';
    }
};

const getStatusColor = () => {
    switch (paymentStatus.value) {
        case 'pending':
            return 'orange';
        case 'completed':
            return 'green';
        case 'failed':
            return 'red';
        default:
            return 'grey';
    }
};

const getPaymentStatusText = () => {
    switch (paymentStatus.value) {
        case 'pending':
            return 'Обрабатывается';
        case 'completed':
            return 'Завершён';
        case 'failed':
            return 'Неуспешен';
        default:
            return 'Проверяется';
    }
};

const checkPaymentStatus = async () => {
    try {
        const response = await apiClient.get(route('api.payment.status', props.orderId));
        
        if (response.success) {
            paymentStatus.value = response.status;
            
            // Если статус изменился на completed, обрабатываем успех
            if (response.status === 'completed') {
                setTimeout(() => {
                    handleSuccess();
                }, 1500); // Даем время пользователю увидеть успех
            }
        }
    } catch (error) {
        // console.error('Ошибка проверки статуса платежа:', error);  // Закомментировано для продакшена
        if (retryCount.value < maxRetries.value) {
            retryCount.value++;
            setTimeout(() => {
                checkPaymentStatus();
            }, retryInterval.value);
        }
    }
};

const handleSuccess = () => {
    if (props.isDocument && props.documentId) {
        // Для документов: переход к документу с автозагрузкой генерации
        // Если заказ был для пополнения баланса (без прямого document_id в заказе), 
        // добавляем параметр для автоматического запуска генерации
        router.visit(route('documents.show', props.documentId) + '?autoload=1&start_generation=1');
    } else {
        // Для пополнения баланса: переход в личный кабинет
        goToDashboard();
    }
};

const handleRetry = () => {
    if (props.isDocument && props.documentId) {
        router.visit(route('documents.show', props.documentId));
    } else {
        goToDashboard();
    }
};

const contactSupport = () => {
    // Переход в Telegram бот поддержки
    const supportBotUrl = 'https://t.me/gptpult_help';
    window.open(supportBotUrl, '_blank');
};

const goToDashboard = () => {
    router.visit(route('dashboard'));
};

const startStatusChecking = () => {
    // Проверяем статус сразу
    checkPaymentStatus();
    
    // Затем проверяем каждые 5 секунд
    statusCheckInterval = setInterval(() => {
        if (paymentStatus.value === 'pending') {
            checkPaymentStatus();
        } else {
            clearInterval(statusCheckInterval);
        }
    }, 5000);
};

onMounted(() => {
    startStatusChecking();
});

onUnmounted(() => {
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
    }
});
</script>

<style scoped>
.payment-waiting-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    padding: 20px;
}

.payment-waiting-card {
    background: white;
    border-radius: 16px;
    padding: 48px 40px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
    text-align: center;
    max-width: 500px;
    width: 100%;
}

.loading-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 32px;
}

.loading-inner-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.payment-waiting-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 16px;
}

.payment-waiting-description {
    font-size: 16px;
    color: #64748b;
    margin-bottom: 32px;
    line-height: 1.6;
}

.order-info {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.order-item:last-child {
    margin-bottom: 0;
}

.order-label {
    font-size: 14px;
    color: #64748b;
}

.order-value {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.payment-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 32px;
    padding: 12px 20px;
    border-radius: 8px;
    background: #f1f5f9;
}

.status-text {
    font-weight: 600;
    font-size: 16px;
}

.status-text.status-pending {
    color: #ea580c;
}

.status-text.status-completed {
    color: #16a34a;
}

.status-text.status-failed {
    color: #dc2626;
}

.action-buttons {
    margin-bottom: 24px;
}

.action-btn {
    min-width: 200px;
    padding: 16px 24px;
    border-radius: 12px;
    font-weight: 600;
}

.support-section {
    margin-bottom: 24px;
}

.support-btn {
    min-width: 200px;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
}

.back-button {
    border-top: 1px solid #e2e8f0;
    padding-top: 24px;
}

/* Адаптивность */
@media (max-width: 768px) {
    .payment-waiting-container {
        padding: 16px;
    }
    
    .payment-waiting-card {
        padding: 32px 24px;
    }
    
    .payment-waiting-title {
        font-size: 20px;
    }
    
    .action-btn,
    .support-btn {
        min-width: 100%;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}
</style> 