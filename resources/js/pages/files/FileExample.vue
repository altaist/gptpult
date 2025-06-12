<template>
    <div class="q-pa-md">
        <div class="q-mb-lg">
            <h4 class="q-mb-md">Загрузка файлов</h4>
            <FileUploader
                @upload-success="handleUploadSuccess"
                @upload-error="handleUploadError"
                @delete-success="handleDeleteSuccess"
            />
        </div>

        <div class="q-mb-lg">
            <h4 class="q-mb-md">Загрузка документов</h4>
            <FileUploader
                accept=".doc,.docx,.pdf"
                :max-size="20"
                type="documents"
                @upload-success="handleUploadSuccess"
                @upload-error="handleUploadError"
                @delete-success="handleDeleteSuccess"
            />
        </div>

        <div class="q-mb-lg">
            <h4 class="q-mb-md">Загрузка изображений</h4>
            <FileUploader
                accept="image/*"
                :max-size="5"
                type="images"
                @upload-success="handleUploadSuccess"
                @upload-error="handleUploadError"
                @delete-success="handleDeleteSuccess"
            />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useQuasar } from 'quasar';
import FileUploader from '@/components/FileUploader.vue';

const $q = useQuasar();

const handleUploadSuccess = (file) => {
    $q.notify({
        type: 'positive',
        message: `Файл "${file.display_name}" успешно загружен`
    });
};

const handleUploadError = (error) => {
    $q.notify({
        type: 'negative',
        message: error
    });
};

const handleDeleteSuccess = (file) => {
    $q.notify({
        type: 'positive',
        message: `Файл "${file.display_name}" успешно удален`
    });
};
</script> 