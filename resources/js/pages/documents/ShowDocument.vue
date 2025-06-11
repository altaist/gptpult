<template>
    <page-layout
        title="Просмотр документа"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <document-view :document="document" />

            <div class="row justify-end q-mt-md">
                <q-btn
                    label="Редактировать"
                    color="primary"
                    :to="route('documents.edit', document.id)"
                />
            </div>
        </div>
    </page-layout>
</template>

<script setup>
import { defineProps } from 'vue';
import PageLayout from '@/components/shared/PageLayout.vue';
import DocumentView from '@/modules/gpt/components/DocumentView.vue';

defineProps({
    document: {
        type: Object,
        required: true
    }
});
</script> 