<template>
    <page-layout
        title="Документ"
        :is-sticky="true"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <!-- Если идет автозагрузка или отслеживание И генерация -->
            <document-generation-status
                v-if="(shouldAutoload || isPollingActive) && getIsGenerating()"
                :estimated-time="30"
                :title="getDisplayStatusText()"
                :generation-type="currentDocument.value?.status === 'full_generating' ? 'full' : 'structure'"
                @timeout="handleGenerationTimeout"
            />

            <!-- Если генерация НЕ идет или нет автозагрузки -->
            <template v-else>
                <document-view 
                    :document="currentDocument"
                    :document-status="documentStatus"
                    :status-text="getDisplayStatusText()"
                    :is-generating="getIsGenerating()"
                    :is-pre-generation-complete="isPreGenerationComplete()"
                    :is-full-generation-complete="getIsFullGenerationComplete()"
                    :has-failed="hasFailed()"
                    :is-approved="isApproved()"
                    :editable="canEdit"
                    @updated="handleDocumentUpdate"
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
                    <!-- Кнопка возобновления отслеживания для документов в процессе генерации -->
                    <q-btn
                        v-if="false && canResumeTracking() && !isPollingActive"
                        label="Продолжить генерацию"
                        color="primary"
                        outline
                        @click="resumeTracking"
                        class="q-px-lg q-py-sm"
                    />
                    
                    <!-- Кнопка остановки отслеживания -->
                    <q-btn
                        v-if="false"
                        label="Остановить отслеживание"
                        color="grey"
                        outline
                        @click="stopTracking"
                        class="q-px-lg q-py-sm"
                    />
                    
                    <!-- Кнопка запуска полной генерации -->
                    <q-btn
                        v-if="getCanStartFullGeneration()"
                        label="Завершить создание документа"
                        color="primary"
                        size="lg"
                        :loading="isStartingFullGeneration"
                        @click="startFullGeneration"
                        class="q-px-xl q-py-md"
                    />
                    
                    <!-- Кнопка скачивания Word - ТОЛЬКО для full_generated -->
                    <q-btn
                        v-if="getIsFullGenerationComplete()"
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
import { useTelegramWebApp } from '@/composables/telegramWebApp';

const $q = useQuasar();
const { downloadFile } = useTelegramWebApp();
const isDownloading = ref(false);
const isStartingFullGeneration = ref(false);
const isPollingActive = ref(false); // Флаг активного отслеживания

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

const canPay = computed(() => {
    // Показываем панель оплаты только если:
    // 1. Баланса недостаточно И
    // 2. Статус документа pre_generated
    return props.balance < props.orderPrice && currentDocument.value?.status === 'pre_generated';
});

// Реактивная ссылка на документ для обновления
const currentDocument = ref(props.document);

// Проверяем наличие параметра autoload в URL
const urlParams = new URLSearchParams(window.location.search);
const shouldAutoload = urlParams.get('autoload') === '1';

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
    hasReferences,
    isWaitingForReferences,
    getStatusText,
    startPolling,
    stopPolling
} = useDocumentStatus(
    () => props.document.id,
    {
        autoStart: shouldAutoload, // Включаем автозапуск только при наличии параметра autoload=1
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
            isPollingActive.value = false; // Останавливаем флаг отслеживания
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
            isPollingActive.value = false; // Останавливаем флаг отслеживания при ошибке
        }
    }
);

// Устанавливаем флаг отслеживания при автозагрузке
if (shouldAutoload) {
    isPollingActive.value = true;
}

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

// Функция для проверки возможности возобновления отслеживания
const canResumeTracking = () => {
    const status = currentDocument.value?.status;
    return status === 'pre_generating' || status === 'full_generating';
};

// Функция возобновления отслеживания
const resumeTracking = () => {
    startPolling();
    isPollingActive.value = true;
    $q.notify({
        type: 'info',
        message: 'Отслеживание статуса возобновлено',
        position: 'top'
    });
};

// Функция остановки отслеживания
const stopTracking = () => {
    stopPolling();
    isPollingActive.value = false;
    $q.notify({
        type: 'info',
        message: 'Отслеживание статуса остановлено',
        position: 'top'
    });
};

// Получить текст статуса для отображения
const getDisplayStatusText = () => {
    // Если есть данные из API, используем их
    if (documentStatus.value) {
        // Специальное сообщение для ожидания ссылок
        if (isWaitingForReferences()) {
            return 'Генерируются ссылки...';
        }
        return getStatusText();
    }
    
    // Если нет автообновления, используем статус из исходных данных документа
    return statusTextMapping[currentDocument.value?.status] || 'Неизвестный статус';
};

// Функции-обертки для работы без автообновления
const getCanStartFullGeneration = () => {
    // Если есть данные из API, используем их
    if (documentStatus.value) {
        return canStartFullGeneration();
    }
    
    // Если нет автообновления, проверяем статус из исходных данных
    return currentDocument.value?.status === 'pre_generated';
};

const getIsGenerating = () => {
    // Если есть данные из API, используем их
    if (documentStatus.value) {
        return isGenerating();
    }
    
    // Если нет автообновления, проверяем статус из исходных данных
    return ['pre_generating', 'full_generating'].includes(currentDocument.value?.status);
};

const getIsFullGenerationComplete = () => {
    // Если есть данные из API, используем их
    if (documentStatus.value) {
        return isFullGenerationComplete();
    }
    
    // Если нет автообновления, проверяем статус из исходных данных
    return currentDocument.value?.status === 'full_generated';
};

// Запуск полной генерации
const startFullGeneration = async () => {
    try {
        isStartingFullGeneration.value = true;
        
        const response = await apiClient.post(route('documents.generate-full', props.document.id));
        
        // Обновляем статус документа локально для мгновенного отображения
        currentDocument.value.status = 'full_generating';
        currentDocument.value.status_label = 'Генерируется содержимое...';
        
        // Запускаем отслеживание статуса
        isPollingActive.value = true;
        resumeTracking();
        
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
        const response = await apiClient.post(route('documents.download-word', props.document.id));
        
        // Проверяем, был ли файл отправлен в Telegram
        if (response.telegram_sent) {
            $q.notify({
                type: 'positive',
                message: 'Документ отправлен в Telegram'
            });
        } else {
            // Используем утилиту для скачивания
            downloadFile(response.url, response.filename);
            
            $q.notify({
                type: 'positive',
                message: 'Документ успешно сгенерирован'
            });
        }
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: error.response?.data?.message || 'Ошибка при генерации документа'
        });
    } finally {
        isDownloading.value = false;
    }
};

// Определяем можно ли редактировать документ
const canEdit = computed(() => {
    const status = currentDocument.value?.status;
    // Разрешаем редактирование для статусов draft, pre_generated, full_generated
    return ['draft', 'pre_generated', 'full_generated'].includes(status);
});

// Обработчик обновления документа из компонента DocumentView
const handleDocumentUpdate = () => {
    // Можно добавить логику для перезагрузки данных документа
    console.log('Документ был обновлен через редактирование');
    
    // Обновляем текущий документ, получив свежие данные
    window.location.reload();
};

// Обработчик события таймаута компонента генерации
const handleGenerationTimeout = () => {
    // Ничего не делаем - просто ловим событие
    console.log('Время ожидания генерации истекло, но продолжаем отслеживание через useDocumentStatus');
};
</script> 