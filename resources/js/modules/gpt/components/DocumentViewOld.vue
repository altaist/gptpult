<template>
    <div class="document-view">
        <div class="text-h5 q-mb-md">{{ document.topic || document.title }}</div>

        <!-- Карточка с основной информацией -->
        <q-card class="q-mb-md">
            <q-card-section>
                <div class="row q-col-gutter-md">
                    <div class="col-6">
                        <div class="text-subtitle2">Тип документа</div>
                        <div>{{ document.document_type?.name || 'Не указан' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-subtitle2">Статус</div>
                        <div>
                            <q-item-section side>
                                <q-icon
                                    :name="documentStatus?.status_icon || getDefaultIcon()"
                                    :color="documentStatus?.status_color || getDefaultColor()"
                                    size="sm"
                                />
                            </q-item-section>
                            
                            <q-item-section>
                                <q-item-label class="text-weight-medium">
                                    {{ statusText }}
                                </q-item-label>
                                <q-item-label 
                                    v-if="documentStatus?.status === 'pre_generated' && !documentStatus?.has_references"
                                    caption 
                                    class="text-warning"
                                >
                                    Ожидается завершение генерации ссылок
                                </q-item-label>
                            </q-item-section>
                        </div>
                    </div>
                </div>
            </q-card-section>
            </q-card>
            
            <!-- Отдельная карточка для темы -->
            <q-card v-if="document.structure?.topic" class="q-mb-md">
                <q-card-section>
                    <div class="flex items-center justify-between">
                        <div class="text-subtitle2">Тема документа</div>
                        <q-btn 
                            v-if="editable"
                            icon="edit" 
                            flat 
                            round 
                            size="sm" 
                            @click="openEditDialog('topic', 'Тема документа', document.structure.topic)"
                            class="q-ml-auto"
                        />
                    </div>
                    <div class="q-mt-sm text-body1">{{ document.structure.topic }}</div>
                </q-card-section>
            </q-card>

            <!-- Карточка для целей -->
            <q-card v-if="document.structure?.objectives && document.structure.objectives.length" class="q-mb-md">
                <q-card-section>
                    <div class="flex items-center justify-between">
                        <div class="text-subtitle2">Цели</div>
                        <q-btn 
                            v-if="editable"
                            icon="edit" 
                            flat 
                            round 
                            size="sm" 
                            @click="openEditDialog('objectives', 'Цели документа', document.structure.objectives.join('\n'))"
                            class="q-ml-auto"
                        />
                    </div>
                    <div class="q-mt-sm">
                        <ul>
                            <li v-for="objective in document.structure.objectives" :key="objective">
                                {{ objective }}
                            </li>
                        </ul>
                    </div>
                </q-card-section>
            </q-card>

            <q-card v-if="document.structure?.theses" class="q-mb-md">
            <q-card-section>
                <div class="flex items-center justify-between">
                    <div class="text-subtitle2">Тезисы</div>
                    <q-btn 
                        v-if="editable"
                        icon="edit" 
                        flat 
                        round 
                        size="sm" 
                        @click="openEditDialog('theses', 'Тезисы документа', document.structure.theses)"
                        class="q-ml-auto"
                    />
                </div>
                <div class="q-mt-sm">{{ document.structure.theses }}</div>
            </q-card-section>
        </q-card>

        <document-contents-view 
            v-if="document.structure?.contents" 
            :contents="document.structure.contents" 
            :editable="editable"
            @edit-contents="openContentsEditDialog"
        />

        <!-- Ссылки на полезные ресурсы -->
        <document-references-view 
            :references="document.structure?.references || []"
            :is-loading="shouldShowReferencesLoading"
            :document-id="document.id"
            @references-updated="handleReferencesUpdated"
        />

        <!-- Унифицированный диалог для редактирования -->
        <q-dialog v-model="editDialog.show" persistent>
            <q-card class="edit-dialog-card">
                <q-card-section class="row items-center q-pb-sm edit-header">
                    <q-icon name="edit" size="24px" class="q-mr-sm text-primary" />
                    <div class="text-h6 text-weight-medium">{{ editDialog.title }}</div>
                    <q-space />
                    <q-btn icon="close" flat round dense v-close-popup />
                </q-card-section>

                <q-separator />

                <q-card-section class="edit-content">
                    <q-input
                        v-if="editDialog.type === 'topic'"
                        v-model="editDialog.value"
                        outlined
                        type="text"
                        label="Тема документа"
                        autofocus
                        class="text-input"
                        hide-bottom-space
                    />
                    <q-input
                        v-else
                        v-model="editDialog.value"
                        outlined
                        type="textarea"
                        :rows="getTextareaRows()"
                        autofocus
                        class="textarea-input"
                        :placeholder="getTextareaPlaceholder()"
                        hide-bottom-space
                    />
                </q-card-section>

                <q-separator />

                <q-card-actions align="right" class="q-pa-lg">
                    <q-btn 
                        flat 
                        label="Отмена" 
                        color="grey-6" 
                        @click="closeEditDialog" 
                        class="q-px-lg"
                        no-caps
                    />
                    <q-btn 
                        unelevated 
                        label="Сохранить" 
                        color="primary" 
                        @click="saveEdit" 
                        :loading="editDialog.loading" 
                        class="q-px-xl q-ml-sm"
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
    // Показываем загрузку если:
    // 1. Документ еще генерируется (структура или полное содержимое)
    // 2. Или если есть содержание, но еще нет ссылок
    const isCurrentlyGenerating = props.isGenerating || 
                                props.documentStatus?.status === 'pre_generating' || 
                                props.documentStatus?.status === 'full_generating';
    
    const hasContents = props.document?.structure?.contents && props.document.structure.contents.length > 0;
    const hasReferences = props.document?.structure?.references && props.document.structure.references.length > 0;
    
    // Показываем загрузку если есть содержание, но нет ссылок, и не генерируется
    return hasContents && !hasReferences && !isCurrentlyGenerating;
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

const getDefaultColor = () => {
    if (props.isGenerating) return 'primary';
    if (props.isPreGenerationComplete) return 'positive';
    if (props.isFullGenerationComplete) return 'green';
    if (props.isApproved) return 'green-10';
    if (props.hasFailed) return 'negative';
    return 'grey';
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
        default:
            return 'Введите текст...';
    }
}

async function saveEdit() {
    editDialog.loading = true;
    
    try {
        let data = {};
        let url = '';

        switch (editDialog.type) {
            case 'topic':
                data = { topic: editDialog.value };
                url = route('documents.update-topic', props.document.id);
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
    max-width: 1200px;
    margin: 0 auto;
}

/* Стили для диалога редактирования */
.edit-dialog-card {
    width: 90vw;
    max-width: 900px;
    max-height: 85vh;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.edit-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 16px 16px 0 0;
}

.edit-content {
    padding: 24px;
    background: #ffffff;
}

.text-input {
    margin-bottom: 0;
}

.text-input :deep(.q-field__control) {
    border-radius: 8px;
    min-height: 56px;
    background: #ffffff;
}

.textarea-input {
    margin-bottom: 0;
}

.textarea-input :deep(.q-field__control) {
    border-radius: 12px;
    background: #ffffff;
}

.textarea-input :deep(.q-field__native) {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    font-size: 14px;
    line-height: 1.6;
    resize: vertical;
    min-height: 200px;
}

.text-input :deep(.q-field__native) {
    font-size: 16px;
    font-weight: 500;
}

/* Красивые границы */
.edit-dialog-card :deep(.q-field--outlined .q-field__control) {
    border: 2px solid #e2e8f0;
    transition: border-color 0.2s ease;
}

.edit-dialog-card :deep(.q-field--outlined.q-field--focused .q-field__control) {
    border-color: var(--q-primary);
}

.edit-dialog-card :deep(.q-field--outlined .q-field__control:before) {
    border: none;
}

.edit-dialog-card :deep(.q-field--outlined .q-field__control:after) {
    border: none;
}

/* Стили для кнопок */
.q-card-actions {
    background: #f8fafc;
    border-radius: 0 0 16px 16px;
}

.q-card-actions .q-btn {
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.q-card-actions .q-btn:hover {
    transform: translateY(-1px);
}

/* Responsive для мобильных */
@media (max-width: 768px) {
    .edit-dialog-card {
        width: 95vw;
        max-height: 90vh;
        border-radius: 12px;
    }
    
    .edit-header {
        border-radius: 12px 12px 0 0;
    }
    
    .q-card-actions {
        border-radius: 0 0 12px 12px;
    }
    
    .edit-content {
        padding: 16px;
    }
    
    .textarea-input :deep(.q-field__native) {
        min-height: 150px;
    }
}
</style> 