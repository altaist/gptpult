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
  }
});
</script>

<template>
    <Head title="Личный кабинет" />

    <page-layout 
        title="Личный кабинет"
        :auto-auth="true"
        left-btn-icon=""
        :left-btn-go-back="false"
    >
        <LkComponent 
          :user="$page.props.auth.user"
          :balance="balance"
          :documents="documents"
        />
    </page-layout>
</template> 