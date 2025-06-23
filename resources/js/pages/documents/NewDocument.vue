<template>
    <page-layout
        title="GPT PULT"
        :auto-auth="true"
    >
    <Head title="Создание документа" />
        <div class="q-pa-xl">
            
            <q-form @submit="onSubmit" class="q-gutter-md">
                <div class="text-center q-mb-xl">
                <p class="text-h5  text-primary q-mb-none">
                    Выберите тип работы:
                </p>
            </div>

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

                <div class="text-center q-mb-xl">
                <p class="text-h5 text-primary q-mb-none">
                    Напишите тему работы, по которой будет создана структура:
                </p>
            </div>

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

                <div class="row justify-center q-mt-lg">
                    <q-btn
                        label="Создать работу"
                        type="submit"
                        color="primary"
                        size="lg"
                        :loading="isLoading"
                        class="q-px-xl q-py-md"
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
import { Head } from '@inertiajs/vue3';
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
        
        // После успешного создания переходим к просмотру документа с автозагрузкой
        if (response && response.document && response.document.id) {
            // Используем redirect_url из ответа или формируем URL с autoload=1
            const redirectUrl = response.redirect_url || route('documents.show', {
                document: response.document.id,
                autoload: 1
            });
            router.visit(redirectUrl);
        } else {
            throw new Error('Неверный формат ответа от сервера');
        }
    } catch (err) {
        error.value = err.message || 'Произошла ошибка при создании документа';
        console.error('Ошибка при создании документа:', err);
    }
};
</script> 