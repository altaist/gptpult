<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

// Пропсы для получения данных от родительского компонента
const props = defineProps({
  user: Object,
  balance: {
    type: Number,
    default: 0
  },
  documents: {
    type: Array,
    default: () => []
  },
  telegram: {
    type: Object,
    default: () => ({
      is_connected: false,
      username: null,
      connected_at: null
    })
  }
})

// Состояние телеграм подключения
const isTelegramConnected = ref(props.telegram.is_connected)
const telegramUsername = ref(props.telegram.username || '')
const isConnectingTelegram = ref(false)

// Функция для перехода к документу
const viewDocument = (documentId) => {
  router.visit(`/documents/${documentId}`)
}

// Функция для создания нового задания
const createNewTask = () => {
  router.visit('/new')
}

// Функция для получения цвета статуса
const getStatusColor = (document) => {
  // Используем цвет из enum если доступен, иначе fallback
  if (document.status_color) {
    return document.status_color
  }
  
  const statusColors = {
    'draft': 'grey',
    'pre_generating': 'primary',
    'pre_generated': 'positive',
    'pre_generation_failed': 'negative',
    'full_generating': 'secondary',
    'full_generated': 'green',
    'full_generation_failed': 'red',
    'in_review': 'warning',
    'approved': 'green-10',
    'rejected': 'red-8'
  }
  return statusColors[document.status] || 'grey'
}

// Функция для получения русского названия статуса
const getStatusLabel = (document) => {
  // Используем метку из enum если доступна, иначе fallback
  if (document.status_label) {
    return document.status_label
  }
  
  const statusLabels = {
    'draft': 'Черновик',
    'pre_generating': 'Генерируется структура...',
    'pre_generated': 'Структура готова',
    'pre_generation_failed': 'Ошибка генерации структуры',
    'full_generating': 'Генерируется содержимое...',
    'full_generated': 'Полностью готов',
    'full_generation_failed': 'Ошибка полной генерации',
    'in_review': 'На проверке',
    'approved': 'Утвержден',
    'rejected': 'Отклонен'
  }
  return statusLabels[document.status] || document.status
}

// Функция для форматирования даты
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ru-RU')
}

// Функция для пополнения баланса
const topUpBalance = async () => {
  try {
    const response = await fetch('/orders/process', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        order_data: {
          description: "Пополнение баланса",
          purpose: "balance_top_up"
        }
      })
    })
    
    const data = await response.json()
    
    if (data.redirect) {
      window.location.href = data.redirect
    } else if (data.error) {
      console.error('Ошибка при создании заказа:', data.error)
    }
  } catch (error) {
    console.error('Ошибка при пополнении баланса:', error)
  }
}

// Функция для связки с телеграммом
const connectTelegram = async () => {
  try {
    isConnectingTelegram.value = true
    
    const response = await fetch('/telegram/bot-link', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Authorization': `Bearer ${props.user?.auth_token || ''}`
      },
    })
    
    if (response.ok) {
      const data = await response.json()
      // Открываем ссылку на телеграм бот
      window.open(data.bot_url, '_blank')
      
      // Показываем уведомление пользователю
      console.log('Переход в Telegram. После нажатия /start вернитесь сюда для проверки статуса.')
      
      // Запускаем периодическую проверку статуса (каждые 3 секунды в течение 30 секунд)
      startConnectionPolling()
    }
  } catch (error) {
    console.error('Ошибка при получении ссылки на бот:', error)
  } finally {
    isConnectingTelegram.value = false
  }
}

// Периодическая проверка статуса подключения
const startConnectionPolling = () => {
  let attempts = 0
  const maxAttempts = 10 // 10 попыток по 3 секунды = 30 секунд
  
  const pollInterval = setInterval(async () => {
    attempts++
    
    try {
      const response = await fetch('/telegram/check-connection', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Authorization': `Bearer ${props.user?.auth_token || ''}`
        },
      })
      
      if (response.ok) {
        const data = await response.json()
        
        if (data.is_connected && !isTelegramConnected.value) {
          // Аккаунт был связан!
          isTelegramConnected.value = true
          telegramUsername.value = data.telegram_username || ''
          clearInterval(pollInterval)
          
          // Показываем уведомление об успешной связке
          console.log('🎉 Аккаунт успешно связан с Telegram!')
          
          // Можно добавить toast уведомление если у вас есть система уведомлений
        }
      }
    } catch (error) {
      console.error('Ошибка при проверке статуса:', error)
    }
    
    // Останавливаем опрос после максимального количества попыток
    if (attempts >= maxAttempts) {
      clearInterval(pollInterval)
    }
  }, 3000) // Проверяем каждые 3 секунды
}

// Проверка статуса подключения телеграма при загрузке
const checkTelegramConnection = async () => {
  try {
    const response = await fetch('/telegram/check-connection', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Authorization': `Bearer ${props.user?.auth_token || ''}`
      },
    })
    
    if (response.ok) {
      const data = await response.json()
      isTelegramConnected.value = data.is_connected
      telegramUsername.value = data.telegram_username || ''
    }
  } catch (error) {
    console.error('Ошибка при проверке статуса телеграма:', error)
  }
}

// Инициализация при загрузке компонента
onMounted(() => {
  // Проверяем статус подключения телеграма при загрузке только если не был передан в пропсах
  if (!props.telegram.is_connected) {
    checkTelegramConnection()
  }
})
</script>

<template>
  <div class="lk-container">
    <!-- Кнопка связки с телеграммом -->
    <q-card class="telegram-card q-mb-md" flat bordered>
      <q-card-section class="q-pa-md">
        <div class="telegram-content">
          <div class="telegram-info">
            <q-icon name="telegram" size="24px" color="primary" class="q-mr-sm" />
            <div>
              <div class="text-subtitle1 text-weight-medium">
                {{ isTelegramConnected ? 'Телеграм подключен' : 'Подключить Телеграм' }}
              </div>
              <div class="text-caption text-grey-6">
                {{ isTelegramConnected ? 'Получайте уведомления в Telegram' : 'Нажмите кнопку, перейдите в бот и нажмите /start' }}
              </div>
            </div>
          </div>
          <q-btn
            :color="isTelegramConnected ? 'positive' : 'primary'"
            :icon="isTelegramConnected ? 'check' : 'telegram'"
            :label="isTelegramConnected ? `Подключено ${telegramUsername ? '@' + telegramUsername : ''}` : 'Подключить'"
            :disable="isTelegramConnected"
            :loading="isConnectingTelegram"
            @click="connectTelegram"
            no-caps
            unelevated
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- Карточка с балансом -->
    <q-card class="balance-card q-mb-md" flat bordered>
      <q-card-section class="q-pa-lg">
        <div class="balance-content">
          <div class="balance-text">
            <div class="text-h6 text-grey-8">Баланс</div>
            <q-btn
              flat
              dense
              color="primary"
              label="Пополнить"
              size="sm"
              @click="topUpBalance"
              class="q-mt-xs"
            />
          </div>
          <div class="balance-divider"></div>
          <div class="balance-amount">
            <div class="text-h4 text-primary text-weight-medium">
              {{ balance?.toLocaleString('ru-RU') || '0' }} ₽
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Кнопка Новое задание -->
    <q-btn 
      class="full-width q-mb-md"
      color="primary"
      size="lg"
      label="Новое задание"
      icon="add"
      @click="createNewTask"
    />

    <!-- Блок Мои задания -->
    <q-card flat bordered>
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="assignment" class="q-mr-sm" />
          Мои задания
        </div>
        
        <div v-if="documents.length === 0" class="text-center q-pa-md">
          <q-icon name="description" size="48px" color="grey-5" />
          <div class="text-grey-6 q-mt-sm">Нет доступных документов</div>
        </div>

        <q-list v-else separator>
          <q-item 
            v-for="document in documents" 
            :key="document.id"
            clickable
            @click="viewDocument(document.id)"
            class="document-item"
          >
            <q-item-section avatar>
              <q-icon name="description" :color="getStatusColor(document)" size="md" />
            </q-item-section>
            
            <q-item-section>
              <q-item-label class="text-weight-medium">
                {{ document.title }}
              </q-item-label>
              <q-item-label caption class="text-grey-6">
                Создан: {{ formatDate(document.created_at) }}
              </q-item-label>
            </q-item-section>
            
            <q-item-section side>
              <q-chip 
                :color="getStatusColor(document)"
                text-color="white"
                :label="getStatusLabel(document)"
                size="sm"
              />
            </q-item-section>
            
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-5" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>
    </q-card>
  </div>
</template>

<style scoped>
.lk-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.telegram-card {
  background: white;
}

.telegram-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.telegram-info {
  display: flex;
  align-items: center;
  flex: 1;
}

.balance-card {
  background: white;
}

.balance-content {
  display: flex;
  align-items: center;
  position: relative;
}

.balance-divider {
  width: 2px;
  height: 60px;
  background-color: #9e9e9e;
  margin: 0 20px;
  border-radius: 1px;
}

.balance-text {
  flex: 1;
}

.balance-amount {
  text-align: right;
}

.document-item:hover {
  background-color: #f5f5f5;
  transition: background-color 0.2s ease;
}

.document-item {
  border-radius: 8px;
  margin: 4px 0;
}
</style> 