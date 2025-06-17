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
const getStatusColor = (status) => {
  const statusColors = {
    'draft': 'grey',
    'in_progress': 'orange', 
    'completed': 'green',
    'rejected': 'red',
    'pending': 'blue'
  }
  return statusColors[status] || 'grey'
}

// Функция для получения русского названия статуса
const getStatusLabel = (status) => {
  const statusLabels = {
    'draft': 'Черновик',
    'in_progress': 'В обработке',
    'completed': 'Готов', 
    'rejected': 'Отклонено',
    'pending': 'Ожидает',
    'pre_generated': 'Сгенерирован',
    'generating': 'Генерируется',
    'error': 'Ошибка',
    'new': 'Новый',
    'paid': 'Оплачен',
    'processing': 'Обрабатывается',
    'ready': 'Готов к скачиванию'
  }
  return statusLabels[status] || status
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

<template>
  <div class="lk-container">
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
              <q-icon name="description" :color="getStatusColor(document.status)" size="md" />
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
                :color="getStatusColor(document.status)"
                text-color="white"
                :label="getStatusLabel(document.status)"
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