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
        </q-card-section>
    </q-card>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    amount: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['payment']);

const loading = ref(false);

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price);
};

const handlePayment = async () => {
    try {
        loading.value = true;
        emit('payment', props.amount);
    } catch (error) {
        console.error('Ошибка при оплате:', error);
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