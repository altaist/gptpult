<template>
    <div class="document-header">
        <div class="header-content">
            <div class="document-info-section">
                <!-- Заголовок документа с кнопкой редактирования -->
                <h1 class="document-main-title">
                    {{ document?.title || document?.topic || 'Документ без названия' }}
                    <q-btn 
                        v-if="editable"
                        icon="edit" 
                        flat 
                        round 
                        size="sm" 
                        @click="openEditTitleDialog"
                        class="edit-title-btn"
                        color="white"
                    />
                </h1>
                
                <!-- Информация о документе -->
                <div class="document-details">
                    <div class="detail-item">
                        <q-icon name="description" class="detail-icon" />
                        <span class="detail-value">{{ document?.document_type?.name || 'Не указан' }}</span>
                    </div>
                    
                    <div v-if="document?.pages_num" class="detail-item">
                        <q-icon name="article" class="detail-icon" />
                        <span class="detail-value">{{ document.pages_num }} страниц</span>
                    </div>

                    <div class="detail-item">
                        <q-icon name="schedule" class="detail-icon" />
                        <span class="detail-value">{{ formatCreatedDate() }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Современный статус блок -->
            <div class="status-section">
                <div class="status-card">
                    <div class="status-header">
                        <span class="status-label">Статус</span>
                    </div>
                    <div class="status-content">
                        <span class="status-text">{{ getDisplayStatusText() }}</span>
                        <div 
                            v-if="documentStatus?.status === 'pre_generated' && !documentStatus?.has_references"
                            class="status-note"
                        >
                            Ожидается завершение генерации ссылок
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Диалог редактирования заголовка -->
    <q-dialog v-model="editTitleDialog.show" persistent>
        <q-card class="edit-dialog">
            <q-card-section class="edit-dialog-header">
                <div class="edit-dialog-title">
                    <q-icon name="edit" class="edit-dialog-icon" />
                    Редактировать заголовок
                </div>
                <q-btn icon="close" flat round dense v-close-popup class="close-btn" />
            </q-card-section>

            <q-separator class="dialog-separator" />

            <q-card-section class="edit-dialog-content">
                <CustomInput
                    v-model="editTitleDialog.value"
                    type="text"
                    label="Заголовок документа"
                    :autofocus="true"
                    placeholder="Введите заголовок документа..."
                    :maxlength="30"
                    :counter="true"
                    :error="titleError"
                />
            </q-card-section>

            <q-separator class="dialog-separator" />

            <q-card-actions class="edit-dialog-actions">
                <q-btn 
                    flat 
                    label="Отмена" 
                    @click="closeEditTitleDialog" 
                    class="cancel-btn"
                    no-caps
                />
                <q-btn 
                    unelevated 
                    label="Сохранить" 
                    color="primary" 
                    @click="saveTitleEdit" 
                    :loading="editTitleDialog.loading" 
                    class="save-btn"
                    no-caps
                />
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { useQuasar } from 'quasar';
import CustomInput from '@/components/shared/CustomInput.vue';

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
    },
    editable: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['updated']);
const $q = useQuasar();

// Состояние диалога редактирования заголовка
const editTitleDialog = reactive({
    show: false,
    value: '',
    loading: false
});

const titleError = ref('');

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
    if (props.isGenerating) return 'status-generating';
    if (props.isPreGenerationComplete) return 'status-pre-complete';
    if (props.isFullGenerationComplete) return 'status-complete';
    if (props.hasFailed) return 'status-failed';
    return 'status-default';
};

const getStatusIcon = () => {
    if (props.isGenerating) return 'sync';
    if (props.isPreGenerationComplete) return 'check_circle';
    if (props.isFullGenerationComplete) return 'task_alt';
    if (props.hasFailed) return 'error';
    return 'radio_button_unchecked';
};

const formatCreatedDate = () => {
    if (!props.document?.created_at) return 'Не указано';
    
    const date = new Date(props.document.created_at);
    const now = new Date();
    const diffInMs = now - date;
    const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60));
    const diffInDays = Math.floor(diffInHours / 24);
    
    if (diffInHours < 1) {
        const diffInMinutes = Math.floor(diffInMs / (1000 * 60));
        return diffInMinutes < 1 ? 'Только что' : `${diffInMinutes} мин. назад`;
    } else if (diffInHours < 24) {
        return `${diffInHours} ч. назад`;
    } else if (diffInDays < 7) {
        return `${diffInDays} дн. назад`;
    } else {
        return date.toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'short',
            year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
        });
    }
};

// Функции для редактирования заголовка
const openEditTitleDialog = () => {
    editTitleDialog.value = props.document?.title || props.document?.topic || '';
    editTitleDialog.show = true;
    editTitleDialog.loading = false;
    titleError.value = '';
};

const closeEditTitleDialog = () => {
    editTitleDialog.show = false;
    editTitleDialog.value = '';
    editTitleDialog.loading = false;
    titleError.value = '';
};

const saveTitleEdit = async () => {
    // Валидация длины заголовка
    if (!editTitleDialog.value.trim()) {
        titleError.value = 'Заголовок не может быть пустым';
        return;
    }
    
    if (editTitleDialog.value.length > 30) {
        titleError.value = 'Заголовок не может быть длиннее 30 символов';
        return;
    }
    
    titleError.value = '';
    editTitleDialog.loading = true;
    
    try {
        const url = route('documents.update-title', props.document.id);
        const data = { title: editTitleDialog.value.trim() };

        await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });

        $q.notify({
            type: 'positive',
            message: 'Заголовок успешно обновлен'
        });

        closeEditTitleDialog();
        emit('updated');
        
        // Перезагружаем страницу для обновления данных
        setTimeout(() => {
            location.reload();
        }, 500);

    } catch (error) {
        console.error('Ошибка при сохранении заголовка:', error);
        $q.notify({
            type: 'negative',
            message: 'Ошибка при сохранении заголовка'
        });
    } finally {
        editTitleDialog.loading = false;
    }
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
    display: flex;
    align-items: center;
    gap: 12px;
}

.edit-title-btn {
    opacity: 0.8;
    transition: all 0.2s ease;
    flex-shrink: 0;
    margin-left: 8px;
}

.edit-title-btn:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.05);
}

.document-details {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
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

.detail-value {
    font-weight: 700;
}

/* Современный статус блок */
.status-section {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.status-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    transition: all 0.3s ease;
    min-width: 200px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.status-card:hover {
    background: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.status-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.status-main-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.status-label {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.status-text {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
}

.status-note {
    font-size: 11px;
    color: #fbbf24;
    font-weight: 500;
    line-height: 1.2;
}

/* Цвета статусов */
.status-generating {
    color: #60a5fa !important;
    animation: spin 2s linear infinite;
}

.status-pre-complete {
    color: #34d399 !important;
}

.status-complete {
    color: #10b981 !important;
}

.status-failed {
    color: #f87171 !important;
}

.status-default {
    color: rgba(255, 255, 255, 0.8) !important;
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
    
    .status-card {
        min-width: 180px;
        padding: 16px;
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
        flex-wrap: wrap;
        justify-content: center;
        text-align: center;
        word-break: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
        max-width: 100%;
        line-height: 1.3;
    }
    
    .edit-title-btn {
        margin-left: 4px;
        margin-top: 4px;
    }
    
    .document-details {
        flex-direction: column;
        gap: 12px;
        width: 100%;
        align-items: stretch;
    }
    
    .detail-item {
        width: 100%;
        min-height: 48px;
        justify-content: center;
        padding: 12px 16px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
        box-sizing: border-box;
    }
    
    .detail-icon {
        font-size: 18px;
        min-width: 18px;
        flex-shrink: 0;
    }
    
    .detail-value {
        text-align: center;
        flex: 1;
        word-break: break-word;
        line-height: 1.3;
    }
    
    .status-card {
        width: 100%;
        min-width: auto;
        text-align: center;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .status-header {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .document-header {
        padding: 16px 20px;
        border-radius: 16px;
    }
    
    .document-main-title {
        font-size: 20px;
        margin-bottom: 12px;
        max-width: 100%;
        line-height: 1.2;
        word-break: break-word;
        overflow-wrap: break-word;
        -webkit-line-clamp: 3;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .edit-title-btn {
        margin-left: 2px;
        margin-top: 2px;
        transform: scale(0.9);
    }
    
    .document-details {
        gap: 10px;
    }
    
    .detail-item {
        min-height: 44px;
        padding: 10px 14px;
        font-size: 13px;
        gap: 8px;
        border-radius: 10px;
    }
    
    .detail-icon {
        font-size: 16px;
        min-width: 16px;
    }
    
    .detail-value {
        font-size: 13px;
        font-weight: 600;
    }
    
    .status-card {
        min-height: 70px;
        padding: 14px 16px;
        border-radius: 16px;
    }
    
    .status-main-icon {
        font-size: 20px;
    }
    
    .status-text {
        font-size: 14px;
        line-height: 1.2;
    }
    
    .status-note {
        font-size: 10px;
        line-height: 1.1;
    }
    
    .status-label {
        font-size: 11px;
    }
}

/* Дополнительные стили для очень маленьких экранов */
@media (max-width: 360px) {
    .document-main-title {
        font-size: 18px;
        -webkit-line-clamp: 2;
        line-height: 1.1;
    }
    
    .detail-item {
        min-height: 40px;
        padding: 8px 12px;
        font-size: 12px;
    }
    
    .detail-icon {
        font-size: 14px;
        min-width: 14px;
    }
    
    .detail-value {
        font-size: 12px;
    }
    
    .status-card {
        min-height: 60px;
        padding: 12px 14px;
    }
    
    .status-text {
        font-size: 13px;
    }
}

/* Стили для диалога редактирования заголовка */
.edit-dialog {
    width: 90vw;
    max-width: 600px;
    max-height: 85vh;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}

.edit-dialog-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.edit-dialog-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 600;
}

.edit-dialog-icon {
    font-size: 24px;
}

.close-btn {
    color: white;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.dialog-separator {
    background: #e2e8f0;
    height: 1px;
}

.edit-dialog-content {
    padding: 32px;
    background: #ffffff;
}

.edit-dialog-actions {
    background: #f8fafc;
    padding: 24px 32px;
    display: flex;
    justify-content: flex-end;
    gap: 16px;
}

.cancel-btn {
    padding: 12px 24px;
    border-radius: 12px;
    color: #6b7280;
    font-weight: 500;
    transition: all 0.2s ease;
}

.cancel-btn:hover {
    background: #f1f5f9;
    color: #374151;
}

.save-btn {
    padding: 12px 32px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
}

.save-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .edit-dialog {
        width: 95vw;
        max-height: 90vh;
        border-radius: 20px;
    }
    
    .edit-dialog-header {
        padding: 20px 24px;
    }
    
    .edit-dialog-content {
        padding: 24px 20px;
    }
    
    .edit-dialog-actions {
        padding: 20px 24px;
        flex-direction: column;
    }
    
    .cancel-btn,
    .save-btn {
        width: 100%;
        justify-content: center;
    }
}
</style> 