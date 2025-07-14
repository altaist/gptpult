<template>
    <div class="q-pa-md">
        <!-- Заголовок -->
        <div class="row items-center q-mb-md">
            <div class="col">
                <div class="text-h4">
                    <q-icon name="settings_applications" class="q-mr-sm" />
                    Управление очередями
                </div>
            </div>
            <div class="col-auto">
                <q-btn
                    color="primary"
                    icon="refresh"
                    label="Обновить"
                    @click="refreshData"
                    :loading="loading.refresh"
                    no-caps
                />
            </div>
        </div>

        <!-- Статистика очередей -->
        <div class="row q-gutter-md q-mb-md">
            <div class="col-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">
                            <q-icon name="queue" class="q-mr-sm" />
                            Статистика очередей
                        </div>
                        <div class="row q-gutter-md">
                            <div 
                                v-for="(queue, key) in queueStats" 
                                :key="key"
                                class="col-md-6 col-xs-12"
                            >
                                <q-card class="bg-blue-1">
                                    <q-card-section>
                                        <div class="text-h6">{{ queue.display_name }}</div>
                                        <div class="q-mt-sm">
                                            <div class="row q-gutter-sm">
                                                <q-chip 
                                                    color="orange" 
                                                    text-color="white" 
                                                    :label="`Ожидают: ${queue.pending}`"
                                                />
                                                <q-chip 
                                                    color="blue" 
                                                    text-color="white" 
                                                    :label="`Обрабатываются: ${queue.processing}`"
                                                />
                                                <q-chip 
                                                    color="red" 
                                                    text-color="white" 
                                                    :label="`Провалены: ${queue.failed}`"
                                                />
                                            </div>
                                        </div>
                                        
                                        <!-- Действия с очередью -->
                                        <div class="q-mt-md">
                                            <q-btn-group flat>
                                                <q-btn
                                                    color="positive"
                                                    icon="play_arrow"
                                                    label="Запустить воркер"
                                                    @click="showStartWorkerDialog(queue.name)"
                                                    size="sm"
                                                    no-caps
                                                />
                                                <q-btn
                                                    color="primary"
                                                    icon="add_task"
                                                    label="Тестовая задача"
                                                    @click="addTestJob(queue.name)"
                                                    :loading="loading.addTestJob[queue.name]"
                                                    size="sm"
                                                    no-caps
                                                />
                                            </q-btn-group>
                                        </div>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </div>
                    </q-card-section>
                </q-card>
            </div>
        </div>

        <!-- Активные воркеры -->
        <div class="row q-gutter-md q-mb-md">
            <div class="col-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">
                            <q-icon name="computer" class="q-mr-sm" />
                            Активные воркеры
                        </div>
                        
                        <div v-if="workerStats.length === 0" class="text-center q-pa-md text-grey">
                            <q-icon name="info" size="md" class="q-mb-sm" />
                            <div>Нет активных воркеров</div>
                        </div>
                        
                        <q-table
                            v-else
                            :rows="workerStats"
                            :columns="workerColumns"
                            row-key="pid"
                            flat
                            bordered
                            hide-pagination
                        >
                            <template #body-cell-status="props">
                                <q-td :props="props">
                                    <q-chip 
                                        :color="props.value === 'running' ? 'positive' : 'negative'"
                                        text-color="white"
                                        :label="props.value === 'running' ? 'Запущен' : 'Остановлен'"
                                    />
                                </q-td>
                            </template>
                            
                            <template #body-cell-actions="props">
                                <q-td :props="props">
                                    <q-btn
                                        color="negative"
                                        icon="stop"
                                        label="Остановить"
                                        @click="stopWorker(props.row.pid)"
                                        :loading="loading.stopWorker[props.row.pid]"
                                        size="sm"
                                        no-caps
                                    />
                                </q-td>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>
            </div>
        </div>

        <!-- Последние задачи и проваленные задачи -->
        <div class="row q-gutter-md">
            <!-- Последние задачи -->
            <div class="col-md-6 col-xs-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">
                            <q-icon name="list" class="q-mr-sm" />
                            Последние задачи
                        </div>
                        
                        <div v-if="recentJobs.length === 0" class="text-center q-pa-md text-grey">
                            <q-icon name="info" size="md" class="q-mb-sm" />
                            <div>Нет задач в очереди</div>
                        </div>
                        
                        <q-table
                            v-else
                            :rows="recentJobs"
                            :columns="jobColumns"
                            row-key="id"
                            flat
                            bordered
                            hide-pagination
                        >
                            <template #body-cell-actions="props">
                                <q-td :props="props">
                                    <q-btn
                                        color="negative"
                                        icon="delete"
                                        @click="deleteJob(props.row.id)"
                                        :loading="loading.deleteJob[props.row.id]"
                                        size="sm"
                                        dense
                                        round
                                    >
                                        <q-tooltip>Удалить задачу</q-tooltip>
                                    </q-btn>
                                </q-td>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>
            </div>
            
            <!-- Проваленные задачи -->
            <div class="col-md-6 col-xs-12">
                <q-card>
                    <q-card-section>
                        <div class="row items-center q-mb-md">
                            <div class="col">
                                <div class="text-h6">
                                    <q-icon name="error" class="q-mr-sm" />
                                    Проваленные задачи
                                </div>
                            </div>
                            <div class="col-auto" v-if="failedJobs.length > 0">
                                <q-btn
                                    color="negative"
                                    icon="clear_all"
                                    label="Очистить все"
                                    @click="clearFailedJobs"
                                    :loading="loading.clearFailedJobs"
                                    size="sm"
                                    no-caps
                                />
                            </div>
                        </div>
                        
                        <div v-if="failedJobs.length === 0" class="text-center q-pa-md text-grey">
                            <q-icon name="check_circle" size="md" class="q-mb-sm" />
                            <div>Нет проваленных задач</div>
                        </div>
                        
                        <q-table
                            v-else
                            :rows="failedJobs"
                            :columns="failedJobColumns"
                            row-key="id"
                            flat
                            bordered
                            hide-pagination
                        >
                            <template #body-cell-exception="props">
                                <q-td :props="props">
                                    <div class="text-caption">{{ props.value }}</div>
                                </q-td>
                            </template>
                            
                            <template #body-cell-actions="props">
                                <q-td :props="props">
                                    <q-btn-group flat>
                                        <q-btn
                                            color="positive"
                                            icon="refresh"
                                            @click="retryFailedJob(props.row.uuid)"
                                            :loading="loading.retryFailedJob[props.row.uuid]"
                                            size="sm"
                                            dense
                                        >
                                            <q-tooltip>Повторить</q-tooltip>
                                        </q-btn>
                                    </q-btn-group>
                                </q-td>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>
            </div>
        </div>

        <!-- Диалог запуска воркера -->
        <q-dialog v-model="dialogs.startWorker.show">
            <q-card style="min-width: 400px">
                <q-card-section>
                    <div class="text-h6">Запуск воркера</div>
                </q-card-section>
                
                <q-card-section>
                    <div class="q-gutter-md">
                        <q-input
                            v-model="dialogs.startWorker.queue"
                            label="Очередь"
                            outlined
                            readonly
                        />
                        
                        <q-input
                            v-model.number="dialogs.startWorker.timeout"
                            label="Таймаут (секунды)"
                            type="number"
                            outlined
                            :min="60"
                            :max="3600"
                        />
                    </div>
                </q-card-section>
                
                <q-card-actions align="right">
                    <q-btn flat label="Отмена" @click="dialogs.startWorker.show = false" />
                    <q-btn 
                        color="positive" 
                        label="Запустить" 
                        @click="startWorker"
                        :loading="loading.startWorker"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'
import { router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

const $q = useQuasar()
const page = usePage()

// Props от сервера
const props = defineProps({
    queueStats: Object,
    workerStats: Array,
    recentJobs: Array,
    failedJobs: Array,
})

// Реактивные данные
const queueStats = ref(props.queueStats)
const workerStats = ref(props.workerStats)
const recentJobs = ref(props.recentJobs)
const failedJobs = ref(props.failedJobs)

// Состояния загрузки
const loading = reactive({
    refresh: false,
    startWorker: false,
    stopWorker: {},
    addTestJob: {},
    deleteJob: {},
    retryFailedJob: {},
    clearFailedJobs: false,
})

// Диалоги
const dialogs = reactive({
    startWorker: {
        show: false,
        queue: '',
        timeout: 600,
    }
})

// Автообновление
let refreshInterval = null

// Колонки для таблиц
const workerColumns = [
    { name: 'pid', label: 'PID', field: 'pid', align: 'left' },
    { name: 'queue', label: 'Очередь', field: 'queue', align: 'left' },
    { name: 'cpu', label: 'CPU %', field: 'cpu', align: 'center' },
    { name: 'memory', label: 'Memory %', field: 'memory', align: 'center' },
    { name: 'start_time', label: 'Время запуска', field: 'start_time', align: 'center' },
    { name: 'status', label: 'Статус', field: 'status', align: 'center' },
    { name: 'actions', label: 'Действия', align: 'center' }
]

const jobColumns = [
    { name: 'id', label: 'ID', field: 'id', align: 'left' },
    { name: 'queue', label: 'Очередь', field: 'queue', align: 'left' },
    { name: 'job_class', label: 'Класс', field: 'job_class', align: 'left' },
    { name: 'document_id', label: 'Документ', field: 'document_id', align: 'center' },
    { name: 'attempts', label: 'Попытки', field: 'attempts', align: 'center' },
    { name: 'created_at', label: 'Создана', field: 'created_at', align: 'center' },
    { name: 'actions', label: 'Действия', align: 'center' }
]

const failedJobColumns = [
    { name: 'id', label: 'ID', field: 'id', align: 'left' },
    { name: 'queue', label: 'Очередь', field: 'queue', align: 'left' },
    { name: 'job_class', label: 'Класс', field: 'job_class', align: 'left' },
    { name: 'document_id', label: 'Документ', field: 'document_id', align: 'center' },
    { name: 'exception', label: 'Ошибка', field: 'exception', align: 'left' },
    { name: 'failed_at', label: 'Провалена', field: 'failed_at', align: 'center' },
    { name: 'actions', label: 'Действия', align: 'center' }
]

// Методы
const refreshData = async () => {
    loading.refresh = true
    try {
        const response = await fetch(route('admin.queue.dashboard-data'))
        const data = await response.json()
        
        queueStats.value = data.queueStats
        workerStats.value = data.workerStats
        recentJobs.value = data.recentJobs
        failedJobs.value = data.failedJobs
        
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Ошибка загрузки данных'
        })
    } finally {
        loading.refresh = false
    }
}

const showStartWorkerDialog = (queue) => {
    dialogs.startWorker.queue = queue
    dialogs.startWorker.show = true
}

const startWorker = async () => {
    loading.startWorker = true
    try {
        const response = await fetch(route('admin.queue.start-worker'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
            body: JSON.stringify({
                queue: dialogs.startWorker.queue,
                timeout: dialogs.startWorker.timeout,
            })
        })
        
        const data = await response.json()
        
        if (data.success) {
            $q.notify({
                type: 'positive',
                message: data.message
            })
            dialogs.startWorker.show = false
            refreshData()
        } else {
            $q.notify({
                type: 'negative',
                message: data.message
            })
        }
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Ошибка запуска воркера'
        })
    } finally {
        loading.startWorker = false
    }
}

const stopWorker = async (pid) => {
    loading.stopWorker[pid] = true
    try {
        const response = await fetch(route('admin.queue.stop-worker'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
            body: JSON.stringify({ pid })
        })
        
        const data = await response.json()
        
        if (data.success) {
            $q.notify({
                type: 'positive',
                message: data.message
            })
            refreshData()
        } else {
            $q.notify({
                type: 'negative',
                message: data.message
            })
        }
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Ошибка остановки воркера'
        })
    } finally {
        delete loading.stopWorker[pid]
    }
}

const addTestJob = async (queue) => {
    loading.addTestJob[queue] = true
    try {
        const response = await fetch(route('admin.queue.add-test-job'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
            body: JSON.stringify({ queue })
        })
        
        const data = await response.json()
        
        if (data.success) {
            $q.notify({
                type: 'positive',
                message: data.message
            })
            refreshData()
        } else {
            $q.notify({
                type: 'negative',
                message: data.message
            })
        }
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Ошибка добавления задачи'
        })
    } finally {
        delete loading.addTestJob[queue]
    }
}

const deleteJob = async (jobId) => {
    loading.deleteJob[jobId] = true
    try {
        const response = await fetch(route('admin.queue.delete-job'), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
            body: JSON.stringify({ job_id: jobId })
        })
        
        const data = await response.json()
        
        if (data.success) {
            $q.notify({
                type: 'positive',
                message: data.message
            })
            refreshData()
        } else {
            $q.notify({
                type: 'negative',
                message: data.message
            })
        }
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Ошибка удаления задачи'
        })
    } finally {
        delete loading.deleteJob[jobId]
    }
}

const retryFailedJob = async (uuid) => {
    loading.retryFailedJob[uuid] = true
    try {
        const response = await fetch(route('admin.queue.retry-failed-job'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
            body: JSON.stringify({ job_uuid: uuid })
        })
        
        const data = await response.json()
        
        if (data.success) {
            $q.notify({
                type: 'positive',
                message: data.message
            })
            refreshData()
        } else {
            $q.notify({
                type: 'negative',
                message: data.message
            })
        }
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Ошибка повтора задачи'
        })
    } finally {
        delete loading.retryFailedJob[uuid]
    }
}

const clearFailedJobs = async () => {
    loading.clearFailedJobs = true
    try {
        const response = await fetch(route('admin.queue.clear-failed-jobs'), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            }
        })
        
        const data = await response.json()
        
        if (data.success) {
            $q.notify({
                type: 'positive',
                message: data.message
            })
            refreshData()
        } else {
            $q.notify({
                type: 'negative',
                message: data.message
            })
        }
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Ошибка очистки проваленных задач'
        })
    } finally {
        loading.clearFailedJobs = false
    }
}

// Жизненный цикл
onMounted(() => {
    // Автообновление каждые 5 секунд
    refreshInterval = setInterval(refreshData, 5000)
})

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval)
    }
})
</script> 