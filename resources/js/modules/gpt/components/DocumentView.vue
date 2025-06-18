<template>
    <div class="document-view">
        <div class="text-h5 q-mb-md">{{ document.topic || document.title }}</div>
        
        <!-- Отдельная карточка для темы -->
        <q-card v-if="document.structure?.topic" class="q-mb-md">
            <q-card-section>
                <div class="text-subtitle2">Тема документа</div>
                <div class="q-mt-sm text-body1">{{ document.structure.topic }}</div>
            </q-card-section>
        </q-card>
        
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
                        <div class="q-mt-xs">
                            <span class="text-body2">{{ statusText }}</span>
                        </div>
                    </div>
                </div>
            </q-card-section>

            <q-card-section v-if="document.structure?.theses">
                <div class="text-subtitle2">Тезисы</div>
                <div class="q-mt-sm">{{ document.structure.theses }}</div>
            </q-card-section>
        </q-card>

        <document-contents-view 
            v-if="document.structure?.contents"
            :contents="document.structure.contents"
        />
    </div>
</template>

<script setup>
import { defineProps } from 'vue';
import DocumentContentsView from './DocumentContentsView.vue';

const props = defineProps({
    document: {
        type: Object,
        required: true
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
</script>

<style scoped>
.document-view {
    max-width: 1200px;
    margin: 0 auto;
}
</style> 