<template>
    <div class="document-generation-status">
        <q-card class="generation-card">
            <q-card-section class="text-center q-pa-xl">
                <!-- Индикатор загрузки -->
                <div class="q-mb-lg flex justify-center">
                    <q-spinner-dots 
                        color="primary" 
                        size="64px"
                    />
                </div>
                
                <!-- Основной текст статуса -->
                <div class="text-h5 q-mb-md">
                    Подготовка документа
                </div>
                
                <!-- Обратный отсчет времени -->
                <div class="text-h6 text-primary q-mb-lg" v-if="!isOvertime">
                    Осталось {{ remainingTime }} сек.
                </div>
                
                <!-- Текущий процесс -->
                <div class="process-container q-mb-lg">
                    <q-linear-progress 
                        :value="processProgress" 
                        color="secondary" 
                        size="4px"
                        class="q-mb-sm"
                    />
                    <div class="text-body1 process-text">

                        {{ currentProcessText }}
                    </div>
                </div>
                
                <!-- Сообщение при превышении времени -->
                <div v-if="isOvertime" class="text-body2 text-grey-6">
                    Генерация продолжается, осталось совсем немного
                </div>
            </q-card-section>
        </q-card>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    // Предполагаемое время завершения в секундах
    estimatedTime: {
        type: Number,
        default: 15
    },
    
    // Заголовок процесса
    title: {
        type: String,
        default: 'Документ создается'
    },

    // Тип генерации: 'structure' или 'full'
    generationType: {
        type: String,
        default: 'structure'
    }
});

const emit = defineEmits(['timeout']);

// Состояние компонента
const remainingTime = ref(props.estimatedTime);
const currentProcessIndex = ref(0);
const isOvertime = ref(false);

// Массив строк процесса
const processSteps = [
    // Базовые шаги для генерации структуры
    'Готовим запрос к системе генерации',
    'Анализируем тему документа',
    'Формируем цели и задачи документа',
    'Создаем структуру содержания',
    'Генерируем разделы и подразделы',
    'Формируем детальное содержимое',
    'Проверяем корректность данных',
    'Добавляем ключевые моменты',
    'Форматируем структуру документа',
    'Финализируем результат',
    'Проверяем качество генерации',
    'Подготавливаем документ к просмотру'
];

// Шаги для полной генерации
const fullGenerationSteps = [
    'Подготовка к полной генерации документа',
    'Анализ структуры документа',
    'Сбор информации по каждому разделу',
    'Генерация основного содержания',
    'Проработка введения',
    'Формирование основной части',
    'Написание заключения',
    'Добавление ссылок и источников',
    'Проверка связности текста',
    'Форматирование документа',
    'Финальная проверка содержания',
    'Подготовка к просмотру'
];

// Таймеры
let countdownTimer = null;
let processTimer = null;

// Вычисляемые свойства
const currentProcessText = computed(() => {
    const steps = props.generationType === 'full' ? fullGenerationSteps : processSteps;
    
    if (currentProcessIndex.value < steps.length) {
        return steps[currentProcessIndex.value];
    }
    return props.generationType === 'full' 
        ? 'Завершаем полную генерацию документа...' 
        : 'Завершаем генерацию документа...';
});

const processProgress = computed(() => {
    const steps = props.generationType === 'full' ? fullGenerationSteps : processSteps;
    const totalSteps = steps.length;
    const progress = Math.min(currentProcessIndex.value / totalSteps, 1);
    
    // Если время превышено, показываем прогресс на основе времени
    if (isOvertime.value) {
        const overtime = (props.estimatedTime - remainingTime.value + props.estimatedTime) / (props.estimatedTime * 2);
        return Math.min(0.8 + overtime * 0.2, 0.95); // Максимум 95%
    }
    
    return Math.max(progress, 0.1); // Минимум 10%
});

// Методы
const startCountdown = () => {
    console.log('Запускаем обратный отсчет времени...');
    countdownTimer = setInterval(() => {
        remainingTime.value--;
        
        if (remainingTime.value <= 0) {
            isOvertime.value = true;
            clearInterval(countdownTimer);
            emit('timeout');
        }
    }, 1000);
};

const startProcessAnimation = () => {
    console.log('Запускаем анимацию процесса...');
    // Рассчитываем интервал смены строк процесса
    const intervalMs = Math.floor((props.estimatedTime * 1000) / processSteps.length);
    
    processTimer = setInterval(() => {
        if (currentProcessIndex.value < processSteps.length - 1) {
            currentProcessIndex.value++;
        }
    }, Math.max(intervalMs, 2000)); // Минимум 2 секунды на строку
};

// Удаляем функцию startPolling - она не нужна

const stopAllTimers = () => {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
    if (processTimer) {
        clearInterval(processTimer);
        processTimer = null;
    }
};

const startAll = () => {
    startCountdown();
    startProcessAnimation();
};

// Хуки жизненного цикла
onMounted(() => {
    console.log('DocumentGenerationStatus смонтирован, запускаем анимацию...');
    startAll();
});

onUnmounted(() => {
    console.log('DocumentGenerationStatus размонтирован, останавливаем таймеры...');
    stopAllTimers();
});
</script>

<style scoped>
.document-generation-status {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 70vh;
    padding: 2rem;
}

.generation-card {
    max-width: 600px;
    width: 100%;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Убрана анимация иконки - теперь используем Quasar спиннеры */

.process-container {
    background: rgba(0, 0, 0, 0.02);
    border-radius: 8px;
    padding: 1rem;
    margin: 0 auto;
    max-width: 400px;
}

.process-text {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    animation: fadeInUp 0.5s ease-in-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Эффект пульсации для времени */
.text-primary {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* Адаптивность */
@media (max-width: 600px) {
    .document-generation-status {
        padding: 1rem;
    }
    
    .generation-card .q-card-section {
        padding: 2rem 1rem;
    }
}
</style> 