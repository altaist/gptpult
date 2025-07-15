<template>
    <div class="content-section">
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <q-icon name="format_list_numbered" class="section-icon" />
                    Содержание
                    <div v-if="isCompleted" class="locked-indicator">
                        <q-icon name="lock" class="lock-icon" />
                        <q-tooltip class="locked-tooltip">
                            Работа готова. Редактирование содержания недоступно.
                        </q-tooltip>
                    </div>
                </div>
                <q-btn 
                    v-if="editable"
                    icon="edit" 
                    flat 
                    round 
                    size="sm" 
                    @click="$emit('edit-contents', contents)"
                    class="edit-btn"
                />
            </div>
            
            <div class="section-content">
                <div class="contents-list">
                    <!-- Титульник -->
                    <div class="content-block">
                        <div class="content-item main-item">
                            <span class="item-number">1</span>
                            <span class="item-title">Титульный лист</span>
                        </div>
                    </div>
                    <!-- Оглавление -->
                    <div class="content-block">
                        <div class="content-item main-item">
                            <span class="item-number">2</span>
                            <span class="item-title">Оглавление</span>
                        </div>
                    </div>
                    
                    <!-- Основные разделы -->
                    <div v-for="(topic, index) in contents" :key="index" class="content-block">
                        <!-- Основная тема -->
                        <div class="content-item main-item">
                            <span class="item-number">{{ index + 3 }}</span>
                            <span class="item-title">{{ topic.title }}</span>
                        </div>
                        
                        <!-- Подтемы -->
                        <div v-if="topic.subtopics && topic.subtopics.length" class="subtopics-container">
                            <div 
                                v-for="(subtopic, subIndex) in topic.subtopics" 
                                :key="subIndex" 
                                class="content-item sub-item"
                            >
                                <span class="item-number">{{ index + 3 }}.{{ subIndex + 1 }}</span>
                                <span class="item-title">{{ subtopic.title }}</span>
                                <span v-if="subtopic.description" class="item-description">
                                    {{ subtopic.description }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Список литературы -->
                    <div class="content-item main-item">
                        <span class="item-number">{{ contents.length + 3 }}</span>
                        <span class="item-title">Список литературы</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    contents: {
        type: Array,
        required: true,
        default: () => []
    },
    
    editable: {
        type: Boolean,
        default: false
    },

    isCompleted: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['edit-contents']);
</script>

<style scoped>
/* Основной контейнер секции */
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

/* Список содержания */
.contents-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Блок содержания (пункт + подпункты) */
.content-block {
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

/* Элементы содержания */
.content-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 20px;
}

/* Основные пункты (синие) */
.main-item {
    background: #3b82f6;
    color: white;
    border-radius: 12px;
    border: 1px solid #2563eb;
}

/* Подпункты (белые) */
.sub-item {
    background: #ffffff;
    color: #374151;
    border-bottom: 1px solid #f1f5f9;
}

.sub-item:last-child {
    border-bottom: none;
}

/* Контейнер подпунктов */
.subtopics-container {
    display: flex;
    flex-direction: column;
}

/* Номер элемента */
.item-number {
    font-size: 14px;
    font-weight: 600;
    flex-shrink: 0;
    min-width: 32px;
}

.main-item .item-number {
    color: white;
}

.sub-item .item-number {
    color: #64748b;
}

/* Заголовок элемента */
.item-title {
    font-size: 16px;
    font-weight: 500;
    line-height: 1.4;
    flex: 1;
}

.main-item .item-title {
    color: white;
    font-weight: 600;
}

.sub-item .item-title {
    color: #374151;
    font-weight: 500;
    font-size: 15px;
}

/* Описание элемента */
.item-description {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
    font-style: italic;
    margin-left: 8px;
}

/* Заблокированный индикатор */
.locked-indicator {
    display: flex;
    align-items: center;
    margin-left: 8px;
    position: relative;
}

.lock-icon {
    font-size: 16px;
    color: #ef4444;
    opacity: 0.8;
}

.locked-tooltip {
    background: #1f2937 !important;
    color: white !important;
    border-radius: 8px !important;
    padding: 8px 12px !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .section-card {
        padding: 28px;
    }
    
    .content-item {
        padding: 14px 18px;
        gap: 14px;
    }
    
    .item-number {
        font-size: 13px;
        min-width: 28px;
    }
    
    .item-title {
        font-size: 15px;
    }
    
    .sub-item .item-title {
        font-size: 14px;
    }
}

@media (max-width: 768px) {
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
    
    .content-item {
        padding: 12px 16px;
        gap: 12px;
    }
    
    .item-number {
        font-size: 12px;
        min-width: 24px;
    }
    
    .item-title {
        font-size: 14px;
    }
    
    .sub-item .item-title {
        font-size: 13px;
    }
    
    .item-description {
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .section-card {
        padding: 20px;
    }
    
    .section-title {
        font-size: 16px;
    }
    
    .content-item {
        padding: 10px 14px;
        gap: 10px;
    }
    
    .item-number {
        font-size: 11px;
        min-width: 20px;
    }
    
    .item-title {
        font-size: 13px;
    }
    
    .sub-item .item-title {
        font-size: 12px;
    }
    
    .item-description {
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
    
    .content-item {
        padding: 8px 12px;
        gap: 8px;
    }
    
    .item-number {
        font-size: 10px;
        min-width: 18px;
    }
    
    .item-title {
        font-size: 12px;
    }
    
    .sub-item .item-title {
        font-size: 11px;
    }
    
    .item-description {
        font-size: 10px;
    }
}
</style> 