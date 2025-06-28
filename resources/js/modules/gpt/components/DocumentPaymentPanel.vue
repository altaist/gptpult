<template>
    <div class="payment-panel">
        <div class="payment-header">
            <div class="payment-icon">
                <q-icon name="payment" />
            </div>
            <div class="payment-title">Оплата документа</div>
        </div>
        
        <div class="payment-content">
            <div class="payment-info">
                <div class="amount-label">Стоимость создания</div>
                <div class="amount-value">{{ formatPrice(amount) }} ₽</div>
            </div>
            
            <q-btn
                label="Оплатить"
                color="primary"
                size="lg"
                :loading="loading"
                @click="handlePayment"
                class="payment-btn"
                unelevated
                no-caps
            />
        </div>
        
        <!-- Сообщение об ошибке -->
        <div v-if="errorMessage" class="error-message">
            <q-icon name="error" class="error-icon" />
            {{ errorMessage }}
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { apiClient } from '@/composables/api';

const props = defineProps({
    amount: {
        type: Number,
        required: true
    },
    document: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['payment']);

const loading = ref(false);
const errorMessage = ref('');

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price);
};

const handlePayment = async () => {
    try {
        loading.value = true;
        errorMessage.value = '';

        // Создаем заказ
        const orderResponse = await apiClient.post(route('orders.process', props.document.id));

        if (!orderResponse.success || !orderResponse.order_id) {
            throw new Error('Ошибка при создании заказа');
        }

        // Создаем платеж ЮКасса и получаем URL для оплаты
        const paymentResponse = await apiClient.post(route('payment.yookassa.create.api', orderResponse.order_id));

        if (paymentResponse.success && paymentResponse.payment_url) {
            // Перенаправляем на оплату ЮКасса
            window.location.href = paymentResponse.payment_url;
        } else {
            throw new Error(paymentResponse.error || 'Ошибка при создании платежа');
        }
    } catch (error) {
        console.error('Ошибка при оплате:', error);
        errorMessage.value = error.message || 'Во время обработки произошла ошибка, мы разбираемся с этой проблемой';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
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
    align-items: center;
    justify-content: space-between;
    gap: 24px;
}

.payment-info {
    flex: 1;
}

.amount-label {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 4px;
}

.amount-value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.payment-btn {
    min-width: 120px;
    padding: 16px 24px;
    border-radius: 12px;
    font-weight: 600;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
}

.payment-btn:hover {
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

/* Адаптивность */
@media (max-width: 768px) {
    .payment-panel {
        padding: 20px;
        border-radius: 16px;
    }
    
    .payment-content {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .payment-btn {
        width: 100%;
        min-width: auto;
    }
    
    .payment-header {
        justify-content: center;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .payment-icon {
        width: 44px;
        height: 44px;
        font-size: 20px;
    }
    
    .payment-title {
        font-size: 18px;
    }
    
    .amount-value {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .payment-panel {
        padding: 16px;
    }
    
    .payment-header {
        margin-bottom: 16px;
        padding-bottom: 12px;
    }
    
    .payment-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .payment-title {
        font-size: 16px;
    }
    
    .amount-value {
        font-size: 18px;
    }
    
    .payment-btn {
        padding: 12px 20px;
    }
}
</style> 