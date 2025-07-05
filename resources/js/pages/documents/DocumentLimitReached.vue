<template>
    <page-layout :auto-auth="true">
        <Head title="Лимит создания документов" />
        
        <div class="limit-container">
            <div class="limit-card">
                <!-- Иконка ограничения -->
                <div class="limit-icon">
                    <q-icon name="block" size="80px" color="orange" />
                </div>

                <!-- Заголовок -->
                <h1 class="limit-title">
                    Лимит создания документов достигнут
                </h1>

                <!-- Сообщение -->
                <div class="limit-message">
                    <p>{{ message }}</p>
                </div>

                <!-- Информация о лимитах (только для отладки в dev режиме) -->
                <div v-if="showDebugInfo" class="limit-debug">
                    <p><strong>Текущих документов:</strong> {{ limitInfo.current_documents }}</p>
                    <p><strong>Доступно слотов:</strong> {{ limitInfo.available_slots }}</p>
                    <p><strong>Есть платежи:</strong> {{ limitInfo.has_payments ? 'Да' : 'Нет' }}</p>
                </div>

                <!-- Кнопка перехода в личный кабинет -->
                <div class="limit-actions">
                    <q-btn
                        color="primary"
                        size="lg"
                        @click="goToPersonalAccount"
                        unelevated
                        no-caps
                        class="go-to-lk-btn"
                    >
                        <q-icon name="person" class="btn-icon" />
                        Перейти в личный кабинет
                    </q-btn>
                </div>

                <!-- Дополнительная информация -->
                <div class="limit-info">
                    <div class="info-item">
                        <q-icon name="info" class="info-icon" />
                        <span>В личном кабинете вы можете пополнить баланс или завершить генерацию существующих документов</span>
                    </div>
                </div>
            </div>
        </div>
    </page-layout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import PageLayout from '@/components/shared/PageLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    limit_info: {
        type: Object,
        required: true
    },
    message: {
        type: String,
        required: true
    }
});

// Показывать отладочную информацию только в dev режиме
const showDebugInfo = computed(() => {
    return import.meta.env.DEV || import.meta.env.VITE_APP_DEBUG === 'true';
});

const goToPersonalAccount = () => {
    router.visit(route('lk'));
};
</script>

<style scoped>
.limit-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.limit-card {
    background: white;
    border-radius: 24px;
    padding: 48px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    text-align: center;
    max-width: 600px;
    width: 100%;
}

.limit-icon {
    margin-bottom: 32px;
}

.limit-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 24px 0;
    line-height: 1.2;
}

.limit-message {
    font-size: 18px;
    color: #6b7280;
    margin-bottom: 32px;
    line-height: 1.6;
}

.limit-debug {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 32px;
    font-size: 14px;
    text-align: left;
}

.limit-debug p {
    margin: 4px 0;
}

.limit-actions {
    margin-bottom: 32px;
}

.go-to-lk-btn {
    padding: 16px 32px;
    border-radius: 16px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    font-size: 18px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.go-to-lk-btn:hover {
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
    transform: translateY(-2px);
}

.btn-icon {
    margin-right: 12px;
    font-size: 20px;
}

.limit-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    text-align: left;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.info-icon {
    color: #3b82f6;
    font-size: 20px;
    margin-top: 2px;
    flex-shrink: 0;
}

.info-item span {
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
}

/* Мобильная адаптация */
@media (max-width: 768px) {
    .limit-container {
        padding: 16px;
        min-height: 100vh;
    }

    .limit-card {
        padding: 32px 24px;
    }

    .limit-title {
        font-size: 24px;
    }

    .limit-message {
        font-size: 16px;
    }

    .go-to-lk-btn {
        width: 100%;
        padding: 16px 24px;
        font-size: 16px;
    }
}
</style> 