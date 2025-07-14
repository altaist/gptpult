<template>
    <div class="q-pa-md">
        <!-- Заголовок и кнопка создания -->
        <div class="row items-center q-mb-md">
            <div class="col">
                <div class="text-h4">
                    <q-icon name="people" class="q-mr-sm" />
                    Управление пользователями
                </div>
            </div>
            <div class="col-auto">
                <q-btn
                    color="positive"
                    icon="person_add"
                    label="Создать пользователя"
                    @click="$inertia.visit(route('admin.users.create'))"
                    no-caps
                />
            </div>
        </div>

        <!-- Фильтры и поиск -->
        <q-card class="q-mb-md">
            <q-card-section>
                <div class="row q-gutter-md">
                    <div class="col-md-4 col-xs-12">
                        <q-input
                            v-model="searchForm.search"
                            label="Поиск по имени, email или ID"
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
                            v-model="searchForm.role"
                            :options="roleOptions"
                            label="Роль"
                            outlined
                            dense
                            clearable
                            emit-value
                            map-options
                        />
                    </div>

                    <div class="col-md-2 col-xs-12">
                        <q-input
                            v-model="searchForm.status"
                            label="Статус"
                            outlined
                            dense
                            clearable
                            type="number"
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

        <!-- Таблица пользователей -->
        <q-card>
            <q-table
                :rows="users.data"
                :columns="columns"
                row-key="id"
                :loading="loading"
                flat
                bordered
            >
                <template #body-cell-role_id="props">
                    <q-td :props="props">
                        <q-badge
                            :color="props.value === 1 ? 'negative' : 'positive'"
                            :label="props.value === 1 ? 'Администратор' : 'Пользователь'"
                        />
                    </q-td>
                </template>

                <template #body-cell-documents_count="props">
                    <q-td :props="props">
                        <q-chip
                            color="primary"
                            text-color="white"
                            :label="props.value"
                            dense
                        />
                    </q-td>
                </template>

                <template #body-cell-orders_count="props">
                    <q-td :props="props">
                        <q-chip
                            color="secondary"
                            text-color="white"
                            :label="props.value"
                            dense
                        />
                    </q-td>
                </template>

                <template #body-cell-balance_rub="props">
                    <q-td :props="props">
                        <div class="text-weight-medium">
                            {{ formatCurrency(props.value) }}
                        </div>
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
                                @click="$inertia.visit(route('admin.users.show', props.row.id))"
                                dense
                                flat
                            >
                                <q-tooltip>Просмотр</q-tooltip>
                            </q-btn>
                            <q-btn
                                color="warning"
                                icon="edit"
                                size="sm"
                                @click="$inertia.visit(route('admin.users.edit', props.row.id))"
                                dense
                                flat
                            >
                                <q-tooltip>Редактировать</q-tooltip>
                            </q-btn>
                            <q-btn
                                color="negative"
                                icon="delete"
                                size="sm"
                                @click="deleteUser(props.row)"
                                dense
                                flat
                                :disable="props.row.id === $page.props.auth.user.id"
                            >
                                <q-tooltip>Удалить</q-tooltip>
                            </q-btn>
                        </q-btn-group>
                    </q-td>
                </template>
            </q-table>

            <!-- Пагинация -->
            <div class="q-pa-md flex flex-center" v-if="users.last_page > 1">
                <q-pagination
                    v-model="currentPage"
                    :max="users.last_page"
                    :max-pages="6"
                    direction-links
                    boundary-links
                    @update:model-value="changePage"
                />
            </div>
        </q-card>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

const $q = useQuasar()

// Пропсы от контроллера
const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array
})

// Реактивные данные
const loading = ref(false)
const currentPage = ref(props.users.current_page)

const searchForm = reactive({
    search: props.filters.search || '',
    role: props.filters.role || null,
    status: props.filters.status || null
})

// Опции для селекта ролей
const roleOptions = computed(() => [
    { label: 'Все роли', value: null },
    ...props.roles.map(role => ({ label: role.label, value: role.value }))
])

// Колонки таблицы
const columns = [
    {
        name: 'id',
        label: 'ID',
        field: 'id',
        sortable: true,
        align: 'left'
    },
    {
        name: 'name',
        label: 'Имя',
        field: 'name',
        sortable: true,
        align: 'left'
    },
    {
        name: 'email',
        label: 'Email',
        field: 'email',
        sortable: true,
        align: 'left'
    },
    {
        name: 'role_id',
        label: 'Роль',
        field: 'role_id',
        align: 'center'
    },
    {
        name: 'documents_count',
        label: 'Документы',
        field: 'documents_count',
        align: 'center'
    },
    {
        name: 'orders_count',
        label: 'Заказы',
        field: 'orders_count',
        align: 'center'
    },
    {
        name: 'balance_rub',
        label: 'Баланс',
        field: 'balance_rub',
        align: 'right'
    },
    {
        name: 'created_at',
        label: 'Дата создания',
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
    router.get(route('admin.users.index'), searchForm, {
        preserveState: true,
        onFinish: () => loading.value = false
    })
}

const clearFilters = () => {
    searchForm.search = ''
    searchForm.role = null
    searchForm.status = null
    applyFilters()
}

const changePage = (page) => {
    loading.value = true
    router.get(route('admin.users.index'), { 
        ...searchForm, 
        page 
    }, {
        preserveState: true,
        onFinish: () => loading.value = false
    })
}

const deleteUser = (user) => {
    $q.dialog({
        title: 'Подтверждение удаления',
        message: `Вы уверены, что хотите удалить пользователя "${user.name}"?`,
        cancel: true,
        persistent: true
    }).onOk(() => {
        router.delete(route('admin.users.destroy', user.id), {
            onSuccess: () => {
                $q.notify({
                    type: 'positive',
                    message: 'Пользователь успешно удален'
                })
            },
            onError: (errors) => {
                $q.notify({
                    type: 'negative',
                    message: errors.message || 'Ошибка при удалении пользователя'
                })
            }
        })
    })
}

// Вспомогательные функции
const formatCurrency = (amount) => {
    if (!amount) return '0 ₽'
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(amount)
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