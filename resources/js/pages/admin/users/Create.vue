<template>
    <div class="q-pa-md">
        <!-- Заголовок -->
        <div class="text-h4 q-mb-md">
            <q-icon name="person_add" class="q-mr-sm" />
            Создать пользователя
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
                                label="Пароль *"
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
                                min="0"
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

                    <div class="text-subtitle2 q-mb-sm">Telegram (опционально)</div>
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
                            label="Создать пользователя"
                            :loading="processing"
                            no-caps
                        />
                        <q-btn
                            color="grey"
                            icon="arrow_back"
                            label="Назад к списку"
                            @click="$inertia.visit(route('admin.users.index'))"
                            flat
                            no-caps
                        />
                    </div>
                </q-form>
            </q-card-section>
        </q-card>
    </div>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useQuasar } from 'quasar'

const $q = useQuasar()

// Пропсы от контроллера
const props = defineProps({
    roles: Array
})

// Опции для селекта ролей
const roleOptions = computed(() => 
    props.roles.map(role => ({ label: role.label, value: role.value }))
)

// Форма
const { data: form, post, processing, errors } = useForm({
    name: '',
    email: '',
    password: '',
    role_id: 0, // По умолчанию обычный пользователь
    status: 1,
    balance_rub: 0,
    telegram_id: '',
    telegram_username: ''
})

// Отправка формы
const submit = () => {
    post(route('admin.users.store'), {
        onSuccess: () => {
            $q.notify({
                type: 'positive',
                message: 'Пользователь успешно создан'
            })
        },
        onError: () => {
            $q.notify({
                type: 'negative',
                message: 'Ошибка при создании пользователя'
            })
        }
    })
}
</script> 