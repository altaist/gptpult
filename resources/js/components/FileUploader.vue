<template>
    <div class="q-pa-md">
        <q-uploader
            :url="route('files.upload')"
            :accept="accept"
            :max-file-size="maxSize * 1024 * 1024"
            :form-fields="formFields"
            @uploaded="onUploaded"
            @failed="onFailed"
            @removed="onRemoved"
            auto-upload
            flat
            bordered
            class="full-width"
        >
            <template v-slot:header="scope">
                <div class="row no-wrap items-center q-pa-sm">
                    <q-btn
                        v-if="scope.canAddFiles"
                        type="a"
                        icon="add_box"
                        round
                        dense
                        flat
                    >
                        <q-uploader-add-trigger />
                    </q-btn>
                    <q-btn
                        v-if="scope.canRemoveFiles"
                        type="a"
                        icon="delete"
                        round
                        dense
                        flat
                        class="q-ml-sm"
                        @click="scope.removeQueuedFiles"
                    />
                    <q-space />
                    <q-btn
                        v-if="scope.uploadedFiles.length > 0"
                        type="a"
                        icon="clear_all"
                        round
                        dense
                        flat
                        class="q-ml-sm"
                        @click="scope.removeUploadedFiles"
                    />
                </div>
            </template>

            <template v-slot:list="scope">
                <q-list separator>
                    <q-item v-for="file in scope.files" :key="file.name">
                        <q-item-section>
                            <q-item-label>{{ file.name }}</q-item-label>
                            <q-item-label caption>{{ formatFileSize(file.size) }}</q-item-label>
                        </q-item-section>

                        <q-item-section side>
                            <q-btn
                                round
                                flat
                                dense
                                icon="delete"
                                @click="scope.removeFile(file)"
                            />
                        </q-item-section>
                    </q-item>
                </q-list>
            </template>
        </q-uploader>

        <!-- Уведомления -->
        <q-banner
            v-if="notification.show"
            :class="notification.type === 'success' ? 'bg-positive text-white' : 'bg-negative text-white'"
            class="q-mt-md"
        >
            {{ notification.message }}
        </q-banner>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useQuasar } from 'quasar';

const $q = useQuasar();

const props = defineProps({
    accept: {
        type: String,
        default: '*/*'
    },
    maxSize: {
        type: Number,
        default: 10
    },
    documentId: {
        type: [String, Number],
        default: null
    }
});

const emit = defineEmits(['upload-success', 'upload-error', 'delete-success']);

const notification = ref({
    show: false,
    type: 'success',
    message: ''
});

const formFields = computed(() => [
    { name: 'display_name', value: '' },
    { name: 'document_id', value: props.documentId },
    { name: '_token', value: document.querySelector('meta[name="csrf-token"]')?.content || '' }
]);

const showNotification = (message, type = 'success') => {
    notification.value = {
        show: true,
        type,
        message
    };

    setTimeout(() => {
        notification.value.show = false;
    }, 3000);
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const onUploaded = (info) => {
    const file = info.xhr.response;
    emit('upload-success', file);
    showNotification(`Файл "${file.display_name}" успешно загружен`);
};

const onFailed = (info) => {
    emit('upload-error', info.xhr.response?.message || 'Ошибка при загрузке файла');
    showNotification(info.xhr.response?.message || 'Ошибка при загрузке файла', 'error');
};

const onRemoved = (file) => {
    emit('delete-success', file);
    showNotification(`Файл "${file.name}" удален`);
};
</script> 