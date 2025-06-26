<template>
    <div class="document-header">
        <div class="header-content">
            <div class="document-info-section">
                <!-- Заголовок документа -->
                <h1 class="document-main-title">{{ document?.topic || document?.title || 'Документ без названия' }}</h1>
                
                <!-- Информация о документе -->
                <div class="document-details">
                    <div class="detail-item">
                        <q-icon name="description" class="detail-icon" />
                        <span class="detail-value">{{ document?.document_type?.name || 'Не указан' }}</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-value" :class="getStatusClass()">{{ getDisplayStatusText() }}</span>
                    </div>
                    
                    <div v-if="document?.pages_num" class="detail-item">
                        <q-icon name="article" class="detail-icon" />
                        <span class="detail-label">Объем:</span>
                        <span class="detail-value">{{ document.pages_num }} страниц</span>
                    </div>
                </div>
            </div>
            
            <!-- Статус прогресса -->
            <div class="progress-section">
                <div class="progress-circle" :class="getProgressClass()">
                    <div class="progress-inner">
                        <q-icon :name="getProgressIcon()" class="progress-icon" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    document: {
        type: Object,
        required: true
    },
    documentStatus: {
        type: Object,
        default: null
    },
    statusText: {
        type: String,
        default: ''
    },
    isGenerating: {
        type: Boolean,
        default: false
    },
    isPreGenerationComplete: {
        type: Boolean,
        default: false
    },
    isFullGenerationComplete: {
        type: Boolean,
        default: false
    },
    hasFailed: {
        type: Boolean,
        default: false
    }
});

// Маппинг статусов для отображения без API
const statusTextMapping = {
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

const getDisplayStatusText = () => {
    // Если есть переданный statusText, используем его
    if (props.statusText) {
        return props.statusText;
    }
    
    // Если нет, используем статус из документа
    return statusTextMapping[props.document?.status] || 'Неизвестный статус';
};

const getStatusClass = () => {
    const status = props.document?.status;
    switch (status) {
        case 'draft': return 'status-draft';
        case 'pre_generating':
        case 'full_generating': return 'status-generating';
        case 'pre_generated':
        case 'full_generated': return 'status-completed';
        case 'pre_generation_failed':
        case 'full_generation_failed': return 'status-failed';
        case 'in_review': return 'status-review';
        case 'approved': return 'status-approved';
        case 'rejected': return 'status-rejected';
        default: return 'status-unknown';
    }
};

const getProgressIcon = () => {
    if (props.isGenerating) return 'autorenew';
    if (props.isFullGenerationComplete) return 'check';
    if (props.hasFailed) return 'close';
    return 'hourglass_empty';
};

const getProgressClass = () => {
    if (props.isGenerating) return 'progress-generating';
    if (props.isFullGenerationComplete) return 'progress-completed';
    if (props.hasFailed) return 'progress-failed';
    return 'progress-pending';
};
</script>

<style scoped>
/* Шапка документа */
.document-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 32px 40px;
    margin-bottom: 32px;
    color: white;
    position: relative;
    overflow: hidden;
}

.document-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    position: relative;
    z-index: 1;
}

.document-info-section {
    flex: 1;
}

.document-main-title {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 20px 0;
    line-height: 1.2;
    color: white;
}

.document-details {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
    align-items: center;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    background: rgba(255, 255, 255, 0.1);
    padding: 8px 16px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.detail-icon {
    font-size: 18px;
    opacity: 0.9;
    flex-shrink: 0;
}

.detail-label {
    font-weight: 500;
    opacity: 0.9;
}

.detail-value {
    font-weight: 700;
}

/* Статусы */
.status-draft { color: #fbbf24; }
.status-generating { color: #60a5fa; }
.status-completed { color: #34d399; }
.status-failed { color: #f87171; }
.status-review { color: #a78bfa; }
.status-approved { color: #34d399; }
.status-rejected { color: #f87171; }
.status-unknown { color: #d1d5db; }

/* Прогресс секция */
.progress-section {
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.progress-inner {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-icon {
    font-size: 28px;
    color: #4f46e5;
}

.progress-generating .progress-icon {
    animation: spin 2s linear infinite;
    color: #3b82f6;
}

.progress-completed .progress-icon {
    color: #10b981;
}

.progress-failed .progress-icon {
    color: #ef4444;
}

.progress-pending .progress-icon {
    color: #f59e0b;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Адаптивность */
@media (max-width: 1024px) {
    .document-header {
        padding: 24px 28px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 24px;
        text-align: center;
    }
    
    .document-details {
        justify-content: center;
        gap: 20px;
    }
    
    .detail-item {
        font-size: 14px;
        padding: 6px 12px;
    }
    
    .detail-icon {
        font-size: 16px;
    }
}

@media (max-width: 768px) {
    .document-header {
        padding: 20px 24px;
        border-radius: 20px;
    }
    
    .document-main-title {
        font-size: 24px;
        margin-bottom: 16px;
    }
    
    .document-details {
        flex-direction: column;
        gap: 12px;
        width: 100%;
    }
    
    .detail-item {
        width: 100%;
        justify-content: center;
        padding: 8px 16px;
        font-size: 14px;
    }
    
    .progress-section {
        flex-direction: column;
        gap: 12px;
    }
    
    .progress-circle {
        width: 60px;
        height: 60px;
    }
    
    .progress-inner {
        width: 45px;
        height: 45px;
    }
    
    .progress-icon {
        font-size: 22px;
    }
}
</style> 