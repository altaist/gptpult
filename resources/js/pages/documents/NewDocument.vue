<template>
    <page-layout
        title="Новый документ"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <q-form @submit="onSubmit" class="q-gutter-md">
                <q-select
                    v-model="form.document_type_id"
                    :options="document_types"
                    option-label="name"
                    option-value="id"
                    label="Тип документа"
                    :rules="[val => !!val || 'Пожалуйста, выберите тип документа']"
                    :error="hasError('document_type_id')"
                    :error-message="getError('document_type_id')"
                    emit-value
                    map-options
                />

                <q-input
                    v-model="form.topic"
                    label="Тема документа"
                    type="textarea"
                    :rules="[val => !!val || 'Пожалуйста, введите тему документа']"
                    :error="hasError('topic')"
                    :error-message="getError('topic')"
                />

                <div v-if="error" class="text-negative q-mb-md">
                    {{ error }}
                </div>

                <div class="row justify-center q-mt-md">
                    <q-btn
                        label="Создать документ"
                        type="submit"
                        color="primary"
                        :loading="isLoading"
                        :disable="createdDocument !== null"
                    />
                </div>
            </q-form>

            <!-- Отображение статуса генерации -->
            <div v-if="createdDocument" class="q-mt-lg">
                <q-card class="q-pa-md">
                    <q-card-section>
                        <div class="text-h6">{{ createdDocument.title }}</div>
                        <div class="text-subtitle2 text-grey-7">ID: {{ createdDocument.id }}</div>
                    </q-card-section>

                    <q-card-section>
                        <div class="row items-center q-gutter-sm">
                            <q-icon 
                                :name="getStatusIcon()" 
                                :color="getStatusColor()"
                                size="sm"
                            />
                            <span class="text-body1">
                                Статус: {{ getStatusText() }}
                            </span>
                        </div>

                        <!-- Прогресс-бар для генерации -->
                        <q-linear-progress 
                            v-if="isGenerating()" 
                            indeterminate 
                            color="primary" 
                            class="q-mt-sm"
                        />
                    </q-card-section>

                    <!-- Действия -->
                    <q-card-actions align="right">
                        <q-btn
                            v-if="isPreGenerationComplete()"
                            label="Перейти к документу"
                            color="primary"
                            @click="goToDocument"
                        />
                        <q-btn
                            v-if="isFullGenerationComplete()"
                            label="Просмотр готового документа"
                            color="green"
                            @click="goToDocument"
                        />
                        <q-btn
                            v-if="isApproved()"
                            label="Просмотр утвержденного документа"
                            color="green-10"
                            @click="goToDocument"
                        />
                        <q-btn
                            v-if="hasFailed()"
                            label="Попробовать снова"
                            color="negative"
                            outline
                            @click="retryGeneration"
                        />
                    </q-card-actions>
                </q-card>
            </div>
        </div>
    </page-layout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PageLayout from '@/components/shared/PageLayout.vue';
import { apiClient, isLoading, useLaravelErrors } from '@/composables/api';
import { useDocumentStatus } from '@/composables/documentStatus';

const props = defineProps({
    document_types: {
        type: Array,
        required: true,
        default: () => []
    }
});

const error = ref('');
const form = ref({
    document_type_id: null,
    topic: ''
});
const createdDocument = ref(null);

const { hasError, getError } = useLaravelErrors();

// Создаем трекер статуса документа
const documentStatusTracker = useDocumentStatus(
    () => createdDocument.value?.id,
    {
        autoStart: false,
        onComplete: (status) => {
            console.log('Базовая генерация завершена!', status);
            // Автоматическая переадресация на просмотр документа
            router.visit(route('documents.show', status.document_id));
        },
        onFullComplete: (status) => {
            console.log('Полная генерация завершена!', status);
        },
        onApproved: (status) => {
            console.log('Документ утвержден! Переадресация...', status);
            // Автоматическая переадресация на просмотр документа
            router.visit(route('documents.show', status.document_id));
        },
        onError: (err) => {
            console.error('Ошибка при генерации:', err);
        }
    }
);

// Деструктурируем нужные методы и свойства
const {
    status: documentStatus,
    isGenerating,
    isPreGenerationComplete,
    isFullGenerationComplete,
    canStartFullGeneration,
    hasFailed,
    isApproved,
    getStatusText,
    startPolling,
    stopPolling
} = documentStatusTracker;

const onSubmit = async () => {
    try {
        error.value = '';

        const data = {
            ...form.value,
            document_type_id: Number(form.value.document_type_id)
        };

        const response = await apiClient.post(route('documents.quick-create'), data);
        
        // После успешного создания сохраняем документ и начинаем отслеживание
        if (response && response.document && response.document.id) {
            createdDocument.value = response.document;
            
            // Начинаем отслеживать статус генерации
            startPolling();
        } else {
            throw new Error('Неверный формат ответа от сервера');
        }
    } catch (err) {
        error.value = err.message || 'Произошла ошибка при создании документа';
        console.error('Ошибка при создании документа:', err);
    }
};

// Методы для работы с интерфейсом
const getStatusIcon = () => {
    // Используем данные из API, если доступны
    if (documentStatus.value?.status_icon) {
        return documentStatus.value.status_icon;
    }
    
    // Fallback для совместимости
    if (isGenerating()) return 'sync';
    if (isPreGenerationComplete()) return 'check_circle';
    if (isFullGenerationComplete()) return 'task_alt';
    if (isApproved()) return 'verified';
    if (hasFailed()) return 'error';
    return 'radio_button_unchecked';
};

const getStatusColor = () => {
    // Используем данные из API, если доступны
    if (documentStatus.value?.status_color) {
        return documentStatus.value.status_color;
    }
    
    // Fallback для совместимости
    if (isGenerating()) return 'primary';
    if (isPreGenerationComplete()) return 'positive';
    if (isFullGenerationComplete()) return 'green';
    if (isApproved()) return 'green-10';
    if (hasFailed()) return 'negative';
    return 'grey';
};

const goToDocument = () => {
    if (createdDocument.value?.id) {
        router.visit(route('documents.show', createdDocument.value.id));
    }
};

const retryGeneration = () => {
    // Очищаем текущий документ и позволяем создать новый
    createdDocument.value = null;
    stopPolling();
};
</script> 