<template>
    <page-layout
        title="Просмотр документа"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <document-view :document="document" />

            <!-- Статус документа и кнопки управления -->
            <div class="q-mt-md">
                <q-card class="q-mb-md">
                    <q-card-section>
                        <div class="row items-center justify-between">
                            <div class="col">
                                <div class="text-h6">Статус генерации</div>
                                <div class="row items-center q-gutter-sm q-mt-sm">
                                    <q-icon 
                                        :name="getStatusIcon()" 
                                        :color="getStatusColor()"
                                        size="sm"
                                    />
                                    <span class="text-body1">{{ getStatusText() }}</span>
                                    <q-linear-progress 
                                        v-if="isGenerating()" 
                                        indeterminate 
                                        :color="getStatusColor()" 
                                        class="q-ml-sm"
                                        style="width: 200px"
                                    />
                                </div>
                                <div v-if="documentStatus?.progress" class="q-mt-sm">
                                    <q-linear-progress 
                                        :value="documentStatus.progress.completion_percentage / 100"
                                        color="positive"
                                        size="8px"
                                    />
                                    <div class="text-caption q-mt-xs">
                                        Завершено: {{ documentStatus.progress.completion_percentage }}%
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-auto">
                                <div class="row q-gutter-sm">
                                    <!-- Кнопка полной генерации -->
                                    <q-btn
                                        v-if="canStartFullGeneration()"
                                        label="Полная генерация"
                                        color="secondary"
                                        icon="autorenew"
                                        :loading="isStartingFullGeneration"
                                        @click="startFullGeneration"
                                    />
                                    
                                    <!-- Кнопка скачивания -->
                                    <q-btn
                                        label="Скачать Word"
                                        color="primary"
                                        icon="download"
                                        :loading="isDownloading"
                                        @click="downloadWord"
                                    />
                                </div>
                            </div>
                        </div>
                    </q-card-section>
                </q-card>
            </div>
        </div>
    </page-layout>
</template>

<script setup>
import { defineProps, ref } from 'vue';
import { useQuasar } from 'quasar';
import PageLayout from '@/components/shared/PageLayout.vue';
import DocumentView from '@/modules/gpt/components/DocumentView.vue';
import { useDocumentStatus } from '@/composables/documentStatus';
import { apiClient } from '@/composables/api';
import { router } from '@inertiajs/vue3';

const $q = useQuasar();
const isDownloading = ref(false);
const isStartingFullGeneration = ref(false);

const props = defineProps({
    document: {
        type: Object,
        required: true
    }
});

// Трекер статуса документа
const {
    status: documentStatus,
    isGenerating,
    canStartFullGeneration,
    isFullGenerationComplete,
    getStatusText,
    startPolling,
    stopPolling
} = useDocumentStatus(
    () => props.document.id,
    {
        autoStart: true,
        onFullComplete: (status) => {
            $q.notify({
                type: 'positive',
                message: 'Полная генерация документа завершена!',
                position: 'top'
            });
        },
        onError: (err) => {
            $q.notify({
                type: 'negative',
                message: 'Ошибка при отслеживании статуса: ' + err.message,
                position: 'top'
            });
        }
    }
);

// Методы для работы с интерфейсом
const getStatusIcon = () => {
    // Используем данные из API, если доступны
    if (documentStatus.value?.status_icon) {
        return documentStatus.value.status_icon;
    }
    return 'radio_button_unchecked';
};

const getStatusColor = () => {
    // Используем данные из API, если доступны
    if (documentStatus.value?.status_color) {
        return documentStatus.value.status_color;
    }
    return 'grey';
};

// Запуск полной генерации
const startFullGeneration = async () => {
    try {
        isStartingFullGeneration.value = true;
        
        const response = await apiClient.post(route('documents.generate-full', props.document.id));
        
        $q.notify({
            type: 'positive',
            message: response.message || 'Полная генерация запущена',
            position: 'top'
        });
        
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: error.response?.data?.message || 'Ошибка при запуске полной генерации',
            position: 'top'
        });
    } finally {
        isStartingFullGeneration.value = false;
    }
};

const downloadWord = async () => {
    try {
        isDownloading.value = true;
        const response = await axios.post(route('documents.download-word', props.document.id));
        
        // Создаем ссылку для скачивания
        const link = document.createElement('a');
        link.href = response.data.url;
        link.download = response.data.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        $q.notify({
            type: 'positive',
            message: 'Документ успешно сгенерирован'
        });
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: error.response?.data?.message || 'Ошибка при генерации документа'
        });
    } finally {
        isDownloading.value = false;
    }
};
</script> 