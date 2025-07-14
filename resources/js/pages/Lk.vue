<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useQuasar } from 'quasar';
import PageLayout from '@/components/shared/PageLayout.vue';
import YandexMetrika from '@/components/shared/YandexMetrika.vue';
import { useTelegramMiniApp } from '@/composables/useTelegramMiniApp.js';
import { useTelegramWebApp } from '@/composables/telegramWebApp.js';

const $q = useQuasar();

// Telegram Mini App
const { isTelegramMiniApp, telegramData, showBackButton, hideBackButton } = useTelegramMiniApp();

// Telegram WebApp для определения среды
const { isTelegramWebApp } = useTelegramWebApp();

// Определяем пропсы, которые могут приходить от контроллера
const props = defineProps({
  auth: Object,
  balance: {
    type: Number,
    default: 15000
  },
  documents: {
    type: Array,
    default: () => []
  },
  isDevelopment: {
    type: Boolean,
    default: false
  }
});

// Состояние Telegram
const telegramStatus = ref({
  is_linked: false,
  telegram_username: null,
  linked_at: null
});
const telegramLoading = ref(false);

// Состояния для модальных окон пополнения
const showTopUpModal = ref(false);
const topUpAmount = ref(300);
const isCreatingOrder = ref(false);

// Состояния для истории транзакций
const showTransitionsModal = ref(false);
const transitions = ref([]);
const isLoadingTransitions = ref(false);

// Состояния для тестового уменьшения баланса
const showDecrementModal = ref(false);
const decrementAmount = ref(100);
const isDecrementingBalance = ref(false);

// Состояния для пополнения баланса - email и согласие
const userEmail = ref('');
const privacyConsent = ref(false);
const isSavingUserContact = ref(false);

// Computed свойство для проверки валидности email
const isEmailValid = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const trimmedEmail = userEmail.value.trim();
  
  // Проверяем формат и что email не автогенерированный
  return trimmedEmail && 
         emailRegex.test(trimmedEmail) && 
         !trimmedEmail.endsWith('@auto.user') && 
         !trimmedEmail.endsWith('@linked.user');
});

// Вычисляем количество оставшихся генераций (цена за генерацию 100₽)
const remainingGenerations = computed(() => {
  const currentBalance = props.balance || 0;
  const pricePerGeneration = 100;
  return Math.floor(currentBalance / pricePerGeneration);
});

// Загрузить статус Telegram при монтировании компонента
onMounted(async () => {
  await loadTelegramStatus();
  
  // Если это Telegram Mini App, настраиваем интерфейс
  if (isTelegramMiniApp.value) {
    console.log('Running in Telegram Mini App mode');
    // Добавляем CSS класс для Telegram WebApp стилей
    document.body.classList.add('tg-viewport');
  }
});

// Загрузить статус связи с Telegram
const loadTelegramStatus = async () => {
  try {
    const response = await fetch('/telegram/status', {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    });
    
    if (response.ok) {
      telegramStatus.value = await response.json();
    }
  } catch (error) {
    console.error('Ошибка при загрузке статуса Telegram:', error);
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
      window.open(data.bot_url, '_blank');
      
      $q.notify({
        type: 'positive',
        message: 'Перейдите в Telegram и нажмите "Старт"',
        timeout: 5000
      });
      
      setTimeout(async () => {
        await loadTelegramStatus();
      }, 2000);
      
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

// Отвязать от Telegram
const unlinkTelegram = async () => {
  $q.dialog({
    title: 'Отвязать Telegram',
    message: 'Вы уверены, что хотите отвязать свой Telegram аккаунт?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    telegramLoading.value = true;
    
    try {
      const response = await fetch('/telegram/unlink', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });
      
      const data = await response.json();
      
      if (response.ok) {
        $q.notify({
          type: 'positive',
          message: 'Telegram успешно отвязан',
          timeout: 3000
        });
        
        await loadTelegramStatus();
      } else {
        $q.notify({
          type: 'negative',
          message: data.error || 'Ошибка при отвязке Telegram',
          timeout: 3000
        });
      }
      
    } catch (error) {
      console.error('Ошибка при отвязке Telegram:', error);
      $q.notify({
        type: 'negative',
        message: 'Ошибка при отвязке Telegram',
        timeout: 3000
      });
    } finally {
      telegramLoading.value = false;
    }
  });
};

// Функция для перехода к документу
const viewDocument = (documentId) => {
  router.visit(`/documents/${documentId}`);
};

// Функция для создания нового задания
const createNewTask = () => {
  router.visit('/new');
};

// Функция для получения цвета статуса
const getStatusColor = (document) => {
  const statusColors = {
    'draft': '#6b7280',
    'pre_generating': '#3b82f6',
    'pre_generated': '#10b981',
    'pre_generation_failed': '#ef4444',
    'full_generating': '#8b5cf6',
    'full_generated': '#059669',
    'full_generation_failed': '#ef4444',
    'in_review': '#f59e0b',
    'approved': '#16a34a',
    'rejected': '#ef4444'
  };
  return statusColors[document.status] || '#6b7280';
};

// Функция для получения русского названия статуса
const getStatusLabel = (document) => {
  if (document.status_label) {
    return document.status_label;
  }
  
  const statusLabels = {
    'draft': 'Черновик',
    'pre_generating': 'Генерируется структура и ссылки...',
    'pre_generated': 'Структура готова',
    'pre_generation_failed': 'Ошибка генерации структуры',
    'full_generating': 'Генерируется содержимое...',
    'full_generated': 'Полностью готов',
    'full_generation_failed': 'Ошибка полной генерации',
    'in_review': 'На проверке',
    'approved': 'Утвержден',
    'rejected': 'Отклонен'
  };
  return statusLabels[document.status] || document.status;
};

// Функция для форматирования даты
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ru-RU');
};

// Автоматическое округление суммы до кратного 100
const roundToHundred = (value) => {
  return Math.round(value / 100) * 100;
};

// Следить за изменением суммы и автоматически округлять
const onAmountChange = (event) => {
  const value = parseInt(event.target.value) || 0;
  topUpAmount.value = value;
};

// Округлить сумму при потере фокуса
const onAmountBlur = (event) => {
  let value = parseInt(event.target.value) || 0;
  if (value > 0) {
    value = roundToHundred(value);
    // Проверяем минимальную сумму
    if (value < 300) {
      value = 300;
    }
    topUpAmount.value = value;
  }
};

// Выбрать сумму и обновить преимущества
const selectAmount = (amount) => {
  topUpAmount.value = amount;
};

// Получить количество генераций в зависимости от суммы
const getGenerationsCount = (amount) => {
  return Math.floor(amount / 100);
};

// Функция для правильного склонения слова "генерация"
const getWordForm = (number) => {
  const lastDigit = number % 10;
  const lastTwoDigits = number % 100;
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
    return 'генераций';
  }
  if (lastDigit === 1) {
    return 'генерация';
  } else if (lastDigit >= 2 && lastDigit <= 4) {
    return 'генерации';
  } else {
    return 'генераций';
  }
};

// Функция для пополнения баланса - открыть модальное окно
const topUpBalance = async () => {
  await loadUserContactData();
  showTopUpModal.value = true;
};

// Загрузить данные пользователя для контактов
const loadUserContactData = async () => {
  try {
    // Получаем данные пользователя из глобального объекта или пропсов
    const user = props.auth?.user;
    if (user) {
      // Проверяем, является ли email автогенерированным
      const isAutoGenerated = user.email && (user.email.endsWith('@auto.user') || user.email.endsWith('@linked.user'));
      userEmail.value = isAutoGenerated ? '' : (user.email || '');
      privacyConsent.value = user.privacy_consent || false;
    }
  } catch (error) {
    console.error('Ошибка при загрузке данных пользователя:', error);
  }
};

// Сохранить контактные данные пользователя
const saveUserContact = async () => {
  if (!userEmail.value.trim()) {
    $q.notify({
      type: 'negative',
      message: 'Введите email адрес',
      position: 'top'
    });
    return false;
  }

  // Проверка формата email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(userEmail.value.trim())) {
    $q.notify({
      type: 'negative',
      message: 'Введите корректный email адрес',
      position: 'top'
    });
    return false;
  }

  // Проверка на автогенерированный email
  if (userEmail.value.trim().endsWith('@auto.user') || userEmail.value.trim().endsWith('@linked.user')) {
    $q.notify({
      type: 'negative',
      message: 'Введите действительный email адрес для получения чека',
      position: 'top'
    });
    return false;
  }

  if (!privacyConsent.value) {
    $q.notify({
      type: 'negative',
      message: 'Необходимо дать согласие на обработку персональных данных',
      position: 'top'
    });
    return false;
  }

  isSavingUserContact.value = true;

  try {
    const response = await fetch('/api/user/update-contact', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        email: userEmail.value.trim(),
        privacy_consent: privacyConsent.value
      })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || `HTTP Error: ${response.status}`);
    }

    if (data.success) {
      return true;
    } else {
      throw new Error(data.error || 'Ошибка при сохранении данных');
    }

  } catch (error) {
    console.error('Ошибка при сохранении контактных данных:', error);
    $q.notify({
      type: 'negative',
      message: error.message || 'Ошибка при сохранении контактных данных',
      position: 'top'
    });
    return false;
  } finally {
    isSavingUserContact.value = false;
  }
};

// Создать заказ на пополнение и перейти на оплату
const processTopUp = async () => {
  if (topUpAmount.value < 300) {
    $q.notify({
      type: 'negative',
      message: 'Минимальная сумма пополнения 300₽',
      position: 'top'
    });
    return;
  }

  isCreatingOrder.value = true;

  try {
    console.log('=== Начало процесса пополнения ===');
    console.log('Сумма:', topUpAmount.value);
    console.log('Email:', userEmail.value);
    console.log('Согласие:', privacyConsent.value);

    // Сначала сохраняем контактные данные
    console.log('Сохраняем контактные данные...');
    const contactSaved = await saveUserContact();
    if (!contactSaved) {
      console.log('Контактные данные не сохранены');
      return;
    }
    console.log('Контактные данные сохранены успешно');

    // Создаем заказ на пополнение
    console.log('Создаем заказ...');
    const orderHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };
    
    const orderResponse = await fetch('/orders/process', {
      method: 'POST',
      headers: orderHeaders,
      body: JSON.stringify({
        amount: topUpAmount.value,
        order_data: {
          description: `Пополнение баланса на ${topUpAmount.value}₽`,
          purpose: "balance_top_up"
        }
      })
    });

    console.log('Order response status:', orderResponse.status);
    console.log('Order response headers:', Object.fromEntries(orderResponse.headers.entries()));

    if (!orderResponse.ok) {
      const orderErrorText = await orderResponse.text();
      console.error('Order response error:', orderErrorText);
      throw new Error(`HTTP Error: ${orderResponse.status} - ${orderErrorText}`);
    }

    const orderData = await orderResponse.json();
    console.log('Order data:', orderData);
    
    if (!orderData.success || !orderData.order_id) {
      throw new Error(orderData.error || 'Ошибка создания заказа');
    }

    console.log('Заказ создан успешно, ID:', orderData.order_id);

    // Создаем платеж ЮКасса
    console.log('Создаем платеж ЮКасса...');
    const paymentHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };
    
    const paymentResponse = await fetch(`/api/payment/yookassa/create/${orderData.order_id}`, {
      method: 'POST',
      headers: paymentHeaders,
      credentials: 'include'
    });

    console.log('Payment response status:', paymentResponse.status);
    console.log('Payment response headers:', Object.fromEntries(paymentResponse.headers.entries()));

    if (!paymentResponse.ok) {
      const paymentErrorText = await paymentResponse.text();
      console.error('Payment response error:', paymentErrorText);
      throw new Error(`Payment API Error: ${paymentResponse.status} - ${paymentErrorText}`);
    }

    const paymentData = await paymentResponse.json();
    console.log('Payment data received:', paymentData);

    if (paymentData.success && paymentData.confirmation_url) {
      console.log('Платеж создан успешно, URL:', paymentData.confirmation_url);
      // Закрываем модальное окно
      showTopUpModal.value = false;
      
      // Переходим на страницу оплаты
      if (isTelegramWebApp()) {
        // В Telegram Web App открываем в браузере
        window.Telegram.WebApp.openLink(paymentData.confirmation_url);
      } else {
        // В обычном браузере переходим обычным способом
        window.location.href = paymentData.confirmation_url;
      }
    } else {
      console.error('Некорректный ответ платежного API:', paymentData);
      throw new Error(paymentData.error || 'Некорректный ответ от платежного API');
    }

  } catch (error) {
    console.error('=== Ошибка при пополнении баланса ===');
    console.error('Error object:', error);
    console.error('Error message:', error.message);
    console.error('Error stack:', error.stack);
    
    $q.notify({
      type: 'negative',
      message: `Ошибка при пополнении баланса: ${error.message}`,
      position: 'top'
    });
  } finally {
    isCreatingOrder.value = false;
  }
};

// Загрузить историю транзакций
const loadTransitions = async () => {
  isLoadingTransitions.value = true;
  
  try {
    const headers = {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };
    
    const response = await fetch('/api/user/transitions', {
      headers: headers,
      credentials: 'include'
    });

    if (response.ok) {
      const data = await response.json();
      transitions.value = data.transitions || [];
    } else {
      $q.notify({
        type: 'negative',
        message: 'Ошибка при загрузке истории операций',
        position: 'top'
      });
    }
  } catch (error) {
    console.error('Ошибка при загрузке транзакций:', error);
    $q.notify({
      type: 'negative',
      message: 'Ошибка при загрузке истории операций',
      position: 'top'
    });
  } finally {
    isLoadingTransitions.value = false;
  }
};

// Открыть модальное окно истории транзакций
const openTransitionsHistory = async () => {
  showTransitionsModal.value = true;
  await loadTransitions();
};

// Тестовое уменьшение баланса (только для development)
const testDecrementBalance = async () => {
  if (decrementAmount.value < 1) {
    $q.notify({
      type: 'negative',
      message: 'Минимальная сумма для списания 1₽',
      position: 'top'
    });
    return;
  }

  isDecrementingBalance.value = true;

  try {
    const response = await fetch('/api/user/test-decrement-balance', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        amount: decrementAmount.value
      })
    });

    const data = await response.json();

    if (data.success) {
      $q.notify({
        type: 'positive',
        message: `Баланс успешно уменьшен на ${decrementAmount.value}₽`,
        position: 'top',
        timeout: 3000
      });

      // Закрываем модальное окно
      showDecrementModal.value = false;
      
      // Перезагружаем страницу для обновления баланса
      window.location.reload();
      
    } else {
      $q.notify({
        type: 'negative',
        message: data.error || 'Ошибка при уменьшении баланса',
        position: 'top'
      });
    }

  } catch (error) {
    console.error('Ошибка при уменьшении баланса:', error);
    $q.notify({
      type: 'negative',
      message: 'Ошибка при уменьшении баланса',
      position: 'top'
    });
  } finally {
    isDecrementingBalance.value = false;
  }
};

// Форматировать сумму операции
const formatTransitionAmount = (transition) => {
  // Приводим difference к числу и берем абсолютное значение
  const difference = parseFloat(transition.difference) || 0;
  const amount = Math.abs(difference);
  const sign = transition.is_credit ? '+' : '-';
  return `${sign}${amount.toLocaleString('ru-RU')} ₽`;
};

// Получить цвет для суммы операции
const getTransitionAmountColor = (transition) => {
  return transition.is_credit ? '#16a34a' : '#ef4444'; // Зеленый для пополнения, красный для списания
};

// Форматировать дату транзакции
const formatTransitionDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Открыть Telegram бот
const openTelegramBot = () => {
  // URL бота (замените на ваш реальный URL)
  const botUrl = 'https://t.me/gptpult_bot';
  window.open(botUrl, '_blank');
};

// Computed для отсортированных документов
const sortedDocuments = computed(() => {
  return [...props.documents].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

// Очистка интервалов при размонтировании компонента
onUnmounted(() => {
  // Удаляем CSS класс при размонтировании
  if (isTelegramMiniApp.value) {
    document.body.classList.remove('tg-viewport');
  }
});

// Проверить, нужно ли показывать кнопку авторизации через Telegram
const shouldShowTelegramAuth = computed(() => {
  const user = props.auth?.user;
  if (!user) return false;
  
  // Показываем если нет связанного Telegram И email автогенерированный
  return !telegramStatus.value.is_linked && 
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

    if (!response.ok) {
      throw new Error(data.error || 'Ошибка при создании ссылки');
    }

    // Открываем Telegram бот
    window.open(data.bot_url, '_blank');
    
    $q.notify({
      type: 'positive',
      message: 'Перейдите в Telegram и нажмите "Старт"',
      position: 'top'
    });
    
    // Обновляем статус связи каждые 3 секунды в течение 2 минут
    let attempts = 0;
    const maxAttempts = 40; // 2 минуты
    
    const checkInterval = setInterval(async () => {
      attempts++;
      await loadTelegramStatus();
      
      if (telegramStatus.value.is_linked || attempts >= maxAttempts) {
        clearInterval(checkInterval);
        telegramLoading.value = false;
        
        if (telegramStatus.value.is_linked) {
          $q.notify({
            type: 'positive',
            message: 'Аккаунт успешно связан с Telegram!',
            position: 'top'
          });
        } else {
          $q.notify({
            type: 'warning',
            message: 'Время ожидания истекло. Попробуйте еще раз.',
            position: 'top'
          });
        }
      }
    }, 3000);
    
  } catch (error) {
    console.error('Ошибка при создании ссылки для авторизации:', error);
    $q.notify({
      type: 'negative',
      message: error.message || 'Ошибка при создании ссылки для авторизации',
      position: 'top'
    });
    telegramLoading.value = false;
  }
};
</script>

<template>
    <Head title="Личный кабинет" />
    <YandexMetrika />

    <page-layout :auto-auth="true">
        <div class="lk-container">
            <!-- Заголовок -->
            <div class="header-section">
                <h1 class="page-title">Личный кабинет</h1>
            </div>

            <!-- Основной контент -->
            <div class="content-grid">
                <!-- Левая колонка -->
                <div class="left-column">
                    <!-- Кнопка создания нового документа -->
                    <div class="new-document-section">
                        <q-btn
                            class="new-document-btn"
                            color="primary"
                            size="lg"
                            @click="createNewTask"
                            unelevated
                            no-caps
                        >
                            <q-icon name="add" class="btn-icon" />
                            Создать работу
                        </q-btn>
                    </div>

                    <!-- Карточка баланса -->
                    <div class="balance-card">
                        <div class="card-header">
                            <div class="card-title">
                                <q-icon name="account_balance_wallet" class="card-icon" />
                                Баланс
                            </div>
                        </div>
                        <div class="balance-content">
                            <div class="balance-info">
                                <div class="balance-amount">
                                    {{ Math.floor(balance || 0).toLocaleString('ru-RU') }} ₽
                                </div>
                                <div class="generations-info">
                                    {{ remainingGenerations }} {{ remainingGenerations === 1 ? 'генерация' : remainingGenerations >= 2 && remainingGenerations <= 4 ? 'генерации' : 'генераций' }}
                                </div>
                            </div>
                            <div class="balance-buttons">
                                <q-btn
                                    color="primary"
                                    label="Пополнить"
                                    size="md"
                                    @click="topUpBalance"
                                    class="balance-btn"
                                    unelevated
                                    no-caps
                                />
                                <q-btn
                                    v-if="isDevelopment"
                                    color="negative"
                                    label="Списать"
                                    size="md"
                                    @click="showDecrementModal = true"
                                    class="balance-btn"
                                    outline
                                    no-caps
                                />
                                <q-btn
                                    color="grey-7"
                                    label="История операций"
                                    size="sm"
                                    @click="openTransitionsHistory"
                                    class="history-btn"
                                    flat
                                    no-caps
                                >
                                    <q-icon name="history" class="q-mr-xs" />
                                </q-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Блок Telegram -->
                    <div v-if="telegramStatus.is_linked" class="telegram-connected-block">
                        <!-- Кнопка перехода в Telegram -->
                        <q-btn
                            @click="openTelegramBot"
                            class="telegram-go-btn"
                            unelevated
                            no-caps
                        >
                            <q-icon name="fab fa-telegram" class="telegram-go-icon" />
                            <span>Перейти в Телеграм</span>
                        </q-btn>
                        
                        <!-- Информация и кнопка отвязки -->
                        <div class="telegram-info-row">
                            <div class="telegram-status-info">
                                <span class="telegram-username">@{{ telegramStatus.telegram_username || 'Связан' }}</span>
                            </div>
                            <q-btn
                                v-if="isDevelopment"
                                color="negative"
                                label="Отвязать"
                                size="sm"
                                @click="unlinkTelegram"
                                :loading="telegramLoading"
                                class="telegram-disconnect-btn"
                                flat
                                no-caps
                            />
                        </div>
                    </div>
                    
                    <!-- Показываем кнопку авторизации для аккаунтов без реального email -->
                    <q-btn
                        v-else-if="shouldShowTelegramAuth"
                        @click="authTelegram"
                        :loading="telegramLoading"
                        class="telegram-auth-btn"
                        unelevated
                        no-caps
                    >
                        <q-icon name="fab fa-telegram" class="telegram-btn-icon" />
                        <span>Авторизоваться через Telegram</span>
                    </q-btn>
                    
                    <!-- Обычная кнопка связки для аккаунтов с реальным email -->
                    <q-btn
                        v-else
                        @click="linkTelegram"
                        :loading="telegramLoading"
                        class="telegram-connect-simple"
                        unelevated
                        no-caps
                    >
                        <q-icon name="fab fa-telegram" class="telegram-btn-icon" />
                        <span>Подключить Telegram</span>
                    </q-btn>
                </div>

                <!-- Правая колонка -->
                <div class="right-column">
                    <!-- Документы -->
                    <div class="documents-card">
                        <div class="card-header">
                            <div class="card-title">
                                <q-icon name="description" class="card-icon" />
                                Мои работы
                            </div>
                        </div>

                        <div v-if="sortedDocuments.length === 0" class="empty-state">
                            <q-icon name="description" class="empty-icon" />
                            <div class="empty-title">Нет работ</div>
                            <div class="empty-subtitle">Создай свою первую работу</div>
                        </div>

                        <div v-else class="documents-list">
                            <div 
                                v-for="document in sortedDocuments" 
                                :key="document.id"
                                @click="viewDocument(document.id)"
                                class="document-item"
                            >
                                <div class="document-icon">
                                    <q-icon name="description" />
                                </div>
                                <div class="document-content">
                                    <div class="document-title">{{ document.title }}</div>
                                    <div class="document-meta">
                                        <div class="document-date">
                                            <q-icon name="schedule" class="meta-icon" />
                                            {{ formatDate(document.created_at) }}
                                        </div>
                                        <div class="document-status" :style="{ color: getStatusColor(document) }">
                                            {{ getStatusLabel(document) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="document-arrow">
                                    <q-icon name="chevron_right" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно ввода суммы пополнения -->
        <q-dialog v-model="showTopUpModal">
            <q-card class="top-up-modal">
                <q-card-section class="modal-header">
                    <div class="modal-title">
                        <q-icon name="account_balance_wallet" class="modal-icon" />
                        Пополнение баланса
                    </div>
                </q-card-section>

                <q-card-section class="modal-content">
                    <div class="amount-input-section">
                        <div class="custom-input-wrapper">
                            <label class="custom-input-label">
                                Сумма пополнения
                                <q-icon 
                                    name="info_outline" 
                                    class="info-tooltip-icon"
                                    size="18px"
                                >
                                    <q-tooltip 
                                        class="bg-dark text-white"
                                        :offset="[10, 10]"
                                        anchor="top middle"
                                        self="bottom middle"
                                    >
                                        Минимум 300₽. Сумма округляется до кратной 100₽ при завершении ввода
                                    </q-tooltip>
                                </q-icon>
                            </label>
                            <div class="custom-input-container">
                                <input
                                    v-model.number="topUpAmount"
                                    type="number"
                                    min="300"
                                    step="100"
                                    class="custom-input"
                                    placeholder="Введите сумму"
                                    @input="onAmountChange"
                                    @blur="onAmountBlur"
                                />
                                <span class="custom-input-suffix">₽</span>
                            </div>
                        </div>

                        <!-- Быстрые кнопки выбора суммы -->
                        <div class="quick-amounts">
                            <div class="quick-amounts-buttons">
                                <button 
                                    v-for="amount in [300, 500, 1000, 1500]" 
                                    :key="amount"
                                    @click="selectAmount(amount)"
                                    :class="['quick-amount-btn', { 'active': topUpAmount === amount }]"
                                >
                                    {{ amount }}₽
                                </button>
                            </div>
                        </div>

                        <!-- Поле email -->
                        <div class="email-input-section">
                            <div class="custom-input-wrapper">
                                <label class="custom-input-label">Email для чека</label>
                                <div class="custom-input-container">
                                    <input
                                        v-model="userEmail"
                                        type="email"
                                        class="custom-input"
                                        placeholder="example@mail.ru"
                                        pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}"
                                        title="Введите корректный email адрес"
                                        required
                                    />
                                    <q-icon name="email" class="input-icon" />
                                </div>
                            </div>
                        </div>

                        <!-- Чекбокс согласия на обработку персональных данных -->
                        <div class="privacy-consent-section">
                            <label class="privacy-consent-wrapper">
                                <input
                                    v-model="privacyConsent"
                                    type="checkbox"
                                    class="privacy-checkbox"
                                    required
                                />
                                <span class="privacy-consent-text">
                                    Я согласен на обработку персональных данных
                                </span>
                            </label>
                        </div>

                        <!-- Кнопка оплаты (перенесена выше преимуществ) -->
                        <div class="payment-action">
                            <q-btn 
                                color="primary" 
                                label="Оплатить" 
                                @click="processTopUp"
                                :loading="isCreatingOrder || isSavingUserContact"
                                :disable="topUpAmount < 300 || !isEmailValid || !privacyConsent"
                                unelevated
                                no-caps
                                class="payment-btn"
                            />
                        </div>
                        
                        <!-- Информация о территориальных ограничениях -->
                        <div class="payment-restriction-notice">
                            <q-icon name="info" class="restriction-icon" />
                            <span>Оплата доступна с IP-адресов Российской Федерации</span>
                        </div>

                        <!-- Преимущества абонемента -->
                        <div v-if="topUpAmount >= 300" class="subscription-benefits">
                            <div class="benefits-title">Что входит в абонемент:</div>
                            <div class="benefit-item">
                                <q-icon name="check_circle" class="benefit-icon" />
                                <span>{{ getGenerationsCount(topUpAmount) }} {{ getWordForm(getGenerationsCount(topUpAmount)) }} документов</span>
                            </div>
                            <div class="benefit-item">
                                <q-icon name="check_circle" class="benefit-icon" />
                                <span>Полное содержание с деталями</span>
                            </div>
                            <div class="benefit-item">
                                <q-icon name="check_circle" class="benefit-icon" />
                                <span>Скачивание в формате Word</span>
                            </div>
                        </div>
                    </div>
                </q-card-section>

                <q-card-actions class="modal-actions">
                    <q-btn 
                        flat 
                        label="Отмена" 
                        @click="showTopUpModal = false"
                        :disable="isCreatingOrder"
                        no-caps
                        class="action-btn cancel-btn"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>

        <!-- Модальное окно истории транзакций -->
        <q-dialog v-model="showTransitionsModal" @hide="showTransitionsModal = false">
            <q-card class="transitions-modal">
                <q-card-section class="modal-header">
                    <div class="modal-title">
                        <q-icon name="history" class="modal-icon" />
                        История операций
                    </div>
                    <q-btn 
                        flat 
                        round 
                        icon="close" 
                        @click="showTransitionsModal = false"
                        class="close-btn"
                    />
                </q-card-section>

                <q-card-section class="transitions-content">
                    <div v-if="isLoadingTransitions" class="loading-section">
                        <q-spinner-dots size="40px" color="primary" />
                        <div class="loading-text">Загружаем историю операций...</div>
                    </div>

                    <div v-else-if="transitions.length === 0" class="empty-transitions">
                        <q-icon name="receipt_long" class="empty-icon" />
                        <div class="empty-title">Операций не найдено</div>
                        <div class="empty-subtitle">Пополните баланс или оплатите документы, чтобы увидеть историю</div>
                    </div>

                    <div v-else class="transitions-list">
                        <div 
                            v-for="transition in transitions" 
                            :key="transition.id"
                            class="transition-item"
                        >
                            <div class="transition-icon">
                                <q-icon 
                                    :name="transition.is_credit ? 'add_circle' : 'remove_circle'"
                                    :color="transition.is_credit ? 'positive' : 'negative'"
                                />
                            </div>
                            <div class="transition-content">
                                <div class="transition-description">{{ transition.description }}</div>
                                <div class="transition-date">{{ formatTransitionDate(transition.created_at) }}</div>
                                <div class="transition-balance">
                                    Баланс: {{ parseFloat(transition.amount_before || 0).toLocaleString('ru-RU') }} ₽ 
                                    → {{ parseFloat(transition.amount_after || 0).toLocaleString('ru-RU') }} ₽
                                </div>
                            </div>
                            <div class="transition-amount" :style="{ color: getTransitionAmountColor(transition) }">
                                {{ formatTransitionAmount(transition) }}
                            </div>
                        </div>
                    </div>
                </q-card-section>
            </q-card>
        </q-dialog>

        <!-- Модальное окно тестового списания баланса (только для development) -->
        <q-dialog v-model="showDecrementModal" v-if="isDevelopment">
            <q-card class="top-up-modal">
                <q-card-section class="modal-header">
                    <div class="modal-title">
                        <q-icon name="remove_circle" class="modal-icon" />
                        Тестовое списание баланса
                    </div>
                </q-card-section>

                <q-card-section class="modal-content">
                    <div class="amount-input-section">
                        <div class="custom-input-wrapper">
                            <label class="custom-input-label">Сумма для списания</label>
                            <div class="custom-input-container">
                                <input
                                    v-model.number="decrementAmount"
                                    type="number"
                                    min="1"
                                    step="10"
                                    class="custom-input"
                                    placeholder="Введите сумму"
                                />
                                <span class="custom-input-suffix">₽</span>
                            </div>
                        </div>
                        
                        <div class="amount-info">
                            <q-icon name="warning" class="info-icon" />
                            <span>Эта функция доступна только в режиме разработки</span>
                        </div>

                        <!-- Быстрые кнопки выбора суммы -->
                        <div class="quick-amounts">
                            <div class="quick-amounts-label">Быстрый выбор:</div>
                            <div class="quick-amounts-buttons">
                                <button 
                                    v-for="amount in [50, 100, 200, 500]" 
                                    :key="amount"
                                    @click="decrementAmount = amount"
                                    :class="['quick-amount-btn', { 'active': decrementAmount === amount }]"
                                >
                                    {{ amount }}₽
                                </button>
                            </div>
                        </div>
                    </div>
                </q-card-section>

                <q-card-actions class="modal-actions">
                    <q-btn 
                        color="negative" 
                        label="Списать" 
                        @click="testDecrementBalance"
                        :loading="isDecrementingBalance"
                        :disable="decrementAmount < 1"
                        unelevated
                        no-caps
                        class="action-btn primary-btn"
                    />
                    <q-btn 
                        flat 
                        label="Отмена" 
                        @click="showDecrementModal = false"
                        :disable="isDecrementingBalance"
                        no-caps
                        class="action-btn cancel-btn"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>

    </page-layout>
</template> 

<style scoped>
.lk-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 40px 32px;
    min-height: 100vh;
}

/* Заголовок */
.header-section {
    margin-bottom: 48px;
    text-align: center;
}

.page-title {
    font-size: 52px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 16px 0;
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.page-subtitle {
    font-size: 20px;
    color: #6b7280;
    margin: 0;
    font-weight: 400;
}

/* Сетка контента */
.content-grid {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 48px;
    align-items: start;
}

/* Колонки */
.left-column {
    display: flex;
    flex-direction: column;
    gap: 28px;
    position: sticky;
    top: 120px;
}

.right-column {
    display: flex;
    flex-direction: column;
    gap: 28px;
}

/* Базовые стили карточек */
.balance-card,
.documents-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.balance-card:hover,
.documents-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

/* Заголовки карточек */
.card-header {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
}

.card-icon {
    font-size: 24px;
    color: #3b82f6;
}

/* Информация о подключенном Telegram */
.telegram-connected-block {
    display: flex;
    flex-direction: column;
    gap: 16px;
    padding: 20px;
    background: #f0f9ff;
    border-radius: 12px;
    border: 1px solid #bae6fd;
}

.telegram-go-btn {
    width: 100%;
    padding: 14px 20px;
    border-radius: 10px;
    background: #0088cc;
    color: white;
    font-size: 15px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    min-height: 48px;
}

.telegram-go-btn:hover {
    background: #006699;
    box-shadow: 0 6px 16px rgba(0, 136, 204, 0.4);
    transform: translateY(-1px);
}

.telegram-go-icon {
    font-size: 18px;
    flex-shrink: 0;
    margin-right: 8px;
}

.telegram-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 0 4px;
}

.telegram-status-info {
    flex: 1;
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}

.telegram-username {
    font-weight: 600;
    color: #0f172a;
    font-size: 14px;
}

.telegram-disconnect-btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 8px 14px;
    color: #ef4444;
    transition: all 0.2s ease;
    font-size: 12px;
    min-height: 32px;
}

.telegram-disconnect-btn:hover {
    background: #fef2f2;
}

.telegram-connect-simple {
    width: 100%;
    padding: 16px 24px;
    border-radius: 12px;
    background: #0088cc;
    color: white;
    font-size: 16px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    min-height: 52px;
}

.telegram-connect-simple:hover {
    background: #006699;
    box-shadow: 0 6px 16px rgba(0, 136, 204, 0.4);
    transform: translateY(-1px);
}

.telegram-btn-icon {
    font-size: 20px;
    flex-shrink: 0;
    margin-right: 10px;
}

/* Пустое состояние */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    color: #d1d5db;
    margin-bottom: 16px;
}

.empty-title {
    font-size: 20px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.empty-subtitle {
    font-size: 16px;
    color: #6b7280;
}

/* Список документов */
.documents-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.document-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 24px;
    background: #f8fafc;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    min-height: 96px;
}

.document-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.document-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    background: #ffffff;
    border-radius: 12px;
    color: #6b7280;
    font-size: 24px;
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
    margin-top: 2px;
}

.document-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.document-title {
    font-size: 17px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.4;
    max-width: 100%;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    hyphens: auto;
}

.document-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.document-date {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: #6b7280;
}

.meta-icon {
    font-size: 14px;
}

.document-status {
    font-size: 14px;
    font-weight: 500;
    line-height: 1.2;
}

.document-arrow {
    color: #9ca3af;
    font-size: 20px;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.document-item:hover .document-arrow {
    color: #3b82f6;
    transform: translateX(4px);
}

/* Адаптивность */
@media (max-width: 1400px) {
    .lk-container {
        max-width: 1200px;
        padding: 32px 20px;
    }
    
    .content-grid {
        grid-template-columns: 380px 1fr;
        gap: 36px;
    }
}

@media (max-width: 1200px) {
    .lk-container {
        max-width: 1000px;
        padding: 28px 18px;
    }
    
    .content-grid {
        grid-template-columns: 350px 1fr;
        gap: 32px;
    }
    
    .page-title {
        font-size: 42px;
    }
    
    .left-column {
        top: 80px;
    }
}

@media (max-width: 1024px) {
    .lk-container {
        padding: 24px 16px;
    }
    
    .content-grid {
        grid-template-columns: 320px 1fr;
        gap: 28px;
    }
    
    .page-title {
        font-size: 38px;
    }
    
    .left-column {
        top: 70px;
    }
    
    .new-document-btn {
        font-size: 16px;
        padding: 18px 28px;
    }
}

@media (max-width: 768px) {
    .lk-container {
        padding: 16px 8px 100px 8px;
        max-width: 100%;
    }
    
    .header-section {
        margin-bottom: 24px;
    }
    
    .page-title {
        font-size: 32px;
    }
    
    .page-subtitle {
        font-size: 16px;
    }
    
    /* Мобильная сетка - вертикальная раскладка */
    .content-grid {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .left-column {
        display: contents; /* Делаем детей прямыми детьми content-grid */
    }
    
    .right-column {
        order: 2; /* Блок "Мои работы" после баланса, перед telegram */
        width: 100%;
    }
    
    /* Улучшения для блока документов на мобильных */
    .documents-card {
        width: 100%;
        margin: 0;
        padding: 20px;
        border-radius: 16px;
    }
    
    /* Фиксированная кнопка в мобильной версии */
    .new-document-section {
        position: fixed;
        bottom: 32px;
        left: 8px;
        right: 8px;
        z-index: 1000;
        width: auto;
        order: 0; /* Не влияет из-за fixed */
    }
    
    .new-document-btn {
        width: 100%;
        padding: 16px 24px;
        font-size: 16px;
        box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
        border-radius: 16px;
    }
    
    /* Убираем дополнительный отступ снизу */
    .lk-container {
        padding-bottom: 40px;
    }
    
    .balance-card {
        order: 1;
        width: calc(50% - 8px);
    }
    
    .telegram-connected-block,
    .telegram-connect-simple,
    .telegram-auth-btn {
        order: 3; /* Telegram после "Мои работы" */
        width: calc(50% - 8px);
    }
    
    .telegram-connected-block {
        padding: 16px;
        gap: 12px;
    }
    
    .telegram-go-btn {
        padding: 12px 16px;
        font-size: 14px;
        min-height: 44px;
    }
    
    .telegram-connect-simple {
        padding: 14px 20px;
        font-size: 15px;
        min-height: 48px;
    }
    
    .new-document-btn {
        width: 100%;
        padding: 16px 24px;
        font-size: 16px;
    }
    
    .balance-card,
    .documents-card {
        padding: 20px;
        border-radius: 16px;
    }
    
    .balance-content {
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }
    
    .balance-info {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }
    
    .balance-amount {
        font-size: 28px;
        text-align: left;
    }
    
    .generations-info {
        text-align: right;
        font-size: 14px;
    }
    
    .balance-btn {
        width: 100%;
    }
    
    .telegram-connected-block {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .document-item {
        padding: 20px;
        gap: 16px;
        min-height: 84px;
    }
    
    .document-title {
        font-size: 15px;
    }
    
    .document-icon {
        width: 44px;
        height: 44px;
        font-size: 20px;
    }
    
    .document-meta {
        gap: 10px;
    }
}

@media (max-width: 640px) {
    .lk-container {
        padding: 12px 4px 24px 4px;
        max-width: 100%;
    }
    
    .content-grid {
        gap: 16px;
    }
    
    /* Поскольку left-column display: contents, дети - siblings */
    .balance-card,
    .telegram-connected-block,
    .telegram-connect-simple,
    .telegram-auth-btn {
        width: 100%;
        order: 3; /* Все блоки Telegram после "Мои работы" */
    }
    
    .balance-card,
    .documents-card {
        padding: 18px;
        border-radius: 14px;
        width: 100%;
        margin: 0;
    }
    
    .new-document-btn {
        padding: 14px 20px;
        font-size: 15px;
    }
    
    .telegram-go-btn {
        font-size: 13px;
        padding: 10px 14px;
    }
    
    .telegram-connect-simple {
        font-size: 13px;
        padding: 10px 14px;
    }
    
    .document-item {
        padding: 16px;
        gap: 12px;
        min-height: 80px;
        width: 100%;
    }
    
    .document-title {
        font-size: 15px;
    }
    
    .document-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }
    
    .document-meta {
        gap: 8px;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .document-date {
        font-size: 12px;
    }
    
    .document-status {
        font-size: 12px;
    }
    
    .new-document-section {
        margin-bottom: 16px;
    }
}

@media (max-width: 480px) {
    .lk-container {
        padding: 8px 2px 20px 2px;
        max-width: 100%;
    }
    
    .page-title {
        font-size: 24px;
    }
    
    .page-subtitle {
        font-size: 14px;
    }
    
    .balance-card,
    .documents-card {
        padding: 16px;
        border-radius: 12px;
        width: 100%;
        margin: 0;
    }
    
    /* Устанавливаем order для всех блоков Telegram */
    .telegram-connected-block,
    .telegram-connect-simple,
    .telegram-auth-btn {
        order: 3 !important;
        width: 100% !important;
    }
    
    .card-title {
        font-size: 16px;
    }
    
    .balance-amount {
        font-size: 24px;
        text-align: center;
    }
    
    .new-document-btn {
        padding: 12px 18px;
        font-size: 14px;
    }
    
    .telegram-go-btn {
        font-size: 13px;
        padding: 10px 14px;
    }
    
    .telegram-connect-simple {
        font-size: 13px;
        padding: 10px 14px;
    }
    
    .document-item {
        padding: 14px;
        gap: 12px;
        min-height: 76px;
        width: 100%;
    }
    
    .document-title {
        font-size: 14px;
    }
    
    .document-icon {
        width: 36px;
        height: 36px;
        font-size: 18px;
    }
    
    .document-meta {
        gap: 6px;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .document-date {
        font-size: 11px;
    }
    
    .document-status {
        font-size: 11px;
    }
    
    .new-document-section {
        margin-bottom: 12px;
    }
}

@media (max-width: 360px) {
    .lk-container {
        padding: 6px 1px 16px 1px;
        max-width: 100%;
    }
    
    .page-title {
        font-size: 22px;
    }
    
    .header-section {
        margin-bottom: 16px;
    }
    
    .balance-card,
    .documents-card {
        padding: 14px;
        border-radius: 10px;
        width: 100%;
        margin: 0;
    }
    
    /* Устанавливаем order для всех блоков Telegram */
    .telegram-connected-block,
    .telegram-connect-simple,
    .telegram-auth-btn {
        order: 3 !important;
        width: 100% !important;
    }
    
    .new-document-btn {
        padding: 10px 16px;
        font-size: 13px;
    }
    
    .balance-amount {
        font-size: 22px;
        text-align: center;
    }
    
    .card-title {
        font-size: 15px;
    }
    
    .document-item {
        padding: 12px;
        gap: 10px;
        min-height: 72px;
        width: 100%;
    }
    
    .document-title {
        font-size: 13px;
    }
    
    .document-icon {
        width: 32px;
        height: 32px;
        font-size: 16px;
    }
    
    .document-meta {
        gap: 4px;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .document-date {
        font-size: 11px;
    }
    
    .document-status {
        font-size: 11px;
    }
}

/* Стили для модальных окон */
.top-up-modal {
    min-width: 520px;
    max-width: 580px;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    background: #ffffff;
    overflow: hidden;
    max-height: 90vh;
}

.modal-header {
    padding: 32px 32px 20px 32px;
    border-bottom: 2px solid #f1f5f9;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.modal-icon {
    font-size: 28px;
    color: #3b82f6;
    padding: 8px;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 12px;
}

.modal-content {
    padding: 32px;
    background: #ffffff;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-actions {
    padding: 20px 32px 32px 32px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
    border-top: 1px solid #f1f5f9;
}

/* Стили для новой кнопки оплаты */
.payment-action {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 4px 0 8px 0;
}

.payment-btn {
    width: 100%;
    padding: 16px 32px;
    min-height: 56px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    color: white;
    border: none;
    font-size: 18px;
    font-weight: 700;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.payment-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

.payment-btn:disabled {
    opacity: 0.6;
    transform: none;
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.2);
}

.action-btn {
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.2s ease;
}

.primary-btn {
    width: 100%;
    padding: 16px 32px;
    min-height: 56px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    color: white;
    border: none;
    font-size: 18px;
    font-weight: 700;
    order: 1;
}

.primary-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

.cancel-btn {
    color: #6b7280;
    background: transparent;
    border: none;
    padding: 12px 24px;
    min-height: 44px;
    font-size: 15px;
    font-weight: 500;
    order: 2;
}

.cancel-btn:hover {
    color: #374151;
    background: rgba(107, 114, 128, 0.05);
    border-radius: 8px;
}

/* Стили для кастомного поля ввода */
.custom-input-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.custom-input-label {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-tooltip-icon {
    color: #6b7280;
    cursor: help;
    transition: color 0.2s ease;
}

.info-tooltip-icon:hover {
    color: #3b82f6;
}

.custom-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.custom-input {
    width: 100%;
    padding: 16px 50px 16px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    background: #ffffff;
    transition: all 0.2s ease;
    outline: none;
}

.custom-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.custom-input::placeholder {
    color: #9ca3af;
    font-weight: 400;
}

.custom-input-suffix {
    position: absolute;
    right: 20px;
    font-size: 18px;
    font-weight: 600;
    color: #6b7280;
    pointer-events: none;
}

/* Стили для быстрых кнопок выбора суммы */
.quick-amounts {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.quick-amounts-label {
    font-size: 15px;
    font-weight: 600;
    color: #374151;
}

.quick-amounts-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.quick-amount-btn {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    color: #64748b;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none;
    min-width: 0;
}

.quick-amount-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.05);
    transform: translateY(-1px);
}

.quick-amount-btn.active {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.quick-amount-btn.active:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

/* Стили для модального окна истории транзакций */
.transitions-modal {
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    min-width: 800px;
    max-width: 900px;
    width: 90vw;
    max-height: 80vh;
}

.transitions-content {
    padding: 0;
    max-height: 60vh;
    overflow-y: auto;
}

.loading-section {
    text-align: center;
    padding: 80px 40px;
}

.loading-text {
    margin-top: 20px;
    color: #64748b;
    font-size: 18px;
    font-weight: 500;
}

.empty-transitions {
    text-align: center;
    padding: 100px 40px;
}

.empty-transitions .empty-icon {
    font-size: 72px;
    color: #cbd5e1;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 50%;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
}

.empty-transitions .empty-title {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 12px;
}

.empty-transitions .empty-subtitle {
    font-size: 16px;
    color: #64748b;
    line-height: 1.5;
    max-width: 400px;
    margin: 0 auto;
}

.transitions-list {
    padding: 24px 0;
}

.transition-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 32px;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
    position: relative;
}

.transition-item:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transform: translateX(4px);
}

.transition-item:last-child {
    border-bottom: none;
}

.transition-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border: 1px solid #e2e8f0;
}

.transition-icon .q-icon {
    font-size: 24px;
}

.transition-content {
    flex: 1;
    min-width: 0;
}

.transition-description {
    font-size: 17px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
    word-break: break-word;
    line-height: 1.4;
}

.transition-date {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 4px;
    font-weight: 500;
}

.transition-balance {
    font-size: 13px;
    color: #94a3b8;
    font-family: 'SF Mono', 'Monaco', 'Cascadia Code', 'Roboto Mono', monospace;
    background: rgba(148, 163, 184, 0.1);
    padding: 4px 8px;
    border-radius: 8px;
    display: inline-block;
}

.transition-amount {
    font-size: 20px;
    font-weight: 700;
    text-align: right;
    padding: 8px 16px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid;
    white-space: nowrap;
    flex-shrink: 0;
}

.transition-amount[style*="color: #16a34a"] {
    border-color: rgba(22, 163, 74, 0.2);
    background: rgba(22, 163, 74, 0.05);
}

.transition-amount[style*="color: #ef4444"] {
    border-color: rgba(239, 68, 68, 0.2);
    background: rgba(239, 68, 68, 0.05);
}

/* Адаптивность для модальных окон */
@media (max-width: 600px) {
    .top-up-modal {
        min-width: 90vw;
        max-width: 90vw;
        margin: 16px;
        border-radius: 20px;
        max-height: 90vh;
    }
    
    .modal-header {
        padding: 24px 20px 16px 20px;
        position: sticky;
        top: 0;
        z-index: 10;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    }
    
    .modal-content {
        max-height: 65vh;
        overflow-y: auto;
        padding: 24px 20px;
    }
    
    .modal-actions {
        padding: 16px 20px 24px 20px;
        flex-direction: column;
        gap: 12px;
        position: sticky;
        bottom: 0;
        z-index: 10;
        background: #ffffff;
        border-top: 1px solid #f1f5f9;
    }
    
    .payment-btn {
        min-height: 52px;
        font-size: 16px;
        padding: 14px 24px;
    }
    
    .transitions-modal {
        min-width: 95vw;
        max-width: 95vw;
        width: 95vw;
        max-height: 90vh;
        margin: 8px;
        border-radius: 20px;
    }
    
    .action-btn {
        width: 100%;
        min-width: auto;
    }
    
    .primary-btn {
        min-height: 52px;
        font-size: 16px;
        order: 1;
    }
    
    .cancel-btn {
        min-height: 40px;
        font-size: 14px;
        order: 2;
    }
    
    .modal-title {
        font-size: 20px;
    }
    
    .modal-icon {
        font-size: 24px;
    }
    
    .amount-input-section {
        gap: 20px;
    }
    
    .custom-input {
        font-size: 18px;
        padding: 14px 45px 14px 16px;
    }
    
    .custom-input-suffix {
        right: 16px;
        font-size: 16px;
    }
    
    .quick-amounts-buttons {
        gap: 8px;
    }
    
    .quick-amount-btn {
        flex: 1;
        min-width: 0;
        font-size: 14px;
        padding: 10px 16px;
    }

    /* Адаптивные стили для чекбокса на мобильных */
    .privacy-consent-section {
        margin-bottom: 2px;
        padding: 6px 8px;
    }
    
    .privacy-consent-wrapper {
        gap: 8px;
    }
    
    .privacy-checkbox {
        width: 16px;
        height: 16px;
    }
    
    .privacy-consent-text {
        font-size: 13px;
        line-height: 1.3;
    }

    /* Уменьшаем отступы других элементов на мобильных */
    .email-input-section {
        margin-bottom: 8px;
    }

    .payment-action {
        margin: 2px 0 4px 0;
    }

    .transition-item {
        padding: 16px 20px;
        gap: 16px;
    }

    .transition-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
    }

    .transition-icon .q-icon {
        font-size: 20px;
    }

    .transition-description {
        font-size: 15px;
    }

    .transition-amount {
        font-size: 16px;
        padding: 6px 12px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .transitions-modal .modal-header {
        padding: 24px 20px 16px 20px;
    }
    
    .transitions-modal .modal-title {
        font-size: 20px;
    }
    
    .transitions-content {
        max-height: 65vh;
    }
    
    .loading-section {
        padding: 60px 20px;
    }
    
    .empty-transitions {
        padding: 60px 20px;
    }
    
    .empty-transitions .empty-icon {
        font-size: 56px;
        width: 100px;
        height: 100px;
    }
    
    .empty-transitions .empty-title {
        font-size: 20px;
    }
}

/* Стили для ввода суммы */
.amount-input-section {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.amount-info {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #6b7280;
    font-size: 15px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 16px 20px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
}

.info-icon {
    font-size: 18px;
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
    padding: 4px;
    border-radius: 8px;
    flex-shrink: 0;
}

.transitions-modal .modal-header {
    padding: 32px 32px 20px 32px;
    border-bottom: 2px solid #e2e8f0;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.transitions-modal .modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.close-btn {
    color: #64748b;
    background: rgba(100, 116, 139, 0.1);
    border-radius: 12px;
    width: 44px;
    height: 44px;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: rgba(100, 116, 139, 0.2);
    color: #475569;
    transform: scale(1.05);
}

/* Стили для блока преимуществ */
.subscription-benefits {
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    margin-top: 8px;
}

.benefits-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 12px;
}

.benefit-item:last-child {
    margin-bottom: 0;
}

.benefit-icon {
    color: #10b981;
    font-size: 18px;
    flex-shrink: 0;
}

/* Стили для уведомления о территориальных ограничениях */
.payment-restriction-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
    margin-top: 8px;
}

.restriction-icon {
    color: #94a3b8;
    font-size: 16px;
    flex-shrink: 0;
}

/* Специальные стили для Telegram WebApp */
body.tg-viewport .lk-container {
    padding-left: 4px;
    padding-right: 4px;
    max-width: 100%;
}

body.tg-viewport .documents-card {
    width: 100% !important;
    margin: 0 !important;
    padding: 16px !important;
}

body.tg-viewport .document-item {
    width: 100% !important;
    padding: 14px !important;
    margin: 0 !important;
}

body.tg-viewport .balance-card,
body.tg-viewport .telegram-connected-block,
body.tg-viewport .telegram-connect-simple {
    width: 100% !important;
    margin: 0 !important;
}

/* Секция создания документа */
.new-document-section {
    margin-bottom: 0;
}

.new-document-btn {
    width: 100%;
    padding: 20px 32px;
    border-radius: 16px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    font-size: 18px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.new-document-btn:hover {
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
    transform: translateY(-3px);
}

.btn-icon {
    margin-right: 12px;
    font-size: 24px;
}

/* Карточка баланса */
.balance-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
    align-items: flex-start;
}

.balance-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.balance-amount {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1;
    text-align: left;
}

.generations-info {
    font-size: 16px;
    font-weight: 500;
    color: #64748b;
    text-align: right;
}

.balance-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
}

.balance-btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 12px 24px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transition: all 0.2s ease;
    width: 100%;
}

.balance-btn:hover {
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    transform: translateY(-1px);
}

.history-btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 8px 16px;
    font-size: 13px;
    width: 100%;
}

.history-btn:hover {
    background: #f8fafc;
}

/* Стили для поля email */
.email-input-section {
    margin-bottom: 12px;
}

.custom-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    right: 16px;
    font-size: 20px;
    color: #64748b;
    pointer-events: none;
}

/* Стили для чекбокса согласия */
.privacy-consent-section {
    margin-bottom: 4px;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.privacy-consent-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    user-select: none;
}

.privacy-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #3b82f6;
    cursor: pointer;
    margin-top: 2px;
}

.privacy-consent-text {
    font-size: 14px;
    color: #374151;
    line-height: 1.4;
    flex: 1;
}

.privacy-consent-wrapper:hover .privacy-consent-text {
    color: #1f2937;
}

/* Кнопка авторизации через Telegram */
.telegram-auth-btn {
    width: 100%;
    padding: 16px 24px;
    border-radius: 12px;
    background: #229954;
    color: white;
    font-size: 16px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(34, 153, 84, 0.3);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    min-height: 52px;
}

.telegram-auth-btn:hover {
    background: #1e8449;
    box-shadow: 0 6px 16px rgba(34, 153, 84, 0.4);
    transform: translateY(-1px);
}

/* ===================== */
/* Финальная фиксация порядка на мобильных (≤768px) */
/* ===================== */
@media (max-width: 768px) {
    .balance-card {
        order: 1 !important;
    }
    .right-column {
        order: 2 !important;
    }
    .telegram-connected-block,
    .telegram-connect-simple,
    .telegram-auth-btn {
        order: 3 !important;
    }
}
</style> 