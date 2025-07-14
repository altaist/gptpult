<template>
    <div class="q-pa-md">
        <div class="row q-gutter-md">
            <!-- Заголовок -->
            <div class="col-12">
                <div class="text-h4 q-mb-md">
                    <q-icon name="admin_panel_settings" class="q-mr-sm" />
                    Панель администратора
                </div>
            </div>

            <!-- Статистические карточки -->
            <div class="col-12">
                <div class="row q-gutter-md">
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <q-card class="bg-primary text-white">
                            <q-card-section>
                                <div class="text-h6">
                                    <q-icon name="people" class="q-mr-sm" />
                                    Пользователи
                                </div>
                                <div class="text-h4">{{ statistics.users_total }}</div>
                                <div class="text-caption">Всего пользователей</div>
                            </q-card-section>
                        </q-card>
                    </div>

                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <q-card class="bg-secondary text-white">
                            <q-card-section>
                                <div class="text-h6">
                                    <q-icon name="description" class="q-mr-sm" />
                                    Документы
                                </div>
                                <div class="text-h4">{{ statistics.documents_total }}</div>
                                <div class="text-caption">Всего документов</div>
                            </q-card-section>
                        </q-card>
                    </div>

                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <q-card class="bg-positive text-white">
                            <q-card-section>
                                <div class="text-h6">
                                    <q-icon name="check_circle" class="q-mr-sm" />
                                    Готовые документы
                                </div>
                                <div class="text-h4">{{ statistics.documents_completed }}</div>
                                <div class="text-caption">Полностью готовы</div>
                            </q-card-section>
                        </q-card>
                    </div>

                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <q-card class="bg-warning text-white">
                            <q-card-section>
                                <div class="text-h6">
                                    <q-icon name="sync" class="q-mr-sm" />
                                    В процессе
                                </div>
                                <div class="text-h4">{{ statistics.documents_processing }}</div>
                                <div class="text-caption">Генерируются</div>
                            </q-card-section>
                        </q-card>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="col-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Быстрые действия</div>
                        <div class="row q-gutter-md">
                            <q-btn
                                color="primary"
                                icon="people"
                                label="Управление пользователями"
                                @click="$inertia.visit(route('admin.users.index'))"
                                no-caps
                            />
                            <q-btn
                                color="secondary"
                                icon="description"
                                label="Управление документами"
                                @click="$inertia.visit(route('admin.documents.index'))"
                                no-caps
                            />
                            <q-btn
                                color="positive"
                                icon="person_add"
                                label="Создать пользователя"
                                @click="$inertia.visit(route('admin.users.create'))"
                                no-caps
                            />
                        </div>
                    </q-card-section>
                </q-card>
            </div>

            <!-- Последние пользователи -->
            <div class="col-md-6 col-xs-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Последние пользователи</div>
                        <q-list separator>
                            <q-item
                                v-for="user in recentUsers"
                                :key="user.id"
                                clickable
                                @click="$inertia.visit(route('admin.users.show', user.id))"
                            >
                                <q-item-section avatar>
                                    <q-icon name="person" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label>{{ user.name }}</q-item-label>
                                    <q-item-label caption>{{ user.email }}</q-item-label>
                                </q-item-section>
                                <q-item-section side>
                                    <q-badge
                                        :color="user.role_id === 1 ? 'negative' : 'positive'"
                                        :label="user.role_id === 1 ? 'Админ' : 'Пользователь'"
                                    />
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-card-section>
                </q-card>
            </div>

            <!-- Последние документы -->
            <div class="col-md-6 col-xs-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Последние документы</div>
                        <q-list separator>
                            <q-item
                                v-for="document in recentDocuments"
                                :key="document.id"
                                clickable
                                @click="$inertia.visit(route('admin.documents.show', document.id))"
                            >
                                <q-item-section avatar>
                                    <q-icon name="description" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label>{{ document.title }}</q-item-label>
                                    <q-item-label caption>{{ document.user?.name }}</q-item-label>
                                </q-item-section>
                                <q-item-section side>
                                    <q-badge
                                        :color="getStatusColor(document.status)"
                                        :label="getStatusLabel(document.status)"
                                    />
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-card-section>
                </q-card>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

// Пропсы от контроллера (статистика будет добавлена позже)
const props = defineProps({
    statistics: {
        type: Object,
        default: () => ({
            users_total: 0,
            documents_total: 0,
            documents_completed: 0,
            documents_processing: 0
        })
    },
    recentUsers: {
        type: Array,
        default: () => []
    },
    recentDocuments: {
        type: Array,
        default: () => []
    }
})

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
</script> 