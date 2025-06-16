<template>
    <div class="q-pa-md">
        <div class="text-h5 q-mb-md">Примеры использования DocumentStatusPanel</div>
        
        <!-- Простое использование -->
        <div class="text-h6 q-mb-sm">Простая панель статуса:</div>
        <document-status-panel
            :document-status="mockStatus"
            :is-generating="true"
            status-text="Генерируется структура..."
            class="q-mb-lg"
        />
        
        <!-- С кнопками действий -->
        <div class="text-h6 q-mb-sm">С кнопками действий:</div>
        <document-status-panel
            :document-status="mockStatusComplete"
            :is-generating="false"
            :is-pre-generation-complete="true"
            :can-start-full-generation="true"
            status-text="Структура готова"
            class="q-mb-lg"
        >
            <template #actions="{ isPreGenerationComplete, canStartFullGeneration }">
                <q-btn
                    v-if="isPreGenerationComplete"
                    label="Перейти к документу"
                    color="primary"
                    icon="visibility"
                />
                <q-btn
                    v-if="canStartFullGeneration"
                    label="Полная генерация"
                    color="secondary"
                    icon="autorenew"
                />
            </template>
        </document-status-panel>
        
        <!-- С дополнительным контентом -->
        <div class="text-h6 q-mb-sm">С дополнительным контентом:</div>
        <document-status-panel
            :document-status="mockStatusWithProgress"
            :is-generating="false"
            :is-full-generation-complete="true"
            status-text="Полностью готов"
            :show-completion-progress="true"
            class="q-mb-lg"
        >
            <template #actions>
                <q-btn label="Скачать Word" color="primary" icon="download" />
                <q-btn label="Поделиться" color="positive" icon="share" />
            </template>
            
            <template #extra>
                <div class="text-body2 text-grey-6">
                    Документ готов к использованию. Вы можете скачать его или поделиться с коллегами.
                </div>
            </template>
        </document-status-panel>
        
        <!-- Минимальная версия без заголовка -->
        <div class="text-h6 q-mb-sm">Компактная версия:</div>
        <document-status-panel
            :document-status="mockStatusFailed"
            :is-generating="false"
            :has-failed="true"
            status-text="Ошибка генерации"
            :show-title="false"
            :show-generating-progress="false"
            :show-completion-progress="false"
        >
            <template #actions="{ hasFailed }">
                <q-btn
                    v-if="hasFailed"
                    label="Попробовать снова"
                    color="negative"
                    outline
                    icon="refresh"
                />
            </template>
        </document-status-panel>
    </div>
</template>

<script setup>
import DocumentStatusPanel from './DocumentStatusPanel.vue';

// Мок-данные для демонстрации
const mockStatus = {
    status_icon: 'sync',
    status_color: 'primary'
};

const mockStatusComplete = {
    status_icon: 'check_circle',
    status_color: 'positive'
};

const mockStatusWithProgress = {
    status_icon: 'task_alt',
    status_color: 'green',
    progress: {
        completion_percentage: 85
    }
};

const mockStatusFailed = {
    status_icon: 'error',
    status_color: 'negative'
};
</script> 