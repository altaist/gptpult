<template>
    <q-card class="payment-panel q-mt-lg">
        <q-card-section>
            <div class="row items-center justify-between">
                <div class="col">
                    <div class="text-subtitle1">Стоимость документа</div>
                    <div class="text-h6 text-primary">{{ formatPrice(amount) }} ₽</div>
                </div>
                <div class="col-auto">
                    <q-btn
                        label="Оплатить"
                        color="primary"
                        :loading="loading"
                        @click="handlePayment"
                    />
                </div>
            </div>
            
            <!-- Сообщение об ошибке -->
            <div v-if="errorMessage" class="text-negative q-mt-sm">
                <q-icon name="error" class="q-mr-xs" />
                {{ errorMessage }}
            </div>
        </q-card-section>
    </q-card>
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

        const response = await apiClient.post(route('orders.process', props.document.id));

        if (response.redirect) {
            window.location.href = response.redirect;
        } else {
            errorMessage.value = 'Во время обработки произошла ошибка, мы разбираемся с этой проблемой';
        }
    } catch (error) {
        errorMessage.value = error.message || 'Во время обработки произошла ошибка, мы разбираемся с этой проблемой';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.payment-panel {
    position: sticky;
    bottom: 0;
    background: white;
    border-top: 1px solid rgba(0, 0, 0, 0.12);
    z-index: 1000;
}
</style> 