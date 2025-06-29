<script setup>
import Checkbox from '@/_breeze/Components/Checkbox.vue';
import GuestLayout from '@/_breeze/Layouts/GuestLayout.vue';
import InputError from '@/_breeze/Components/InputError.vue';
import InputLabel from '@/_breeze/Components/InputLabel.vue';
import PrimaryButton from '@/_breeze/Components/PrimaryButton.vue';
import TextInput from '@/_breeze/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { authAndAutoReg } from '@/composables/auth';

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

const isAutoAuthLoading = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const autoAuth = async () => {
    isAutoAuthLoading.value = true;
    try {
        const user = await authAndAutoReg();
        if (user) {
            router.visit(route('dashboard'));
        } else {
            // Если автоматическая авторизация не удалась
            alert('Не удалось автоматически авторизоваться. Попробуйте войти по почте.');
        }
    } catch (error) {
        console.error('Auto auth error:', error);
        alert('Ошибка при автоматической авторизации');
    } finally {
        isAutoAuthLoading.value = false;
    }
};

const goToDashboard = () => {
    router.visit(route('dashboard'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <!-- Кнопки выбора способа авторизации -->
        <div class="mb-6 space-y-3">
            <Link
                :href="route('email-auth')"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center block transition duration-200"
            >
                Войти по почте
            </Link>

            <button
                @click="autoAuth"
                :disabled="isAutoAuthLoading"
                class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold py-2 px-4 rounded transition duration-200"
            >
                <span v-if="isAutoAuthLoading">Авторизация...</span>
                <span v-else>Автоматическая авторизация</span>
            </button>
        </div>

        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">или войти с паролем</span>
            </div>
        </div>

        <form @submit.prevent="submit" class="mt-6">
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

            <div class="flex items-center justify-center mt-4">
                <button
                    type="button"
                    @click="goToDashboard"
                    class="text-sm text-gray-600 hover:text-gray-900 underline"
                >
                    Перейти на главную
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
