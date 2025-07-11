<template>
    <div class="actions-column">
        <!-- Если не хватает баланса — панель оплаты -->
        <div v-if="canPay && document.status === 'pre_generated'">
            <div class="payment-panel">
                
                <div class="payment-content">
                    <div class="subscription-benefits">
                        <div class="benefits-title">Что входит в абонемент:</div>
                        <div class="benefit-item">
                            <q-icon name="check_circle" class="benefit-icon" />
                            <span>3 генерации документов</span>
                        </div>
                        <div class="benefit-item">
                            <q-icon name="check_circle" class="benefit-icon" />
                            <span>Полная работа с деталями</span>
                        </div>
                        <div class="benefit-item">
                            <q-icon name="check_circle" class="benefit-icon" />
                            <span>Скачивание в формате Word</span>
                        </div>
                    </div>
                    <div class="pricing-info">
                        <div class="price-item main-price">
                            <div class="price-label">Стоимость</div>
                            <div class="price-value highlight">300 ₽</div>
                        </div>
                    </div>

                    <!-- Поле email -->
                    <div class="email-input-section">
                        <div class="custom-input-wrapper">
                            <label class="custom-input-label">Email для чека</label>
                            <div class="custom-input-container">
                                <input
                                    v-model="userEmail"
                                    type="email"
                                    class="custom-input"
                                    placeholder="example@mail.ru"
                                    pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}"
                                    title="Введите корректный email адрес"
                                    required
                                />
                                <q-icon name="email" class="input-icon" />
                            </div>
                        </div>
                    </div>

                    <!-- Чекбокс согласия на обработку персональных данных -->
                    <div class="privacy-consent-section">
                        <label class="privacy-consent-wrapper">
                            <input
                                v-model="privacyConsent"
                                type="checkbox"
                                class="privacy-checkbox"
                                required
                            />
                            <span class="privacy-consent-text">
                                Я согласен на обработку персональных данных
                            </span>
                        </label>
                    </div>
                    
                    <q-btn
                        label="Оформить Абонемент"
                        color="primary"
                        size="lg"
                        :loading="isProcessingPayment || isSavingUserContact"
                        :disable="!isEmailValid || !privacyConsent"
                        @click="handleSubscriptionPayment"
                        class="subscription-btn"
                        unelevated
                        no-caps
                    />
                    
                    <!-- Информация о территориальных ограничениях -->
                    <div class="payment-restriction-notice">
                        <q-icon name="info" class="restriction-icon" />
                        <span>Оплата доступна с IP-адресов Российской Федерации</span>
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

        <!-- Кнопка запуска полной генерации в стиле "Действия с документом" -->
        <div v-if="canStartFullGeneration && !canPay" class="generation-action-container">            
            <q-btn 
                class="generate-btn action-generate-btn"
                unelevated
                rounded
                size="lg"
                no-caps
                :loading="isStartingFullGeneration"
                @click="$emit('start-full-generation')"
            >
                <q-icon name="auto_awesome" class="btn-icon" />
                <span>Сгенерировать</span>
            </q-btn>

            <!-- Компактная информация о стоимости -->
            <div class="cost-info">
                <div class="generations-remaining" :class="{ 'last-generation': Math.floor((balance || 0) / 100) === 1 }">
                    {{ getGenerationsText() }}
                </div>
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
import { computed, defineProps, defineEmits, ref, onMounted } from 'vue';
import { apiClient } from '@/composables/api';
import { useQuasar } from 'quasar';

const $q = useQuasar();

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
    },
    user: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['start-full-generation', 'download-word', 'retry-generation']);

// Состояния для email и согласия
const userEmail = ref('');
const privacyConsent = ref(false);
const isSavingUserContact = ref(false);

// Computed свойство для проверки валидности email
const isEmailValid = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const trimmedEmail = userEmail.value.trim();
    
    // Проверяем формат и что email не автогенерированный
    return trimmedEmail && 
           emailRegex.test(trimmedEmail) && 
           !trimmedEmail.endsWith('@auto.user') && 
           !trimmedEmail.endsWith('@linked.user');
});

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

const paymentErrorMessage = ref('');

// Загрузить данные пользователя для контактов
const loadUserContactData = async () => {
    try {
        // Получаем данные пользователя из пропсов
        const user = props.user;
        if (user) {
            // Проверяем, является ли email автогенерированным
            const isAutoGenerated = user.email && (user.email.endsWith('@auto.user') || user.email.endsWith('@linked.user'));
            userEmail.value = isAutoGenerated ? '' : (user.email || '');
            privacyConsent.value = user.privacy_consent || false;
        }
    } catch (error) {
        console.error('Ошибка при загрузке данных пользователя:', error);
    }
};

// Сохранить контактные данные пользователя
const saveUserContact = async () => {
    if (!userEmail.value.trim()) {
        $q.notify({
            type: 'negative',
            message: 'Введите email адрес',
            position: 'top'
        });
        return false;
    }

    // Проверка формата email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(userEmail.value.trim())) {
        $q.notify({
            type: 'negative',
            message: 'Введите корректный email адрес',
            position: 'top'
        });
        return false;
    }

    // Проверка на автогенерированный email
    if (userEmail.value.trim().endsWith('@auto.user') || userEmail.value.trim().endsWith('@linked.user')) {
        $q.notify({
            type: 'negative',
            message: 'Введите действительный email адрес для получения чека',
            position: 'top'
        });
        return false;
    }

    if (!privacyConsent.value) {
        $q.notify({
            type: 'negative',
            message: 'Необходимо дать согласие на обработку персональных данных',
            position: 'top'
        });
        return false;
    }

    isSavingUserContact.value = true;

    try {
        const response = await fetch('/api/user/update-contact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                email: userEmail.value.trim(),
                privacy_consent: privacyConsent.value
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || `HTTP Error: ${response.status}`);
        }

        if (data.success) {
            return true;
        } else {
            throw new Error(data.error || 'Ошибка при сохранении данных');
        }

    } catch (error) {
        console.error('Ошибка при сохранении контактных данных:', error);
        $q.notify({
            type: 'negative',
            message: error.message || 'Ошибка при сохранении контактных данных',
            position: 'top'
        });
        return false;
    } finally {
        isSavingUserContact.value = false;
    }
};

// Открыть бот поддержки в Telegram
const openSupportBot = () => {
    // URL бота поддержки
    const supportBotUrl = 'https://t.me/gptpult_help';
    window.open(supportBotUrl, '_blank');
};

const handleSubscriptionPayment = async () => {
    try {
        paymentErrorMessage.value = '';

        console.log('=== Начало процесса оплаты абонемента ===');
        console.log('Email:', userEmail.value);
        console.log('Согласие:', privacyConsent.value);

        // Сначала сохраняем контактные данные
        console.log('Сохраняем контактные данные...');
        const contactSaved = await saveUserContact();
        if (!contactSaved) {
            console.log('Контактные данные не сохранены');
            return;
        }
        console.log('Контактные данные сохранены успешно');

        // Сначала создаем заказ на пополнение баланса на 300 рублей
        console.log('Создаем заказ...');
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

        console.log('Заказ создан успешно, ID:', orderResponse.order_id);

        // Затем создаем платеж для этого заказа
        console.log('Создаем платеж ЮКасса...');
        const paymentResponse = await apiClient.post(route('api.payment.yookassa.create', orderResponse.order_id));

        if (paymentResponse.success && paymentResponse.confirmation_url) {
            console.log('Платеж создан успешно, URL:', paymentResponse.confirmation_url);
            // Перенаправляем на оплату ЮКасса
            window.location.href = paymentResponse.confirmation_url;
        } else {
            console.error('Некорректный ответ платежного API:', paymentResponse);
            throw new Error(paymentResponse.error || 'Ошибка при создании платежа');
        }
    } catch (error) {
        console.error('=== Ошибка при оплате абонемента ===');
        console.error('Error object:', error);
        console.error('Error message:', error.message);
        
        paymentErrorMessage.value = error.message || 'Во время обработки произошла ошибка, мы разбираемся с этой проблемой';
    }
};

// Загрузить данные пользователя при монтировании компонента
onMounted(async () => {
    await loadUserContactData();
});

const getGenerationsText = () => {
    const remainingGenerations = Math.floor((props.balance || 0) / 100);
    
    if (remainingGenerations === 0) {
        return 'Осталось 0 генераций';
    } else if (remainingGenerations === 1) {
        return 'Осталась 1 генерация';
    } else {
        // Функция для правильного склонения
        const getWordForm = (number) => {
            const lastDigit = number % 10;
            const lastTwoDigits = number % 100;
            
            // Исключения для 11, 12, 13, 14
            if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
                return 'генераций';
            }
            
            // Правила склонения
            if (lastDigit === 1) {
                return 'генерация';
            } else if (lastDigit >= 2 && lastDigit <= 4) {
                return 'генерации';
            } else {
                return 'генераций';
            }
        };
        
        const wordForm = getWordForm(remainingGenerations);
        const verb = remainingGenerations === 1 || (remainingGenerations % 10 === 1 && remainingGenerations % 100 !== 11) 
            ? 'Осталась' 
            : 'Осталось';
            
        return `${verb} ${remainingGenerations} ${wordForm}`;
    }
};
</script>

<style scoped>
/* Колонка с действиями */
.actions-column {
    display: flex;
    flex-direction: column;
    gap: 16px;
    position: sticky;
    top: 24px;
}

/* Панель оплаты */
.payment-panel {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px;
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
    gap: 16px;
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
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.benefits-title {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 12px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 10px;
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
    padding: 14px 20px;
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
    border-radius: 16px;
    padding: 20px;
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
    gap: 16px;
}

.action-info {
    text-align: center;
}

.action-name {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
}

.action-description {
    font-size: 13px;
    color: #64748b;
    line-height: 1.4;
}

.action-btn {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.primary-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    font-size: 16px;
    padding: 18px 36px;
    border-radius: 16px;
    letter-spacing: 0.5px;
    transform: translateZ(0);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.primary-btn:hover {
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
    transform: translateY(-3px) scale(1.02);
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

/* Восстановление размеров в модальном окне */
.q-dialog .actions-column {
    gap: 24px;
}

.q-dialog .payment-panel {
    border-radius: 20px;
    padding: 28px;
}

.q-dialog .action-card {
    border-radius: 20px;
    padding: 28px;
}

.q-dialog .payment-content {
    gap: 20px;
}

.q-dialog .action-item {
    gap: 20px;
}

.q-dialog .action-name {
    font-size: 20px;
    margin-bottom: 8px;
}

.q-dialog .action-description {
    font-size: 14px;
    line-height: 1.5;
}

.q-dialog .action-btn {
    padding: 16px 32px;
}

.q-dialog .subscription-benefits {
    padding: 20px;
}

.q-dialog .benefits-title {
    font-size: 16px;
    margin-bottom: 16px;
}

.q-dialog .benefit-item {
    gap: 12px;
    font-size: 14px;
    margin-bottom: 12px;
}

.q-dialog .subscription-btn {
    padding: 16px 24px;
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

    .cost-info {
        gap: 12px;
        font-size: 12px;
        margin-top: 12px;
        padding: 6px 12px;
    }

    .cost-separator {
        margin: 0 2px;
    }

    .primary-btn {
        padding: 16px 28px;
        font-size: 15px;
        border-radius: 14px;
    }

    .generate-btn {
        font-size: 12px;
        padding: 6px 10px;
        border-radius: 8px;
    }

    .generate-btn .btn-icon {
        font-size: 12px;
        margin-right: 4px;
    }

    .generation-action-container {
        gap: 12px;
    }
}

/* Стили для уведомления о территориальных ограничениях */
.payment-restriction-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
}

.restriction-icon {
    color: #94a3b8;
    font-size: 16px;
    flex-shrink: 0;
}

/* Стили для поля email */
.email-input-section {
    margin-bottom: 0;
}

.custom-input-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.custom-input-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 4px;
}

.custom-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.custom-input {
    width: 100%;
    padding: 12px 40px 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    background: #ffffff;
    transition: all 0.2s ease;
    outline: none;
}

.custom-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.custom-input::placeholder {
    color: #9ca3af;
    font-weight: 400;
}

.input-icon {
    position: absolute;
    right: 12px;
    font-size: 18px;
    color: #64748b;
    pointer-events: none;
}

/* Стили для чекбокса согласия */
.privacy-consent-section {
    margin: 0;
    padding: 14px 16px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.privacy-consent-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    user-select: none;
}

.privacy-checkbox {
    width: 16px;
    height: 16px;
    accent-color: #3b82f6;
    cursor: pointer;
    margin-top: 2px;
}

.privacy-consent-text {
    font-size: 13px;
    color: #374151;
    line-height: 1.4;
    flex: 1;
    font-weight: 500;
}

.privacy-consent-wrapper:hover .privacy-consent-text {
    color: #1f2937;
}

/* Обновленные стили для кнопки */
.subscription-btn:disabled {
    opacity: 0.6;
    transform: none;
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.2);
}

/* Адаптивность для новых элементов */
@media (max-width: 768px) {
    .custom-input {
        font-size: 14px;
        padding: 10px 36px 10px 14px;
    }

    .input-icon {
        right: 10px;
        font-size: 16px;
    }

    .privacy-consent-section {
        padding: 12px 14px;
        margin: 0;
    }

    .privacy-consent-wrapper {
        gap: 8px;
    }

    .privacy-checkbox {
        width: 15px;
        height: 15px;
    }

    .privacy-consent-text {
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .custom-input-label {
        font-size: 13px;
    }

    .custom-input {
        font-size: 13px;
        padding: 10px 34px 10px 12px;
    }

    .privacy-consent-section {
        padding: 10px 12px;
        margin: 0;
    }

    .privacy-consent-text {
        font-size: 11px;
        line-height: 1.3;
    }
}

/* Блок информации о балансе */
.balance-info-block {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
}

.balance-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.balance-label {
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}

.balance-value {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.price-value {
    font-size: 16px;
    font-weight: 600;
    color: #3b82f6;
}

.remaining-value {
    font-size: 16px;
    font-weight: 600;
    color: #059669;
}

/* Компактная информация о стоимости */
.cost-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    margin-top: 12px;
    padding: 0;
    background: transparent;
    border-radius: 8px;
    opacity: 0.8;
    transition: opacity 0.2s ease;
}

.cost-info:hover {
    opacity: 1;
}

.generations-remaining {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    text-align: center;
}

.last-generation {
    color: #ef4444;
    font-weight: 600;
}

/* Контейнер для кнопки генерации */
.generation-action-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Стили кнопки в стиле "Действия с документом" */
.generate-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 13px;
    font-weight: 600;
    border-radius: 10px;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
    transition: all 0.3s ease;
    width: 100%;
    padding: 8px 12px;
}

.generate-btn:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    transform: translateY(-2px);
}

.generate-btn .btn-icon {
    margin-right: 6px;
    font-size: 14px;
}

.action-generate-btn {
    justify-content: flex-start;
}
</style> 