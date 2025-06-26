<template>
  <div class="min-h-screen bg-gradient-to-br from-primary/5 to-accent/5 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
      <div class="bg-white rounded-lg shadow-xl p-8">
        <!-- Заголовок -->
        <div class="text-center mb-8">
          <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
            <q-icon name="telegram" color="white" size="32px" />
          </div>
          <h1 class="text-2xl font-bold text-grey-9 mb-2">
            Создание аккаунта
          </h1>
          <p class="text-grey-6">
            Быстрая регистрация через Telegram
          </p>
        </div>

        <!-- Информация о пользователе -->
        <div class="bg-grey-1 rounded-lg p-4 mb-6">
          <div class="flex items-center space-x-3">
            <q-icon name="account_circle" color="primary" size="24px" />
            <div>
              <div class="text-sm text-grey-6">Telegram</div>
              <div class="font-medium">
                {{ telegramUsername ? `@${telegramUsername}` : `ID: ${telegramId}` }}
              </div>
            </div>
          </div>
        </div>

        <!-- Форма -->
        <form @submit.prevent="register">
          <div class="space-y-6">
            <!-- Имя -->
            <div>
              <label class="block text-sm font-medium text-grey-7 mb-2">
                Ваше имя
              </label>
              <q-input
                v-model="form.name"
                outlined
                placeholder="Введите ваше имя"
                :error="!!errors.name"
                :error-message="errors.name"
                class="w-full"
              />
            </div>

            <!-- Согласие на обработку данных -->
            <div>
              <q-checkbox
                v-model="form.accept_terms"
                color="primary"
                :error="!!errors.accept_terms"
              >
                <span class="text-sm text-grey-7">
                  Я согласен с 
                  <a href="/terms" target="_blank" class="text-primary hover:underline">
                    условиями использования
                  </a>
                  и 
                  <a href="/privacy" target="_blank" class="text-primary hover:underline">
                    политикой конфиденциальности
                  </a>
                </span>
              </q-checkbox>
              <div v-if="errors.accept_terms" class="text-negative text-xs mt-1">
                {{ errors.accept_terms }}
              </div>
            </div>

            <!-- Кнопки -->
            <div class="space-y-3">
              <q-btn
                type="submit"
                color="primary"
                unelevated
                class="w-full"
                size="lg"
                :loading="loading"
                :disable="!form.accept_terms || !form.name"
              >
                <q-icon name="person_add" class="mr-2" />
                Создать аккаунт
              </q-btn>

              <q-btn
                color="grey-6"
                flat
                class="w-full"
                size="md"
                @click="goBack"
              >
                Отмена
              </q-btn>
            </div>
          </div>
        </form>

        <!-- Преимущества -->
        <div class="mt-8 pt-6 border-t border-grey-3">
          <h3 class="text-sm font-medium text-grey-7 mb-3">
            Что вы получите:
          </h3>
          <div class="space-y-2">
            <div class="flex items-center text-sm text-grey-6">
              <q-icon name="check_circle" color="positive" size="16px" class="mr-2" />
              Уведомления о готовности документов
            </div>
            <div class="flex items-center text-sm text-grey-6">
              <q-icon name="check_circle" color="positive" size="16px" class="mr-2" />
              Быстрый доступ к личному кабинету
            </div>
            <div class="flex items-center text-sm text-grey-6">
              <q-icon name="check_circle" color="positive" size="16px" class="mr-2" />
              Контроль баланса и операций
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { Notify } from 'quasar'

// Props
const props = defineProps({
  telegram_id: String,
  telegram_username: String,
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Reactive data
const loading = ref(false)
const form = reactive({
  name: '',
  telegram_id: props.telegram_id,
  telegram_username: props.telegram_username,
  accept_terms: false
})

// Methods
const register = async () => {
  if (!form.accept_terms || !form.name) {
    return
  }

  loading.value = true

  try {
    await router.post('/auto-register', form)
  } catch (error) {
    console.error('Registration error:', error)
    Notify.create({
      type: 'negative',
      message: 'Произошла ошибка при регистрации',
      position: 'top'
    })
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  window.history.back()
}
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style> 