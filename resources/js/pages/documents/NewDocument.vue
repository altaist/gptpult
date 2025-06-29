<template>
    <page-layout :auto-auth="false">
        <Head title="Создание документа" />
        
        <div class="container">
            <!-- Мобильный заголовок и прогресс (показывается только на мобильных) -->
            <div class="mobile-header">
                <h1 class="mobile-title">Расскажи о своей работе</h1>
                <div class="mobile-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: `${(1/3) * 100}%` }"></div>
                    </div>
                    <div class="progress-text">Шаг 1 из 3</div>
                </div>
            </div>

            <!-- Основной контент с двумя колонками -->
            <div class="main-content">
                <!-- Левая колонка с информацией -->
                <div class="info-column">
                    <!-- Заголовок (скрывается на мобильных) -->
                    <div class="header-section">
                        <h1 class="main-title">Расскажи о своей работе</h1>
                    </div>

                    <!-- Блок с шагами (скрывается на мобильных) -->
                    <div class="steps-card">
                        <h3 class="steps-title">
                            3 шага до сдачи
                        </h3>
                        <div class="steps-blocks">
                            <div class="step-block active">
                                <h4 class="step-block-title">Опиши работу</h4>
                            </div>
                            <div class="step-block">
                                <h4 class="step-block-title">Утверди содержание</h4>
                            </div>
                            <div class="step-block">
                                <h4 class="step-block-title">Получи работу</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Правая колонка с формой -->
                <div class="form-column">
                    <div class="form-container">
                        <q-form @submit="onSubmit" class="document-form">
                            
                            <!-- Выбор типа работы -->
                            <div class="form-section">
                                <h3 class="section-title">Тип работы</h3>
                                <div class="work-type-buttons">
                                    <button 
                                        v-for="type in document_types" 
                                        :key="type.id"
                                        type="button"
                                        @click="selectWorkType(type)"
                                        :class="[
                                            'work-type-btn',
                                            { 'active': form.document_type_id === type.id }
                                        ]"
                                    >
                                        <div class="work-type-icon">
                                            <q-icon :name="getWorkTypeIcon(type.name)" size="28px" />
                                        </div>
                                        <span class="work-type-name">{{ type.name }}</span>
                                    </button>
                                </div>
                                <div v-if="hasError('document_type_id')" class="error-message">
                                    {{ getError('document_type_id') }}
                                </div>
                            </div>

                            <!-- Тема работы -->
                            <div class="form-section">
                                <h3 class="section-title">Тема работы</h3>
                                <p class="section-description">
                                    Опиши тему твоей работы. Чем подробнее, тем лучше будет результат (минимум 10 символов)
                                </p>
                                <div class="input-container">
                                    <CustomInput
                                        v-model="form.topic"
                                        type="textarea"
                                        placeholder="Введите тему документа..."
                                        :rows="4"
                                        :error="hasError('topic') ? getError('topic') : ''"
                                    />
                                </div>
                            </div>

                            <!-- Объем работы -->
                            <div class="form-section">
                                <h3 class="section-title">Объем работы</h3>
                                <p class="section-description">
                                    Укажи приблизительное количество страниц (от 3 до 25)
                                </p>
                                <div class="pages-input-container">
                                    <div class="pages-counter">
                                        <button 
                                            type="button" 
                                            @click="decrementPages" 
                                            :disabled="form.pages_num <= 3"
                                            class="counter-btn"
                                        >
                                            <q-icon name="remove" />
                                        </button>
                                        <div class="pages-display">
                                            <span class="pages-number">{{ form.pages_num }}</span>
                                            <span class="pages-label">страниц</span>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="incrementPages" 
                                            :disabled="form.pages_num >= 25"
                                            class="counter-btn"
                                        >
                                            <q-icon name="add" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Ошибки -->
                            <div v-if="error" class="global-error">
                                <q-icon name="error" class="error-icon" />
                                <span>{{ error }}</span>
                            </div>

                            <!-- Кнопка отправки -->
                            <div class="submit-section">
                                <div class="submit-container">
                                    <div class="submit-wrapper" @click="handleSubmitClick">
                                        <q-btn
                                            type="button"
                                            :loading="isLoading"
                                            :disable="!canSubmit"
                                            class="submit-btn"
                                            unelevated
                                        >
                                            <q-tooltip 
                                                v-if="!canSubmit && !isMobile" 
                                                class="submit-tooltip"
                                                anchor="top middle" 
                                                self="bottom middle"
                                                :offset="[0, 8]"
                                            >
                                                {{ getSubmitHint() }}
                                            </q-tooltip>
                                            <q-icon name="auto_awesome" class="submit-icon" />
                                            <span>Создать работу</span>
                                        </q-btn>
                                    </div>
                                    
                                    <!-- Время генерации -->
                                    <div class="time-estimate">
                                        <q-icon name="schedule" class="time-icon" />
                                        <span>Общее время генерации: 5-10 минут</span>
                                    </div>
                                    
                                    <!-- Мобильная подсказка -->
                                    <div v-if="showMobileHint" class="mobile-hint">
                                        <q-icon name="info" class="mobile-hint-icon" />
                                        <span>{{ getMobileHintText() }}</span>
                                    </div>
                                </div>
                            </div>
                        </q-form>
                    </div>
                </div>
            </div>
        </div>
    </page-layout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import PageLayout from '@/components/shared/PageLayout.vue';
import { Head } from '@inertiajs/vue3';
import { apiClient, isLoading, useLaravelErrors } from '@/composables/api';
import CustomInput from '@/components/shared/CustomInput.vue';

const props = defineProps({
    document_types: {
        type: Array,
        required: true,
        default: () => []
    }
});

const error = ref('');
const form = ref({
    document_type_id: null,
    topic: '',
    pages_num: 10
});

const showMobileHint = ref(false);
const isMobile = ref(false);

const { hasError, getError } = useLaravelErrors();

// Computed свойства
const canSubmit = computed(() => {
    return form.value.document_type_id && form.value.topic.trim().length >= 10;
});

const currentStep = computed(() => {
    // На странице создания документа всегда активен первый шаг
    return 1;
});

// Методы
const checkMobile = () => {
    isMobile.value = window.innerWidth <= 768;
};

const selectWorkType = (type) => {
    form.value.document_type_id = type.id;
};

const getWorkTypeIcon = (typeName) => {
    const icons = {
        'Реферат': 'description',
        'Отчет о практике': 'assignment',
        'Эссе': 'edit_note'
    };
    return icons[typeName] || 'article';
};

const getSubmitHint = () => {
    if (!form.value.document_type_id && form.value.topic.trim().length < 10) {
        return 'Выбери тип работы и введи тему (минимум 10 символов)';
    }
    if (!form.value.document_type_id) {
        return 'Выбери тип работы';
    }
    if (form.value.topic.trim().length < 10) {
        return 'Введи тему работы (минимум 10 символов)';
    }
    return '';
};

const handleSubmitClick = (event) => {
    if (!canSubmit.value) {
        if (isMobile.value) {
            event.preventDefault();
            event.stopPropagation();
            showMobileHint.value = true;
            setTimeout(() => {
                showMobileHint.value = false;
            }, 3000);
        }
        return;
    }
    
    // Если можем отправить форму, вызываем onSubmit
    if (canSubmit.value && !isLoading.value) {
        onSubmit();
    }
};

const incrementPages = () => {
    if (form.value.pages_num < 25) {
        form.value.pages_num++;
    }
};

const decrementPages = () => {
    if (form.value.pages_num > 3) {
        form.value.pages_num--;
    }
};

const onSubmit = async () => {
    try {
        error.value = '';

        // Проверка минимальной длины темы
        if (form.value.topic.trim().length < 10) {
            error.value = 'Тема работы должна содержать минимум 10 символов';
            return;
        }

        const data = {
            ...form.value,
            document_type_id: Number(form.value.document_type_id)
        };

        const response = await apiClient.post(route('documents.quick-create'), data);
        
        if (response && response.document && response.document.id) {
            const redirectUrl = response.redirect_url || route('documents.show', {
                document: response.document.id,
                autoload: 1
            });
            router.visit(redirectUrl);
        } else {
            throw new Error('Неверный формат ответа от сервера');
        }
    } catch (err) {
        error.value = err.message || 'Произошла ошибка при создании документа';
        console.error('Ошибка при создании документа:', err);
    }
};

const getMobileHintText = () => {
    return getSubmitHint();
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
});
</script>

<style scoped>
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 24px;
    min-height: 100vh;
}

/* Мобильный заголовок и прогресс */
.mobile-header {
    display: none;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 32px;
    position: relative;
}

.mobile-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.1;
    text-align: center;
}

.mobile-progress {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
}

.progress-bar {
    width: 100%;
    max-width: 300px;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

/* Двухколоночный layout */
.main-content {
    display: grid;
    grid-template-columns: 1fr 700px;
    gap: 60px;
    align-items: start;
}

/* Левая колонка с информацией */
.info-column {
    display: flex;
    flex-direction: column;
    gap: 40px;
}

.header-section {
    text-align: left;
}

.main-title {
    font-size: 48px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.1;
}

/* Информационная карточка */
.info-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #f1f5f9;
    position: sticky;
    top: calc(80px + 24px);
}

.info-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 24px 0;
}

.info-icon {
    font-size: 28px;
    color: #3b82f6;
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
    color: #374151;
    line-height: 1.5;
}

.list-icon {
    font-size: 20px;
    color: #3b82f6;
    flex-shrink: 0;
}

/* Карточка с шагами */
.steps-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #f1f5f9;
    position: sticky;
    top: calc(80px + 24px);
}

.steps-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 32px 0;
}

.steps-icon {
    font-size: 28px;
    color: #3b82f6;
}

.steps-blocks {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.step-block {
    padding: 16px 20px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.step-block.active {
    background: #3b82f6;
    border-color: #3b82f6;
}

.step-block-title {
    font-size: 16px;
    font-weight: 500;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
    transition: all 0.3s ease;
}

.step-block.active .step-block-title {
    color: #ffffff;
    font-weight: 600;
}

/* Правая колонка с формой */
.form-column {
    width: 100%;
}

.form-container {
    background: #ffffff;
    border-radius: 24px;
    padding: 36px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    border: 1px solid #f1f5f9;
    width: 100%;
}

.document-form {
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.form-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.section-title {
    font-size: 22px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
    letter-spacing: -0.01em;
}

.section-description {
    font-size: 15px;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

/* Кнопки выбора типа работы */
.work-type-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.work-type-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px 8px;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 85px;
    justify-content: center;
}

.work-type-btn:hover {
    border-color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
}

.work-type-btn.active {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
}

.work-type-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    transition: all 0.2s ease;
}

.work-type-btn.active .work-type-icon {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.work-type-name {
    font-size: 13px;
    font-weight: 600;
    text-align: center;
    line-height: 1.2;
}

/* Поле ввода темы */
.input-container {
    position: relative;
}

/* Счетчик страниц */
.pages-input-container {
    display: flex;
    justify-content: center;
}

.pages-counter {
    display: flex;
    align-items: center;
    gap: 0;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.counter-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border: none;
    background: #ffffff;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
}

.counter-btn:hover:not(:disabled) {
    background: #3b82f6;
    color: white;
}

.counter-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pages-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0 32px;
    min-width: 120px;
    background: white;
}

.pages-number {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1;
}

.pages-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

/* Кнопка отправки */
.submit-section {
    margin-top: 8px;
}

.submit-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.submit-wrapper {
    display: inline-block;
}

.submit-btn {
    padding: 16px 40px;
    border-radius: 16px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    font-size: 17px;
    font-weight: 600;
    min-width: 200px;
    height: auto;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    position: relative;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
}

.submit-btn:disabled {
    opacity: 0.6;
    transform: none;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    cursor: not-allowed;
}

.submit-icon {
    margin-right: 8px;
    font-size: 20px;
}

/* Время генерации */
.time-estimate {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-size: 13px;
    font-weight: 500;
}

.time-icon {
    font-size: 16px;
    color: #3b82f6;
}

/* Tooltip для кнопки */
.submit-tooltip {
    background: #1f2937 !important;
    color: white !important;
    border-radius: 8px !important;
    padding: 8px 12px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

/* Мобильная подсказка */
.mobile-hint {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #fef3c7;
    border: 1px solid #fbbf24;
    border-radius: 12px;
    color: #92400e;
    font-size: 14px;
    font-weight: 500;
    animation: slideIn 0.3s ease;
    max-width: 100%;
    text-align: center;
    margin: 0 auto;
}

.mobile-hint-icon {
    font-size: 18px;
    color: #f59e0b;
    flex-shrink: 0;
}

@keyframes slideIn {
    from { 
        opacity: 0; 
        transform: translateY(-10px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

/* Ошибки */
.error-message {
    color: #ef4444;
    font-size: 14px;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.global-error {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    color: #dc2626;
    font-size: 16px;
}

.error-icon {
    font-size: 20px;
}

/* Адаптивность */
@media (max-width: 1200px) {
    .main-content {
        grid-template-columns: 1fr 650px;
        gap: 40px;
    }
    
    .main-title {
        font-size: 42px;
    }
}

@media (max-width: 1024px) {
    .main-content {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .info-column {
        order: 1;
    }
    
    .form-column {
        order: 0;
    }
    
    .steps-card {
        position: static;
    }
    
    .work-type-buttons {
        grid-template-columns: 1fr;
    }
    
    .work-type-btn {
        min-height: 80px;
        padding: 16px;
    }
    
    .work-type-name {
        font-size: 16px;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 24px 16px;
    }
    
    /* Показываем мобильный заголовок */
    .mobile-header {
        display: flex;
    }
    
    /* Скрываем десктопный заголовок и блок шагов */
    .header-section,
    .steps-card {
        display: none;
    }
    
    .main-title {
        font-size: 36px;
    }
    
    .form-container {
        padding: 32px 24px;
        border-radius: 20px;
    }
    
    .submit-btn {
        padding: 16px 32px;
        font-size: 16px;
        min-width: 180px;
    }
}

@media (max-width: 480px) {
    .main-title {
        font-size: 28px;
    }
    
    .section-title {
        font-size: 20px;
    }
    
    .form-container {
        padding: 24px 20px;
    }
    
    .pages-display {
        padding: 0 24px;
        min-width: 100px;
    }
    
    .submit-btn {
        min-width: 160px;
    }
}
</style>