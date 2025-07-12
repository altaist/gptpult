<template>
  <div class="lk-container">
    <!-- Карточка Telegram -->
    <q-card class="telegram-card q-mb-md" flat bordered>
      <q-card-section class="q-pa-lg">
        <div class="telegram-content">
          <div class="telegram-icon">
            <q-icon name="fab fa-telegram" size="2rem" color="primary" />
          </div>
          <div class="telegram-info">
            <div class="text-h6 text-grey-8">Telegram</div>
            <div v-if="telegramStatus.is_linked" class="text-caption text-positive">
              @{{ telegramStatus.telegram_username || 'Связан' }}
            </div>
            <div v-else class="text-caption text-grey-6">
              Быстрый доступ к ЛК
            </div>
          </div>
          <div class="telegram-actions">
            <q-btn
              v-if="!telegramStatus.is_linked"
              color="primary"
              label="Связать"
              size="md"
              @click="linkTelegram"
              :loading="telegramLoading"
              unelevated
            />
            <q-btn
              v-else-if="isDevelopment"
              color="negative"
              label="Отвязать"
              size="md"
              @click="unlinkTelegram"
              :loading="telegramLoading"
              outline
            />
            <div v-else-if="telegramStatus.is_linked" class="text-caption text-grey-6">
              Связан
            </div>
          </div>
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
              color="primary"
              label="Пополнить"
              size="md"
              @click="topUpBalance"
              class="q-mt-sm"
              unelevated
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
    <div class="new-task-wrapper q-mb-md">
      <q-btn 
        class="new-task-btn"
        color="primary"
        size="lg"
        label="Новое задание"
        icon="add"
        @click="createNewTask"
      />
    </div>

    <!-- Блок Мои задания -->
    <q-card flat bordered>
      <q-card-section class="documents-section">
        <div class="text-h6 q-mb-md">
          <q-icon name="assignment" class="q-mr-sm" />
          Мои задания
        </div>
        
        <div v-if="documents.length === 0" class="text-center q-pa-md">
          <q-icon name="description" size="48px" color="grey-5" />
          <div class="text-grey-6 q-mt-sm">Нет доступных документов</div>
        </div>

        <q-list v-else separator class="documents-list">
          <q-item 
            v-for="document in documents" 
            :key="document.id"
            clickable
            @click="viewDocument(document.id)"
            class="document-item"
          >
            <q-item-section avatar>
              <q-icon name="description" color="grey-6" size="md" />
            </q-item-section>
            
            <q-item-section class="document-content">
              <q-item-label class="text-weight-medium document-title">
                {{ document.title }}
              </q-item-label>
                             <div class="document-meta">
                 <span class="document-date">
                   Создан: {{ formatDate(document.created_at) }}
                 </span>
                 <span class="document-status" :style="{ color: getStatusColor(document) }">
                   {{ getStatusLabel(document) }}
                 </span>
               </div>
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

/* Стили для десктопной версии */
@media (min-width: 601px) {
  .new-task-wrapper {
    display: flex;
    justify-content: center;
  }
  
  .new-task-btn {
    width: auto;
    min-width: 200px;
    padding: 0 32px;
  }
}

/* Мобильная адаптация */
@media (max-width: 600px) {
  .lk-container {
    padding: 12px;
  }
  
  .documents-section {
    padding: 12px !important;
  }
  
  .documents-list {
    margin: 0 -8px;
  }
  
  .new-task-btn {
    width: 100%;
  }
}

.balance-card {
  background: white;
}

.telegram-card {
  background: white;
}

.telegram-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.telegram-icon {
  flex-shrink: 0;
}

.telegram-info {
  flex: 1;
}

.telegram-actions {
  flex-shrink: 0;
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

.document-content {
  min-width: 0;
}

.document-title {
  line-height: 1.3;
  margin-bottom: 4px;
}

.document-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.document-status {
  font-weight: 500;
  font-size: 0.875rem;
  line-height: 1.2;
}

.document-date {
  color: #757575;
  font-size: 0.75rem;
  line-height: 1.2;
}

/* Мобильная адаптация для meta информации */
@media (max-width: 480px) {
  .document-meta {
    gap: 1px;
  }
  
  .document-status {
    font-size: 0.8rem;
  }
  
  .document-date {
    font-size: 0.7rem;
  }
}
</style>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'
import { useTelegramMiniApp } from '@/composables/useTelegramMiniApp.js'

const $q = useQuasar()

// Telegram Mini App
const { isTelegramMiniApp, telegramData, showBackButton, hideBackButton } = useTelegramMiniApp()

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
  isDevelopment: {
    type: Boolean,
    default: false
  }
})

// Состояние Telegram
const telegramStatus = ref({
  is_linked: false,
  telegram_username: null,
  linked_at: null
})
const telegramLoading = ref(false)

// Загрузить статус Telegram при монтировании компонента
onMounted(async () => {
  // console.log('Running in Telegram Mini App mode')  // Закомментировано для продакшена
  await loadTelegramStatus()
  
  // Если это Telegram Mini App, настраиваем интерфейс
  if (isTelegramMiniApp.value) {
    // console.log('Running in Telegram Mini App mode')  // Закомментировано для продакшена
    // Добавляем CSS класс для Telegram WebApp стилей
    document.body.classList.add('tg-viewport');
  }
})

// Загрузить статус связи с Telegram
const loadTelegramStatus = async () => {
  try {
    const response = await fetch('/telegram/status', {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (response.ok) {
      telegramStatus.value = await response.json()
    }
  } catch (error) {
    // console.error('Ошибка при загрузке статуса Telegram:', error)  // Закомментировано для продакшена
  }
}

// Связать с Telegram
const linkTelegram = async () => {
  telegramLoading.value = true
  
  try {
    const response = await fetch('/telegram/link', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    const data = await response.json()
    
    if (response.ok) {
      // Открываем ссылку на бота в новой вкладке
      window.open(data.bot_url, '_blank')
      
      $q.notify({
        type: 'positive',
        message: 'Перейдите в Telegram и нажмите "Старт"',
        timeout: 5000
      })
      
      // Проверяем статус через некоторое время
      setTimeout(async () => {
        await loadTelegramStatus()
      }, 2000)
      
    } else {
      $q.notify({
        type: 'negative',
        message: data.error || 'Ошибка при создании ссылки',
        timeout: 3000
      })
    }
    
  } catch (error) {
    // console.error('Ошибка при связке с Telegram:', error)  // Закомментировано для продакшена
    $q.notify({
      type: 'negative',
      message: 'Ошибка при связке с Telegram',
      timeout: 3000
    })
  } finally {
    telegramLoading.value = false
  }
}

// Отвязать от Telegram
const unlinkTelegram = async () => {
  $q.dialog({
    title: 'Отвязать Telegram',
    message: 'Вы уверены, что хотите отвязать свой Telegram аккаунт?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    telegramLoading.value = true
    
    try {
      const response = await fetch('/telegram/unlink', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })
      
      const data = await response.json()
      
      if (response.ok) {
        $q.notify({
          type: 'positive',
          message: 'Telegram успешно отвязан',
          timeout: 3000
        })
        
        await loadTelegramStatus()
      } else {
        $q.notify({
          type: 'negative',
          message: data.error || 'Ошибка при отвязке Telegram',
          timeout: 3000
        })
      }
      
    } catch (error) {
      // console.error('Ошибка при отвязке Telegram:', error)  // Закомментировано для продакшена
      $q.notify({
        type: 'negative',
        message: 'Ошибка при отвязке Telegram',
        timeout: 3000
      })
    } finally {
      telegramLoading.value = false
    }
  })
}

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
  
  const statusColors = {
    'draft': '#757575',
    'pre_generating': '#1976d2',
    'pre_generated': '#388e3c',
    'pre_generation_failed': '#f44336',
    'full_generating': '#7b1fa2',
    'full_generated': '#2e7d32',
    'full_generation_failed': '#f44336',
    'in_review': '#f57c00',
    'approved': '#1b5e20',
    'rejected': '#f44336'
  }
  return statusColors[document.status] || '#757575'
}

// Функция для получения русского названия статуса
const getStatusLabel = (document) => {
  // Используем метку из enum если доступна, иначе fallback
  if (document.status_label) {
    return document.status_label
  }
  
  const statusLabels = {
    'draft': 'Черновик',
    'pre_generating': 'Генерируется структура и ссылки...',
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
        amount: 300, // Минимальная сумма пополнения
        order_data: {
          description: "Пополнение баланса на 300₽",
          purpose: "balance_top_up"
        }
      })
    })
    
    const data = await response.json()
    
    if (data.redirect) {
      window.location.href = data.redirect
    } else if (!data.success) {
      // console.error('Ошибка при создании заказа:', data.error)  // Закомментировано для продакшена
      return
    }
  } catch (error) {
    // console.error('Ошибка при пополнении баланса:', error)  // Закомментировано для продакшена
  }
}
</script> 