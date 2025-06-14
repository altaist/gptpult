<template>
    <page-layout
        title="Просмотр документа"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <document-view :document="document" />

            <div class="row justify-end q-mt-md">
                <q-btn
                    label="Скачать Word"
                    color="primary"
                    class="q-mr-sm"
                    :loading="isDownloading"
                    @click="downloadWord"
                />
                <!--q-btn
                    label="Редактировать"
                    color="primary"
                    :to="route('documents.edit', document.id)"
                /-->
            </div>
        </div>
    </page-layout>
</template>

<script setup>
import { defineProps, ref } from 'vue';
import { useQuasar } from 'quasar';
import PageLayout from '@/components/shared/PageLayout.vue';
import DocumentView from '@/modules/gpt/components/DocumentView.vue';
import { router } from '@inertiajs/vue3';

const $q = useQuasar();
const isDownloading = ref(false);

const props = defineProps({
    document: {
        type: Object,
        required: true
    }
});

const downloadWord = async () => {
    try {
        isDownloading.value = true;
        const response = await axios.post(route('documents.download-word', props.document.id));
        
        // Создаем ссылку для скачивания
        const link = document.createElement('a');
        link.href = response.data.url;
        link.download = response.data.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        $q.notify({
            type: 'positive',
            message: 'Документ успешно сгенерирован'
        });
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: error.response?.data?.message || 'Ошибка при генерации документа'
        });
    } finally {
        isDownloading.value = false;
    }
};
</script> 