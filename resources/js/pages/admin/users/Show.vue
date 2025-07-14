<template>
    <div class="q-pa-md">
        <!-- Заголовок -->
        <div class="row items-center q-mb-md">
            <div class="col">
                <div class="text-h4">
                    <q-icon name="person" class="q-mr-sm" />
                    {{ user.name }}
                </div>
                <div class="text-subtitle1 text-grey-6">
                    {{ user.email }}
                </div>
            </div>
            <div class="col-auto">
                <q-btn-group>
                    <q-btn
                        color="warning"
                        icon="edit"
                        label="Редактировать"
                        @click="$inertia.visit(route('admin.users.edit', user.id))"
                        no-caps
                    />
                    <q-btn
                        color="grey"
                        icon="arrow_back"
                        label="К списку"
                        @click="$inertia.visit(route('admin.users.index'))"
                        flat
                        no-caps
                    />
                </q-btn-group>
            </div>
        </div>

        <div class="row q-gutter-md">
            <!-- Основная информация -->
            <div class="col-md-6 col-xs-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Основная информация</div>
                        
                        <q-list>
                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>ID</q-item-label>
                                    <q-item-label>{{ user.id }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Роль</q-item-label>
                                    <q-item-label>
                                        <q-badge
                                            :color="user.role_id === 1 ? 'negative' : 'positive'"
                                            :label="user.role_id === 1 ? 'Администратор' : 'Пользователь'"
                                        />
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Статус</q-item-label>
                                    <q-item-label>{{ user.status || 'Не указан' }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Баланс</q-item-label>
                                    <q-item-label class="text-weight-medium">
                                        {{ formatCurrency(user.balance_rub) }}
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Дата регистрации</q-item-label>
                                    <q-item-label>{{ formatDate(user.created_at) }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section>
                                    <q-item-label overline>Последнее обновление</q-item-label>
                                    <q-item-label>{{ formatDate(user.updated_at) }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-card-section>
                </q-card>
            </div>

            <!-- Статистика -->
            <div class="col-md-6 col-xs-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Статистика</div>
                        
                        <div class="row q-gutter-md">
                            <div class="col-6">
                                <q-card class="bg-primary text-white">
                                    <q-card-section class="text-center">
                                        <div class="text-h5">{{ statistics.documents_count }}</div>
                                        <div class="text-caption">Документов</div>
                                    </q-card-section>
                                </q-card>
                            </div>

                            <div class="col-6">
                                <q-card class="bg-secondary text-white">
                                    <q-card-section class="text-center">
                                        <div class="text-h5">{{ statistics.orders_count }}</div>
                                        <div class="text-caption">Заказов</div>
                                    </q-card-section>
                                </q-card>
                            </div>

                            <div class="col-6">
                                <q-card class="bg-positive text-white">
                                    <q-card-section class="text-center">
                                        <div class="text-h6">{{ formatCurrency(statistics.payments_sum) }}</div>
                                        <div class="text-caption">Всего платежей</div>
                                    </q-card-section>
                                </q-card>
                            </div>

                            <div class="col-6">
                                <q-card class="bg-warning text-white">
                                    <q-card-section class="text-center">
                                        <div class="text-h6">{{ formatCurrency(statistics.balance) }}</div>
                                        <div class="text-caption">Текущий баланс</div>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </div>
                    </q-card-section>
                </q-card>
            </div>

            <!-- Telegram информация -->
            <div class="col-md-6 col-xs-12" v-if="user.telegram_id || user.telegram_username">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">
                            <q-icon name="telegram" class="q-mr-sm" />
                            Telegram
                        </div>
                        
                        <q-list>
                            <q-item v-if="user.telegram_id">
                                <q-item-section>
                                    <q-item-label overline>Telegram ID</q-item-label>
                                    <q-item-label>{{ user.telegram_id }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item v-if="user.telegram_username">
                                <q-item-section>
                                    <q-item-label overline>Telegram Username</q-item-label>
                                    <q-item-label>@{{ user.telegram_username }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item v-if="user.telegram_linked_at">
                                <q-item-section>
                                    <q-item-label overline>Дата привязки</q-item-label>
                                    <q-item-label>{{ formatDate(user.telegram_linked_at) }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-card-section>
                </q-card>
            </div>

            <!-- Последние документы -->
            <div class="col-12" v-if="user.documents && user.documents.length > 0">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Последние документы</div>
                        
                        <q-table
                            :rows="user.documents"
                            :columns="documentColumns"
                            row-key="id"
                            flat
                            bordered
                            :pagination="{ rowsPerPage: 5 }"
                        >
                            <template #body-cell-status="props">
                                <q-td :props="props">
                                    <q-badge
                                        :color="getStatusColor(props.value)"
                                        :label="getStatusLabel(props.value)"
                                    />
                                </q-td>
                            </template>

                            <template #body-cell-document_type="props">
                                <q-td :props="props">
                                    {{ props.row.document_type?.title || 'Неизвестно' }}
                                </q-td>
                            </template>

                            <template #body-cell-created_at="props">
                                <q-td :props="props">
                                    {{ formatDate(props.value) }}
                                </q-td>
                            </template>

                            <template #body-cell-actions="props">
                                <q-td :props="props">
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
                                </q-td>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>
            </div>
        </div>
    </div>
</template>

<script setup>
// Пропсы от контроллера
const props = defineProps({
    user: Object,
    statistics: Object
})

// Колонки таблицы документов
const documentColumns = [
    {
        name: 'id',
        label: 'ID',
        field: 'id',
        align: 'left'
    },
    {
        name: 'title',
        label: 'Название',
        field: 'title',
        align: 'left'
    },
    {
        name: 'document_type',
        label: 'Тип',
        align: 'left'
    },
    {
        name: 'status',
        label: 'Статус',
        field: 'status',
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

// Методы для получения цвета и лейбла статуса
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