<template>
    <q-card class="document-status-panel">
        <q-card-section>
            <div class="row items-center justify-between">
                <div class="col">
                    <div class="text-h6" v-if="showTitle">{{ title }}</div>
                    <div class="row items-center q-gutter-sm" :class="{ 'q-mt-sm': showTitle }">
                        <q-icon 
                            :name="getStatusIcon()" 
                            :color="getStatusColor()"
                            size="sm"
                        />
                        <span class="text-body1">{{ statusText }}</span>
                        <!-- Индикатор генерации -->
                        <q-linear-progress 
                            v-if="isGenerating && showGeneratingProgress" 
                            indeterminate 
                            :color="getStatusColor()" 
                            class="q-ml-sm"
                            style="width: 200px"
                        />
                    </div>
                    
                    <!-- Общий прогресс завершенности -->
                    <div v-if="documentStatus?.progress && showCompletionProgress" class="q-mt-sm">
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
                
                <!-- Слот для кнопок действий -->
                <div class="col-auto" v-if="$slots.actions">
                    <div class="row q-gutter-sm">
                        <slot name="actions" 
                            :status="documentStatus"
                            :isGenerating="isGenerating"
                            :canStartFullGeneration="canStartFullGeneration"
                            :isPreGenerationComplete="isPreGenerationComplete"
                            :isFullGenerationComplete="isFullGenerationComplete"
                            :hasFailed="hasFailed"
                            :isApproved="isApproved"
                        />
                    </div>
                </div>
            </div>
        </q-card-section>
        
        <!-- Слот для дополнительного контента -->
        <q-card-section v-if="$slots.extra" class="q-pt-none">
            <slot name="extra" 
                :status="documentStatus"
                :isGenerating="isGenerating"
            />
        </q-card-section>
    </q-card>
</template>

<script setup>
const props = defineProps({
    // Статус документа из API
    documentStatus: {
        type: Object,
        default: () => null
    },
    
    // Заголовок панели
    title: {
        type: String,
        default: 'Статус генерации'
    },
    
    // Показывать ли заголовок
    showTitle: {
        type: Boolean,
        default: true
    },
    
    // Показывать ли прогресс-бар генерации
    showGeneratingProgress: {
        type: Boolean,
        default: true
    },
    
    // Показывать ли прогресс завершенности
    showCompletionProgress: {
        type: Boolean,
        default: true
    },
    
    // Текст статуса
    statusText: {
        type: String,
        default: 'Неизвестно'
    },
    
    // Boolean состояния (вместо функций)
    isGenerating: {
        type: Boolean,
        default: false
    },
    
    canStartFullGeneration: {
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

// Методы для получения иконки и цвета статуса
const getStatusIcon = () => {
    // Используем данные из API, если доступны
    if (props.documentStatus?.status_icon) {
        return props.documentStatus.status_icon;
    }
    
    // Fallback для совместимости
    if (props.isGenerating) return 'sync';
    if (props.isPreGenerationComplete) return 'check_circle';
    if (props.isFullGenerationComplete) return 'task_alt';
    if (props.isApproved) return 'verified';
    if (props.hasFailed) return 'error';
    return 'radio_button_unchecked';
};

const getStatusColor = () => {
    // Используем данные из API, если доступны
    if (props.documentStatus?.status_color) {
        return props.documentStatus.status_color;
    }
    
    // Fallback для совместимости
    if (props.isGenerating) return 'primary';
    if (props.isPreGenerationComplete) return 'positive';
    if (props.isFullGenerationComplete) return 'green';
    if (props.isApproved) return 'green-10';
    if (props.hasFailed) return 'negative';
    return 'grey';
};
</script>

<style scoped>
.document-status-panel {
    margin-bottom: 1rem;
}
</style> 