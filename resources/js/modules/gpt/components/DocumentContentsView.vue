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
                            Документ завершен. Редактирование содержания недоступно.
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
                    <div v-for="(topic, index) in contents" :key="index" class="topic-item">
                        <!-- Основная тема -->
                        <div class="topic-main">
                            <div class="topic-number">{{ index + 1 }}</div>
                            <div class="topic-content">
                                <div class="topic-title">{{ topic.title }}</div>
                            </div>
                        </div>
                        
                        <!-- Подтемы -->
                        <div v-if="topic.subtopics && topic.subtopics.length" class="subtopics-list">
                            <div 
                                v-for="(subtopic, subIndex) in topic.subtopics" 
                                :key="subIndex" 
                                class="subtopic-item"
                            >
                                <div class="subtopic-number">{{ index + 1 }}.{{ subIndex + 1 }}</div>
                                <div class="subtopic-content">
                                    <div class="subtopic-title">{{ subtopic.title }}</div>
                                    <div v-if="subtopic.description" class="subtopic-description">
                                        {{ subtopic.description }}
                                    </div>
                                </div>
                            </div>
                        </div>
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
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.section-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
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
    gap: 20px;
}

.topic-item {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Основная тема */
.topic-main {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.topic-main:hover {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-color: #cbd5e1;
    transform: translateX(4px);
}

.topic-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.topic-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.topic-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.4;
}

/* Подтемы */
.subtopics-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-left: 32px;
    padding-left: 24px;
    border-left: 3px solid #e2e8f0;
    position: relative;
}

.subtopics-list::before {
    content: '';
    position: absolute;
    left: -3px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 2px;
}

.subtopic-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
    transition: all 0.2s ease;
    position: relative;
}

.subtopic-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
    transform: translateX(4px);
}

.subtopic-number {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 56px;
    height: 28px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.subtopic-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.subtopic-title {
    font-size: 15px;
    font-weight: 500;
    color: #374151;
    line-height: 1.4;
}

.subtopic-description {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
    font-style: italic;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .section-card {
        padding: 24px;
    }
    
    .topic-number {
        width: 36px;
        height: 36px;
        font-size: 15px;
    }
    
    .topic-title {
        font-size: 17px;
    }
}

@media (max-width: 768px) {
    .section-card {
        padding: 20px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .topic-main {
        padding: 16px;
        gap: 12px;
    }
    
    .topic-number {
        width: 32px;
        height: 32px;
        font-size: 14px;
        border-radius: 10px;
    }
    
    .topic-title {
        font-size: 16px;
    }
    
    .subtopics-list {
        margin-left: 20px;
        padding-left: 16px;
    }
    
    .subtopic-item {
        padding: 10px 12px;
        gap: 10px;
    }
    
    .subtopic-number {
        min-width: 48px;
        height: 24px;
        font-size: 12px;
        border-radius: 6px;
    }
    
    .subtopic-title {
        font-size: 14px;
    }
    
    .subtopic-description {
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .section-card {
        padding: 16px;
    }
    
    .section-title {
        font-size: 18px;
    }
    
    .topic-main {
        padding: 12px;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 12px;
    }
    
    .topic-number {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
    
    .subtopics-list {
        margin-left: 0;
        padding-left: 16px;
        border-left: 2px solid #e2e8f0;
    }
    
    .subtopics-list::before {
        width: 2px;
        left: -2px;
    }
    
    .subtopic-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 8px;
    }
    
    .subtopic-number {
        min-width: 44px;
        height: 26px;
    }
}

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
</style> 