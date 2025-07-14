<template>
    <div class="q-pa-md">
        <!-- Заголовок с админскими действиями -->
        <div class="row items-center q-mb-md">
            <div class="col">
                <div class="text-h4">
                    <q-icon name="description" class="q-mr-sm" />
                    {{ document.title }}
                </div>
                <div class="text-subtitle1 text-grey-6">
                    Документ #{{ document.id }} | 
                    Пользователь: {{ document.user?.name }} | 
                    {{ formatDate(document.created_at) }}
                </div>
            </div>
            <div class="col-auto">
                <q-btn-group>
                    <q-btn
                        color="warning"
                        icon="edit"
                        label="Редактировать"
                        @click="$inertia.visit(route('admin.documents.edit', document.id))"
                        no-caps
                    />
                    <q-btn
                        color="info"
                        icon="update"
                        label="Изменить статус"
                        @click="showStatusDialog"
                        no-caps
                    />
                    <q-btn
                        color="secondary"
                        icon="swap_horiz"
                        label="Перенести"
                        @click="showTransferDialog"
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
                </q-btn-group>
            </div>
        </div>

        <!-- Информация о документе -->
        <div class="row q-gutter-md q-mb-md">
            <div class="col-md-8 col-xs-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Основная информация</div>
                        
                        <div class="row q-gutter-md">
                            <div class="col-md-6 col-xs-12">
                                <q-list>
                                    <q-item>
                                        <q-item-section>
                                            <q-item-label overline>Статус</q-item-label>
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
                                            <q-item-label overline>Количество страниц</q-item-label>
                                            <q-item-label>{{ document.pages_num || 'Не указано' }}</q-item-label>
                                        </q-item-section>
                                    </q-item>
                                </q-list>
                            </div>

                            <div class="col-md-6 col-xs-12">
                                <q-list>
                                    <q-item>
                                        <q-item-section>
                                            <q-item-label overline>Пользователь</q-item-label>
                                            <q-item-label>
                                                {{ document.user?.name }}
                                                <div class="text-caption text-grey-6">{{ document.user?.email }}</div>
                                            </q-item-label>
                                        </q-item-section>
                                        <q-item-section side>
                                            <q-btn
                                                color="primary"
                                                icon="person"
                                                size="sm"
                                                @click="$inertia.visit(route('admin.users.show', document.user?.id))"
                                                dense
                                                flat
                                            >
                                                <q-tooltip>Просмотр пользователя</q-tooltip>
                                            </q-btn>
                                        </q-item-section>
                                    </q-item>

                                    <q-item>
                                        <q-item-section>
                                            <q-item-label overline>Thread ID</q-item-label>
                                            <q-item-label>{{ document.thread_id || 'Не указан' }}</q-item-label>
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
                        </div>
                    </q-card-section>
                </q-card>
            </div>

            <div class="col-md-4 col-xs-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Быстрые действия</div>
                        
                        <div class="column q-gutter-sm">
                            <q-btn
                                color="primary"
                                icon="open_in_new"
                                label="Открыть как пользователь"
                                @click="openAsUser"
                                no-caps
                                outline
                            />
                            
                            <q-btn
                                color="secondary"
                                icon="file_download"
                                label="Скачать файлы"
                                @click="downloadFiles"
                                no-caps
                                outline
                                :disable="!document.files || document.files.length === 0"
                            />
                        </div>
                    </q-card-section>
                </q-card>
            </div>
        </div>

        <!-- Содержимое документа (используем существующий компонент) -->
        <DocumentContentsView 
            :document="document"
            :readonly="true"
            admin-mode
        />

        <!-- Диалог изменения статуса -->
        <q-dialog v-model="statusDialog.show">
            <q-card style="min-width: 400px">
                <q-card-section>
                    <div class="text-h6">Изменить статус документа</div>
                </q-card-section>

                <q-card-section>
                    <q-select
                        v-model="statusDialog.status"
                        :options="statusOptions"
                        label="Новый статус"
                        outlined
                        emit-value
                        map-options
                    />
                </q-card-section>

                <q-card-actions align="right">
                    <q-btn flat label="Отмена" color="grey" v-close-popup />
                    <q-btn 
                        label="Сохранить" 
                        color="primary" 
                        @click="updateStatus"
                        :loading="statusDialog.processing"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>

        <!-- Диалог переноса документа -->
        <q-dialog v-model="transferDialog.show">
            <q-card style="min-width: 400px">
                <q-card-section>
                    <div class="text-h6">Перенести документ к другому пользователю</div>
                </q-card-section>

                <q-card-section>
                    <q-select
                        v-model="transferDialog.user_id"
                        :options="userOptions"
                        label="Выберите пользователя"
                        outlined
                        emit-value
                        map-options
                        option-label="name"
                        option-value="id"
                        use-input
                        @filter="filterUsers"
                    />
                </q-card-section>

                <q-card-actions align="right">
                    <q-btn flat label="Отмена" color="grey" v-close-popup />
                    <q-btn 
                        label="Перенести" 
                        color="primary" 
                        @click="transferDocument"
                        :loading="transferDialog.processing"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>
    </div>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'
import DocumentContentsView from '../../../modules/gpt/components/DocumentContentsView.vue'

const $q = useQuasar()

// Пропсы от контроллера
const props = defineProps({
    document: Object
})

// Диалоги
const statusDialog = reactive({
    show: false,
    status: props.document.status,
    processing: false
})

const transferDialog = reactive({
    show: false,
    user_id: null,
    processing: false
})

// Данные для селектов
const allUsers = ref([])
const userOptions = ref([])

// Опции статусов (захардкодим, так как они не меняются)
const statusOptions = [
    { label: 'Черновик', value: 'draft' },
    { label: 'Генерация структуры', value: 'pre_generating' },
    { label: 'Структура готова', value: 'pre_generated' },
    { label: 'Ошибка структуры', value: 'pre_generation_failed' },
    { label: 'Генерация содержимого', value: 'full_generating' },
    { label: 'Готов', value: 'full_generated' },
    { label: 'Ошибка генерации', value: 'full_generation_failed' },
    { label: 'На проверке', value: 'in_review' },
    { label: 'Утвержден', value: 'approved' },
    { label: 'Отклонен', value: 'rejected' }
]

// Методы
const showStatusDialog = () => {
    statusDialog.status = props.document.status
    statusDialog.show = true
}

const updateStatus = () => {
    statusDialog.processing = true
    router.patch(route('admin.documents.update-status', props.document.id), {
        status: statusDialog.status
    }, {
        onSuccess: () => {
            statusDialog.show = false
            $q.notify({
                type: 'positive',
                message: 'Статус документа изменен'
            })
        },
        onError: () => {
            $q.notify({
                type: 'negative',
                message: 'Ошибка при изменении статуса'
            })
        },
        onFinish: () => statusDialog.processing = false
    })
}

const showTransferDialog = async () => {
    // Загружаем список пользователей
    try {
        const response = await fetch('/admin/users?per_page=100')
        const data = await response.json()
        allUsers.value = data.users?.data || []
        userOptions.value = allUsers.value
        transferDialog.show = true
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Ошибка при загрузке списка пользователей'
        })
    }
}

const filterUsers = (val, update) => {
    update(() => {
        if (val === '') {
            userOptions.value = allUsers.value
        } else {
            const needle = val.toLowerCase()
            userOptions.value = allUsers.value.filter(
                user => user.name.toLowerCase().indexOf(needle) > -1 ||
                        user.email.toLowerCase().indexOf(needle) > -1
            )
        }
    })
}

const transferDocument = () => {
    if (!transferDialog.user_id) {
        $q.notify({
            type: 'negative',
            message: 'Выберите пользователя'
        })
        return
    }

    transferDialog.processing = true
    router.patch(route('admin.documents.transfer', props.document.id), {
        user_id: transferDialog.user_id
    }, {
        onSuccess: () => {
            transferDialog.show = false
            $q.notify({
                type: 'positive',
                message: 'Документ успешно перенесен'
            })
        },
        onError: () => {
            $q.notify({
                type: 'negative',
                message: 'Ошибка при переносе документа'
            })
        },
        onFinish: () => transferDialog.processing = false
    })
}

const openAsUser = () => {
    // Открываем документ в новой вкладке как обычный пользователь
    window.open(route('documents.show', props.document.id), '_blank')
}

const downloadFiles = () => {
    // Здесь можно добавить логику скачивания файлов
    $q.notify({
        type: 'info',
        message: 'Функция скачивания файлов будет реализована'
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