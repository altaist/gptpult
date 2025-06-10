<template>
    <page-layout
        title="Просмотр документа"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <div class="text-h5 q-mb-md">{{ document.topic }}</div>
            
            <div class="row q-col-gutter-md">
                <div class="col-12">
                    <q-card class="q-pa-md">
                        <q-card-section>
                            <div class="text-subtitle2">Тип документа</div>
                            <div>{{ document.document_type?.name }}</div>
                        </q-card-section>

                        <q-card-section>
                            <div class="text-subtitle2">Тезисы</div>
                            <div>{{ document.structure?.theses || 'Нет тезисов' }}</div>
                        </q-card-section>

                        <q-card-section>
                            <div class="text-subtitle2">Содержание</div>
                            <div>{{ document.structure?.contents || 'Нет содержания' }}</div>
                        </q-card-section>
                    </q-card>
                </div>
            </div>

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

defineProps({
    document: {
        type: Object,
        required: true
    }
});
</script> 