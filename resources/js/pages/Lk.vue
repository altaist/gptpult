<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useQuasar } from 'quasar';
import PageLayout from '@/components/shared/PageLayout.vue';
import { useTelegramMiniApp } from '@/composables/useTelegramMiniApp.js';

const $q = useQuasar();

// Telegram Mini App
const { isTelegramMiniApp, telegramData, showBackButton, hideBackButton } = useTelegramMiniApp();

// Определяем пропсы, которые могут приходить от контроллера
const props = defineProps({
  auth: Object,
  balance: {
    type: Number,
    default: 15000
  },
  documents: {
    type: Array,
    default: () => []
  },
  isDevelopment: {
    type: Boolean,
    default: false
  }
});

// Состояние Telegram
const telegramStatus = ref({
  is_linked: false,
  telegram_username: null,
  linked_at: null
});
const telegramLoading = ref(false);

// Загрузить статус Telegram при монтировании компонента
onMounted(async () => {
  await loadTelegramStatus();
  
  // Если это Telegram Mini App, настраиваем интерфейс
  if (isTelegramMiniApp.value) {
    console.log('Running in Telegram Mini App mode');
  }
});

// Загрузить статус связи с Telegram
const loadTelegramStatus = async () => {
  try {
    const response = await fetch('/telegram/status', {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    });
    
    if (response.ok) {
      telegramStatus.value = await response.json();
    }
  } catch (error) {
    console.error('Ошибка при загрузке статуса Telegram:', error);
  }
};

// Связать с Telegram
const linkTelegram = async () => {
  telegramLoading.value = true;
  
  try {
    const response = await fetch('/telegram/link', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    });
    
    const data = await response.json();
    
    if (response.ok) {
      window.open(data.bot_url, '_blank');
      
      $q.notify({
        type: 'positive',
        message: 'Перейдите в Telegram и нажмите "Старт"',
        timeout: 5000
      });
      
      setTimeout(async () => {
        await loadTelegramStatus();
      }, 2000);
      
    } else {
      $q.notify({
        type: 'negative',
        message: data.error || 'Ошибка при создании ссылки',
        timeout: 3000
      });
    }
    
  } catch (error) {
    console.error('Ошибка при связке с Telegram:', error);
    $q.notify({
      type: 'negative',
      message: 'Ошибка при связке с Telegram',
      timeout: 3000
    });
  } finally {
    telegramLoading.value = false;
  }
};

// Отвязать от Telegram
const unlinkTelegram = async () => {
  $q.dialog({
    title: 'Отвязать Telegram',
    message: 'Вы уверены, что хотите отвязать свой Telegram аккаунт?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    telegramLoading.value = true;
    
    try {
      const response = await fetch('/telegram/unlink', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });
      
      const data = await response.json();
      
      if (response.ok) {
        $q.notify({
          type: 'positive',
          message: 'Telegram успешно отвязан',
          timeout: 3000
        });
        
        await loadTelegramStatus();
      } else {
        $q.notify({
          type: 'negative',
          message: data.error || 'Ошибка при отвязке Telegram',
          timeout: 3000
        });
      }
      
    } catch (error) {
      console.error('Ошибка при отвязке Telegram:', error);
      $q.notify({
        type: 'negative',
        message: 'Ошибка при отвязке Telegram',
        timeout: 3000
      });
    } finally {
      telegramLoading.value = false;
    }
  });
};

// Функция для перехода к документу
const viewDocument = (documentId) => {
  router.visit(`/documents/${documentId}`);
};

// Функция для создания нового задания
const createNewTask = () => {
  router.visit('/new');
};

// Функция для получения цвета статуса
const getStatusColor = (document) => {
  const statusColors = {
    'draft': '#6b7280',
    'pre_generating': '#3b82f6',
    'pre_generated': '#10b981',
    'pre_generation_failed': '#ef4444',
    'full_generating': '#8b5cf6',
    'full_generated': '#059669',
    'full_generation_failed': '#ef4444',
    'in_review': '#f59e0b',
    'approved': '#16a34a',
    'rejected': '#ef4444'
  };
  return statusColors[document.status] || '#6b7280';
};

// Функция для получения русского названия статуса
const getStatusLabel = (document) => {
  if (document.status_label) {
    return document.status_label;
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
  };
  return statusLabels[document.status] || document.status;
};

// Функция для форматирования даты
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ru-RU');
};

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
    });
    
    const data = await response.json();
    
    if (data.redirect) {
      window.location.href = data.redirect;
    } else if (data.error) {
      console.error('Ошибка при создании заказа:', data.error);
    }
  } catch (error) {
    console.error('Ошибка при пополнении баланса:', error);
  }
};

// Computed для отсортированных документов
const sortedDocuments = computed(() => {
  return [...props.documents].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});
</script>

<template>
    <Head title="Личный кабинет" />

    <page-layout 
        title="Личный кабинет"
        :auto-auth="true"
        left-btn-icon=""
        :left-btn-go-back="false"
        :logo-go-home="true"
    >
        <div class="lk-container">
            <!-- Заголовок -->
            <div class="header-section">
                <h1 class="page-title">Личный кабинет</h1>
                <p class="page-subtitle">Управляйте своими документами и настройками</p>
            </div>

            <!-- Основной контент -->
            <div class="content-grid">
                <!-- Левая колонка -->
                <div class="left-column">
                    <!-- Кнопка создания нового документа -->
                    <div class="new-document-section">
                        <q-btn
                            class="new-document-btn"
                            color="primary"
                            size="lg"
                            @click="createNewTask"
                            unelevated
                            no-caps
                        >
                            <q-icon name="add" class="btn-icon" />
                            Создать новый документ
                        </q-btn>
                    </div>

                    <!-- Карточка баланса -->
                    <div class="balance-card">
                        <div class="card-header">
                            <div class="card-title">
                                <q-icon name="account_balance_wallet" class="card-icon" />
                                Баланс
                            </div>
                        </div>
                        <div class="balance-content">
                            <div class="balance-amount">
                                {{ balance?.toLocaleString('ru-RU') || '0' }} ₽
                            </div>
                            <q-btn
                                color="primary"
                                label="Пополнить"
                                size="md"
                                @click="topUpBalance"
                                class="balance-btn"
                                unelevated
                                no-caps
                            />
                        </div>
                    </div>

                    <!-- Кнопка Telegram -->
                    <div v-if="telegramStatus.is_linked" class="telegram-connected-info">
                        <div class="telegram-status-text">
                            <q-icon name="fab fa-telegram" class="telegram-status-icon" />
                            <span>Telegram подключен (@{{ telegramStatus.telegram_username || 'Связан' }})</span>
                        </div>
                        <q-btn
                            v-if="isDevelopment"
                            color="negative"
                            label="Отвязать"
                            size="sm"
                            @click="unlinkTelegram"
                            :loading="telegramLoading"
                            class="telegram-disconnect-simple"
                            flat
                            no-caps
                        />
                    </div>
                    
                    <q-btn
                        v-else
                        @click="linkTelegram"
                        :loading="telegramLoading"
                        class="telegram-connect-simple"
                        unelevated
                        no-caps
                    >
                        <q-icon name="fab fa-telegram" class="telegram-btn-icon" />
                        <span>Подключить Telegram</span>
                    </q-btn>
                </div>

                <!-- Правая колонка -->
                <div class="right-column">
                    <!-- Документы -->
                    <div class="documents-card">
                        <div class="card-header">
                            <div class="card-title">
                                <q-icon name="description" class="card-icon" />
                                Мои документы
                            </div>
                        </div>

                        <div v-if="sortedDocuments.length === 0" class="empty-state">
                            <q-icon name="description" class="empty-icon" />
                            <div class="empty-title">Нет документов</div>
                            <div class="empty-subtitle">Создайте свой первый документ</div>
                        </div>

                        <div v-else class="documents-list">
                            <div 
                                v-for="document in sortedDocuments" 
                                :key="document.id"
                                @click="viewDocument(document.id)"
                                class="document-item"
                            >
                                <div class="document-icon">
                                    <q-icon name="description" />
                                </div>
                                <div class="document-content">
                                    <div class="document-title">{{ document.title }}</div>
                                    <div class="document-meta">
                                        <div class="document-date">
                                            <q-icon name="schedule" class="meta-icon" />
                                            {{ formatDate(document.created_at) }}
                                        </div>
                                        <div class="document-status" :style="{ color: getStatusColor(document) }">
                                            {{ getStatusLabel(document) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="document-arrow">
                                    <q-icon name="chevron_right" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </page-layout>
</template>

<style scoped>
.lk-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 24px;
    min-height: 100vh;
}

/* Заголовок */
.header-section {
    margin-bottom: 40px;
    text-align: center;
}

.page-title {
    font-size: 48px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 12px 0;
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.page-subtitle {
    font-size: 18px;
    color: #6b7280;
    margin: 0;
    font-weight: 400;
}

/* Сетка контента */
.content-grid {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 40px;
    align-items: start;
}

/* Колонки */
.left-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
    position: sticky;
    top: 100px;
}

.right-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Базовые стили карточек */
.balance-card,
.documents-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.balance-card:hover,
.documents-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

/* Заголовки карточек */
.card-header {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}

.card-icon {
    font-size: 24px;
    color: #3b82f6;
}

/* Информация о подключенном Telegram */
.telegram-connected-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 16px;
    background: #f0f9ff;
    border-radius: 12px;
    border: 1px solid #bae6fd;
    margin-bottom: 8px;
}

.telegram-status-text {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    font-size: 14px;
    color: #0f172a;
    font-weight: 500;
}

.telegram-status-icon {
    font-size: 18px;
    color: #0088cc;
    flex-shrink: 0;
}

.telegram-disconnect-simple {
    border-radius: 8px;
    font-weight: 500;
    padding: 6px 12px;
    color: #ef4444;
    transition: all 0.2s ease;
    font-size: 12px;
}

.telegram-disconnect-simple:hover {
    background: #fef2f2;
}

.telegram-connect-simple {
    width: 100%;
    padding: 14px 24px;
    border-radius: 12px;
    background: #0088cc;
    color: white;
    font-size: 15px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
    transition: all 0.2s ease;
}

.telegram-connect-simple:hover {
    background: #006699;
    box-shadow: 0 6px 16px rgba(0, 136, 204, 0.4);
    transform: translateY(-1px);
}

.telegram-btn-icon {
    margin-right: 8px;
    font-size: 20px;
}

/* Пустое состояние */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 16px;
}

.empty-title {
    font-size: 20px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.empty-subtitle {
    font-size: 16px;
    color: #6b7280;
}

/* Список документов */
.documents-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.document-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
}

.document-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.document-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: #ffffff;
    border-radius: 12px;
    color: #6b7280;
    font-size: 24px;
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
}

.document-content {
    flex: 1;
    min-width: 0;
}

.document-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.document-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.document-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #6b7280;
}

.meta-icon {
    font-size: 14px;
}

.document-status {
    font-size: 14px;
    font-weight: 500;
    line-height: 1.2;
}

.document-arrow {
    color: #9ca3af;
    font-size: 20px;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.document-item:hover .document-arrow {
    color: #3b82f6;
    transform: translateX(4px);
}

/* Адаптивность */
@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 350px 1fr;
        gap: 32px;
    }
    
    .page-title {
        font-size: 42px;
    }
}

@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    
    .left-column {
        position: static;
        order: 1;
    }
    
    .right-column {
        order: 0;
    }
}

@media (max-width: 768px) {
    .lk-container {
        padding: 24px 16px;
    }
    
    .header-section {
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 36px;
    }
    
    .page-subtitle {
        font-size: 16px;
    }
    
    .content-grid {
        gap: 24px;
    }
    
    .balance-card,
    .telegram-card,
    .documents-card {
        padding: 20px;
        border-radius: 16px;
    }
    
    .balance-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .balance-btn {
        width: 100%;
    }
    
    .telegram-connected-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .telegram-connect-simple {
        width: 100%;
    }
    
    .new-document-btn {
        padding: 16px 24px;
        font-size: 16px;
    }
    
    .document-item {
        padding: 16px;
    }
    
    .document-title {
        font-size: 15px;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 28px;
    }
    
    .balance-card,
    .documents-card {
        padding: 16px;
    }
    
    .card-title {
        font-size: 18px;
    }
    
    .balance-amount {
        font-size: 28px;
    }
    
    .document-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }
    
    .telegram-connect-simple {
        font-size: 14px;
        padding: 12px 20px;
    }
}

/* Секция создания документа */
.new-document-section {
    margin-bottom: 0;
}

.new-document-btn {
    width: 100%;
    padding: 20px 32px;
    border-radius: 16px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    font-size: 18px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.new-document-btn:hover {
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
    transform: translateY(-3px);
}

.btn-icon {
    margin-right: 12px;
    font-size: 24px;
}

/* Карточка баланса */
.balance-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.balance-amount {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
}

.balance-btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 12px 24px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
}

.balance-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}
</style> 