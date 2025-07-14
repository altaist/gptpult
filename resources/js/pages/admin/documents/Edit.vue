<template>
    <div class="q-pa-md">
        <!-- Заголовок -->
        <div class="text-h4 q-mb-md">
            <q-icon name="edit" class="q-mr-sm" />
            Редактировать документ: {{ document.title }}
        </div>

        <!-- Форма -->
        <q-card class="q-mb-md" style="max-width: 1000px;">
            <q-card-section>
                <q-form @submit="submit" class="q-gutter-md">
                    <div class="row q-gutter-md">
                        <div class="col-md-8 col-xs-12">
                            <q-input
                                v-model="form.title"
                                label="Название документа *"
                                outlined
                                :error="!!errors.title"
                                :error-message="errors.title"
                            />
                        </div>

                        <div class="col-md-4 col-xs-12">
                            <q-select
                                v-model="form.status"
                                :options="statusOptions"
                                label="Статус *"
                                outlined
                                emit-value
                                map-options
                                :error="!!errors.status"
                                :error-message="errors.status"
                            />
                        </div>
                    </div>

                    <div class="row q-gutter-md">
                        <div class="col-md-6 col-xs-12">
                            <q-select
                                v-model="form.user_id"
                                :options="userOptions"
                                label="Пользователь *"
                                outlined
                                emit-value
                                map-options
                                option-label="label"
                                option-value="value"
                                use-input
                                @filter="filterUsers"
                                :error="!!errors.user_id"
                                :error-message="errors.user_id"
                            />
                        </div>

                        <div class="col-md-6 col-xs-12">
                            <q-select
                                v-model="form.document_type_id"
                                :options="documentTypeOptions"
                                label="Тип документа *"
                                outlined
                                emit-value
                                map-options
                                :error="!!errors.document_type_id"
                                :error-message="errors.document_type_id"
                            />
                        </div>
                    </div>

                    <div class="row q-gutter-md">
                        <div class="col-md-4 col-xs-12">
                            <q-input
                                v-model="form.pages_num"
                                label="Количество страниц"
                                type="number"
                                min="1"
                                outlined
                                :error="!!errors.pages_num"
                                :error-message="errors.pages_num"
                            />
                        </div>

                        <div class="col-md-8 col-xs-12">
                            <q-input
                                v-model="form.thread_id"
                                label="Thread ID"
                                outlined
                                :error="!!errors.thread_id"
                                :error-message="errors.thread_id"
                            />
                        </div>
                    </div>

                    <q-separator class="q-my-md" />

                    <div class="text-subtitle2 q-mb-sm">Структура документа (JSON)</div>
                    <q-input
                        v-model="structureJson"
                        label="Структура"
                        type="textarea"
                        rows="8"
                        outlined
                        :error="!!structureError"
                        :error-message="structureError"
                        @update:model-value="updateStructure"
                    />

                    <div class="text-subtitle2 q-mb-sm q-mt-md">Настройки GPT (JSON)</div>
                    <q-input
                        v-model="gptSettingsJson"
                        label="Настройки GPT"
                        type="textarea"
                        rows="6"
                        outlined
                        :error="!!gptSettingsError"
                        :error-message="gptSettingsError"
                        @update:model-value="updateGptSettings"
                    />

                    <!-- Кнопки -->
                    <div class="row q-gutter-md q-mt-md">
                        <q-btn
                            type="submit"
                            color="positive"
                            icon="save"
                            label="Сохранить изменения"
                            :loading="processing"
                            no-caps
                        />
                        <q-btn
                            color="primary"
                            icon="visibility"
                            label="Просмотр"
                            @click="$inertia.visit(route('admin.documents.show', document.id))"
                            flat
                            no-caps
                        />
                        <q-btn
                            color="grey"
                            icon="arrow_back"
                            label="К списку"
                            @click="$inertia.visit(route('admin.documents.index'))"
                            flat
                            no-caps
                        />
                    </div>
                </q-form>
            </q-card-section>
        </q-card>

        <!-- Дополнительная информация -->
        <q-card>
            <q-card-section>
                <div class="text-h6 q-mb-md">Дополнительная информация</div>
                
                <div class="row q-gutter-md">
                    <div class="col-md-6 col-xs-12">
                        <q-list>
                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>ID документа</q-item-label>
                                    <q-item-label>{{ document.id }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Текущий пользователь</q-item-label>
                                    <q-item-label>
                                        {{ document.user?.name }}
                                        <div class="text-caption text-grey-6">{{ document.user?.email }}</div>
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Дата создания</q-item-label>
                                    <q-item-label>{{ formatDate(document.created_at) }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </div>

                    <div class="col-md-6 col-xs-12">
                        <q-list>
                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Текущий статус</q-item-label>
                                    <q-item-label>
                                        <q-badge
                                            :color="getStatusColor(document.status)"
                                            :label="getStatusLabel(document.status)"
                                        />
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Тип документа</q-item-label>
                                    <q-item-label>{{ document.document_type?.title || 'Неизвестно' }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Последнее обновление</q-item-label>
                                    <q-item-label>{{ formatDate(document.updated_at) }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </div>
                </div>

                <!-- Предупреждение -->
                <q-banner class="bg-warning text-dark q-mt-md" dense>
                    <template #avatar>
                        <q-icon name="warning" />
                    </template>
                    <strong>Внимание:</strong> Изменение структуры или настроек GPT может повлиять на генерацию документа. 
                    Убедитесь, что JSON корректный, иначе сохранение не удастся.
                </q-banner>
            </q-card-section>
        </q-card>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

const $q = useQuasar()

// Пропсы от контроллера
const props = defineProps({
    document: Object,
    statuses: Array,
    document_types: Array,
    users: Array
})

// Реактивные данные для JSON
const structureJson = ref(JSON.stringify(props.document.structure, null, 2))
const gptSettingsJson = ref(JSON.stringify(props.document.gpt_settings, null, 2))
const structureError = ref('')
const gptSettingsError = ref('')

// Фильтруемые пользователи
const filteredUsers = ref(props.users)

// Опции для селектов
const statusOptions = computed(() => 
    props.statuses.map(status => ({ label: status.label, value: status.value }))
)

const documentTypeOptions = computed(() => 
    props.document_types.map(type => ({ label: type.title, value: type.id }))
)

const userOptions = computed(() => 
    filteredUsers.value.map(user => ({ 
        label: `${user.name} (${user.email})`, 
        value: user.id 
    }))
)

// Форма с предзаполненными данными
const { data: form, patch, processing, errors } = useForm({
    title: props.document.title,
    status: props.document.status,
    user_id: props.document.user_id,
    document_type_id: props.document.document_type_id,
    pages_num: props.document.pages_num,
    structure: props.document.structure,
    gpt_settings: props.document.gpt_settings,
    thread_id: props.document.thread_id || ''
})

// Методы
const filterUsers = (val, update) => {
    update(() => {
        if (val === '') {
            filteredUsers.value = props.users
        } else {
            const needle = val.toLowerCase()
            filteredUsers.value = props.users.filter(
                user => user.name.toLowerCase().indexOf(needle) > -1 ||
                        user.email.toLowerCase().indexOf(needle) > -1
            )
        }
    })
}

const updateStructure = (value) => {
    structureError.value = ''
    try {
        const parsed = JSON.parse(value)
        form.structure = parsed
    } catch (e) {
        structureError.value = 'Неверный JSON формат'
    }
}

const updateGptSettings = (value) => {
    gptSettingsError.value = ''
    try {
        const parsed = JSON.parse(value)
        form.gpt_settings = parsed
    } catch (e) {
        gptSettingsError.value = 'Неверный JSON формат'
    }
}

// Отправка формы
const submit = () => {
    // Проверяем JSON перед отправкой
    if (structureError.value || gptSettingsError.value) {
        $q.notify({
            type: 'negative',
            message: 'Исправьте ошибки в JSON полях'
        })
        return
    }

    patch(route('admin.documents.update', props.document.id), {
        onSuccess: () => {
            $q.notify({
                type: 'positive',
                message: 'Документ успешно обновлен'
            })
        },
        onError: () => {
            $q.notify({
                type: 'negative',
                message: 'Ошибка при обновлении документа'
            })
        }
    })
}

// Вспомогательные функции
const getStatusColor = (status) => {
    const colors = {
        'draft': 'grey',
        'pre_generating': 'primary',
        'pre_generated': 'positive',
        'pre_generation_failed': 'negative',
        'full_generating': 'secondary',
        'full_generated': 'green',
        'full_generation_failed': 'red',
        'in_review': 'warning',
        'approved': 'green-10',
        'rejected': 'red-8'
    }
    return colors[status] || 'grey'
}

const getStatusLabel = (status) => {
    const labels = {
        'draft': 'Черновик',
        'pre_generating': 'Генерация структуры',
        'pre_generated': 'Структура готова',
        'pre_generation_failed': 'Ошибка структуры',
        'full_generating': 'Генерация содержимого',
        'full_generated': 'Готов',
        'full_generation_failed': 'Ошибка генерации',
        'in_review': 'На проверке',
        'approved': 'Утвержден',
        'rejected': 'Отклонен'
    }
    return labels[status] || status
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script> 