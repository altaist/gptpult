<template>
    <page-layout
        title="GPT PULT"
        :is-sticky="true"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <!-- Если идет генерация - показываем ТОЛЬКО компонент генерации -->
            <document-generation-status
                v-if="isGenerating()"
                :estimated-time="30"
                :title="getStatusText()"
                @timeout="handleGenerationTimeout"
            />

            <!-- Если генерация НЕ идет -->
            <template v-else>
                <document-view 
                    :document="currentDocument"
                    :document-status="documentStatus"
                    :status-text="getStatusText()"
                    :is-generating="isGenerating()"
                    :is-pre-generation-complete="isPreGenerationComplete()"
                    :is-full-generation-complete="isFullGenerationComplete()"
                    :has-failed="hasFailed()"
                    :is-approved="isApproved()"
                />

                <!-- Если не хватает баланса — панель оплаты -->
                <DocumentPaymentPanel
                    v-if="canPay"
                    :amount="orderPrice"
                    :document="currentDocument"
                    class="q-mt-md"
                />

                <!-- Если хватает баланса — панель кнопок действий -->
                <div
                    v-else
                    class="q-mt-md text-center q-gutter-md"
                >
                    <q-btn
                        v-if="canStartFullGeneration()"
                        label="Завершить создание документа"
                        color="primary"
                        size="lg"
                        :loading="isStartingFullGeneration"
                        @click="startFullGeneration"
                        class="q-px-xl q-py-md"
                    />
                    <q-btn
                        v-if="isPreGenerationComplete() || isFullGenerationComplete()"
                        label="Скачать Word"
                        color="primary"
                        size="lg"
                        :loading="isDownloading"
                        @click="downloadWord"
                        class="q-px-xl q-py-md"
                    />
                </div>
            </template>
        </div>
    </page-layout>
</template>

<script setup>
import { defineProps, ref, computed } from 'vue';
import { useQuasar } from 'quasar';
import PageLayout from '@/components/shared/PageLayout.vue';
import DocumentView from '@/modules/gpt/components/DocumentView.vue';
import DocumentStatusPanel from '@/modules/gpt/components/DocumentStatusPanel.vue';
import DocumentGenerationStatus from '@/modules/gpt/components/DocumentGenerationStatus.vue';
import { useDocumentStatus } from '@/composables/documentStatus';
import { apiClient } from '@/composables/api';
import { router } from '@inertiajs/vue3';
import DocumentPaymentPanel from '@/modules/gpt/components/DocumentPaymentPanel.vue';

const $q = useQuasar();
const isDownloading = ref(false);
const isStartingFullGeneration = ref(false);

const props = defineProps({
    document: {
        type: Object,
        required: true
    },
    balance: {
        type: Number,
        required: true,
        default: 0
    },
    orderPrice: {
        type: Number,
        required: true
    }
});

const canPay = computed(() => props.balance < props.orderPrice);

// Реактивная ссылка на документ для обновления
const currentDocument = ref(props.document);

// Трекер статуса документа
const {
    status: documentStatus,
    document: updatedDocument,
    isGenerating,
    canStartFullGeneration,
    isPreGenerationComplete,
    isFullGenerationComplete,
    hasFailed,
    isApproved,
    getStatusText,
    startPolling,
    stopPolling
} = useDocumentStatus(
    () => props.document.id,
    {
        autoStart: true,
        onComplete: (status) => {
            $q.notify({
                type: 'positive',
                message: 'Базовая генерация документа завершена!',
                position: 'top'
            });
        },
        onFullComplete: (status) => {
            $q.notify({
                type: 'positive',
                message: 'Полная генерация документа завершена!',
                position: 'top'
            });
        },
        onDocumentUpdate: (newDocument, oldDocument) => {
            // Обновляем текущий документ когда приходят новые данные
            currentDocument.value = newDocument;
            console.log('Документ обновлен:', newDocument);
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

// Обработчик события таймаута компонента генерации
const handleGenerationTimeout = () => {
    // Ничего не делаем - просто ловим событие
    console.log('Время ожидания генерации истекло, но продолжаем отслеживание через useDocumentStatus');
};
</script> 