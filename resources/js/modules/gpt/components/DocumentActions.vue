<template>
    <div class="actions-column">
        <!-- Если не хватает баланса — панель оплаты -->
        <div v-if="canPay && document.status === 'pre_generated'">
            <DocumentPaymentPanel
                :amount="orderPrice"
                :document="document"
            />
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
import { computed, defineProps, defineEmits } from 'vue';
import DocumentPaymentPanel from '@/modules/gpt/components/DocumentPaymentPanel.vue';

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

// Открыть бот поддержки в Telegram
const openSupportBot = () => {
    // URL бота поддержки (замените на ваш реальный URL)
    const supportBotUrl = 'https://t.me/gptpult_support_bot';
    window.open(supportBotUrl, '_blank');
};
</script>

<style scoped>
/* Колонка с действиями */
.actions-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
    position: sticky;
    top: 100px;
}

/* Карточки действий */
.action-card {
    background: white;
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

.action-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.action-info {
    flex: 1;
}

.action-name {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 4px 0;
}

.action-description {
    font-size: 13px;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

.action-btn {
    min-width: 100px;
    border-radius: 12px;
    font-weight: 600;
    padding: 12px 20px;
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

/* Информационная карточка */
.info-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
}

/* Информационный элемент */
.info-item {
    display: flex;
    align-items: center;
    gap: 16px;
}

.info-icon {
    font-size: 24px;
    color: #6b7280;
    flex-shrink: 0;
}

.info-icon.generating {
    color: #3b82f6;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.info-content {
    flex: 1;
}

.info-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 4px 0;
}

.info-text {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

/* Карточка платежа */
.payment-card {
    /* Удаляем специальные стили для карточки платежа, используем стандартные */
}

/* Карточка ошибки генерации */
.error-card {
    border: 2px solid #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

/* Элементы ошибки */
.error-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.error-icon {
    font-size: 24px;
    color: #ef4444;
    flex-shrink: 0;
    margin-top: 2px;
}

.error-content {
    flex: 1;
}

.error-title {
    font-size: 16px;
    font-weight: 600;
    color: #dc2626;
    margin: 0 0 8px 0;
}

.error-text {
    font-size: 14px;
    color: #7f1d1d;
    margin: 0 0 16px 0;
    line-height: 1.5;
}

.error-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.retry-btn {
    border-radius: 8px;
    font-weight: 600;
    padding: 8px 16px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
}

.retry-btn:hover {
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

.support-btn {
    border-radius: 8px;
    font-weight: 600;
    padding: 8px 16px;
    border: 2px solid #4b5563;
    color: #4b5563;
    transition: all 0.2s ease;
}

.support-btn:hover {
    background: #4b5563;
    color: white;
    transform: translateY(-1px);
}

/* Адаптивность */
@media (max-width: 1024px) {
    .actions-column {
        position: static;
        order: -1;
    }
}

@media (max-width: 768px) {
    .action-card {
        padding: 20px;
        border-radius: 16px;
    }
    
    .action-item {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .action-btn {
        width: 100%;
        min-width: auto;
    }
    
    .error-actions {
        flex-direction: column;
        gap: 8px;
    }
    
    .retry-btn,
    .support-btn {
        width: 100%;
    }
}
</style> 