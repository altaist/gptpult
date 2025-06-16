<template>
    <page-layout
        title="Новый документ"
        :auto-auth="true"
    >
        <div class="q-pa-md">
            <q-form @submit="onSubmit" class="q-gutter-md">
                <q-select
                    v-model="form.document_type_id"
                    :options="document_types"
                    option-label="name"
                    option-value="id"
                    label="Тип документа"
                    :rules="[val => !!val || 'Пожалуйста, выберите тип документа']"
                    :error="hasError('document_type_id')"
                    :error-message="getError('document_type_id')"
                    emit-value
                    map-options
                />

                <q-input
                    v-model="form.topic"
                    label="Тема документа"
                    type="textarea"
                    :rules="[val => !!val || 'Пожалуйста, введите тему документа']"
                    :error="hasError('topic')"
                    :error-message="getError('topic')"
                />

                <div v-if="error" class="text-negative q-mb-md">
                    {{ error }}
                </div>

                <div class="row justify-center q-mt-md">
                    <q-btn
                        label="Создать документ"
                        type="submit"
                        color="primary"
                        :loading="isLoading"
                    />
                </div>
            </q-form>
        </div>
    </page-layout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PageLayout from '@/components/shared/PageLayout.vue';
import { apiClient, isLoading, useLaravelErrors } from '@/composables/api';

const props = defineProps({
    document_types: {
        type: Array,
        required: true,
        default: () => []
    }
});

const error = ref('');
const form = ref({
    document_type_id: null,
    topic: ''
});

const { hasError, getError } = useLaravelErrors();

const onSubmit = async () => {
    try {
        error.value = '';

        const data = {
            ...form.value,
            document_type_id: Number(form.value.document_type_id)
        };

        const response = await apiClient.post(route('documents.quick-create'), data);
        
        // После успешного создания сразу переходим к просмотру документа
        if (response && response.document && response.document.id) {
            router.visit(route('documents.show', response.document.id));
        } else {
            throw new Error('Неверный формат ответа от сервера');
        }
    } catch (err) {
        error.value = err.message || 'Произошла ошибка при создании документа';
        console.error('Ошибка при создании документа:', err);
    }
};
</script> 