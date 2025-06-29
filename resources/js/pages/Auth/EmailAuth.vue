<script setup>
import GuestLayout from '@/_breeze/Layouts/GuestLayout.vue';
import InputError from '@/_breeze/Components/InputError.vue';
import InputLabel from '@/_breeze/Components/InputLabel.vue';
import PrimaryButton from '@/_breeze/Components/PrimaryButton.vue';
import TextInput from '@/_breeze/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    step: {
        type: String,
        default: 'email'
    },
    email: {
        type: String,
        default: ''
    },
    message: {
        type: String,
        default: ''
    }
});

// Форма для ввода email
const emailForm = useForm({
    email: props.email || '',
});

// Форма для ввода кода
const codeForm = useForm({
    email: props.email || '',
    code: '',
});

const currentStep = ref(props.step);

// Отправка кода на email
const sendCode = () => {
    emailForm.post(route('email-auth.send-code'), {
        onSuccess: () => {
            currentStep.value = 'code';
            codeForm.email = emailForm.email;
        },
    });
};

// Проверка кода
const verifyCode = () => {
    codeForm.post(route('email-auth.verify-code'));
};

// Повторная отправка кода
const resendCode = () => {
    const resendForm = useForm({
        email: codeForm.email,
    });
    
    resendForm.post(route('email-auth.resend-code'), {
        preserveScroll: true,
    });
};

// Вернуться к вводу email
const goBackToEmail = () => {
    currentStep.value = 'email';
    codeForm.reset('code');
    codeForm.clearErrors();
};

onMounted(() => {
    if (props.step === 'code') {
        currentStep.value = 'code';
        codeForm.email = props.email;
    }
});
</script>

<template>
    <GuestLayout>
        <Head title="Авторизация по почте" />

        <!-- Шаг 1: Ввод email -->
        <div v-if="currentStep === 'email'">
            <div class="mb-4 text-sm text-gray-600">
                Введите ваш email. Мы отправим код подтверждения для входа.
            </div>

            <form @submit.prevent="sendCode">
                <div>
                    <InputLabel for="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="emailForm.email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="your@email.com"
                    />

                    <InputError class="mt-2" :message="emailForm.errors.email" />
                </div>

                <div class="flex items-center justify-end mt-6">
                    <Link
                        :href="route('login')"
                        class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-4"
                    >
                        Другие способы входа
                    </Link>

                    <PrimaryButton 
                        :class="{ 'opacity-25': emailForm.processing }" 
                        :disabled="emailForm.processing"
                    >
                        Отправить код
                    </PrimaryButton>
                </div>
            </form>
        </div>

        <!-- Шаг 2: Ввод кода -->
        <div v-if="currentStep === 'code'">
            <div class="mb-4 text-sm text-gray-600">
                Мы отправили код подтверждения на <strong>{{ codeForm.email }}</strong>
            </div>

            <div v-if="message" class="mb-4 font-medium text-sm text-green-600">
                {{ message }}
            </div>

            <form @submit.prevent="verifyCode">
                <div>
                    <InputLabel for="code" value="Код подтверждения" />

                    <TextInput
                        id="code"
                        type="text"
                        class="mt-1 block w-full text-center text-2xl tracking-widest"
                        v-model="codeForm.code"
                        required
                        autofocus
                        maxlength="6"
                        placeholder="000000"
                        @input="codeForm.code = codeForm.code.replace(/[^0-9]/g, '')"
                    />

                    <InputError class="mt-2" :message="codeForm.errors.code" />
                </div>

                <div class="flex items-center justify-between mt-6">
                    <div class="flex space-x-4">
                        <button
                            type="button"
                            @click="goBackToEmail"
                            class="text-sm text-gray-600 hover:text-gray-900 underline"
                        >
                            Изменить email
                        </button>

                        <button
                            type="button"
                            @click="resendCode"
                            class="text-sm text-gray-600 hover:text-gray-900 underline"
                        >
                            Отправить код повторно
                        </button>
                    </div>

                    <PrimaryButton 
                        :class="{ 'opacity-25': codeForm.processing }" 
                        :disabled="codeForm.processing || codeForm.code.length !== 6"
                    >
                        Войти
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
/* Дополнительные стили для лучшего UX */
input[type="text"]::-webkit-outer-spin-button,
input[type="text"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="text"] {
    -moz-appearance: textfield;
}
</style> 