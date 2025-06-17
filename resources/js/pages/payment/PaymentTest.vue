<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

// Пропсы для получения данных от контроллера
const props = defineProps({
  order_id: [String, Number],
  amount: [String, Number],
  description: String
})

const $q = useQuasar()
const isProcessing = ref(false)

// Функция имитации успешной оплаты
const simulateSuccess = () => {
  isProcessing.value = true
  const paymentId = 'test_' + Date.now()
  // Переход по роуту фиксации оплаты (имитация callback платёжки)
  router.visit(route('payment.complete', { orderId: props.order_id, payment_id: paymentId }))
}

// Функция имитации неудачной оплаты  
const simulateFailure = () => {
  isProcessing.value = true
  
  setTimeout(() => {
    isProcessing.value = false
    $q.notify({
      type: 'negative', 
      message: 'Ошибка обработки платежа',
      position: 'top'
    })
  }, 1500)
}

// Функция отмены платежа
const cancelPayment = () => {
  router.visit('/lk')
}
</script>

<template>
  <div class="payment-test-container">
    <!-- Заголовок -->
    <q-card class="payment-card q-mb-md" flat bordered>
      <q-card-section class="text-center">
        <div class="text-h5 text-primary q-mb-sm">
          <q-icon name="payment" class="q-mr-sm" />
          Тестовая страница оплаты
        </div>
        <div class="text-caption text-grey-6">
          Это тестовая страница для имитации процесса оплаты
        </div>
      </q-card-section>
    </q-card>

    <!-- Информация о платеже -->
    <q-card class="payment-info-card q-mb-md" flat bordered>
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="info" class="q-mr-sm" />
          Детали платежа
        </div>
        
        <q-list separator>
          <q-item>
            <q-item-section avatar>
              <q-icon name="receipt" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Номер заказа</q-item-label>
              <q-item-label caption>{{ order_id || 'Не указан' }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section avatar>
              <q-icon name="attach_money" color="green" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Сумма к оплате</q-item-label>
              <q-item-label caption class="text-weight-bold">
                {{ amount ? `${amount} ₽` : 'Не указана' }}
              </q-item-label>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section avatar>
              <q-icon name="description" color="blue" />
            </q-item-section>
            <q-item-section>
              <q-item-label>Описание</q-item-label>
              <q-item-label caption>{{ description || 'Описание не указано' }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>
    </q-card>

    <!-- Кнопки управления -->
    <q-card class="actions-card" flat bordered>
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="touch_app" class="q-mr-sm" />
          Действия
        </div>
        
        <div class="row q-gutter-md">
          <q-btn
            class="col"
            color="positive"
            size="lg"
            label="Успешная оплата"
            icon="check_circle"
            :loading="isProcessing"
            @click="simulateSuccess"
          />
          
          <q-btn
            class="col"
            color="negative"
            size="lg"
            label="Ошибка оплаты"
            icon="error"
            :loading="isProcessing"
            @click="simulateFailure"
          />
        </div>

        <q-btn
          class="full-width q-mt-md"
          color="grey"
          size="md"
          label="Отменить платеж"
          icon="close"
          outline
          :disable="isProcessing"
          @click="cancelPayment"
        />
      </q-card-section>
    </q-card>

    <!-- Информационное сообщение -->
    <q-banner class="bg-blue-1 text-blue-8 q-mt-md" rounded>
      <template v-slot:avatar>
        <q-icon name="info" color="blue" />
      </template>
      Это тестовая страница. В реальной системе здесь будет интеграция с платежными системами.
    </q-banner>
  </div>
</template>

<style scoped>
.payment-test-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.payment-card,
.payment-info-card, 
.actions-card {
  background: white;
}

.text-h5 {
  font-weight: 500;
}

.text-h6 {
  font-weight: 500;
}
</style> 