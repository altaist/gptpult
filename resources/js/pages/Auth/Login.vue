<script setup>
import Checkbox from '@/_breeze/Components/Checkbox.vue';
import InputError from '@/_breeze/Components/InputError.vue';
import InputLabel from '@/_breeze/Components/InputLabel.vue';
import PrimaryButton from '@/_breeze/Components/PrimaryButton.vue';
import TextInput from '@/_breeze/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { checkAuth, authAndAutoReg } from '@/composables/auth';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showForm = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

onMounted(async () => {
    // console.log('Требуется авторизация');  // Закомментировано для продакшена
    
    // Пытаемся автоматически авторизовать или зарегистрировать пользователя
    try {
        const user = await authAndAutoReg();
        if (user) {
            // Если пользователь авторизован, перенаправляем в ЛК
            router.visit('/lk');
        } else {
            // Если автоматическая авторизация не удалась, показываем форму через 3 секунды
            setTimeout(() => {
                showForm.value = true;
            }, 3000);
        }
    } catch (error) {
        // console.error('Auto auth failed:', error);  // Закомментировано для продакшена
        // В случае ошибки показываем форму через 2 секунды
        setTimeout(() => {
            showForm.value = true;
        }, 2000);
    }
});
</script>

<template>
    <div class="login-container">
        <Head title="Вход" />
        
        <!-- Простой белый экран с загрузкой -->
        <div class="login-content">
            <div class="loading-section">
                <div class="loading-spinner"></div>
                <div class="loading-text">Загрузка...</div>
            </div>
        </div>
        
        <!-- Скрытая форма входа (для экстренных случаев) -->
        <div v-if="showForm" class="hidden-form">
            <div class="form-toggle">
                <button @click="showForm = false" class="close-btn">×</button>
            </div>
            
            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        <span class="ms-2 text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Forgot your password?
                    </Link>

                    <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Log in
                    </PrimaryButton>
                </div>
            </form>
        </div>
        
        <!-- Кнопка для показа формы (скрытая, активируется двойным кликом) -->
        <div class="emergency-access" @dblclick="showForm = true"></div>
    </div>
</template>

<style scoped>
.login-container {
    min-height: 100vh;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.login-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    width: 100%;
}

.loading-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f4f6;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.loading-text {
    font-size: 16px;
    color: #6b7280;
    font-weight: 500;
}

.hidden-form {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(5px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 40px;
}

.form-toggle {
    position: absolute;
    top: 20px;
    right: 20px;
}

.close-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: #f3f4f6;
    border-radius: 50%;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: #e5e7eb;
    color: #374151;
}

.emergency-access {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    opacity: 0;
    cursor: pointer;
}

/* Стили для формы */
.hidden-form form {
    max-width: 400px;
    width: 100%;
    background: #ffffff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 1px solid #f3f4f6;
}

.hidden-form .mt-1 {
    margin-top: 0.25rem;
}

.hidden-form .mt-4 {
    margin-top: 1rem;
}

.hidden-form .mb-4 {
    margin-bottom: 1rem;
}

.hidden-form .ms-2 {
    margin-left: 0.5rem;
}

.hidden-form .ms-4 {
    margin-left: 1rem;
}

.hidden-form .block {
    display: block;
}

.hidden-form .w-full {
    width: 100%;
}

.hidden-form .flex {
    display: flex;
}

.hidden-form .items-center {
    align-items: center;
}

.hidden-form .justify-end {
    justify-content: flex-end;
}

.hidden-form .text-sm {
    font-size: 0.875rem;
}

.hidden-form .text-gray-600 {
    color: #6b7280;
}

.hidden-form .text-green-600 {
    color: #059669;
}

.hidden-form .font-medium {
    font-weight: 500;
}

.hidden-form .underline {
    text-decoration: underline;
}

.hidden-form .rounded-md {
    border-radius: 0.375rem;
}

.hidden-form .focus\:outline-none:focus {
    outline: none;
}

.hidden-form .focus\:ring-2:focus {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.hidden-form .focus\:ring-offset-2:focus {
    box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px rgba(59, 130, 246, 0.5);
}

.hidden-form .hover\:text-gray-900:hover {
    color: #111827;
}

.hidden-form .opacity-25 {
    opacity: 0.25;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Адаптивность */
@media (max-width: 768px) {
    .hidden-form {
        padding: 20px;
    }
    
    .hidden-form form {
        padding: 30px 20px;
        margin: 0 10px;
    }
    
    .loading-spinner {
        width: 35px;
        height: 35px;
    }
    
    .loading-text {
        font-size: 14px;
    }
}
</style>
