<template>
    <page-layout 
        title="Главная"
        footer-text="Контакты"
        :left-btn-go-back="true"
        :auto-auth="false"
        @click:footer:menu="onMenuClick"
        >

        <page-section title="Добро пожаловать!">
            <div v-if="isAuthenticated">
                <p>Привет, {{ user.name }}!</p>
                <button @click="handleLogout" class="btn btn-primary">Выйти</button>
            </div>
            <div v-else>
                <p>Вы не авторизованы</p>
                <div class="space-y-2">
                    <button @click="goToEmailAuth" class="btn btn-primary block w-full">Войти по почте</button>
                    <button @click="handleAutoAuth" class="btn btn-secondary block w-full">Автоматическая авторизация</button>
                    <button @click="goToLogin" class="btn btn-outline block w-full">Войти с паролем</button>
                </div>
            </div>
        </page-section>
        
        <page-section title="О сервисе">
            Немного о нас
        </page-section>

        <block color="text-white" bg-color="bg-secondary" title="Важная информация">В этом блоке отображается важная информация</block>

    </page-layout>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { checkAuthOnly, isAuthenticated, user, logout, authAndAutoReg } from '@/composables/auth';
import { router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({

});

const onMenuClick = (menuId) => {
    console.log(`Menu ${menuId}`);
}

// Проверяем только существующую авторизацию при загрузке
onMounted(async () => {
    await checkAuthOnly();
});

// Переход к авторизации по email
const goToEmailAuth = () => {
    router.visit('/email-auth');
};

// Переход к обычной авторизации
const goToLogin = () => {
    router.visit('/login');
};

// Автоматическая авторизация по выбору пользователя
const handleAutoAuth = async () => {
    try {
        const result = await authAndAutoReg();
        if (result) {
            router.visit('/lk');
        } else {
            alert('Не удалось автоматически авторизоваться');
        }
    } catch (error) {
        console.error('Auto auth error:', error);
        alert('Ошибка при автоматической авторизации');
    }
};

// Обработка выхода из системы
const handleLogout = async () => {
    try {
        await logout(true);
        router.visit('/');
    } catch (error) {
        console.error('Logout failed:', error);
    }
};
</script>
