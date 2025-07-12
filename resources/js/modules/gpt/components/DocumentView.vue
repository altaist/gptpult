<template>
    <div class="document-view">

        <!-- Заголовок документа (внутренний) -->
        <div v-if="document.structure?.document_title" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <q-icon name="title" class="section-icon" />
                        Заголовок документа
                    </div>
                    <q-btn 
                        v-if="editable"
                        icon="edit" 
                        flat 
                        round 
                        size="sm" 
                        @click="openEditDialog('document_title', 'Редактировать заголовок', document.structure.document_title)"
                        class="edit-btn"
                    />
                </div>
                <div class="section-content document-title-content">
                    {{ document.structure.document_title }}
                </div>
            </div>
        </div>

        <!-- Описание документа -->
        <div v-if="document.structure?.description" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <q-icon name="description" class="section-icon" />
                        Описание
                    </div>
                    <q-btn 
                        v-if="editable"
                        icon="edit" 
                        flat 
                        round 
                        size="sm" 
                        @click="openEditDialog('description', 'Описание документа', document.structure.description)"
                        class="edit-btn"
                    />
                </div>
                <div class="section-content">
                    {{ document.structure.description }}
                </div>
            </div>
        </div>

        <!-- Тема документа -->
        <!-- <div v-if="document.structure?.topic" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <q-icon name="lightbulb" class="section-icon" />
                        Тема документа
                    </div>
                    <q-btn 
                        v-if="editable"
                        icon="edit" 
                        flat 
                        round 
                        size="sm" 
                        @click="openEditDialog('topic', 'Тема документа', document.structure.topic)"
                        class="edit-btn"
                    />
                </div>
                <div class="section-content">
                    {{ document.structure.topic }}
                </div>
            </div>
        </div> -->

        <!-- Цели документа -->
        <div v-if="document.structure?.objectives && document.structure.objectives.length" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <q-icon name="flag" class="section-icon" />
                        Цели
                    </div>
                    <q-btn 
                        v-if="editable"
                        icon="edit" 
                        flat 
                        round 
                        size="sm" 
                        @click="openEditDialog('objectives', 'Цели документа', document.structure.objectives.join('\n'))"
                        class="edit-btn"
                    />
                </div>
                <div class="section-content">
                    <div class="objectives-list">
                        <div v-for="(objective, index) in document.structure.objectives" :key="index" class="objective-item">
                            <div class="objective-number">{{ index + 1 }}</div>
                            <div class="objective-text">{{ objective }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Тезисы -->
        <div v-if="document.structure?.theses" class="content-section">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <q-icon name="format_quote" class="section-icon" />
                        Тезисы
                    </div>
                    <q-btn 
                        v-if="editable"
                        icon="edit" 
                        flat 
                        round 
                        size="sm" 
                        @click="openEditDialog('theses', 'Тезисы документа', document.structure.theses)"
                        class="edit-btn"
                    />
                </div>
                <div class="section-content">
                    {{ document.structure.theses }}
                </div>
            </div>
        </div>

        <!-- Содержание документа -->
        <document-contents-view 
            v-if="document.structure?.contents" 
            :contents="document.structure.contents" 
            :editable="canEditContents"
            :is-completed="isFullGenerationComplete || document?.status === 'full_generated'"
            @edit-contents="openContentsEditDialog"
        />

        <!-- Ссылки на полезные ресурсы -->
        <document-references-view 
            :references="document.structure?.references || []"
            :is-loading="shouldShowReferencesLoading"
            :document-id="document.id"
            @references-updated="handleReferencesUpdated"
        />

        <!-- Современный диалог для редактирования -->
        <q-dialog v-model="editDialog.show" persistent>
            <q-card class="edit-dialog">
                <q-card-section class="edit-dialog-header">
                    <div class="edit-dialog-title">
                        <q-icon name="edit" class="edit-dialog-icon" />
                        {{ editDialog.title }}
                    </div>
                    <q-btn icon="close" flat round dense v-close-popup class="close-btn" />
                </q-card-section>

                <q-separator class="dialog-separator" />

                <q-card-section class="edit-dialog-content">
                    <div v-if="editDialog.type === 'topic'" class="input-container">
                        <CustomInput
                            v-model="editDialog.value"
                            type="text"
                            label="Тема документа"
                            :autofocus="true"
                            :maxlength="getMaxLength()"
                            :error="isValueTooLong() ? getErrorMessage() : ''"
                        />
                        <div class="character-counter" :class="{ 'counter-error': isValueTooLong() }">
                            {{ editDialog.value.length }} / {{ getMaxLength() }}
                        </div>
                    </div>
                    <div v-else-if="editDialog.type === 'document_title'" class="input-container">
                        <CustomInput
                            v-model="editDialog.value"
                            type="text"
                            label="Заголовок документа"
                            :autofocus="true"
                            :maxlength="getMaxLength()"
                            :error="isValueTooLong() ? getErrorMessage() : ''"
                        />
                        <div class="character-counter" :class="{ 'counter-error': isValueTooLong() }">
                            {{ editDialog.value.length }} / {{ getMaxLength() }}
                        </div>
                    </div>
                    <div v-else class="textarea-container">
                        <CustomInput
                            v-model="editDialog.value"
                            type="textarea"
                            :rows="getTextareaRows()"
                            :autofocus="true"
                            :placeholder="getTextareaPlaceholder()"
                            :maxlength="getMaxLength()"
                            :error="isValueTooLong() ? getErrorMessage() : ''"
                        />
                        <div class="character-counter" :class="{ 'counter-error': isValueTooLong() }">
                            {{ editDialog.value.length }} / {{ getMaxLength() }}
                        </div>
                    </div>
                </q-card-section>

                <q-separator class="dialog-separator" />

                <q-card-actions class="edit-dialog-actions">
                    <q-btn 
                        flat 
                        label="Отмена" 
                        @click="closeEditDialog" 
                        class="cancel-btn"
                        no-caps
                    />
                    <q-btn 
                        unelevated 
                        label="Сохранить" 
                        color="primary" 
                        @click="saveEdit" 
                        :loading="editDialog.loading" 
                        :disable="isValueTooLong()"
                        class="save-btn"
                        no-caps
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>
    </div>
</template>

<script setup>
import { defineProps, defineEmits, ref, reactive, computed } from 'vue';
import { useQuasar } from 'quasar';
import { router } from '@inertiajs/vue3';
import DocumentContentsView from './DocumentContentsView.vue';
import DocumentReferencesView from './DocumentReferencesView.vue';
import CustomInput from '@/components/shared/CustomInput.vue';

const props = defineProps({
    document: {
        type: Object,
        required: true
    },
    
    editable: {
        type: Boolean,
        default: false
    },

    // Статус документа
    documentStatus: {
        type: Object,
        default: () => null
    },

    // Текст статуса
    statusText: {
        type: String,
        default: 'Неизвестно'
    },

    // Boolean состояния
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

    isApproved: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['updated']);
const $q = useQuasar();

const editDialog = reactive({
    show: false,
    type: '',
    title: '',
    value: '',
    loading: false
});

// Определяем когда показывать загрузочное состояние для ссылок
const shouldShowReferencesLoading = computed(() => {
    // Показываем загрузку только если документ генерируется (структура)
    // Теперь ссылки генерируются вместе с содержанием
    const isCurrentlyGenerating = props.isGenerating || 
                                props.documentStatus?.status === 'pre_generating';
    
    return isCurrentlyGenerating;
});

// Определяем можно ли редактировать содержание документа
const canEditContents = computed(() => {
    // Запрещаем редактирование если документ полностью готов
    if (props.isFullGenerationComplete || props.document?.status === 'full_generated') {
        return false;
    }
    
    // В остальных случаях используем базовое правило editable
    return props.editable;
});

// Функции для получения иконки и цвета по умолчанию
const getDefaultIcon = () => {
    if (props.isGenerating) return 'sync';
    if (props.isPreGenerationComplete) return 'check_circle';
    if (props.isFullGenerationComplete) return 'task_alt';
    if (props.isApproved) return 'verified';
    if (props.hasFailed) return 'error';
    return 'radio_button_unchecked';
};

const getStatusClass = () => {
    if (props.isGenerating) return 'status-generating';
    if (props.isPreGenerationComplete) return 'status-pre-complete';
    if (props.isFullGenerationComplete) return 'status-complete';
    if (props.isApproved) return 'status-approved';
    if (props.hasFailed) return 'status-failed';
    return 'status-default';
};

function openEditDialog(type, title, value) {
    editDialog.type = type;
    editDialog.title = title;
    editDialog.value = value || '';
    editDialog.show = true;
    editDialog.loading = false;
}

function openContentsEditDialog(contents) {
    const contentsText = formatContentsForEdit(contents);
    openEditDialog('contents', 'Содержание документа', contentsText);
}

function closeEditDialog() {
    editDialog.show = false;
    editDialog.type = '';
    editDialog.title = '';
    editDialog.value = '';
    editDialog.loading = false;
}

function formatContentsForEdit(contents) {
    return contents.map((topic, index) => {
        let text = `${index + 1}. ${topic.title}`;
        if (topic.subtopics && topic.subtopics.length) {
            topic.subtopics.forEach((subtopic, subIndex) => {
                text += `\n  ${index + 1}.${subIndex + 1} ${subtopic.title}`;
            });
        }
        return text;
    }).join('\n\n');
}

function parseContentsFromText(text) {
    const lines = text.split('\n').map(line => line.trim()).filter(line => line);
    const contents = [];
    let currentTopic = null;

    lines.forEach(line => {
        // Основная тема (начинается с цифры и точки)
        const mainTopicMatch = line.match(/^(\d+)\.\s*(.+)$/);
        if (mainTopicMatch && !line.match(/^\d+\.\d+/)) {
            if (currentTopic) {
                contents.push(currentTopic);
            }
            currentTopic = {
                title: mainTopicMatch[2],
                subtopics: []
            };
        }
        // Подтема (формат 1.1, 1.2 и т.д.)
        else if (line.match(/^\d+\.\d+/) && currentTopic) {
            const subtopicMatch = line.match(/^\d+\.\d+\s*(.+)$/);
            if (subtopicMatch) {
                currentTopic.subtopics.push({
                    title: subtopicMatch[1],
                    content: ''
                });
            }
        }
    });

    if (currentTopic) {
        contents.push(currentTopic);
    }

    return contents;
}

function getTextareaRows() {
    switch (editDialog.type) {
        case 'contents':
            return 12;
        case 'theses':
            return 8;
        case 'objectives':
            return 6;
        default:
            return 8;
    }
}

function getTextareaPlaceholder() {
    switch (editDialog.type) {
        case 'contents':
            return 'Введите содержание в формате:\n1. Основная тема 1\n  1.1 Подтема 1.1\n  1.2 Подтема 1.2\n\n2. Основная тема 2\n  2.1 Подтема 2.1';
        case 'theses':
            return 'Введите основные тезисы документа...';
        case 'objectives':
            return 'Введите цели документа, каждую с новой строки...';
        case 'document_title':
            return 'Введите заголовок документа...';
        case 'description':
            return 'Введите описание документа...';
        default:
            return 'Введите текст...';
    }
}

// Функция для получения максимальной длины текста
function getMaxLength() {
    switch (editDialog.type) {
        case 'document_title':
            return 200;
        case 'objectives':
            return 600;
        case 'description':
            return 800;
        case 'contents':
            return 4000; // Увеличиваем лимит для содержания
        case 'theses':
            return 1000;
        case 'topic':
            return 300;
        default:
            return 1000;
    }
}

// Функция для проверки превышения лимита
function isValueTooLong() {
    return editDialog.value.length > getMaxLength();
}

// Функция для получения сообщения об ошибке
function getErrorMessage() {
    if (isValueTooLong()) {
        return `Превышен лимит символов (${getMaxLength()})`;
    }
    return '';
}

async function saveEdit() {
    // Проверяем лимит символов перед сохранением
    if (isValueTooLong()) {
        $q.notify({
            type: 'negative',
            message: `Превышен лимит символов (${getMaxLength()}). Сократите текст.`
        });
        return;
    }
    
    editDialog.loading = true;
    
    try {
        let data = {};
        let url = '';

        switch (editDialog.type) {
            case 'topic':
                data = { topic: editDialog.value };
                url = route('documents.update-topic', props.document.id);
                break;
            case 'document_title':
                data = { document_title: editDialog.value };
                url = route('documents.update-document-title', props.document.id);
                break;
            case 'description':
                data = { description: editDialog.value };
                url = route('documents.update-description', props.document.id);
                break;
            case 'objectives':
                data = { 
                    objectives: editDialog.value
                        .split('\n')
                        .map(line => line.trim())
                        .filter(line => line)
                };
                url = route('documents.update-objectives', props.document.id);
                break;
            case 'theses':
                data = { theses: editDialog.value };
                url = route('documents.update-theses', props.document.id);
                break;
            case 'contents':
                data = { contents: parseContentsFromText(editDialog.value) };
                url = route('documents.update-contents', props.document.id);
                break;
        }

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
            message: 'Изменения успешно сохранены'
        });

        closeEditDialog();
        emit('updated');
        
        // Перезагружаем страницу для обновления данных
        setTimeout(() => {
            location.reload();
        }, 500);

    } catch (error) {
        // console.error('Ошибка при сохранении:', error);  // Закомментировано для продакшена
    } finally {
        editDialog.loading = false;
    }
}

// Обработчик обновления ссылок
function handleReferencesUpdated(newReferences) {
    // console.log('Ссылки обновлены:', newReferences);  // Закомментировано для продакшена
    
    $q.notify({
        type: 'positive',
        message: 'Ссылки успешно сгенерированы!',
        position: 'top'
    });
    
    // Перезагружаем страницу через небольшую задержку для показа уведомления
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}
</script>

<style scoped>
.document-view {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Заголовок документа */
.document-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

/* Секция с основной информацией */
.info-section {
    width: 100%;
}

.info-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 300px;
}

.info-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.info-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.info-icon {
    font-size: 24px;
    color: #3b82f6;
    flex-shrink: 0;
}

.info-label {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.4;
}

/* Цвета статусов */
.status-generating {
    color: #3b82f6 !important;
    animation: spin 2s linear infinite;
}

.status-pre-complete {
    color: #10b981 !important;
}

.status-complete {
    color: #059669 !important;
}

.status-approved {
    color: #16a34a !important;
}

.status-failed {
    color: #ef4444 !important;
}

.status-default {
    color: #6b7280 !important;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Секции контента */
.content-section {
    width: 100%;
}

.section-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
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

.edit-btn {
    color: #6b7280;
    transition: all 0.2s ease;
}

.edit-btn:hover {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
}

.section-content {
    font-size: 16px;
    line-height: 1.6;
    color: #374151;
}

/* Список целей */
.objectives-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.objective-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.objective-number {
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    flex-shrink: 0;
    min-width: 32px;
}

.objective-text {
    flex: 1;
    font-size: 16px;
    font-weight: 500;
    line-height: 1.4;
    color: #374151;
}

/* Стили для заголовка документа */
.document-title-content {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.4;
    text-align: left;
    padding: 4px 0;
}

/* Современный диалог */
.edit-dialog {
    width: 90vw;
    max-width: 800px;
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

/* Контейнер для textarea с счетчиком */
.textarea-container {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Контейнер для input с счетчиком */
.input-container {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.character-counter {
    display: flex;
    justify-content: flex-end;
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.counter-error {
    color: #ef4444;
    font-weight: 600;
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

.save-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .document-title {
        font-size: 28px;
    }
    
    .section-card {
        padding: 28px;
    }
    
    .objective-item {
        padding: 14px 18px;
        gap: 14px;
    }
    
    .objective-number {
        font-size: 13px;
        min-width: 28px;
    }
    
    .objective-text {
        font-size: 15px;
    }
}

@media (max-width: 768px) {
    .document-view {
        gap: 20px;
    }
    
    .document-title {
        font-size: 24px;
    }
    
    .section-card {
        padding: 24px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .section-title {
        font-size: 18px;
    }
    
    .objective-item {
        padding: 12px 16px;
        gap: 12px;
    }
    
    .objective-number {
        font-size: 12px;
        min-width: 24px;
    }
    
    .objective-text {
        font-size: 14px;
    }
    
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
    }
}

@media (max-width: 480px) {
    .document-title {
        font-size: 22px;
    }
    
    .section-card {
        padding: 20px;
    }
    
    .section-title {
        font-size: 16px;
    }
    
    .objective-item {
        padding: 10px 14px;
        gap: 10px;
    }
    
    .objective-number {
        font-size: 11px;
        min-width: 20px;
    }
    
    .objective-text {
        font-size: 13px;
    }
    
    .edit-dialog-header {
        padding: 16px 20px;
    }
    
    .edit-dialog-title {
        font-size: 18px;
    }
    
    .edit-dialog-content {
        padding: 20px 16px;
    }
    
    .edit-dialog-actions {
        padding: 16px 20px;
        flex-direction: column-reverse;
    }
    
    .cancel-btn,
    .save-btn {
        width: 100%;
        justify-content: center;
    }
    
    .character-counter {
        font-size: 11px;
    }
}

@media (max-width: 360px) {
    .section-card {
        padding: 16px;
    }
    
    .section-title {
        font-size: 15px;
        gap: 8px;
    }
    
    .section-icon {
        font-size: 20px;
    }
    
    .objective-item {
        padding: 8px 12px;
        gap: 8px;
    }
    
    .objective-number {
        font-size: 10px;
        min-width: 18px;
    }
    
    .objective-text {
        font-size: 12px;
    }
    
    .character-counter {
        font-size: 10px;
    }
}
</style>