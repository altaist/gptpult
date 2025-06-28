<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

// Пропсы от контроллера
const props = defineProps({
  yookassaConfigured: Boolean,
  configurationError: String,
  user: Object
})

const $q = useQuasar()
const isCreatingOrder = ref(false)
const isProcessingPayment = ref(false)
const testAmount = ref(300)

// Создать тестовый заказ
const createTestOrder = async () => {
  if (!props.yookassaConfigured) {
    $q.notify({
      type: 'negative',
      message: 'ЮКасса не настроена',
      position: 'top'
    })
    return
  }

  isCreatingOrder.value = true

  try {
    const response = await fetch(route('payment.test.create-order'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        amount: testAmount.value
      })
    })

    const data = await response.json()

    if (data.success) {
      $q.notify({
        type: 'positive',
        message: 'Тестовый заказ создан',
        position: 'top'
      })
      
      // Перенаправляем на создание платежа ЮКасса
      await createYookassaPayment(data.order_id)
    } else {
      throw new Error(data.error || 'Ошибка создания заказа')
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.message,
      position: 'top'
    })
  } finally {
    isCreatingOrder.value = false
  }
}

// Создать платеж ЮКасса и перенаправить на оплату
const createYookassaPayment = async (orderId) => {
  isProcessingPayment.value = true

  try {
    const url = `/api/payment/yookassa/create/${orderId}`
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include'
    })

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.error || `HTTP Error: ${response.status}`)
    }

    const data = await response.json()

    if (data.success) {
      // Перенаправляем на страницу оплаты ЮКасса
      window.location.href = data.payment_url
    } else {
      throw new Error(data.error || 'Ошибка создания платежа')
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.message,
      position: 'top'
    })
    isProcessingPayment.value = false
  }
}

// Создать платеж ЮКасса и перенаправить на оплату (альтернативный метод через Inertia)
const createYookassaPaymentInertia = (orderId) => {
  isProcessingPayment.value = true

  router.post(route('payment.yookassa.create', orderId), {}, {
    onSuccess: (page) => {
      // Если вернулся JSON с payment_url
      if (page.props?.payment_url) {
        window.location.href = page.props.payment_url
      }
    },
    onError: (errors) => {
      const errorMessage = Object.values(errors)[0] || 'Ошибка создания платежа'
      $q.notify({
        type: 'negative',
        message: errorMessage,
        position: 'top'
      })
      isProcessingPayment.value = false
    },
    onFinish: () => {
      // isProcessingPayment.value будет сброшен в onSuccess или onError
    }
  })
}

// Вернуться на главную
const goToDashboard = () => {
  router.visit('/dashboard')
}
</script>

<template>
  <div class="payment-test-container">
    <!-- Заголовок -->
    <q-card class="payment-card q-mb-md" flat bordered>
      <q-card-section class="text-center">
        <div class="text-h5 text-primary q-mb-sm">
          <q-icon name="payment" class="q-mr-sm" />
          Тестирование интеграции ЮКасса
        </div>
        <div class="text-caption text-grey-6">
          Проверка работы платежной системы ЮКасса
        </div>
      </q-card-section>
    </q-card>

    <!-- Статус конфигурации -->
    <q-card class="config-status-card q-mb-md" flat bordered>
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="settings" class="q-mr-sm" />
          Статус конфигурации
        </div>
        
        <q-item>
          <q-item-section avatar>
            <q-icon 
              :name="yookassaConfigured ? 'check_circle' : 'error'" 
              :color="yookassaConfigured ? 'positive' : 'negative'" 
            />
          </q-item-section>
          <q-item-section>
            <q-item-label>Интеграция ЮКасса</q-item-label>
            <q-item-label caption :class="yookassaConfigured ? 'text-positive' : 'text-negative'">
              {{ yookassaConfigured ? 'Настроена корректно' : (configurationError || 'Не настроена') }}
            </q-item-label>
          </q-item-section>
        </q-item>

        <q-item v-if="user">
          <q-item-section avatar>
            <q-icon name="account_circle" color="blue" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Пользователь</q-item-label>
            <q-item-label caption>{{ user.email }}</q-item-label>
          </q-item-section>
        </q-item>
      </q-card-section>
    </q-card>

    <!-- Настройки тестового платежа -->
    <q-card class="test-settings-card q-mb-md" flat bordered v-if="yookassaConfigured">
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="tune" class="q-mr-sm" />
          Настройки тестового платежа
        </div>
        
        <q-input
          v-model.number="testAmount"
          label="Сумма для тестирования (₽)"
          type="number"
          min="300"
          max="10000"
          outlined
          :rules="[val => val >= 300 || 'Минимальная сумма 300₽']"
        />
      </q-card-section>
    </q-card>

    <!-- Кнопки управления -->
    <q-card class="actions-card q-mb-md" flat bordered>
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="touch_app" class="q-mr-sm" />
          Действия
        </div>
        
        <q-btn
          v-if="yookassaConfigured"
          class="full-width q-mb-md"
          color="primary"
          size="lg"
          label="Создать тестовый платеж"
          icon="payment"
          :loading="isCreatingOrder || isProcessingPayment"
          @click="createTestOrder"
        />

        <q-btn
          v-else
          class="full-width q-mb-md"
          color="negative"
          size="lg"
          label="ЮКасса не настроена"
          icon="error"
          disable
        />

        <q-btn
          class="full-width"
          color="grey"
          size="md"
          label="Вернуться на главную"
          icon="home"
          outline
          :disable="isCreatingOrder || isProcessingPayment"
          @click="goToDashboard"
        />
      </q-card-section>
    </q-card>

    <!-- Инструкции по настройке -->
    <q-card v-if="!yookassaConfigured" class="instructions-card" flat bordered>
      <q-card-section>
        <div class="text-h6 q-mb-md text-negative">
          <q-icon name="warning" class="q-mr-sm" />
          Требуется настройка
        </div>
        
        <div class="text-body1 q-mb-md">
          Для работы с ЮКасса необходимо настроить следующие переменные в .env файле:
        </div>

        <q-list>
          <q-item>
            <q-item-section avatar>
              <q-icon name="vpn_key" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-bold">YOOKASSA_SHOP_ID</q-item-label>
              <q-item-label caption>ID магазина из личного кабинета ЮКасса</q-item-label>
            </q-item-section>
          </q-item>

          <q-item>
            <q-item-section avatar>
              <q-icon name="security" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-bold">YOOKASSA_SECRET_KEY</q-item-label>
              <q-item-label caption>Секретный ключ из личного кабинета ЮКасса</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <q-banner class="bg-blue-1 text-blue-8 q-mt-md" rounded>
          <template v-slot:avatar>
            <q-icon name="info" color="blue" />
          </template>
          Подробная инструкция по настройке находится в файле YOOKASSA_SETUP.md
        </q-banner>
      </q-card-section>
    </q-card>

    <!-- Информация о тестировании -->
    <q-banner v-if="yookassaConfigured" class="bg-green-1 text-green-8 q-mt-md" rounded>
      <template v-slot:avatar>
        <q-icon name="info" color="green" />
      </template>
      <div>
        <div class="text-weight-bold q-mb-xs">Тестовые карты:</div>
        <div>Успешная оплата: 5555555555554444</div>
        <div>Отклоненная оплата: 5555555555554477</div>
        <div>CVC: любой 3-значный код, срок: любая дата в будущем</div>
      </div>
    </q-banner>
  </div>
</template>

<style scoped>
.payment-test-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.payment-card,
.config-status-card,
.test-settings-card,
.actions-card,
.instructions-card {
  background: white;
}

.text-h5,
.text-h6 {
  font-weight: 500;
}
</style> 