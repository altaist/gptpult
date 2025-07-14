<template>
    <div class="q-pa-md">
        <!-- Заголовок -->
        <div class="text-h4 q-mb-md">
            <q-icon name="edit" class="q-mr-sm" />
            Редактировать пользователя: {{ user.name }}
        </div>

        <!-- Форма -->
        <q-card class="q-mb-md" style="max-width: 800px;">
            <q-card-section>
                <q-form @submit="submit" class="q-gutter-md">
                    <div class="row q-gutter-md">
                        <div class="col-md-5 col-xs-12">
                            <q-input
                                v-model="form.name"
                                label="Имя пользователя *"
                                outlined
                                :error="!!errors.name"
                                :error-message="errors.name"
                            />
                        </div>

                        <div class="col-md-5 col-xs-12">
                            <q-input
                                v-model="form.email"
                                label="Email *"
                                type="email"
                                outlined
                                :error="!!errors.email"
                                :error-message="errors.email"
                            />
                        </div>
                    </div>

                    <div class="row q-gutter-md">
                        <div class="col-md-5 col-xs-12">
                            <q-input
                                v-model="form.password"
                                label="Новый пароль (оставьте пустым, чтобы не менять)"
                                type="password"
                                outlined
                                :error="!!errors.password"
                                :error-message="errors.password"
                            />
                        </div>

                        <div class="col-md-5 col-xs-12">
                            <q-select
                                v-model="form.role_id"
                                :options="roleOptions"
                                label="Роль *"
                                outlined
                                emit-value
                                map-options
                                :error="!!errors.role_id"
                                :error-message="errors.role_id"
                            />
                        </div>
                    </div>

                    <div class="row q-gutter-md">
                        <div class="col-md-5 col-xs-12">
                            <q-input
                                v-model="form.balance_rub"
                                label="Баланс (₽)"
                                type="number"
                                step="0.01"
                                outlined
                                :error="!!errors.balance_rub"
                                :error-message="errors.balance_rub"
                            />
                        </div>

                        <div class="col-md-5 col-xs-12">
                            <q-input
                                v-model="form.status"
                                label="Статус"
                                type="number"
                                outlined
                                :error="!!errors.status"
                                :error-message="errors.status"
                            />
                        </div>
                    </div>

                    <q-separator class="q-my-md" />

                    <div class="text-subtitle2 q-mb-sm">Telegram</div>
                    <div class="row q-gutter-md">
                        <div class="col-md-5 col-xs-12">
                            <q-input
                                v-model="form.telegram_id"
                                label="Telegram ID"
                                outlined
                                :error="!!errors.telegram_id"
                                :error-message="errors.telegram_id"
                            />
                        </div>

                        <div class="col-md-5 col-xs-12">
                            <q-input
                                v-model="form.telegram_username"
                                label="Telegram Username"
                                outlined
                                :error="!!errors.telegram_username"
                                :error-message="errors.telegram_username"
                            />
                        </div>
                    </div>

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
                            @click="$inertia.visit(route('admin.users.show', user.id))"
                            flat
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
                                    <q-item-label overline>ID пользователя</q-item-label>
                                    <q-item-label>{{ user.id }}</q-item-label>
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
                    </div>

                    <div class="col-md-6 col-xs-12">
                        <q-list>
                            <q-item v-if="user.telegram_linked_at">
                                <q-item-section>
                                    <q-item-label overline>Telegram привязан</q-item-label>
                                    <q-item-label>{{ formatDate(user.telegram_linked_at) }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item v-if="user.privacy_consent_at">
                                <q-item-section>
                                    <q-item-label overline>Согласие на обработку данных</q-item-label>
                                    <q-item-label>{{ formatDate(user.privacy_consent_at) }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </div>
                </div>
            </q-card-section>
        </q-card>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

const $q = useQuasar()

// Пропсы от контроллера
const props = defineProps({
    user: Object,
    roles: Array
})

// Опции для селекта ролей
const roleOptions = computed(() => 
    props.roles.map(role => ({ label: role.label, value: role.value }))
)

// Форма с предзаполненными данными
const { data: form, patch, processing, errors } = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    password: '',
    role_id: props.user.role_id || 0,
    status: props.user.status || 1,
    balance_rub: props.user.balance_rub || 0,
    telegram_id: props.user.telegram_id || '',
    telegram_username: props.user.telegram_username || ''
})

// Отправка формы
const submit = () => {
    patch(route('admin.users.update', props.user.id), {
        onSuccess: () => {
            $q.notify({
                type: 'positive',
                message: 'Пользователь успешно обновлен'
            })
        },
        onError: () => {
            $q.notify({
                type: 'negative',
                message: 'Ошибка при обновлении пользователя'
            })
        }
    })
}

// Вспомогательные функции
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