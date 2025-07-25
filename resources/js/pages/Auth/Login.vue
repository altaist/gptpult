<script setup>
import Checkbox from '@/_breeze/Components/Checkbox.vue';
import GuestLayout from '@/_breeze/Layouts/GuestLayout.vue';
import InputError from '@/_breeze/Components/InputError.vue';
import InputLabel from '@/_breeze/Components/InputLabel.vue';
import PrimaryButton from '@/_breeze/Components/PrimaryButton.vue';
import TextInput from '@/_breeze/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { checkAuth } from '@/composables/auth';

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

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const goToDashboard = () => {
    router.visit(route('dashboard'));
};

onMounted(async () => {
    try {
        await checkAuth();
        router.visit(route('dashboard'));
    } catch (error) {
        console.log('Требуется авторизация');
    }
});
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

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
