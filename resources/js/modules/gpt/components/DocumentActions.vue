<template>
    <div class="actions-column">
        <!-- Если не хватает баланса — панель оплаты -->
        <div v-if="canPay && document.status === 'pre_generated'">
            <div class="payment-panel">
                <div class="payment-header">
                    <div class="payment-icon">
                        <q-icon name="credit_card" />
                    </div>
                    <div class="payment-title">Оформить Абонемент</div>
                </div>
                
                <div class="payment-content">
                    <div class="pricing-info">
                        <div class="price-item main-price">
                            <div class="price-label">Стоимость</div>
                            <div class="price-value highlight">300 ₽</div>
                        </div>
                    </div>
                    
                    <q-btn
                        label="Оформить Абонемент"
                        color="primary"
                        size="lg"
                        :loading="isProcessingPayment"
                        @click="handleSubscriptionPayment"
                        class="subscription-btn"
                        unelevated
                        no-caps
                    />
                    
                    <div class="subscription-benefits">
                        <div class="benefits-title">Что входит в абонемент:</div>
                        <div class="benefit-item">
                            <q-icon name="check_circle" class="benefit-icon" />
                            <span>3 генерации документов</span>
                        </div>
                        <div class="benefit-item">
                            <q-icon name="check_circle" class="benefit-icon" />
                            <span>Полное содержание с деталями</span>
                        </div>
                        <div class="benefit-item">
                            <q-icon name="check_circle" class="benefit-icon" />
                            <span>Скачивание в формате Word</span>
                        </div>
                    </div>
                </div>
                
                <!-- Сообщение об ошибке -->
                <div v-if="paymentErrorMessage" class="error-message">
                    <q-icon name="error" class="error-icon" />
                    {{ paymentErrorMessage }}
                </div>
            </div>
        </div>
        <!-- Карточка ошибки генерации -->
        <div v-if="hasGenerationError" class="action-card error-card">
            <div class="error-item">
                <q-icon name="error" class="error-icon" />
                <div class="error-content">
                    <h4 class="error-title">{{ errorTitle }}</h4>
                    <p class="error-text">{{ errorDescription }}</p>
                    <div class="error-actions">
                        <q-btn
                            v-if="canRetryGeneration"
                            label="Попробовать снова"
                            color="primary"
                            size="md"
                            @click="$emit('retry-generation')"
                            class="retry-btn"
                            unelevated
                            no-caps
                        />
                        <q-btn
                            label="Обратиться в поддержку"
                            color="grey-7"
                            size="md"
                            @click="openSupportBot"
                            class="support-btn"
                            icon="fab fa-telegram-plane"
                            outline
                            no-caps
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Кнопка запуска полной генерации -->
        <div v-if="canStartFullGeneration && !canPay" class="action-card">
            <div class="action-item">
                <div class="action-info">
                    <h4 class="action-name">Завершить создание</h4>
                    <p class="action-description">Создать полное содержание документа с деталями</p>
                </div>
                <q-btn
                    label="Создать"
                    color="primary"
                    size="lg"
                    :loading="isStartingFullGeneration"
                    @click="$emit('start-full-generation')"
                    class="action-btn primary-btn"
                    unelevated
                    no-caps
                />
            </div>
        </div>
        
        <!-- Кнопка скачивания Word - ТОЛЬКО для full_generated -->
        <div v-if="isFullGenerationComplete" class="action-card">
            <div class="action-item">
                <div class="action-info">
                    <h4 class="action-name">Скачать документ</h4>
                    <p class="action-description">Получить готовый документ в формате Word</p>
                </div>
                <q-btn
                    label="Скачать"
                    color="positive"
                    size="lg"
                    :loading="isDownloading"
                    @click="$emit('download-word')"
                    class="action-btn success-btn"
                    icon="download"
                    unelevated
                    no-caps
                />
            </div>
        </div>

        <!-- Информационная карточка если генерируется -->
        <div v-if="isGenerating" class="action-card info-card">
            <div class="info-item">
                <q-icon name="autorenew" class="info-icon generating" />
                <div class="info-content">
                    <h4 class="info-title">Генерируется</h4>
                    <p class="info-text">Документ создается, пожалуйста подождите...</p>
                </div>
            </div>
        </div>

        <!-- Информационная карточка если нет доступных действий -->
        <div v-if="!canStartFullGeneration && !isFullGenerationComplete && !isGenerating && !canPay && !hasGenerationError" class="action-card info-card">
            <div class="info-item">
                <q-icon name="info" class="info-icon" />
                <div class="info-content">
                    <h4 class="info-title">Готов к просмотру</h4>
                    <p class="info-text">Документ готов для просмотра и редактирования</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, defineProps, defineEmits, ref } from 'vue';
import { apiClient } from '@/composables/api';

const props = defineProps({
    document: {
        type: Object,
        required: true
    },
    balance: {
        type: Number,
        required: true,
        default: 0
    },
    orderPrice: {
        type: Number,
        required: true
    },
    canStartFullGeneration: {
        type: Boolean,
        default: false
    },
    isFullGenerationComplete: {
        type: Boolean,
        default: false
    },
    isGenerating: {
        type: Boolean,
        default: false
    },
    isStartingFullGeneration: {
        type: Boolean,
        default: false
    },
    isDownloading: {
        type: Boolean,
        default: false
    },
    isProcessingPayment: {
        type: Boolean,
        default: false
    },
    paymentErrorMessage: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['start-full-generation', 'download-word', 'retry-generation']);

const canPay = computed(() => {
    // Показываем панель оплаты только если:
    // 1. Баланса недостаточно И
    // 2. Статус документа pre_generated
    return props.balance < props.orderPrice && props.document?.status === 'pre_generated';
});

const hasGenerationError = computed(() => {
    return ['pre_generation_failed', 'full_generation_failed'].includes(props.document?.status);
});

const errorTitle = computed(() => {
    const status = props.document?.status;
    if (status === 'pre_generation_failed') {
        return 'Ошибка генерации структуры';
    } else if (status === 'full_generation_failed') {
        return 'Ошибка полной генерации';
    }
    return 'Ошибка генерации';
});

const errorDescription = computed(() => {
    const status = props.document?.status;
    if (status === 'pre_generation_failed') {
        return 'Произошла ошибка при создании структуры документа. Попробуйте еще раз или обратитесь в поддержку.';
    } else if (status === 'full_generation_failed') {
        return 'Произошла ошибка при создании полного содержания. Попробуйте еще раз или обратитесь в поддержку.';
    }
    return 'Произошла ошибка при генерации документа.';
});

const canRetryGeneration = computed(() => {
    // Можно повторить генерацию для любого типа ошибки
    return hasGenerationError.value;
});

const isProcessingPayment = ref(false);
const paymentErrorMessage = ref('');

// Открыть бот поддержки в Telegram
const openSupportBot = () => {
    // URL бота поддержки
    const supportBotUrl = 'https://t.me/gptpult_help';
    window.open(supportBotUrl, '_blank');
};

const handleSubscriptionPayment = async () => {
    try {
        isProcessingPayment.value = true;
        paymentErrorMessage.value = '';

        // Сначала создаем заказ на пополнение баланса на 300 рублей
        const orderResponse = await apiClient.post(route('orders.process-without-document'), {
            amount: 300,
            order_data: {
                purpose: 'balance_top_up',
                source_document_id: props.document.id
            }
        });

        if (!orderResponse.success) {
            throw new Error(orderResponse.error || 'Ошибка при создании заказа');
        }

        // Затем создаем платеж для этого заказа
        const paymentResponse = await apiClient.post(route('api.payment.yookassa.create', orderResponse.order_id));

        if (paymentResponse.success && paymentResponse.payment_url) {
            // Перенаправляем на оплату ЮКасса
            window.location.href = paymentResponse.payment_url;
        } else {
            throw new Error(paymentResponse.error || 'Ошибка при создании платежа');
        }
    } catch (error) {
        console.error('Ошибка при оплате абонемента:', error);
        paymentErrorMessage.value = error.message || 'Во время обработки произошла ошибка, мы разбираемся с этой проблемой';
    } finally {
        isProcessingPayment.value = false;
    }
};
</script>

<style scoped>
/* Колонка с действиями */
.actions-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
    position: sticky;
    top: 24px;
}

/* Панель оплаты */
.payment-panel {
    background: #ffffff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.payment-panel:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.payment-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}

.payment-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 16px;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.payment-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}

.payment-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.pricing-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.price-item.main-price {
    padding: 20px 24px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #3b82f6;
}

.price-label {
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}

.main-price .price-label {
    font-size: 16px;
    color: #1e293b;
    font-weight: 600;
}

.price-value {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.price-value.highlight {
    color: #3b82f6;
    font-size: 18px;
    font-weight: 700;
}

.main-price .price-value.highlight {
    font-size: 28px;
    font-weight: 800;
}

.subscription-benefits {
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.benefits-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 12px;
}

.benefit-item:last-child {
    margin-bottom: 0;
}

.benefit-icon {
    color: #10b981;
    font-size: 18px;
    flex-shrink: 0;
}

.subscription-btn {
    width: 100%;
    padding: 16px 24px;
    border-radius: 12px;
    font-weight: 600;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
}

.subscription-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

.error-message {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    padding: 12px 16px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    color: #dc2626;
    font-size: 14px;
}

.error-icon {
    font-size: 18px;
    flex-shrink: 0;
}

/* Карточки действий */
.action-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.action-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.error-card {
    border-color: #fecaca;
    background: linear-gradient(135deg, #fefefe 0%, #fef7f7 100%);
}

.info-card {
    border-color: #bfdbfe;
    background: linear-gradient(135deg, #fefefe 0%, #f0f9ff 100%);
}

.action-item {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.action-info {
    text-align: center;
}

.action-name {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
}

.action-description {
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
}

.action-btn {
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.primary-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.primary-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

.success-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.success-btn:hover {
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    transform: translateY(-1px);
}

/* Информационные карточки */
.info-item, .error-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.info-icon, .error-icon {
    flex-shrink: 0;
    font-size: 28px;
    margin-top: 4px;
}

.info-icon {
    color: #3b82f6;
}

.info-icon.generating {
    animation: spin 2s linear infinite;
}

.error-icon {
    color: #dc2626;
}

.info-content, .error-content {
    flex: 1;
}

.info-title, .error-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
}

.info-title {
    color: #1e293b;
}

.error-title {
    color: #dc2626;
}

.info-text, .error-text {
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 16px;
}

.info-text {
    color: #64748b;
}

.error-text {
    color: #7f1d1d;
}

.error-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.retry-btn, .support-btn {
    border-radius: 10px;
    font-weight: 500;
    padding: 12px 20px;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Адаптивность */
@media (max-width: 768px) {
    .actions-column {
        gap: 16px;
        position: static;
    }
    
    .payment-panel,
    .action-card {
        padding: 20px;
        border-radius: 16px;
    }
    
    .price-item {
        padding: 10px 12px;
    }
    
    .subscription-benefits {
        padding: 12px;
    }
    
    .action-item {
        gap: 16px;
    }
    
    .error-actions {
        flex-direction: column;
    }
}
</style> 