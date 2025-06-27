<template>
    <div class="content-section">
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <q-icon name="link" class="section-icon" />
                    Ссылки
                </div>
            </div>
            
            <div class="section-content">
                <!-- Загрузочное состояние -->
                <div v-if="isLoading" class="loading-container">
                    <div class="loading-content">
                        <div class="loading-icon">
                            <q-icon name="autorenew" class="spinning-icon" />
                        </div>
                        <div class="loading-text">Генерируем ссылки на полезные ресурсы...</div>
                        <div class="loading-subtitle">Это займет несколько секунд</div>
                    </div>
                </div>

                <!-- Список ссылок -->
                <div v-else-if="references && references.length" class="references-list">
                    <div 
                        v-for="(reference, index) in references" 
                        :key="index"
                        class="reference-item"
                        @click="openLink(reference.url)"
                    >
                        <div class="reference-content">
                            <div class="reference-title">{{ reference.title }}</div>
                            <div v-if="reference.author && reference.author.trim()" class="reference-author">{{ reference.author }}</div>
                        </div>
                        <div class="reference-action">
                            <q-icon name="open_in_new" class="action-icon" />
                        </div>
                    </div>
                </div>

                <!-- Пустое состояние -->
                <div v-else class="empty-state">
                    <q-icon name="link_off" class="empty-icon" />
                    <div class="empty-text">Ссылки пока недоступны</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, ref, onMounted, onUnmounted, watch } from 'vue';
import { useQuasar } from 'quasar';
import { apiClient } from '@/composables/api';

const props = defineProps({
    references: {
        type: Array,
        required: false,
        default: () => []
    },
    isLoading: {
        type: Boolean,
        default: true
    },
    documentId: {
        type: [Number, String],
        required: false
    }
});

const emit = defineEmits(['references-updated']);

const $q = useQuasar();
const statusCheckInterval = ref(null);

// Проверка статуса документа
const checkDocumentStatus = async () => {
    if (!props.documentId || !props.isLoading) return;
    
    try {
        const response = await apiClient.get(route('documents.status', props.documentId));
        
        // Если появились ссылки, уведомляем родительский компонент
        if (response.document?.structure?.references && response.document.structure.references.length > 0) {
            emit('references-updated', response.document.structure.references);
        }
    } catch (error) {
        console.error('Ошибка при проверке статуса документа:', error);
    }
};

// Запускаем периодическую проверку статуса
const startStatusPolling = () => {
    if (!props.documentId) return;
    
    // Проверяем каждые 3 секунды
    statusCheckInterval.value = setInterval(checkDocumentStatus, 3000);
};

// Останавливаем проверку статуса
const stopStatusPolling = () => {
    if (statusCheckInterval.value) {
        clearInterval(statusCheckInterval.value);
        statusCheckInterval.value = null;
    }
};

// Следим за изменением isLoading
watch(() => props.isLoading, (newValue) => {
    if (newValue) {
        startStatusPolling();
    } else {
        stopStatusPolling();
    }
});

onMounted(() => {
    if (props.isLoading) {
        startStatusPolling();
    }
});

onUnmounted(() => {
    stopStatusPolling();
});

// Функция для открытия ссылки в новой вкладке
const openLink = (url) => {
    if (url) {
        window.open(url, '_blank', 'noopener,noreferrer');
    } else {
        $q.notify({
            type: 'negative',
            message: 'Ссылка недоступна',
            position: 'top'
        });
    }
};
</script>

<style scoped>
/* Основной контейнер секции */
.content-section {
    width: 100%;
}

.section-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.section-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}

.section-icon {
    font-size: 24px;
    color: #3b82f6;
}

.section-content {
    font-size: 16px;
    line-height: 1.6;
    color: #374151;
}

/* Загрузочное состояние */
.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px 20px;
}

.loading-content {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.loading-icon {
    margin-bottom: 8px;
}

.spinning-icon {
    font-size: 32px;
    color: #3b82f6;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.loading-text {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.loading-subtitle {
    font-size: 14px;
    color: #6b7280;
}

/* Список ссылок */
.references-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.reference-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
}

.reference-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateX(4px);
}

.reference-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.reference-content > *:not(:last-child) {
    margin-bottom: 4px;
}

.reference-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.4;
}

.reference-author {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.reference-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.reference-item:hover .reference-action {
    background: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.action-icon {
    font-size: 16px;
    color: #6b7280;
    transition: color 0.2s ease;
}

.reference-item:hover .action-icon {
    color: #ffffff;
}

/* Пустое состояние */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}

.empty-icon {
    font-size: 48px;
    color: #d1d5db;
    margin-bottom: 16px;
}

.empty-text {
    font-size: 16px;
    font-weight: 500;
    color: #6b7280;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .section-card {
        padding: 24px;
    }
}

@media (max-width: 768px) {
    .section-card {
        padding: 20px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .reference-item {
        padding: 14px 16px;
    }
    
    .reference-title {
        font-size: 15px;
    }
    
    .reference-author {
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .section-card {
        padding: 16px;
    }
    
    .section-title {
        font-size: 18px;
    }
    
    .reference-item {
        padding: 12px 14px;
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .reference-action {
        align-self: flex-end;
        width: 28px;
        height: 28px;
    }
    
    .action-icon {
        font-size: 14px;
    }
}
</style> 