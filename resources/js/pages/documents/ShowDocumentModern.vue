<template>
    <page-layout
        title="Документ"
        :is-sticky="true"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <!-- Креативный блок загрузки -->
            <div 
                v-if="(shouldAutoload || isPollingActive) && getIsGenerating()"
                class="generation-container"
            >
                <div class="generation-card">
                    <!-- Заголовок -->
                    <div class="generation-header">
                        <h2 class="generation-title">{{ getDisplayStatusText() }}</h2>
                        <p class="generation-subtitle">
                            {{ currentDocument.value?.status === 'full_generating' ? 
                                'Создаем полное содержание вашего документа' : 
                                'Формируем структуру и план документа' 
                            }}
                        </p>
                    </div>

                    <!-- Креативная анимация пишущей машинки -->
                    <div class="typewriter-container">
                        <!-- Пишущая машинка -->
                        <div class="typewriter">
                            <!-- Бумага (выходит сверху машинки) -->
                            <div class="paper">
                                <div class="paper-lines"></div>
                                <div class="typed-content">
                                    <!-- Уже напечатанный текст -->
                                    <div class="printed-lines">
                                        <div v-for="(line, index) in printedLines" 
                                             :key="index" 
                                             class="printed-line">
                                            {{ line }}
                                        </div>
                                    </div>
                                    <!-- Текущая печатающаяся строка -->
                                    <div class="current-line">
                                        <span class="typed-text">{{ currentTypedText }}</span>
                                        <span class="cursor">|</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Корпус машинки -->
                            <div class="machine-body">
                                <div class="keys">
                                    <div v-for="(key, index) in typewriterKeys" 
                                         :key="index" 
                                         class="key" 
                                         :class="{ 'key-pressed': key.isPressed }"
                                         :style="{ animationDelay: index * 0.05 + 's' }">
                                        {{ key.letter }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Время ожидания с анимированными точками -->
                    <div class="time-estimate">
                        <q-icon name="schedule" class="time-icon" />
                        <span>Примерное время: {{ currentDocument.value?.status === 'full_generating' ? '5-8' : '1-3' }} минут</span>
                    </div>

                    <!-- Советы пользователю -->
                    <div class="generation-tips">
                        <div v-if="!user.telegram_id" class="telegram-section">
                            <q-btn
                                color="primary"
                                text-color="white"
                                label="Авторизироваться через Telegram"
                                size="md"
                                @click="linkTelegram"
                                :loading="telegramLoading"
                                unelevated
                                rounded
                                class="telegram-notification-btn"
                                icon="fab fa-telegram-plane"
                            />
                            <p class="telegram-caption">
                                Свяжите аккаунт с Telegram для получения уведомлений о готовности документа
                            </p>
                        </div>
                    </div>
                </div>
            </div>

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
import { defineProps, ref, computed, onMounted, onUnmounted } from 'vue';
import { useQuasar } from 'quasar';
import PageLayout from '@/components/shared/PageLayout.vue';
import DocumentView from '@/modules/gpt/components/DocumentView.vue';
import DocumentStatusPanel from '@/modules/gpt/components/DocumentStatusPanel.vue';
import { useDocumentStatus } from '@/composables/documentStatus';
import { apiClient } from '@/composables/api';
import { router } from '@inertiajs/vue3';
import DocumentPaymentPanel from '@/modules/gpt/components/DocumentPaymentPanel.vue';

const $q = useQuasar();
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
    },
    user: {
        type: Object,
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

// Креативная анимация
const typingText = ref('');
const progressPercentage = ref(0);
const currentProcessText = ref('Инициализация генерации...');

// Новые переменные для улучшенной машинки
const currentTypedText = ref('');
const printedLines = ref([]);
const typewriterKeys = ref([]);

// Процессы для отображения
const processes = [
    'Инициализация генерации документа',
    'Анализ темы и требований',
    'Формирование структуры работы',
    'Создание плана содержания',
    'Генерация основных разделов',
    'Проработка деталей',
    'Добавление связующих элементов',
    'Проверка логической структуры',
    'Финальная обработка',
    'Подготовка к просмотру'
];

let typingInterval = null;
let processInterval = null;
let progressInterval = null;
let lettersInterval = null;
let typewriterInterval = null;

// Инициализация клавиш машинки
const initTypewriterKeys = () => {
    const keyboardLayout = [
        'Й', 'Ц', 'У', 'К',
        'Е', 'Н', 'Г', 'Ш',
        'Щ', 'З', 'Х', 'Ъ',
        'Ф', 'Ы', 'В', 'А'
    ];
    
    typewriterKeys.value = keyboardLayout.map(letter => ({
        letter,
        isPressed: false
    }));
};

// Тексты для печати
const typewriterTexts = [
    'Анализируем тему документа...',
    'Создаем структуру работы...',
    'Генерируем содержание...',
    'Добавляем детали и ссылки...',
    'Финализируем документ...',
    'Проверяем качество...'
];

// Новая функция анимации печати на машинке
const startTypewriterAnimation = () => {
    let textIndex = 0;
    let charIndex = 0;
    let currentText = typewriterTexts[textIndex];
    
    typewriterInterval = setInterval(() => {
        if (charIndex < currentText.length) {
            // Добавляем символ к текущей строке
            const char = currentText[charIndex];
            currentTypedText.value += char;
            
            // Анимируем нажатие случайной клавиши
            animateRandomKeyPress();
            
            charIndex++;
        } else {
            // Завершили строку - перемещаем в напечатанные
            printedLines.value.push(currentTypedText.value);
            currentTypedText.value = '';
            
            // Ограничиваем количество напечатанных строк
            if (printedLines.value.length > 4) {
                printedLines.value.shift();
            }
            
            // Переходим к следующему тексту
            textIndex = (textIndex + 1) % typewriterTexts.length;
            currentText = typewriterTexts[textIndex];
            charIndex = 0;
            
            // Пауза между строками
            setTimeout(() => {}, 1500);
        }
    }, 120); // Скорость печати
};

// Анимация нажатия случайной клавиши
const animateRandomKeyPress = () => {
    const randomIndex = Math.floor(Math.random() * typewriterKeys.value.length);
    const key = typewriterKeys.value[randomIndex];
    
    key.isPressed = true;
    
    // Убираем эффект нажатия через короткое время
    setTimeout(() => {
        key.isPressed = false;
    }, 120);
};

// Функция анимации прогресса
const startProgressAnimation = () => {
    progressInterval = setInterval(() => {
        progressPercentage.value = Math.min(progressPercentage.value + Math.random() * 2, 95);
    }, 500);
};

// Функция смены процессов
const startProcessAnimation = () => {
    let processIndex = 0;
    
    processInterval = setInterval(() => {
        currentProcessText.value = processes[processIndex];
        processIndex = (processIndex + 1) % processes.length;
    }, 2000);
};

// Запуск всех анимаций
const startCreativeAnimations = () => {
    initTypewriterKeys();
    startTypewriterAnimation();
    startProgressAnimation();
    startProcessAnimation();
};

// Остановка всех анимаций
const stopCreativeAnimations = () => {
    if (typingInterval) clearInterval(typingInterval);
    if (processInterval) clearInterval(processInterval);
    if (progressInterval) clearInterval(progressInterval);
    if (lettersInterval) clearInterval(lettersInterval);
    if (typewriterInterval) clearInterval(typewriterInterval);
};

// Запускаем анимации при монтировании
onMounted(() => {
    if ((shouldAutoload || isPollingActive.value) && getIsGenerating()) {
        startCreativeAnimations();
    }
});

// Останавливаем анимации при размонтировании
onUnmounted(() => {
    stopCreativeAnimations();
});

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
        
        // Создаем ссылку для скачивания
        const link = document.createElement('a');
        link.href = response.url;
        link.download = response.filename;
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

// Состояние Telegram
const telegramLoading = ref(false);

// Связать с Telegram
const linkTelegram = async () => {
    telegramLoading.value = true;
    
    try {
        const response = await fetch('/telegram/link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Открываем ссылку на бота в новой вкладке
            window.open(data.bot_url, '_blank');
            
            $q.notify({
                type: 'positive',
                message: 'Перейдите в Telegram и нажмите "Старт"',
                timeout: 5000
            });
            
        } else {
            $q.notify({
                type: 'negative',
                message: data.error || 'Ошибка при создании ссылки',
                timeout: 3000
            });
        }
        
    } catch (error) {
        console.error('Ошибка при связке с Telegram:', error);
        $q.notify({
            type: 'negative',
            message: 'Ошибка при связке с Telegram',
            timeout: 3000
        });
    } finally {
        telegramLoading.value = false;
    }
};
</script>

<style scoped>
/* Современный блок загрузки */
.generation-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    padding: 32px 16px;
}

.generation-card {
    background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
    border-radius: 24px;
    padding: 48px 40px;
    max-width: 600px;
    width: 100%;
    text-align: center;
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.08),
        0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.8);
    position: relative;
    overflow: hidden;
}

.generation-card::before {
    display: none;
}

@keyframes gradientShift {
    0%, 100% { opacity: 0; }
    50% { opacity: 0; }
}

/* Заголовок */
.generation-header {
    margin-bottom: 40px;
}

.generation-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.generation-subtitle {
    font-size: 16px;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

/* Креативная анимация пишущей машинки */
.typewriter-container {
    margin-bottom: 20px;
    position: relative;
    display: flex;
    justify-content: center;
    height: 280px;
    overflow: visible;
    margin-top: 80px;
}

.typewriter {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    width: 420px;
    height: 270px;
    background: linear-gradient(145deg, #4a5568 0%, #2d3748 100%);
    border-radius: 24px 24px 12px 12px;
    z-index: 2;
    box-shadow: 
        0 20px 40px rgba(74, 85, 104, 0.4),
        inset 0 -2px 8px rgba(0, 0, 0, 0.2);
    animation: typewriterBounce 3s ease-in-out infinite;
}

@keyframes typewriterBounce {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

.machine-body {
    position: relative;
    width: 360px;
    height: 150px;
    background: linear-gradient(145deg, #374151 0%, #1f2937 100%);
    border-radius: 18px;
    padding: 12px;
    margin-bottom: 30px;
    box-shadow: 
        inset 0 2px 4px rgba(0, 0, 0, 0.3),
        0 4px 8px rgba(0, 0, 0, 0.2);
}

.keys {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 9px;
    height: 100%;
}

.key {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: linear-gradient(145deg, #f3f4f6 0%, #d1d5db 100%);
    border-radius: 6px;
    font-size: 16px;
    font-weight: 700;
    color: #374151;
    box-shadow: 
        0 2px 4px rgba(0, 0, 0, 0.2),
        inset 0 1px 2px rgba(255, 255, 255, 0.5);
    transition: all 0.1s ease;
    cursor: pointer;
    animation: keyFloat 3s ease-in-out infinite;
}

.key:hover {
    transform: translateY(-1px);
    box-shadow: 
        0 4px 8px rgba(0, 0, 0, 0.3),
        inset 0 1px 2px rgba(255, 255, 255, 0.7);
}

.key-pressed {
    transform: translateY(2px) !important;
    background: linear-gradient(145deg, #3b82f6 0%, #1d4ed8 100%) !important;
    color: white !important;
    box-shadow: 
        0 1px 2px rgba(0, 0, 0, 0.4),
        inset 0 -1px 2px rgba(0, 0, 0, 0.2) !important;
}

@keyframes keyFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-1px); }
}

.paper {
    position: absolute;
    top: -45px;
    left: 50%;
    transform: translateX(-50%);
    width: 270px;
    height: 105px;
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 6px 6px 0 0;
    box-shadow: 
        0 8px 16px rgba(0, 0, 0, 0.15),
        inset 0 1px 2px rgba(255, 255, 255, 0.8);
    overflow: hidden;
    animation: paperMove 3s ease-in-out infinite;
}

@keyframes paperMove {
    0%, 100% { transform: translateX(-50%) translateY(0px); }
    50% { transform: translateX(-50%) translateY(-8px); }
}

.paper-lines {
    position: absolute;
    top: 18px;
    left: 18px;
    right: 18px;
    height: calc(100% - 36px);
    background: repeating-linear-gradient(
        transparent,
        transparent 12px,
        #e2e8f0 12px,
        #e2e8f0 13px
    );
}

.typed-content {
    position: absolute;
    bottom: 24px;
    left: 24px;
    right: 24px;
    top: 24px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    overflow: hidden;
}

.printed-lines {
    margin-bottom: 3px;
    display: flex;
    flex-direction: column;
}

.printed-line {
    font-size: 12px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
    margin-bottom: 2px;
    opacity: 0.7;
    animation: fadeInLine 0.5s ease-in;
}

@keyframes fadeInLine {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 0.7; transform: translateY(0); }
}

.current-line {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.3;
    display: flex;
    align-items: center;
}

.typed-text {
    animation: none;
    border: none;
}

.cursor {
    display: inline-block;
    background: #3b82f6;
    width: 2px;
    height: 15px;
    margin-left: 2px;
    animation: blink 1s step-end infinite;
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0; }
}

/* Магические частицы */
.magic-particles {
    display: none;
}

/* Статус процесса */
.process-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 24px;
    padding: 12px 20px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    position: relative;
    overflow: hidden;
}

.process-status::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%);
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

.status-icon {
    color: #3b82f6;
}

.magic-icon {
    font-size: 18px;
    animation: iconSpin 4s ease-in-out infinite;
}

@keyframes iconSpin {
    0%, 100% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(90deg) scale(1.1); }
    50% { transform: rotate(180deg) scale(1); }
    75% { transform: rotate(270deg) scale(1.1); }
}

.status-text {
    font-size: 14px;
    color: #1e293b;
    font-weight: 600;
    text-align: center;
    animation: textFade 2s ease-in-out infinite;
}

@keyframes textFade {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Время ожидания с анимированными точками */
.time-estimate {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 0;
    padding: 10px 16px;
    background: rgba(16, 185, 129, 0.1);
    border-radius: 10px;
    color: #059669;
    font-size: 13px;
    font-weight: 600;
}

.time-icon {
    font-size: 16px;
    animation: tickTock 2s ease-in-out infinite;
}

@keyframes tickTock {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(15deg); }
    75% { transform: rotate(-15deg); }
}

.loading-dots {
    display: flex;
    gap: 3px;
    margin-left: 8px;
}

.dot {
    width: 3px;
    height: 3px;
    background: #059669;
    border-radius: 50%;
    animation: dotBounce 1.4s ease-in-out infinite both;
}

.dot:nth-child(1) { animation-delay: -0.32s; }
.dot:nth-child(2) { animation-delay: -0.16s; }
.dot:nth-child(3) { animation-delay: 0s; }

@keyframes dotBounce {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1.2);
        opacity: 1;
    }
}

/* Советы пользователю */
.generation-tips {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-top: 24px;
    align-items: center;
}

.telegram-notification-btn {
    background: linear-gradient(135deg, #0088cc 0%, #0066aa 100%);
    box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
    font-weight: 600;
    padding: 12px 24px;
    border-radius: 25px;
}

.telegram-notification-btn:hover {
    background: linear-gradient(135deg, #0099dd 0%, #0077bb 100%);
    box-shadow: 0 6px 16px rgba(0, 136, 204, 0.4);
    transform: translateY(-1px);
}

.telegram-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    text-align: center;
}

.telegram-caption {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
    line-height: 1.4;
    max-width: 280px;
}

.tip-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px;
    background: rgba(249, 250, 251, 0.8);
    border-radius: 12px;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.tip-icon {
    font-size: 20px;
    color: #9ca3af;
    flex-shrink: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
    .generation-container {
        padding: 24px 16px;
        min-height: 70vh;
    }
    
    .generation-card {
        padding: 32px 24px;
        border-radius: 20px;
    }
    
    .generation-title {
        font-size: 24px;
    }
    
    .generation-subtitle {
        font-size: 15px;
    }
    
    .typewriter {
        width: 64px;
        height: 64px;
    }
    
    .typed-text {
        font-size: 20px;
    }
    
    .generation-tips {
        margin-top: 20px;
    }
    
    .tip-item {
        padding: 12px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .generation-card {
        padding: 24px 20px;
    }
    
    .generation-title {
        font-size: 22px;
    }
    
    .tip-item {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
}
</style> 