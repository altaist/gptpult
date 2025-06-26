<script setup>
import { Head } from '@inertiajs/vue3';
import LkComponent from '@/components/Lk.vue';
import PageLayout from '@/components/shared/PageLayout.vue';

// Определяем пропсы, которые могут приходить от контроллера
defineProps({
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
</script>

<template>
    <Head title="Личный кабинет" />

    <page-layout 
        title="GPT Пульт"
        :auto-auth="true"
        left-btn-icon=""
        :left-btn-go-back="false"
        :logo-go-home="true"
    >
        <LkComponent 
          :user="$page.props.auth.user"
          :balance="balance"
          :documents="documents"
          :isDevelopment="isDevelopment"
        />
    </page-layout>
</template> 