<template>
    <page-layout 
        title="Главная"
        footer-text="Контакты"
        :left-btn-go-back="true"
        :auto-auth="true"
        @click:footer:menu="onMenuClick"
        >

        <page-section title="Добро пожаловать!">
            <div v-if="isAuthenticated">
                <p>Привет, {{ user.name }}!</p>
                <button @click="handleLogout" class="btn btn-primary">Выйти</button>
            </div>
            <div v-else>
                <p>Вы не авторизованы</p>
                <button @click="checkAuth" class="btn btn-primary">Авторизоваться</button>
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
import { checkAuth, isAuthenticated, user, logout } from '@/composables/auth';
import { router } from '@inertiajs/vue3';

defineProps({

});

const onMenuClick = (menuId) => {
    // console.log(`Menu ${menuId}`);  // Закомментировано для продакшена
}

// Обработка выхода из системы
const handleLogout = async () => {
    try {
        await logout(true);
        router.visit('/');
    } catch (error) {
        // console.error('Logout failed:', error);  // Закомментировано для продакшена
    }
};
</script>
