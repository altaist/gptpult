<template>
    <div class="document-references">
        <q-card class="q-mt-md">
            <q-card-section>
                <!-- Загрузочный блок -->
                <div v-if="isLoading" class="loading-block">
                    <div class="text-center q-py-xl">
                        <!-- Убираем спиннер с точками -->
                        <div class="text-h6 q-mb-sm">Генерируем ссылки</div>
                        <div class="text-body2 text-grey-6 q-mb-lg">
                            {{ loadingText }}
                        </div>
                        
                        <!-- Прогресс-бар -->
                        <div class="loading-progress q-mb-md">
                            <q-linear-progress 
                                :value="loadingProgress" 
                                color="primary" 
                                size="4px"
                                class="q-mb-sm"
                            />
                            <div class="text-caption text-grey-6">
                                {{ progressText }}
                            </div>
                        </div>
                        
                        <!-- Имитация генерируемых ссылок -->
                        <div class="generating-items">
                            <q-list>
                                <q-item 
                                    v-for="n in 3" 
                                    :key="n"
                                    class="skeleton-item"
                                >
                                    <q-item-section avatar>
                                        <q-skeleton type="QAvatar" />
                                    </q-item-section>
                                    <q-item-section>
                                        <q-skeleton type="text" width="60%" />
                                        <q-skeleton type="text" width="40%" />
                                        <q-skeleton type="text" width="80%" />
                                    </q-item-section>
                                    <q-item-section side>
                                        <q-skeleton type="QBtn" />
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </div>
                    </div>
                </div>

                <!-- Основной контент со ссылками с анимацией -->
                <div v-else-if="references && references.length" class="references-container">
                    <div class="flex items-center justify-between q-mb-md">
                        <div class="text-h6">
                            <q-icon name="link" class="q-mr-sm" />
                            Ссылки
                        </div>
                        <q-chip 
                            :label="`${references.length} ресурсов`" 
                            color="primary" 
                            text-color="white" 
                            size="sm"
                        />
                    </div>
                    
                    <div class="text-body2 text-grey-7 q-mb-md">
                        Релевантные источники для изучения темы
                    </div>

                    <q-list separator>
                        <q-item 
                            v-for="(reference, index) in references" 
                            :key="index"
                            clickable
                            @click="openLink(reference.url)"
                            class="reference-item"
                            :style="{ animationDelay: `${index * 100}ms` }"
                        >
                            <q-item-section avatar>
                                <q-icon 
                                    :name="getTypeIcon(reference.type)" 
                                    :color="getTypeColor(reference.type)" 
                                    size="md" 
                                />
                            </q-item-section>
                            
                            <q-item-section>
                                <q-item-label class="text-weight-medium reference-title">
                                    {{ reference.title }}
                                </q-item-label>
                                
                                <q-item-label caption class="reference-description">
                                    {{ reference.description }}
                                </q-item-label>
                                
                                <q-item-label caption class="text-grey-6 q-mt-xs">
                                    <div class="row items-center q-gutter-sm">
                                        <q-chip 
                                            :label="getTypeLabel(reference.type)" 
                                            size="sm" 
                                            outline 
                                            :color="getTypeColor(reference.type)"
                                        />
                                        
                                        <span v-if="reference.author" class="text-caption">
                                            <q-icon name="person" size="xs" class="q-mr-xs" />
                                            {{ reference.author }}
                                        </span>
                                        
                                        <span v-if="reference.publication_date" class="text-caption">
                                            <q-icon name="event" size="xs" class="q-mr-xs" />
                                            {{ reference.publication_date }}
                                        </span>
                                    </div>
                                </q-item-label>
                            </q-item-section>
                            
                            <q-item-section side>
                                <q-icon name="open_in_new" color="grey-5" />
                            </q-item-section>
                        </q-item>
                    </q-list>
                </div>

                <!-- Сообщение когда нет ссылок -->
                <div v-else-if="!isLoading" class="text-center q-py-lg text-grey-6">
                    <q-icon name="link_off" size="48px" class="q-mb-md" />
                    <div class="text-body1">Ссылки пока недоступны</div>
                </div>
            </q-card-section>
        </q-card>
    </div>
</template>

<script setup>
import { defineProps, ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useQuasar } from 'quasar';
import { apiClient } from '@/composables/api';

const props = defineProps({
    references: {
        type: Array,
        required: false,
        default: () => []
    },
    isLoading: {
        type: Boolean,
        default: true
    },
    documentId: {
        type: [Number, String],
        required: false
    }
});

const emit = defineEmits(['references-updated']);

const $q = useQuasar();

// Состояние загрузки
const loadingProgress = ref(0);
const currentLoadingStep = ref(0);
const statusCheckInterval = ref(null);

const loadingSteps = [
    'Анализируем тему документа...',
    'Ищем релевантные источники...',
    'Проверяем качество ссылок...',
    'Формируем список ресурсов...',
    'Добавляем описания...',
    'Завершаем подготовку ссылок...'
];

const loadingText = computed(() => {
    return loadingSteps[currentLoadingStep.value] || 'Генерируем ссылки...';
});

// Текст прогресса - не показываем проценты после 85%
const progressText = computed(() => {
    if (loadingProgress.value >= 0.85) {
        return 'Осталось немного...';
    }
    return `${Math.round(loadingProgress.value * 100)}% завершено`;
});

let loadingTimer = null;

// Проверка статуса документа
const checkDocumentStatus = async () => {
    if (!props.documentId || !props.isLoading) return;
    
    try {
        const response = await apiClient.get(route('documents.status', props.documentId));
        
        // Если появились ссылки, уведомляем родительский компонент
        if (response.document?.structure?.references && response.document.structure.references.length > 0) {
            emit('references-updated', response.document.structure.references);
        }
    } catch (error) {
        console.error('Ошибка при проверке статуса документа:', error);
    }
};

// Симуляция процесса загрузки - ограничиваем до 85%
const simulateLoading = () => {
    if (!props.isLoading) return;
    
    loadingTimer = setInterval(() => {
        if (loadingProgress.value < 0.85) { // Ограничиваем до 85%
            loadingProgress.value += 0.015; // Увеличиваем на 1.5% каждые 300ms
            
            // Меняем текст каждые ~15%
            const stepIndex = Math.floor(loadingProgress.value * loadingSteps.length);
            if (stepIndex < loadingSteps.length) {
                currentLoadingStep.value = stepIndex;
            }
        }
    }, 300);
};

// Запускаем периодическую проверку статуса
const startStatusPolling = () => {
    if (!props.documentId) return;
    
    // Проверяем каждые 3 секунды
    statusCheckInterval.value = setInterval(checkDocumentStatus, 3000);
};

// Останавливаем проверку статуса
const stopStatusPolling = () => {
    if (statusCheckInterval.value) {
        clearInterval(statusCheckInterval.value);
        statusCheckInterval.value = null;
    }
};

// Следим за изменением isLoading
watch(() => props.isLoading, (newValue) => {
    if (newValue) {
        simulateLoading();
        startStatusPolling();
    } else {
        if (loadingTimer) {
            clearInterval(loadingTimer);
            loadingTimer = null;
        }
        stopStatusPolling();
    }
});

onMounted(() => {
    if (props.isLoading) {
        simulateLoading();
        startStatusPolling();
    }
});

onUnmounted(() => {
    if (loadingTimer) {
        clearInterval(loadingTimer);
    }
    stopStatusPolling();
});

// Функция для открытия ссылки в новой вкладке
const openLink = (url) => {
    if (url) {
        window.open(url, '_blank', 'noopener,noreferrer');
    } else {
        $q.notify({
            type: 'negative',
            message: 'Ссылка недоступна',
            position: 'top'
        });
    }
};

// Функция для получения иконки типа ресурса
const getTypeIcon = (type) => {
    const icons = {
        'article': 'article',
        'pdf': 'picture_as_pdf',
        'book': 'menu_book',
        'website': 'language',
        'research_paper': 'science',
        'other': 'link'
    };
    return icons[type] || 'link';
};

// Функция для получения цвета типа ресурса
const getTypeColor = (type) => {
    const colors = {
        'article': 'blue',
        'pdf': 'red',
        'book': 'green',
        'website': 'purple',
        'research_paper': 'orange',
        'other': 'grey'
    };
    return colors[type] || 'grey';
};

// Функция для получения русского названия типа
const getTypeLabel = (type) => {
    const labels = {
        'article': 'Статья',
        'pdf': 'PDF',
        'book': 'Книга',
        'website': 'Сайт',
        'research_paper': 'Исследование',
        'other': 'Другое'
    };
    return labels[type] || 'Ресурс';
};
</script>

<style scoped>
.reference-item {
    transition: background-color 0.2s ease;
    border-radius: 8px;
    margin: 4px 0;
}

.reference-item:hover {
    background-color: #f5f5f5;
}

.reference-title {
    color: #1976d2;
    text-decoration: none;
}

.reference-description {
    margin-top: 4px;
    line-height: 1.4;
}

.document-references {
    margin-top: 1.5rem;
}

/* Стили для загрузочного блока */
.loading-block {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.loading-progress {
    max-width: 300px;
    margin: 0 auto;
}

.generating-items {
    max-width: 500px;
    margin: 0 auto;
}

.skeleton-item {
    margin-bottom: 8px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
}

/* Анимация появления ссылок */
.references-container {
    animation: fadeInUp 0.6s ease-out;
}

.reference-item {
    animation: slideInFromLeft 0.5s ease-out both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInFromLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Анимация для загрузочного блока */
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.loading-block {
    animation: fade-in-up 0.5s ease-out;
}
</style> 