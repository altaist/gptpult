<template>
    <page-layout 
        title="ПРИЛОЖЕНИЕ"
        footer-text="Контакты"
        :left-btn-go-back="true"
        @click:footer:menu="onMenuClick"
        >

        <page-section title="Добро пожаловать!">
            <lorem></lorem>
        </page-section>
        
        <page-section title="О сервисе">
            Немного о нас
        </page-section>

        <block color="text-white" bg-color="bg-secondary" title="Важная информация">В этом блоке отображается важная информация</block>

    </page-layout>
</template>
<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({

});

const onMenuClick = (menuId) => {
    console.log(`Menu ${menuId}`);
}

</script>
