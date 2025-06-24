<template>
  <div class="lk-container">
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
                   {{ getStatusLabel(document) }} {{ document.status }}
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
import { ref } from 'vue'
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
  }
})

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
</script> 