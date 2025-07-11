<template>
    <!-- Современные уведомления - ВЫНЕСЕНЫ НА САМЫЙ ВЕРХ -->
    <div class="modern-notifications-container">
        <TransitionGroup name="notification" tag="div" class="notifications-list">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="[
                    'modern-notification',
                    `notification-${notification.type}`,
                    `notification-${notification.position}`
                ]"
                @click="removeNotification(notification.id)"
            >
                <!-- Иконка -->
                <div class="notification-icon">
                    <q-icon :name="notification.icon" />
                </div>
                
                <!-- Содержимое -->
                <div class="notification-content">
                    <div v-if="notification.title" class="notification-title">
                        {{ notification.title }}
                    </div>
                    <div class="notification-message">
                        {{ notification.message }}
                    </div>
                </div>
                
                <!-- Кнопка закрытия -->
                <div class="notification-close">
                    <q-icon name="close" />
                </div>
                
                <!-- Прогресс-бар -->
                <div 
                    v-if="notification.showProgress && notification.timeout > 0"
                    class="notification-progress"
                    :style="{ 
                        animationDuration: `${notification.timeout}ms`,
                        animationDelay: '100ms'
                    }"
                ></div>
            </div>
        </TransitionGroup>
    </div>

    <Head :title="pageTitle" />

    <page-layout
        :is-sticky="true"
        :auto-auth="true"
    >
        <div class="modern-container">
            <!-- Креативный блок загрузки -->
            <div 
                v-if="getIsGenerating()"
                class="generation-container"
            >
                <div class="generation-card">
                    <!-- Заголовок -->
                    <div class="generation-header">
                        <h2 class="generation-title">{{ getDisplayStatusText() }}</h2>
                        <p class="generation-subtitle">
                            {{ currentDocument.status === 'full_generating' ? 
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
                        <span>Примерное время: {{ currentDocument.status === 'full_generating' ? '5-8' : '1-3' }} минут</span>
                    </div>

                    <!-- Подпись о возможности закрыть страницу -->
                    <div v-if="user.telegram_id" class="close-page-hint">
                        <span>Вы можете закрыть эту страницу — уведомление о готовности придет в Telegram</span>
                    </div>

                    <!-- Советы пользователю -->
                    <div class="generation-tips">
                        <div v-if="!user.telegram_id" class="telegram-section">
                            <q-btn
                                color="primary"
                                text-color="white"
                                :label="shouldShowTelegramAuth ? 'Авторизоваться через Telegram' : 'Авторизироваться через Telegram'"
                                size="md"
                                @click="shouldShowTelegramAuth ? authTelegram() : linkTelegram()"
                                :loading="telegramLoading"
                                unelevated
                                rounded
                                class="telegram-notification-btn"
                                icon="fab fa-telegram-plane"
                            />
                            <p class="telegram-caption">
                                {{ shouldShowTelegramAuth ? 'Авторизуйтесь через Telegram для удобного доступа к аккаунту' : 'Свяжите аккаунт с Telegram для получения уведомлений о готовности документа' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Если генерация НЕ идет или нет автозагрузки -->
            <template v-else>
                <!-- Заголовок с кнопкой возврата -->
                <div class="header-section">
                    <!-- Кнопка возврата в личный кабинет -->
                    <div class="back-to-dashboard">
                        <q-btn
                            @click="goToDashboard"
                            class="dashboard-btn"
                            unelevated
                            no-caps
                        >
                            <q-icon name="arrow_back" />
                            <span>Личный кабинет</span>
                        </q-btn>
                    </div>

                    <!-- Шапка документа -->
                    <document-header 
                        :document="currentDocument"
                        :document-status="documentStatus"
                        :status-text="getDisplayStatusText()"
                        :is-generating="getIsGenerating()"
                        :is-pre-generation-complete="isPreGenerationComplete()"
                        :is-full-generation-complete="getIsFullGenerationComplete()"
                        :has-failed="hasFailed()"
                        :editable="canEdit"
                        @updated="handleDocumentUpdate"
                    />
                </div>

                <!-- Блок действий для мобильных (когда оплата не нужна) -->
                <div v-if="!canPay" class="mobile-actions-container mobile-only">
                    <DocumentActions 
                        :document="currentDocument"
                        :balance="balance"
                        :order-price="orderPrice"
                        :can-start-full-generation="getCanStartFullGeneration()"
                        :is-full-generation-complete="getIsFullGenerationComplete()"
                        :is-generating="getIsGenerating()"
                        :is-starting-full-generation="isStartingFullGeneration"
                        :is-downloading="isDownloading"
                        :user="user"
                        @start-full-generation="startFullGeneration"
                        @download-word="downloadWord"
                        @retry-generation="retryGeneration"
                    />
                </div>

                <!-- Основной контент -->
                <div class="main-content" :class="{ 'single-column': canPay }">
                    <!-- Левая колонка с документом -->
                    <div class="document-column">
                        <div class="document-card">
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
                        </div>
                        
                        <!-- Кнопка генерации внизу документа (когда нужна оплата) -->
                        <div v-if="canPay" class="bottom-generate-section">
                            <div class="bottom-generate-header">
                                <h3 class="bottom-generate-title">Структура документа готова! Теперь можно сгенерировать работу</h3>
                            </div>
                            <div class="bottom-generate-container">
                                <q-btn 
                                    class="generate-btn bottom-btn"
                                    unelevated
                                    rounded
                                    size="lg"
                                    no-caps
                                    @click="showActionsModal = true"
                                >
                                    <q-icon name="auto_awesome" class="btn-icon" />
                                    <span>Сгенерировать</span>
                                </q-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Правая колонка с блоком действий (только на десктопе, когда оплата не нужна) -->
                    <div v-if="!canPay" class="actions-column desktop-only">
                        <DocumentActions 
                            :document="currentDocument"
                            :balance="balance"
                            :order-price="orderPrice"
                            :can-start-full-generation="getCanStartFullGeneration()"
                            :is-full-generation-complete="getIsFullGenerationComplete()"
                            :is-generating="getIsGenerating()"
                            :is-starting-full-generation="isStartingFullGeneration"
                            :is-downloading="isDownloading"
                            :user="user"
                            @start-full-generation="startFullGeneration"
                            @download-word="downloadWord"
                            @retry-generation="retryGeneration"
                        />
                    </div>
                </div>
            </template>
        </div>
    </page-layout>

    <!-- Модальное окно с блоком действий -->
    <q-dialog v-model="showActionsModal">
        <q-card class="actions-modal-card">
            <q-card-section class="modal-header">
                <div class="modal-title">Для полной генерации работы нужно оформить абонемент</div>
                <q-btn 
                    flat 
                    round 
                    dense 
                    icon="close" 
                    @click="showActionsModal = false"
                    class="modal-close-btn"
                />
            </q-card-section>
            
            <q-card-section class="modal-content">
                <DocumentActions 
                    :document="currentDocument"
                    :balance="balance"
                    :order-price="orderPrice"
                    :can-start-full-generation="getCanStartFullGeneration()"
                    :is-full-generation-complete="getIsFullGenerationComplete()"
                    :is-generating="getIsGenerating()"
                    :is-starting-full-generation="isStartingFullGeneration"
                    :is-downloading="isDownloading"
                    :user="user"
                    @start-full-generation="startFullGeneration"
                    @download-word="downloadWord"
                    @retry-generation="retryGeneration"
                />
            </q-card-section>
        </q-card>
    </q-dialog>
</template>

<script setup>
import { defineProps, ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useQuasar } from 'quasar';
import PageLayout from '@/components/shared/PageLayout.vue';
import DocumentView from '@/modules/gpt/components/DocumentView.vue';
import DocumentStatusPanel from '@/modules/gpt/components/DocumentStatusPanel.vue';
import { useDocumentStatus } from '@/composables/documentStatus';
import { apiClient } from '@/composables/api';
import { router } from '@inertiajs/vue3';
import DocumentPaymentPanel from '@/modules/gpt/components/DocumentPaymentPanel.vue';
import DocumentHeader from '@/modules/gpt/components/DocumentHeader.vue';
import DocumentActions from '@/modules/gpt/components/DocumentActions.vue';
import { showModernNotification, useModernNotifications } from '@/utils/modernNotifications';
import { useTelegramWebApp } from '@/composables/telegramWebApp';
import { Head } from '@inertiajs/vue3';

const $q = useQuasar();
const isDownloading = ref(false);
const isStartingFullGeneration = ref(false);
const isPollingActive = ref(false); // Флаг активного отслеживания

// Используем глобальное состояние уведомлений
const { notifications, removeNotification } = useModernNotifications();

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

// Computed свойство для заголовка страницы
const pageTitle = computed(() => {
    const title = currentDocument.value?.structure?.title;
    if (title) {
        return title.length > 50 ? title.substring(0, 50) + '...' : title;
    }
    return 'Документ';
});

// Проверяем наличие параметра autoload в URL
const urlParams = new URLSearchParams(window.location.search);
const shouldAutoload = urlParams.get('autoload') === '1';
const shouldStartGeneration = urlParams.get('start_generation') === '1';

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
            // Уведомление убрано по запросу
        },
        onFullComplete: (status) => {
            showModernNotification({
                type: 'positive',
                title: 'Успех!',
                message: 'Полная генерация документа завершена!',
                icon: 'celebration'
            });
            isPollingActive.value = false; // Останавливаем флаг отслеживания
        },
        onDocumentUpdate: (newDocument, oldDocument) => {
            // Обновляем текущий документ когда приходят новые данные
            currentDocument.value = newDocument;
            // console.log('Документ обновлен:', newDocument);
            
            // Проверяем нужно ли перейти на загрузочный экран
            checkAndRedirectToLoadingScreen();
        },
        onError: (err) => {
            showModernNotification({
                type: 'negative',
                title: 'Ошибка',
                message: 'Ошибка при отслеживании статуса: ' + err.message,
                icon: 'error_outline'
            });
            isPollingActive.value = false; // Останавливаем флаг отслеживания при ошибке
        }
    }
);

// Устанавливаем флаг отслеживания при автозагрузке
if (shouldAutoload) {
    isPollingActive.value = true;
}

// Новые переменные для улучшенной машинки
const currentTypedText = ref('');
const printedLines = ref([]);
const typewriterKeys = ref([]);
const allTextsCompleted = ref(false); // Флаг завершения всех текстов
const currentTextIndex = ref(0); // Текущий индекс текста

// Интервал для анимации печати
let typewriterInterval = null;

// Функция сохранения состояния анимации
const saveTypewriterState = () => {
    const state = {
        allTextsCompleted: allTextsCompleted.value,
        currentTextIndex: currentTextIndex.value
    };
    sessionStorage.setItem('typewriter_state', JSON.stringify(state));
    // console.log('Сохранено состояние анимации:', state);
};

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

// Финальный текст после завершения всех основных текстов
const finalText = 'Еще чуть-чуть...';

// Новая функция анимации печати на машинке
const startTypewriterAnimation = () => {
    // Останавливаем предыдущую анимацию если она была запущена
    if (typewriterInterval) {
        clearInterval(typewriterInterval);
        typewriterInterval = null;
    }
    
    // Проверяем, не завершены ли уже все тексты из предыдущей сессии
    if (allTextsCompleted.value) {
        // Если все тексты уже были показаны, сразу показываем финальный текст
        showFinalText();
        return;
    }
    
    // Сбрасываем состояние анимации только если начинаем с начала
    if (currentTextIndex.value === 0) {
        currentTypedText.value = '';
        printedLines.value = [];
    }
    
    let charIndex = 0;
    let currentText = typewriterTexts[currentTextIndex.value];
    
    // console.log('Анимация пишущей машинки запущена с индекса:', currentTextIndex.value);
    
    typewriterInterval = setInterval(() => {
        if (charIndex < currentText.length) {
            // Добавляем символ к текущей строке
            const char = currentText[charIndex];
            currentTypedText.value += char;
            
            // Анимируем нажатие случайной клавиши (упрощенно)
            animateRandomKeyPress();
            
            charIndex++;
        } else {
            // Завершили строку - перемещаем в напечатанные
            printedLines.value.push(currentTypedText.value);
            currentTypedText.value = '';
            
            // Ограничиваем количество напечатанных строк
            if (printedLines.value.length > 3) {
                printedLines.value.shift();
            }
            
            // Переходим к следующему тексту
            currentTextIndex.value++;
            saveTypewriterState(); // Сохраняем состояние при каждом шаге
            
            // Проверяем, не закончились ли все тексты
            if (currentTextIndex.value >= typewriterTexts.length) {
                // Все тексты показаны, переходим к финальному
                allTextsCompleted.value = true;
                saveTypewriterState(); // Сохраняем состояние сразу
                clearInterval(typewriterInterval);
                typewriterInterval = null;
                
                // Показываем финальный текст после небольшой паузы
                setTimeout(() => {
                    showFinalText();
                }, 500);
                return;
            }
            
            currentText = typewriterTexts[currentTextIndex.value];
            charIndex = 0;
        }
    }, 150);
};

// Функция показа финального текста
const showFinalText = () => {
    if (typewriterInterval) {
        clearInterval(typewriterInterval);
        typewriterInterval = null;
    }
    
    currentTypedText.value = '';
    let charIndex = 0;
    
    // console.log('Показываем финальный текст:', finalText);
    
    typewriterInterval = setInterval(() => {
        if (charIndex < finalText.length) {
            const char = finalText[charIndex];
            currentTypedText.value += char;
            animateRandomKeyPress();
            charIndex++;
        } else {
            // Финальный текст написан, перемещаем в напечатанные строки
            printedLines.value.push(currentTypedText.value);
            currentTypedText.value = '';
            
            // Ограничиваем количество напечатанных строк
            if (printedLines.value.length > 3) {
                printedLines.value.shift();
            }
            
            // Сбрасываем индекс для повторной печати того же текста
            charIndex = 0;
            
            // Делаем паузу перед следующей итерацией
            clearInterval(typewriterInterval);
            typewriterInterval = null;
            
            setTimeout(() => {
                // Запускаем снова тот же финальный текст
                showFinalText();
            }, 1000); // Пауза 1 секунда между повторениями
        }
    }, 120);
};

// Функция циклического мигания для финального текста
const startFinalBlinking = () => {
    // Эта функция больше не нужна, так как showFinalText теперь циклическая
    // console.log('Финальная анимация запущена (циклическая)');
};

// Анимация нажатия случайной клавиши
const animateRandomKeyPress = () => {
    // Умеренная частота анимации клавиш
    if (Math.random() > 0.4) { // Уменьшаем с 80% до 60% для более спокойной анимации
        const randomIndex = Math.floor(Math.random() * typewriterKeys.value.length);
        const key = typewriterKeys.value[randomIndex];
        
        key.isPressed = true;
        
        // Убираем эффект нажатия через более длительное время
        setTimeout(() => {
            key.isPressed = false;
        }, 100); // Увеличиваем с 60ms до 100ms
    }
};

// Автоматически переходим на загрузочный экран если документ генерируется
const checkAndRedirectToLoadingScreen = () => {
    const status = currentDocument.value?.status;
    
    // Дополнительная проверка: если мы уже на загрузочном экране, не делаем ничего
    if (shouldAutoload) {
        // console.log('Уже на загрузочном экране, пропускаем перенаправление');
        return;
    }
    
    // Проверяем если документ генерируется и мы не на загрузочном экране
    if ((status === 'full_generating' || status === 'pre_generating') && !shouldAutoload) {
        // console.log('Документ генерируется, переходим на загрузочный экран...', status);
        // Если документ генерируется, но мы не на загрузочном экране - переходим туда
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('autoload', '1');
        // Удаляем опасные параметры
        currentUrl.searchParams.delete('start_generation');
        
        // Используем timeout чтобы дать возможность завершиться текущим операциям
        setTimeout(() => {
            window.location.href = currentUrl.toString();
        }, 100);
    }
};

// Добавляем watcher для отслеживания изменения статуса генерации
watch(
    () => getIsGenerating(),
    (isGenerating, wasGenerating) => {
        // console.log('Статус генерации изменился:', { isGenerating, wasGenerating });
        
        if (isGenerating && !wasGenerating) {
            // Генерация началась - запускаем анимацию
            // console.log('Запускаем анимацию пишущей машинки...');
            
            // Инициализируем клавиши если они еще не инициализированы
            if (typewriterKeys.value.length === 0) {
                initTypewriterKeys();
            }
            
            // Запускаем анимацию
            startTypewriterAnimation();
        } else if (!isGenerating && wasGenerating) {
            // Генерация завершилась - останавливаем анимацию
            // console.log('Останавливаем анимацию пишущей машинки...');
            if (typewriterInterval) {
                clearInterval(typewriterInterval);
                typewriterInterval = null;
            }
        }
    },
    { immediate: false } // Не вызываем при первом монтировании
);

// Дополнительный watcher для отслеживания изменения статуса документа
watch(
    () => currentDocument.value?.status,
    (newStatus, oldStatus) => {
        // console.log('Статус документа изменился:', { newStatus, oldStatus });
        
        // Если статус изменился на генерацию, запускаем анимацию
        if (['pre_generating', 'full_generating'].includes(newStatus) && 
            !['pre_generating', 'full_generating'].includes(oldStatus)) {
            
            // console.log('Документ начал генерироваться, запускаем анимацию...');
            
            // Инициализируем клавиши если они еще не инициализированы
            if (typewriterKeys.value.length === 0) {
                initTypewriterKeys();
            }
            
            // Запускаем анимацию
            startTypewriterAnimation();
        }
    },
    { immediate: false }
);

// Проверяем при монтировании компонента
onMounted(() => {
    // Добавляем обработчик для сохранения состояния при обновлении страницы
    const handleBeforeUnload = () => {
        saveTypewriterState();
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    
    // Сохраняем ссылку на обработчик для удаления в onUnmounted
    window._typewriterBeforeUnloadHandler = handleBeforeUnload;
    
    // Сразу очищаем URL от потенциально опасных параметров
    const currentUrl = new URL(window.location.href);
    let urlChanged = false;
    
    // Если документ уже генерируется или готов, принудительно удаляем все параметры автозапуска
    const currentStatus = currentDocument.value?.status;
    if (['full_generating', 'full_generated'].includes(currentStatus)) {
        if (currentUrl.searchParams.has('start_generation')) {
            currentUrl.searchParams.delete('start_generation');
            urlChanged = true;
        }
        if (currentUrl.searchParams.has('autoload') && currentStatus === 'full_generated') {
            currentUrl.searchParams.delete('autoload');
            urlChanged = true;
        }
    }
    
    // Обновляем URL если что-то изменилось
    if (urlChanged) {
        window.history.replaceState({}, '', currentUrl.toString());
        // console.log('URL очищен от параметров автозапуска для статуса:', currentStatus);
    }
    
    checkAndRedirectToLoadingScreen();
    
    // Восстанавливаем состояние анимации ВСЕГДА (не только при генерации)
    const savedState = sessionStorage.getItem('typewriter_state');
    if (savedState) {
        const state = JSON.parse(savedState);
        allTextsCompleted.value = state.allTextsCompleted || false;
        currentTextIndex.value = state.currentTextIndex || 0;
        // console.log('Восстановлено состояние анимации:', state);
    }
    
    if (getIsGenerating()) {
        initTypewriterKeys();
        
        // Если все тексты уже завершены, сразу запускаем финальный цикл
        if (allTextsCompleted.value) {
            // console.log('Автоматический запуск финального цикла при восстановлении состояния');
            showFinalText();
        } else {
            // Иначе запускаем обычную анимацию
            startTypewriterAnimation();
        }
        
        // Включаем отслеживание если оно ещё не включено
        if (!isPollingActive.value && !shouldAutoload) {
            isPollingActive.value = true;
            resumeTracking();
        }
    }
    
    // Автоматический запуск генерации если есть параметр start_generation=1
    if (shouldStartGeneration && getCanStartFullGeneration()) {
        // Дополнительные проверки чтобы избежать повторного запуска
        const currentStatus = currentDocument.value?.status;
        const isAlreadyGenerating = ['full_generating', 'full_generated'].includes(currentStatus);
        
        // console.log('Проверка автозапуска:', {
        //     shouldStartGeneration,
        //     canStart: getCanStartFullGeneration(),
        //     currentStatus,
        //     isAlreadyGenerating
        // });
        
        if (!isAlreadyGenerating) {
            setTimeout(() => {
                // console.log('Запускаем полную генерацию автоматически');
                startFullGeneration();
                // Удаляем параметр из URL после запуска
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('start_generation');
                window.history.replaceState({}, '', currentUrl.toString());
            }, 1000); // Небольшая задержка для корректной инициализации
        } else {
            // console.log('Автозапуск отменен - документ уже генерируется или готов:', currentStatus);
            // Если документ уже генерируется или сгенерирован, просто удаляем параметр
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('start_generation');
            window.history.replaceState({}, '', currentUrl.toString());
        }
    }
});

// Останавливаем анимации при размонтировании
onUnmounted(() => {
    if (typewriterInterval) clearInterval(typewriterInterval);
    
    // Удаляем обработчик beforeunload
    if (window._typewriterBeforeUnloadHandler) {
        window.removeEventListener('beforeunload', window._typewriterBeforeUnloadHandler);
        delete window._typewriterBeforeUnloadHandler;
    }
    
    // Сохраняем состояние анимации
    saveTypewriterState();
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
    showModernNotification({
        type: 'info',
        title: 'Отслеживание',
        message: 'Отслеживание статуса возобновлено',
        icon: 'play_circle'
    });
};

// Функция остановки отслеживания
const stopTracking = () => {
    stopPolling();
    isPollingActive.value = false;
    showModernNotification({
        type: 'info',
        title: 'Отслеживание',
        message: 'Отслеживание статуса остановлено',
        icon: 'pause_circle'
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
    console.log('startFullGeneration() вызвана', {
        currentStatus: currentDocument.value?.status,
        timestamp: new Date().toISOString()
    });
    
    try {
        // Проверяем, что генерация еще не запущена
        const currentStatus = currentDocument.value?.status;
        if (['full_generating', 'full_generated'].includes(currentStatus)) {
            console.warn('Попытка запустить полную генерацию для документа со статусом:', currentStatus);
            showModernNotification({
                type: 'warning',
                title: 'Генерация уже запущена',
                message: 'Документ уже генерируется или готов',
                icon: 'info'
            });
            return;
        }
        
        console.log('Отправляем запрос на сервер для запуска генерации');
        isStartingFullGeneration.value = true;
        
        const response = await apiClient.post(route('documents.generate-full', props.document.id));
        
        console.log('Получен ответ от сервера:', response);
        
        // Обновляем статус документа локально для мгновенного отображения
        currentDocument.value.status = 'full_generating';
        currentDocument.value.status_label = 'Генерируется содержимое...';
        
        // Запускаем отслеживание статуса
        isPollingActive.value = true;
        resumeTracking();
        
        showModernNotification({
            type: 'positive',
            title: 'Генерация запущена',
            message: response.message || 'Полная генерация запущена',
            icon: 'auto_awesome'
        });

        // Убираем автоматическое перенаправление - остаемся на текущей странице
        // setTimeout(() => {
        //     // Перенаправляем на загрузочный экран с параметром autoload
        //     const currentUrl = new URL(window.location.href);
        //     currentUrl.searchParams.set('autoload', '1');
        //     window.location.href = currentUrl.toString();
        // }, 200);
        
    } catch (error) {
        console.error('Ошибка при запуске генерации:', error);
        showModernNotification({
            type: 'negative',
            title: 'Ошибка генерации',
            message: error.response?.data?.message || 'Ошибка при запуске полной генерации',
            icon: 'error'
        });
    } finally {
        isStartingFullGeneration.value = false;
    }
};

const { downloadDocumentFile } = useTelegramWebApp();

const downloadWord = async () => {
    try {
        isDownloading.value = true;
        
        // Используем новую функцию для скачивания
        const result = await downloadDocumentFile(props.document.id);
        
        if (result.telegram_sent) {
            showModernNotification({
                type: 'positive',
                title: 'Документ отправлен',
                message: result.message || 'Документ отправлен в Telegram чат',
                icon: 'send'
            });
        } else {
            showModernNotification({
                type: 'positive',
                title: 'Документ готов',
                message: result.message || 'Документ успешно скачан',
                icon: 'download_done'
            });
        }
    } catch (error) {
        showModernNotification({
            type: 'negative',
            title: 'Ошибка скачивания',
            message: error.message || 'Ошибка при скачивании документа',
            icon: 'download_for_offline'
        });
    } finally {
        isDownloading.value = false;
    }
};

// Обработчик повторной генерации
const retryGeneration = async () => {
    try {
        const status = currentDocument.value?.status;
        
        // Определяем какую генерацию нужно повторить
        if (status === 'pre_generation_failed') {
            // Повторяем базовую генерацию
            isStartingFullGeneration.value = true;
            
            const response = await apiClient.post(route('documents.start-generation', props.document.id));
            
            // Обновляем статус документа локально
            currentDocument.value.status = 'pre_generating';
            currentDocument.value.status_label = 'Генерируется структура...';
            
            showModernNotification({
                type: 'positive',
                title: 'Повторная генерация',
                message: 'Повторная генерация структуры запущена',
                icon: 'refresh'
            });
            
        } else if (status === 'full_generation_failed') {
            // Повторяем полную генерацию
            isStartingFullGeneration.value = true;
            
            const response = await apiClient.post(route('documents.generate-full', props.document.id));
            
            // Обновляем статус документа локально
            currentDocument.value.status = 'full_generating';
            currentDocument.value.status_label = 'Генерируется содержимое...';
            
            showModernNotification({
                type: 'positive',
                title: 'Повторная генерация',
                message: 'Повторная полная генерация запущена',
                icon: 'refresh'
            });
        }
        
        // Запускаем отслеживание статуса
        isPollingActive.value = true;
        resumeTracking();
        
    } catch (error) {
        showModernNotification({
            type: 'negative',
            title: 'Ошибка повтора',
            message: error.response?.data?.message || 'Ошибка при запуске повторной генерации',
            icon: 'error'
        });
    } finally {
        isStartingFullGeneration.value = false;
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
    // console.log('Документ был обновлен через редактирование');
    
    // Обновляем текущий документ, получив свежие данные
    window.location.reload();
};

// Состояние Telegram
const telegramLoading = ref(false);

// Проверить, нужно ли показывать кнопку авторизации через Telegram
const shouldShowTelegramAuth = computed(() => {
    const user = props.user;
    if (!user) return false;
    
    // Показываем если нет связанного Telegram И email автогенерированный
    return !user.telegram_id && 
           user.email && 
           (user.email.endsWith('@auto.user') || user.email.endsWith('@linked.user'));
});

// Авторизация через Telegram (не связка, а именно авторизация)
const authTelegram = async () => {
    telegramLoading.value = true;
    
    try {
        const response = await fetch('/telegram/auth-link', {
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
            
            showModernNotification({
                type: 'positive',
                title: 'Telegram',
                message: 'Перейдите в Telegram и нажмите "Старт"',
                icon: 'fab fa-telegram-plane',
                timeout: 5000
            });
            
        } else {
            showModernNotification({
                type: 'negative',
                title: 'Ошибка авторизации',
                message: data.error || 'Ошибка при авторизации через Telegram',
                icon: 'link_off'
            });
        }
        
    } catch (error) {
        showModernNotification({
            type: 'negative',
            title: 'Ошибка',
            message: 'Произошла ошибка при авторизации через Telegram',
            icon: 'error'
        });
    } finally {
        telegramLoading.value = false;
    }
};

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
            
            showModernNotification({
                type: 'positive',
                title: 'Telegram',
                message: 'Перейдите в Telegram и нажмите "Старт"',
                icon: 'fab fa-telegram-plane',
                timeout: 5000
            });
            
        } else {
            showModernNotification({
                type: 'negative',
                title: 'Ошибка связи',
                message: data.message || 'Ошибка при связывании с Telegram',
                icon: 'link_off'
            });
        }
        
    } catch (error) {
        showModernNotification({
            type: 'negative',
            title: 'Ошибка',
            message: 'Произошла ошибка при связывании с Telegram',
            icon: 'error'
        });
    } finally {
        telegramLoading.value = false;
    }
};

// Новые функции для современного дизайна
const getStatusIcon = () => {
    const status = currentDocument.value?.status;
    switch (status) {
        case 'draft': return 'edit';
        case 'pre_generating': return 'hourglass_empty';
        case 'pre_generated': return 'check_circle_outline';
        case 'pre_generation_failed': return 'error_outline';
        case 'full_generating': return 'autorenew';
        case 'full_generated': return 'check_circle';
        case 'full_generation_failed': return 'error';
        case 'in_review': return 'visibility';
        case 'approved': return 'verified';
        case 'rejected': return 'cancel';
        default: return 'help_outline';
    }
};

const getStatusClass = () => {
    const status = currentDocument.value?.status;
    switch (status) {
        case 'draft': return 'status-draft';
        case 'pre_generating':
        case 'full_generating': return 'status-generating';
        case 'pre_generated':
        case 'full_generated': return 'status-completed';
        case 'pre_generation_failed':
        case 'full_generation_failed': return 'status-failed';
        case 'in_review': return 'status-review';
        case 'approved': return 'status-approved';
        case 'rejected': return 'status-rejected';
        default: return 'status-unknown';
    }
};

const getProgressIcon = () => {
    const status = currentDocument.value?.status;
    if (getIsGenerating()) return 'autorenew';
    if (getIsFullGenerationComplete()) return 'check';
    if (hasFailed() && hasFailed()) return 'close';
    return 'hourglass_empty';
};

const getProgressClass = () => {
    const status = currentDocument.value?.status;
    if (getIsGenerating()) return 'progress-generating';
    if (getIsFullGenerationComplete()) return 'progress-completed';
    if (hasFailed() && hasFailed()) return 'progress-failed';
    return 'progress-pending';
};

const getProgressText = () => {
    const status = currentDocument.value?.status;
    switch (status) {
        case 'draft': return 'Черновик';
        case 'pre_generating': return 'Генерация структуры';
        case 'pre_generated': return 'Структура готова';
        case 'full_generating': return 'Создание содержания';
        case 'full_generated': return 'Полностью готов';
        case 'pre_generation_failed':
        case 'full_generation_failed': return 'Ошибка генерации';
        default: return 'Неизвестно';
    }
};

// Функция форматирования даты (используется только на главной странице)
const formatDate = (dateString) => {
    if (!dateString) return 'Не указано';
    
    const date = new Date(dateString);
    const now = new Date();
    const diffInMs = now - date;
    const diffInHours = Math.floor(diffInMs / (1000 * 60 * 60));
    const diffInDays = Math.floor(diffInHours / 24);
    
    if (diffInHours < 1) {
        const diffInMinutes = Math.floor(diffInMs / (1000 * 60));
        return diffInMinutes < 1 ? 'Только что' : `${diffInMinutes} мин. назад`;
    } else if (diffInHours < 24) {
        return `${diffInHours} ч. назад`;
    } else if (diffInDays < 7) {
        return `${diffInDays} дн. назад`;
    } else {
        return date.toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'short',
            year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
        });
    }
};

const getCompletionPercentage = () => {
    const status = currentDocument.value?.status;
    switch (status) {
        case 'draft': return 10;
        case 'pre_generating': return 25;
        case 'pre_generated': return 50;
        case 'full_generating': return 75;
        case 'full_generated': return 100;
        case 'pre_generation_failed':
        case 'full_generation_failed': return 0;
        default: return 0;
    }
};

const getEstimatedTime = () => {
    if (!getIsGenerating()) {
        return null; // Не показываем время если не генерируется
    }
    
    const status = currentDocument.value?.status;
    if (status === 'pre_generating') {
        return '2-3 минуты';
    } else if (status === 'full_generating') {
        return '5-8 минут';
    }
    
    return null;
};

// Функция возврата в личный кабинет
const goToDashboard = () => {
    router.visit('/lk');
};

const showActionsModal = ref(false);
</script>

<style scoped>
/* Секция заголовка с кнопкой возврата */
.header-section {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 0px;
}

.back-to-dashboard {
    display: flex;
    justify-content: flex-start;
}

.dashboard-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 12px;
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
}

.dashboard-btn:hover {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.dashboard-btn .q-icon {
    font-size: 18px;
    flex-shrink: 0;
}

/* Основной контейнер */
.modern-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 24px;
    min-height: 100vh;
}

/* Современный блок загрузки */
.generation-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    padding: 32px 16px;
}

.generation-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 48px 40px;
    max-width: 600px;
    width: 100%;
    text-align: center;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    position: relative;
    overflow: hidden;
}

/* Убираем сложные анимации градиентов */
.generation-card::before {
    display: none;
}

/* Упрощаем блок курсора */
.cursor {
    display: inline-block;
    background: #3b82f6;
    width: 2px;
    height: 15px;
    margin-left: 2px;
    animation: blink 2s step-end infinite;
}

@keyframes blink {
    0%, 60% { opacity: 1; }
    61%, 100% { opacity: 0; }
}

/* Заголовок загрузки */
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
    background: linear-gradient(145deg, #7dd3fc 0%, #38bdf8 100%);
    border-radius: 24px 24px 12px 12px;
    z-index: 2;
    box-shadow: 
        0 20px 40px rgba(56, 189, 248, 0.4),
        inset 0 -2px 8px rgba(0, 0, 0, 0.2);
    animation: typewriterBounce 4s ease-in-out infinite;
}

@keyframes typewriterBounce {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-4px); }
}

.machine-body {
    position: relative;
    width: 360px;
    height: 150px;
    background: linear-gradient(145deg, #0ea5e9 0%, #0284c7 100%);
    border-radius: 18px;
    padding: 12px;
    margin-bottom: 30px;
    box-shadow: 0 8px 16px rgba(14, 165, 233, 0.3);
}

.keys {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-template-rows: repeat(4, 1fr);
    gap: 9px;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-items: center;
}

.key {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    max-width: 75px;
    max-height: 28px;
    background: linear-gradient(145deg, #f3f4f6 0%, #d1d5db 100%);
    border-radius: 6px;
    font-size: 16px;
    font-weight: 700;
    color: #374151;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    transition: all 0.1s ease;
    cursor: pointer;
}

.key:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.key-pressed {
    transform: translateY(2px) !important;
    background: linear-gradient(145deg, #3b82f6 0%, #1d4ed8 100%) !important;
    color: white !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.4) !important;
}

@keyframes paperMove {
    0%, 100% { transform: translateX(-50%) translateY(0px); }
    50% { transform: translateX(-50%) translateY(-4px); }
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
    animation: paperMove 4s ease-in-out infinite;
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

/* === ОСНОВНОЙ ДИЗАЙН СТРАНИЦЫ === */

/* Шапка документа */
.document-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 32px 40px;
    margin-bottom: 32px;
    color: white;
    position: relative;
    overflow: hidden;
}

.document-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    position: relative;
    z-index: 1;
}

.document-info-section {
    flex: 1;
}

.document-main-title {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 20px 0;
    line-height: 1.2;
    color: white;
}

.document-details {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
    align-items: center;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    background: rgba(255, 255, 255, 0.1);
    padding: 8px 16px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.detail-icon {
    font-size: 18px;
    opacity: 0.9;
    flex-shrink: 0;
}

.detail-label {
    font-weight: 500;
    opacity: 0.9;
}

.detail-value {
    font-weight: 700;
}

/* Статусы */
.status-draft { color: #f59e0b; }
.status-generating { color: #3b82f6; }
.status-completed { color: #10b981; }
.status-failed { color: #ef4444; }
.status-review { color: #8b5cf6; }
.status-approved { color: #10b981; }
.status-rejected { color: #ef4444; }
.status-unknown { color: #6b7280; }

/* Прогресс секция */
.progress-section {
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.progress-inner {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-icon {
    font-size: 28px;
    color: #4f46e5;
}

.progress-generating .progress-icon {
    animation: spin 2s linear infinite;
    color: #3b82f6;
}

.progress-completed .progress-icon {
    color: #10b981;
}

.progress-failed .progress-icon {
    color: #ef4444;
}

.progress-pending .progress-icon {
    color: #f59e0b;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.progress-label {
    font-size: 14px;
    font-weight: 600;
    color: #ffffff;
    margin-top: 8px;
}

/* Основной контент */
.main-content {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 32px;
    align-items: start;
}

/* Одноколоночная раскладка когда нужна оплата */
.main-content.single-column {
    grid-template-columns: 1fr;
    max-width: 900px;
    margin: 0 auto;
}

/* Колонка с документом */
.document-column {
    min-height: 600px;
}

.document-card {
    background: white;
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.document-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

/* Колонка с действиями */
.actions-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
    position: sticky;
    top: 100px;
}

/* Карточки действий */
.action-card {
    background: white;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.action-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

/* Карточка действий */
.actions-card {
    border: 2px solid transparent;
    background: linear-gradient(white, white) padding-box,
                linear-gradient(135deg, #667eea, #764ba2) border-box;
}

.actions-header {
    margin-bottom: 24px;
}

.actions-title {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.actions-subtitle {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.actions-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.action-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.action-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
}

.action-info {
    flex: 1;
}

.action-name {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 4px 0;
}

.action-description {
    font-size: 13px;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

.action-btn {
    min-width: 100px;
    border-radius: 12px;
    font-weight: 600;
    padding: 12px 20px;
    transition: all 0.2s ease;
}

.primary-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.primary-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

.success-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.success-btn:hover {
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    transform: translateY(-1px);
}

/* Информационный элемент */
.info-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 16px;
    border: 1px solid #f59e0b;
}

.info-icon {
    font-size: 24px;
    color: #d97706;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-title {
    font-size: 16px;
    font-weight: 600;
    color: #92400e;
    margin: 0 0 4px 0;
}

.info-text {
    font-size: 14px;
    color: #a16207;
    margin: 0;
}

/* Карточка статистики */
.stats-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
}

.stats-header {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.stats-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-icon {
    font-size: 20px;
    color: #4f46e5;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.stat-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.stat-value {
    font-size: 15px;
    color: #1e293b;
    font-weight: 600;
}

/* Карточка платежа */
.payment-card {
    border: 2px solid #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

/* Адаптивность */
@media (max-width: 1200px) {
    .main-content {
        grid-template-columns: 3fr 2fr;
        gap: 24px;
    }
    
    .document-main-title {
        font-size: 28px;
    }
    
    .modern-container {
        padding: 24px 20px;
    }
    
    .dashboard-btn {
        padding: 10px 18px;
        font-size: 13px;
    }
    
    .dashboard-btn .q-icon {
        font-size: 16px;
    }
}

@media (max-width: 1100px) {
    .main-content {
        grid-template-columns: 3fr 2fr;
        gap: 20px;
    }
}

@media (max-width: 1024px) {
    .main-content,
    .main-content.single-column {
        display: flex;
        flex-direction: column;
        gap: 0px;
        max-width: 100%;
    }
    
    .document-header {
        padding: 24px 28px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 24px;
        text-align: center;
    }
    
    .document-details {
        justify-content: center;
        gap: 20px;
    }
    
    .detail-item {
        font-size: 14px;
        padding: 6px 12px;
    }
    
    .detail-icon {
        font-size: 16px;
    }
    
    .modern-container {
        padding: 20px 16px;
    }
    
    .header-section {
        gap: 14px;
        margin-bottom: 0px;
    }
    
    .dashboard-btn {
        padding: 10px 16px;
        font-size: 13px;
        gap: 6px;
    }
    
    .dashboard-btn .q-icon {
        font-size: 16px;
    }
    
    .actions-modal-card {
        max-width: 95vw;
    }
}

@media (max-width: 768px) {
    .modern-container {
        padding: 20px 16px;
    }
    
    .mobile-btn {
        width: 100%;
        font-size: 15px;
        padding: 10px 20px;
    }
    
    .mobile-btn .btn-icon {
        font-size: 18px;
    }
    
    .mobile-actions-container {
        padding: 16px;
        border-radius: 16px;
    }
    
    .bottom-generate-section {
        margin-top: 20px;
    }
    
    .bottom-generate-header {
        margin-bottom: 16px;
    }
    
    .bottom-generate-title {
        font-size: 20px;
        padding-bottom: 10px;
    }
    
    .bottom-generate-title::after {
        width: 60px;
        height: 2px;
    }
    
    .bottom-generate-container {
        padding: 20px;
        border-radius: 16px;
    }
    
    .bottom-btn {
        padding: 14px 28px;
        font-size: 16px;
        min-width: 180px;
    }
    
    /* Адаптация секции заголовка */
    .header-section {
        gap: 12px;
        margin-bottom: 0px;
    }
    
    .back-to-dashboard {
        margin-bottom: 4px;
    }
    
    .dashboard-btn {
        padding: 8px 14px;
        font-size: 12px;
        gap: 6px;
        border-radius: 10px;
    }
    
    .dashboard-btn .q-icon {
        font-size: 15px;
    }
    
    .document-header {
        padding: 20px 24px;
        border-radius: 20px;
    }
    
    .document-main-title {
        font-size: 24px;
        margin-bottom: 16px;
    }
    
    .document-details {
        flex-direction: column;
        gap: 12px;
        width: 100%;
    }
    
    .detail-item {
        width: 100%;
        justify-content: center;
        padding: 8px 16px;
        font-size: 14px;
    }
    
    .progress-section {
        flex-direction: column;
        gap: 12px;
    }
    
    .progress-circle {
        width: 60px;
        height: 60px;
    }
    
    .progress-inner {
        width: 45px;
        height: 45px;
    }
    
    .progress-icon {
        font-size: 22px;
    }
    
    .progress-label {
        font-size: 13px;
    }
    
    .document-card {
        padding: 24px 20px;
        border-radius: 16px;
    }
    
    .action-card {
        padding: 20px;
        border-radius: 16px;
    }
    
    .action-item {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .action-btn {
        width: 100%;
        min-width: auto;
    }
    
    /* Адаптация блока машинки для планшетов */
    .typewriter-container {
        margin-top: 40px;
        height: 200px;
    }
    
    .typewriter {
        width: 320px;
        height: 200px;
    }
    
    .machine-body {
        width: 280px;
        height: 120px;
        margin-bottom: 20px;
    }
    
    .keys {
        gap: 6px;
    }
    
    .key {
        font-size: 14px;
    }
    
    .paper {
        width: 200px;
        height: 80px;
        top: -35px;
    }
    
    .printed-line {
        font-size: 10px;
    }
    
    .current-line {
        font-size: 11px;
    }
    
    .cursor {
        height: 12px;
    }
}

@media (max-width: 640px) {
    .modern-container {
        padding: 16px 12px;
    }
    
    .header-section {
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .mobile-actions-container {
        padding: 14px;
        border-radius: 14px;
    }
    
    .dashboard-btn {
        padding: 8px 12px;
        font-size: 11px;
        gap: 5px;
        border-radius: 8px;
    }
    
    .dashboard-btn .q-icon {
        font-size: 14px;
    }
    
    .document-header {
        padding: 16px 20px;
        border-radius: 16px;
    }
    
    .document-main-title {
        font-size: 20px;
        margin-bottom: 12px;
    }
    
    .document-card {
        padding: 20px 16px;
        border-radius: 14px;
    }
    
    .action-card {
        padding: 16px;
        border-radius: 14px;
    }
    
    .typewriter-container {
        margin-top: 30px;
        height: 170px;
    }
    
    .typewriter {
        width: 280px;
        height: 170px;
    }
    
    .machine-body {
        width: 240px;
        height: 100px;
        margin-bottom: 18px;
    }
    
    .paper {
        width: 180px;
        height: 70px;
        top: -30px;
    }
}

@media (max-width: 480px) {
    .generation-card {
        padding: 24px 20px;
    }
    
    .generation-title {
        font-size: 22px;
    }
    
    .modern-container {
        padding: 16px 8px;
    }
    
    .mobile-actions-container {
        padding: 12px;
        border-radius: 12px;
    }
    
    .bottom-generate-section {
        margin-top: 16px;
    }
    
    .bottom-generate-header {
        margin-bottom: 12px;
    }
    
    .bottom-generate-title {
        font-size: 18px;
        padding-bottom: 8px;
    }
    
    .bottom-generate-title::after {
        width: 50px;
        height: 2px;
    }
    
    .bottom-generate-container {
        padding: 16px;
        border-radius: 12px;
    }
    
    .bottom-btn {
        padding: 12px 24px;
        font-size: 15px;
        min-width: 160px;
    }
    
    /* Кнопка возврата на мобильных */
    .header-section {
        gap: 8px;
        margin-bottom: 0px;
    }
    
    .dashboard-btn {
        padding: 6px 10px;
        font-size: 10px;
        gap: 4px;
        border-radius: 8px;
        min-height: 32px;
    }
    
    .dashboard-btn .q-icon {
        font-size: 13px;
    }
    
    .dashboard-btn span {
        font-size: 10px;
    }
    
    .document-header {
        padding: 14px 16px;
        border-radius: 14px;
    }
    
    .document-main-title {
        font-size: 18px;
        margin-bottom: 10px;
    }
    
    .document-card {
        padding: 16px 12px;
        border-radius: 12px;
    }
    
    .action-card {
        padding: 14px;
        border-radius: 12px;
    }
    
    /* Адаптация блока машинки для мобильных */
    .typewriter-container {
        margin-top: 20px;
        height: 140px;
    }
    
    .typewriter {
        width: 240px;
        height: 140px;
    }
    
    .machine-body {
        width: 200px;
        height: 80px;
        margin-bottom: 15px;
        border-radius: 12px;
        padding: 8px;
    }
    
    .keys {
        gap: 3px;
        width: 100%;
        height: 100%;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: repeat(4, 1fr);
        align-items: center;
        justify-items: center;
    }
    
    .key {
        font-size: 10px;
        font-weight: 600;
        border-radius: 3px;
        width: 100%;
        height: 100%;
        max-width: 42px;
        max-height: 16px;
        min-width: 38px;
        min-height: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .paper {
        width: 150px;
        height: 60px;
        top: -25px;
    }
    
    .paper-lines {
        top: 12px;
        left: 12px;
        right: 12px;
        height: calc(100% - 24px);
        background: repeating-linear-gradient(
            transparent,
            transparent 8px,
            #e2e8f0 8px,
            #e2e8f0 9px
        );
    }
    
    .typed-content {
        bottom: 16px;
        left: 16px;
        right: 16px;
        top: 16px;
    }
    
    .printed-line {
        font-size: 8px;
        line-height: 1.2;
        margin-bottom: 1px;
    }
    
    .current-line {
        font-size: 9px;
        line-height: 1.2;
    }
    
    .cursor {
        height: 10px;
        width: 1px;
    }
}

@media (max-width: 360px) {
    .modern-container {
        padding: 12px 6px;
    }
    
    .header-section {
        gap: 6px;
        margin-bottom: 0px;
    }
    
    .bottom-generate-title {
        font-size: 16px;
        padding-bottom: 6px;
    }
    
    .bottom-generate-title::after {
        width: 40px;
        height: 2px;
    }
    
    .dashboard-btn {
        padding: 5px 8px;
        font-size: 9px;
        gap: 3px;
        border-radius: 6px;
        min-height: 28px;
    }
    
    .dashboard-btn .q-icon {
        font-size: 12px;
    }
    
    .dashboard-btn span {
        font-size: 9px;
    }
    
    .document-header {
        padding: 12px 14px;
        border-radius: 12px;
    }
    
    .document-main-title {
        font-size: 16px;
        margin-bottom: 8px;
    }
    
    .document-card {
        padding: 14px 10px;
        border-radius: 10px;
    }
    
    .action-card {
        padding: 12px;
        border-radius: 10px;
    }
    
    .generation-card {
        padding: 18px 14px;
        border-radius: 16px;
    }
    
    .generation-title {
        font-size: 18px;
    }
    
    .generation-subtitle {
        font-size: 13px;
    }
    
    .typewriter-container {
        margin-top: 15px;
        height: 120px;
    }
    
    .typewriter {
        width: 200px;
        height: 120px;
    }
    
    .machine-body {
        width: 170px;
        height: 70px;
        margin-bottom: 12px;
    }
    
    .paper {
        width: 130px;
        height: 50px;
        top: -20px;
    }
    
    .keys {
        gap: 2px;
    }
    
    .key {
        font-size: 8px;
        max-width: 36px;
        max-height: 14px;
        min-width: 32px;
        min-height: 12px;
    }
}

/* ===== СОВРЕМЕННЫЕ УВЕДОМЛЕНИЯ ===== */

.modern-notifications-container {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2147483647 !important;
    pointer-events: none;
    max-width: 420px;
    width: 100%;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
}

.modern-notification {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 4px 16px rgba(0, 0, 0, 0.08);
    border: none;
    backdrop-filter: blur(10px);
    min-width: 320px;
    max-width: 400px;
    position: relative;
    cursor: pointer;
    pointer-events: auto;
    overflow: hidden;
    transition: all 0.3s ease;
    transform: translateY(0);
}

.modern-notification:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15), 0 6px 20px rgba(0, 0, 0, 0.1);
}

.modern-notification::before {
    display: none;
}

/* Типы уведомлений */
.notification-positive {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.notification-positive .notification-icon {
    color: #10b981;
    background: rgba(16, 185, 129, 0.15);
}

.notification-negative {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.notification-negative .notification-icon {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.15);
}

.notification-warning {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
}

.notification-warning .notification-icon {
    color: #f59e0b;
    background: rgba(245, 158, 11, 0.15);
}

.notification-info {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.notification-info .notification-icon {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.15);
}

/* Компоненты уведомления */
.modern-notification {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.notification-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    font-size: 20px;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.notification-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.notification-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.2;
    margin: 0;
}

.notification-message {
    font-size: 14px;
    color: #64748b;
    line-height: 1.4;
    margin: 0;
    word-wrap: break-word;
}

.notification-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
    margin-top: -2px;
}

.notification-close:hover {
    background: rgba(148, 163, 184, 0.1);
    color: #64748b;
}

/* Прогресс-бар */
.notification-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: currentColor;
    border-radius: 0 0 16px 16px;
    opacity: 0.6;
    animation: notificationProgress linear forwards;
    width: 100%;
    transform-origin: left center;
}

@keyframes notificationProgress {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}

/* Анимации появления/исчезновения */
.notification-enter-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.notification-leave-active {
    transition: all 0.3s cubic-bezier(0.55, 0.085, 0.68, 0.53);
}

.notification-enter-from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
}

.notification-leave-to {
    opacity: 0;
    transform: translateY(-30px) scale(0.95);
}

.notification-move {
    transition: transform 0.3s ease;
}

/* Позиционирование */
.notification-top-left {
    /* Будет реализовано при необходимости */
}

.notification-top-right {
    /* По умолчанию */
}

.notification-bottom-left {
    /* Будет реализовано при необходимости */
}

.notification-bottom-right {
    /* Будет реализовано при необходимости */
}

/* Адаптивность уведомлений */
@media (max-width: 768px) {
    .modern-notifications-container {
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
        max-width: calc(100vw - 32px);
        width: auto;
        right: auto;
    }
    
    .modern-notification {
        min-width: 280px;
        max-width: calc(100vw - 40px);
        padding: 16px;
        border-radius: 12px;
    }
    
    .notification-icon {
        width: 36px;
        height: 36px;
        font-size: 18px;
        border-radius: 10px;
    }
    
    .notification-title {
        font-size: 15px;
    }
    
    .notification-message {
        font-size: 13px;
    }
    
    .notification-close {
        width: 20px;
        height: 20px;
    }
}

@media (max-width: 480px) {
    .modern-notifications-container {
        top: 70px;
        left: 50%;
        transform: translateX(-50%);
        max-width: calc(100vw - 24px);
    }
    
    .modern-notification {
        min-width: 260px;
        max-width: calc(100vw - 32px);
        padding: 14px;
        gap: 12px;
    }
    
    .notification-icon {
        width: 32px;
        height: 32px;
        font-size: 16px;
        border-radius: 8px;
    }
    
    .notification-title {
        font-size: 14px;
    }
    
    .notification-message {
        font-size: 12px;
        line-height: 1.3;
    }
    
    .notification-close {
        width: 18px;
        height: 18px;
    }
}

/* Эффекты при наведении на разные типы уведомлений */
.notification-positive:hover {
    box-shadow: 0 12px 40px rgba(16, 185, 129, 0.2), 0 6px 20px rgba(16, 185, 129, 0.15);
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.notification-negative:hover {
    box-shadow: 0 12px 40px rgba(239, 68, 68, 0.2), 0 6px 20px rgba(239, 68, 68, 0.15);
    background: linear-gradient(135deg, #fef1f1 0%, #fecaca 100%);
}

.notification-warning:hover {
    box-shadow: 0 12px 40px rgba(245, 158, 11, 0.2), 0 6px 20px rgba(245, 158, 11, 0.15);
    background: linear-gradient(135deg, #fefce8 0%, #fef08a 100%);
}

.notification-info:hover {
    box-shadow: 0 12px 40px rgba(59, 130, 246, 0.2), 0 6px 20px rgba(59, 130, 246, 0.15);
    background: linear-gradient(135deg, #f0f9ff 0%, #bfdbfe 100%);
}

/* Улучшенная анимация прогресс-бара */
.notification-progress {
    background: linear-gradient(90deg, 
        currentColor 0%, 
        currentColor 70%, 
        transparent 100%
    );
    filter: brightness(1.2);
}

.notification-positive .notification-progress {
    background: linear-gradient(90deg, #34d399 0%, #10b981 70%, transparent 100%);
}

.notification-negative .notification-progress {
    background: linear-gradient(90deg, #f87171 0%, #ef4444 70%, transparent 100%);
}

.notification-warning .notification-progress {
    background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 70%, transparent 100%);
}

.notification-info .notification-progress {
    background: linear-gradient(90deg, #60a5fa 0%, #3b82f6 70%, transparent 100%);
}

/* Подпись о возможности закрыть страницу */
.close-page-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 16px;
    font-size: 14px;
    color: #6b7280;
}

.hint-icon {
    font-size: 18px;
    color: #f59e0b;
}

/* Адаптивная видимость */
.desktop-only {
    display: block;
}

.mobile-only {
    display: none;
}

@media (max-width: 1024px) {
    .desktop-only {
        display: none !important;
    }
    
    .mobile-only {
        display: block !important;
    }
}

/* Контейнер для мобильной кнопки */
.mobile-button-container {
    margin-top: 10px;
    margin-bottom: 10px;
}

/* Контейнер для мобильных действий */
.mobile-actions-container {
    margin-top: 10px;
    margin-bottom: 10px;
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
}

/* Правая колонка с действиями */
.actions-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
    position: sticky;
    top: 100px;
}

/* Общие стили кнопки */
.generate-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 16px;
    font-weight: 600;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
    transition: all 0.3s ease;
}

.generate-btn:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    transform: translateY(-2px);
}

.generate-btn .btn-icon {
    margin-right: 8px;
    font-size: 20px;
}

/* Стили для мобильной кнопки */
.mobile-btn {
    padding: 12px 24px;
    width: 100%;
}

/* Стили для десктопной кнопки */
.desktop-btn {
    padding: 14px 18px;
    width: 100%;
    justify-content: flex-start;
}

/* Секция генерации внизу документа */
.bottom-generate-section {
    margin-top: 32px;
}

.bottom-generate-header {
    margin-bottom: 20px;
    text-align: center;
}

.bottom-generate-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.3;
    position: relative;
    padding-bottom: 12px;
}

.bottom-generate-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

/* Контейнер для кнопки внизу документа */
.bottom-generate-container {
    display: flex;
    justify-content: center;
    padding: 24px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 20px;
    border: 2px dashed #e2e8f0;
    transition: all 0.3s ease;
}

.bottom-generate-container:hover {
    border-color: #667eea;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    transform: translateY(-2px);
}

/* Стили для кнопки внизу */
.bottom-btn {
    padding: 16px 32px;
    font-size: 18px;
    font-weight: 600;
    min-width: 200px;
}

/* Модальное окно */
.actions-modal-card {
    width: 600px;
    max-width: 90vw;
    max-height: 90vh;
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
}

.modal-close-btn {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    transition: all 0.2s ease;
}

.modal-close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.modal-content {
    padding: 0;
    background: #f8fafc;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

@media (max-width: 1024px) {
    /* Равные отступы сверху и снизу от кнопки */
    .document-header {
        margin-bottom: 10px;
    }
    
    .mobile-button-container {
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    /* Отступ между кнопкой и контентом */
    .main-content {
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .actions-modal-card {
        width: 95vw;
        max-width: 95vw;
        max-height: 85vh;
        margin: auto;
    }
    
    .modal-header {
        padding: 16px 20px;
    }
    
    .modal-title {
        font-size: 18px;
        line-height: 1.3;
    }
}
</style> 