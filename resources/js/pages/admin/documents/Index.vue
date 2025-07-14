<template>
    <div class="q-pa-md">
        <!-- Заголовок -->
        <div class="row items-center q-mb-md">
            <div class="col">
                <div class="text-h4">
                    <q-icon name="description" class="q-mr-sm" />
                    Управление документами
                </div>
            </div>
        </div>

        <!-- Фильтры и поиск -->
        <q-card class="q-mb-md">
            <q-card-section>
                <div class="row q-gutter-md">
                    <div class="col-md-3 col-xs-12">
                        <q-input
                            v-model="searchForm.search"
                            label="Поиск по названию или ID"
                            outlined
                            dense
                            clearable
                            @keyup.enter="applyFilters"
                        >
                            <template #prepend>
                                <q-icon name="search" />
                            </template>
                        </q-input>
                    </div>

                    <div class="col-md-2 col-xs-12">
                        <q-select
                            v-model="searchForm.status"
                            :options="statusOptions"
                            label="Статус"
                            outlined
                            dense
                            clearable
                            emit-value
                            map-options
                        />
                    </div>

                    <div class="col-md-2 col-xs-12">
                        <q-select
                            v-model="searchForm.user_id"
                            :options="userOptions"
                            label="Пользователь"
                            outlined
                            dense
                            clearable
                            emit-value
                            map-options
                        />
                    </div>

                    <div class="col-md-2 col-xs-12">
                        <q-select
                            v-model="searchForm.document_type_id"
                            :options="documentTypeOptions"
                            label="Тип документа"
                            outlined
                            dense
                            clearable
                            emit-value
                            map-options
                        />
                    </div>

                    <div class="col-auto">
                        <q-btn
                            color="primary"
                            icon="search"
                            label="Поиск"
                            @click="applyFilters"
                            no-caps
                        />
                        <q-btn
                            color="grey"
                            icon="clear"
                            label="Сбросить"
                            @click="clearFilters"
                            flat
                            no-caps
                            class="q-ml-sm"
                        />
                    </div>
                </div>
            </q-card-section>
        </q-card>

        <!-- Таблица документов -->
        <q-card>
            <q-table
                :rows="documents.data"
                :columns="columns"
                row-key="id"
                :loading="loading"
                flat
                bordered
            >
                <template #body-cell-title="props">
                    <q-td :props="props">
                        <div class="text-weight-medium">{{ props.value }}</div>
                        <div class="text-caption text-grey-6">ID: {{ props.row.id }}</div>
                    </q-td>
                </template>

                <template #body-cell-user="props">
                    <q-td :props="props">
                        <div>{{ props.row.user?.name }}</div>
                        <div class="text-caption text-grey-6">{{ props.row.user?.email }}</div>
                    </q-td>
                </template>

                <template #body-cell-document_type="props">
                    <q-td :props="props">
                        {{ props.row.document_type?.title || 'Неизвестно' }}
                    </q-td>
                </template>

                <template #body-cell-status="props">
                    <q-td :props="props">
                        <q-badge
                            :color="getStatusColor(props.value)"
                            :label="getStatusLabel(props.value)"
                        />
                    </q-td>
                </template>

                <template #body-cell-pages_num="props">
                    <q-td :props="props">
                        <q-chip
                            color="info"
                            text-color="white"
                            :label="props.value || 'не указано'"
                            dense
                        />
                    </q-td>
                </template>

                <template #body-cell-created_at="props">
                    <q-td :props="props">
                        {{ formatDate(props.value) }}
                    </q-td>
                </template>

                <template #body-cell-actions="props">
                    <q-td :props="props">
                        <q-btn-group flat>
                            <q-btn
                                color="primary"
                                icon="visibility"
                                size="sm"
                                @click="$inertia.visit(route('admin.documents.show', props.row.id))"
                                dense
                                flat
                            >
                                <q-tooltip>Просмотр</q-tooltip>
                            </q-btn>
                            <q-btn
                                color="warning"
                                icon="edit"
                                size="sm"
                                @click="$inertia.visit(route('admin.documents.edit', props.row.id))"
                                dense
                                flat
                            >
                                <q-tooltip>Редактировать</q-tooltip>
                            </q-btn>
                            <q-btn
                                color="secondary"
                                icon="swap_horiz"
                                size="sm"
                                @click="showTransferDialog(props.row)"
                                dense
                                flat
                            >
                                <q-tooltip>Перенести к другому пользователю</q-tooltip>
                            </q-btn>
                            <q-btn
                                color="info"
                                icon="update"
                                size="sm"
                                @click="showStatusDialog(props.row)"
                                dense
                                flat
                            >
                                <q-tooltip>Изменить статус</q-tooltip>
                            </q-btn>
                            <q-btn
                                color="negative"
                                icon="delete"
                                size="sm"
                                @click="deleteDocument(props.row)"
                                dense
                                flat
                            >
                                <q-tooltip>Удалить</q-tooltip>
                            </q-btn>
                        </q-btn-group>
                    </q-td>
                </template>
            </q-table>

            <!-- Пагинация -->
            <div class="q-pa-md flex flex-center" v-if="documents.last_page > 1">
                <q-pagination
                    v-model="currentPage"
                    :max="documents.last_page"
                    :max-pages="6"
                    direction-links
                    boundary-links
                    @update:model-value="changePage"
                />
            </div>
        </q-card>

        <!-- Диалоги -->
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
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

const $q = useQuasar()

// Пропсы от контроллера
const props = defineProps({
    documents: Object,
    filters: Object,
    statuses: Array,
    documentTypes: Array,
    users: Array
})

// Реактивные данные
const loading = ref(false)
const currentPage = ref(props.documents.current_page)

const searchForm = reactive({
    search: props.filters.search || '',
    status: props.filters.status || null,
    user_id: props.filters.user_id || null,
    document_type_id: props.filters.document_type_id || null
})

// Диалоги
const statusDialog = reactive({
    show: false,
    document: null,
    status: null,
    processing: false
})

const transferDialog = reactive({
    show: false,
    document: null,
    user_id: null,
    processing: false
})

// Опции для селектов
const statusOptions = computed(() => [
    { label: 'Все статусы', value: null },
    ...props.statuses.map(status => ({ label: status.label, value: status.value }))
])

const userOptions = computed(() => [
    { label: 'Все пользователи', value: null },
    ...props.users.map(user => ({ label: `${user.name} (${user.email})`, value: user.id }))
])

const documentTypeOptions = computed(() => [
    { label: 'Все типы', value: null },
    ...props.documentTypes.map(type => ({ label: type.title, value: type.id }))
])

// Колонки таблицы
const columns = [
    {
        name: 'title',
        label: 'Название',
        field: 'title',
        align: 'left',
        sortable: true
    },
    {
        name: 'user',
        label: 'Пользователь',
        align: 'left'
    },
    {
        name: 'document_type',
        label: 'Тип документа',
        align: 'left'
    },
    {
        name: 'status',
        label: 'Статус',
        field: 'status',
        align: 'center'
    },
    {
        name: 'pages_num',
        label: 'Страниц',
        field: 'pages_num',
        align: 'center'
    },
    {
        name: 'created_at',
        label: 'Создан',
        field: 'created_at',
        align: 'center'
    },
    {
        name: 'actions',
        label: 'Действия',
        align: 'center'
    }
]

// Методы
const applyFilters = () => {
    loading.value = true
    router.get(route('admin.documents.index'), searchForm, {
        preserveState: true,
        onFinish: () => loading.value = false
    })
}

const clearFilters = () => {
    searchForm.search = ''
    searchForm.status = null
    searchForm.user_id = null
    searchForm.document_type_id = null
    applyFilters()
}

const changePage = (page) => {
    loading.value = true
    router.get(route('admin.documents.index'), { 
        ...searchForm, 
        page 
    }, {
        preserveState: true,
        onFinish: () => loading.value = false
    })
}

const showStatusDialog = (document) => {
    statusDialog.document = document
    statusDialog.status = document.status
    statusDialog.show = true
}

const updateStatus = () => {
    statusDialog.processing = true
    router.patch(route('admin.documents.update-status', statusDialog.document.id), {
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

const showTransferDialog = (document) => {
    transferDialog.document = document
    transferDialog.user_id = null
    transferDialog.show = true
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
    router.patch(route('admin.documents.transfer', transferDialog.document.id), {
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

const deleteDocument = (document) => {
    $q.dialog({
        title: 'Подтверждение удаления',
        message: `Вы уверены, что хотите удалить документ "${document.title}"?`,
        cancel: true,
        persistent: true
    }).onOk(() => {
        router.delete(route('admin.documents.destroy', document.id), {
            onSuccess: () => {
                $q.notify({
                    type: 'positive',
                    message: 'Документ успешно удален'
                })
            },
            onError: () => {
                $q.notify({
                    type: 'negative',
                    message: 'Ошибка при удалении документа'
                })
            }
        })
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