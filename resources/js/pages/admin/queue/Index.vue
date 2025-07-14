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
                <q-btn-group>
                    <q-btn
                        color="primary"
                        icon="refresh"
                        label="Обновить"
                        @click="refreshData"
                        :loading="loading.refresh"
                        no-caps
                    />
                    <q-btn
                        color="secondary"
                        icon="settings"
                        label="Расширенное управление"
                        @click="dialogs.advancedControls.show = true"
                        no-caps
                    />
                </q-btn-group>
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
                                                <q-btn
                                                    color="secondary"
                                                    icon="work"
                                                    label="Создать Job"
                                                    @click="showCreateJobDialog(queue.name)"
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

        <!-- Диалог создания Job -->
        <q-dialog v-model="dialogs.createJob.show" persistent>
            <q-card style="min-width: 600px">
                <q-card-section>
                    <div class="text-h6">Создать Job</div>
                </q-card-section>
                
                <q-card-section>
                    <q-tabs v-model="dialogs.createJob.activeTab" class="text-grey" active-color="primary" indicator-color="primary" align="justify">
                        <q-tab name="single" label="Одиночный Job" />
                        <q-tab name="batch" label="Batch Job" />
                    </q-tabs>

                    <q-separator />

                    <q-tab-panels v-model="dialogs.createJob.activeTab" animated>
                        <!-- Одиночный Job -->
                        <q-tab-panel name="single">
                            <div class="q-gutter-md">
                                <q-select
                                    v-model="dialogs.createJob.single.document"
                                    :options="availableDocuments"
                                    option-label="display_text"
                                    option-value="id"
                                    label="Выберите документ"
                                    outlined
                                    clearable
                                    use-input
                                    @filter="filterDocuments"
                                    @focus="loadDocuments"
                                >
                                    <template v-slot:no-option>
                                        <q-item>
                                            <q-item-section class="text-grey">
                                                Документы не найдены
                                            </q-item-section>
                                        </q-item>
                                    </template>
                                </q-select>
                                
                                <q-select
                                    v-model="dialogs.createJob.single.jobType"
                                    :options="jobTypes"
                                    label="Тип генерации"
                                    outlined
                                    emit-value
                                    map-options
                                />
                                
                                <q-select
                                    v-model="dialogs.createJob.single.queue"
                                    :options="queueOptions"
                                    label="Очередь"
                                    outlined
                                    emit-value
                                    map-options
                                />
                            </div>
                        </q-tab-panel>

                        <!-- Batch Job -->
                        <q-tab-panel name="batch">
                            <div class="q-gutter-md">
                                <q-select
                                    v-model="dialogs.createJob.batch.documents"
                                    :options="availableDocuments"
                                    option-label="display_text"
                                    option-value="id"
                                    label="Выберите документы"
                                    outlined
                                    multiple
                                    clearable
                                    use-input
                                    use-chips
                                    @filter="filterDocuments"
                                    @focus="loadDocuments"
                                >
                                    <template v-slot:no-option>
                                        <q-item>
                                            <q-item-section class="text-grey">
                                                Документы не найдены
                                            </q-item-section>
                                        </q-item>
                                    </template>
                                </q-select>
                                
                                <q-select
                                    v-model="dialogs.createJob.batch.queue"
                                    :options="queueOptions"
                                    label="Очередь"
                                    outlined
                                    emit-value
                                    map-options
                                />
                                
                                <q-banner class="bg-blue-1">
                                    <template v-slot:avatar>
                                        <q-icon name="info" color="blue" />
                                    </template>
                                    Batch Job создаст асинхронные задачи генерации для всех выбранных документов
                                </q-banner>
                            </div>
                        </q-tab-panel>
                    </q-tab-panels>
                </q-card-section>
                
                <q-card-actions align="right">
                    <q-btn flat label="Отмена" @click="closeCreateJobDialog" />
                    <q-btn 
                        color="primary" 
                        :label="dialogs.createJob.activeTab === 'single' ? 'Создать Job' : 'Создать Batch Job'"
                        @click="createJob"
                        :loading="loading.createJob"
                        :disable="!canCreateJob"
                    />
                </q-card-actions>
            </q-card>
        </q-dialog>

        <!-- Расширенная панель управления -->
        <q-dialog v-model="dialogs.advancedControls.show">
            <q-card style="min-width: 500px">
                <q-card-section>
                    <div class="text-h6">Расширенное управление очередями</div>
                </q-card-section>
                
                <q-card-section>
                    <div class="q-gutter-md">
                        <q-btn-group spread>
                            <q-btn
                                color="warning"
                                icon="pause"
                                label="Приостановить все воркеры"
                                @click="pauseAllWorkers"
                                :loading="loading.pauseAllWorkers"
                                no-caps
                            />
                            <q-btn
                                color="positive"
                                icon="play_arrow"
                                label="Возобновить все воркеры"
                                @click="resumeAllWorkers"
                                :loading="loading.resumeAllWorkers"
                                no-caps
                            />
                        </q-btn-group>
                        
                        <q-separator />
                        
                        <q-btn
                            color="negative"
                            icon="delete_sweep"
                            label="Очистить всю очередь"
                            @click="confirmClearAllJobs"
                            :loading="loading.clearAllJobs"
                            class="full-width"
                            no-caps
                        />
                        
                        <q-btn
                            color="info"
                            icon="refresh"
                            label="Перезапустить все воркеры"
                            @click="restartAllWorkers"
                            :loading="loading.restartAllWorkers"
                            class="full-width"
                            no-caps
                        />
                    </div>
                </q-card-section>
                
                <q-card-actions align="right">
                    <q-btn flat label="Закрыть" @click="dialogs.advancedControls.show = false" />
                </q-card-actions>
            </q-card>
        </q-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
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
    createJob: false,
    pauseAllWorkers: false,
    resumeAllWorkers: false,
    clearAllJobs: false,
    restartAllWorkers: false,
})

// Диалоги
const dialogs = reactive({
    startWorker: {
        show: false,
        queue: '',
        timeout: 600,
    },
    createJob: {
        show: false,
        activeTab: 'single',
        queue: '',
        single: {
            document: null,
            jobType: 'base_generation',
            queue: 'document_creates',
        },
        batch: {
            documents: [],
            queue: 'document_creates',
        }
    },
    advancedControls: {
        show: false,
    }
})

// Автообновление
let refreshInterval = null

// Данные для создания job
const availableDocuments = ref([])
const allDocuments = ref([])

// Опции для селектов
const jobTypes = [
    { label: 'Базовая генерация', value: 'base_generation' },
    { label: 'Полная генерация', value: 'full_generation' },
    { label: 'Асинхронная генерация', value: 'async_generation' }
]

const queueOptions = [
    { label: 'Очередь документов', value: 'document_creates' },
    { label: 'Основная очередь', value: 'default' }
]

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

// Вычисляемые свойства
const canCreateJob = computed(() => {
    if (dialogs.createJob.activeTab === 'single') {
        return dialogs.createJob.single.document && 
               dialogs.createJob.single.jobType && 
               dialogs.createJob.single.queue
    } else {
        return dialogs.createJob.batch.documents.length > 0 && 
               dialogs.createJob.batch.queue
    }
})

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

// Методы для создания Job
const showCreateJobDialog = (queue) => {
    dialogs.createJob.queue = queue
    dialogs.createJob.single.queue = queue
    dialogs.createJob.batch.queue = queue
    dialogs.createJob.show = true
    loadDocuments()
}

const closeCreateJobDialog = () => {
    dialogs.createJob.show = false
    dialogs.createJob.single.document = null
    dialogs.createJob.batch.documents = []
}

const loadDocuments = async () => {
    try {
        const response = await fetch(route('admin.queue.documents-for-job'))
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        
        // Проверяем, что данные корректные
        if (data.success === false) {
            throw new Error(data.message || 'Неизвестная ошибка сервера')
        }
        
        const documents = Array.isArray(data) ? data : []
        
        // Форматируем документы для отображения
        allDocuments.value = documents.map(doc => ({
            ...doc,
            display_text: `#${doc.id} - ${doc.title || 'Без названия'} (${doc.user?.name || 'Без пользователя'})`
        }))
        
        availableDocuments.value = allDocuments.value
        
        console.log('Документы успешно загружены:', documents.length)
        
    } catch (error) {
        console.error('Ошибка загрузки документов:', error)
        $q.notify({
            type: 'negative',
            message: `Ошибка загрузки документов: ${error.message}`
        })
    }
}

const filterDocuments = (val, update) => {
    update(() => {
        if (val === '') {
            availableDocuments.value = allDocuments.value
        } else {
            const needle = val.toLowerCase()
            availableDocuments.value = allDocuments.value.filter(
                doc => doc.display_text.toLowerCase().indexOf(needle) > -1
            )
        }
    })
}

const createJob = async () => {
    loading.createJob = true
    
    try {
        let endpoint, payload
        
        if (dialogs.createJob.activeTab === 'single') {
            endpoint = route('admin.queue.create-document-job')
            payload = {
                document_id: dialogs.createJob.single.document.id,
                job_type: dialogs.createJob.single.jobType,
                queue: dialogs.createJob.single.queue,
            }
        } else {
            endpoint = route('admin.queue.create-batch-job')
            payload = {
                document_ids: dialogs.createJob.batch.documents.map(doc => doc.id),
                queue: dialogs.createJob.batch.queue,
            }
        }
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrf_token,
            },
            body: JSON.stringify(payload)
        })
        
        const data = await response.json()
        
        if (data.success) {
            $q.notify({
                type: 'positive',
                message: data.message
            })
            closeCreateJobDialog()
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
            message: 'Ошибка создания job'
        })
    } finally {
        loading.createJob = false
    }
}

// Расширенные методы управления
const pauseAllWorkers = async () => {
    loading.pauseAllWorkers = true
    try {
        const response = await fetch(route('admin.queue.stop-all-workers'), {
            method: 'POST',
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
            message: 'Ошибка остановки воркеров'
        })
    } finally {
        loading.pauseAllWorkers = false
    }
}

const resumeAllWorkers = async () => {
    loading.resumeAllWorkers = true
    try {
        const response = await fetch(route('admin.queue.restart-all-workers'), {
            method: 'POST',
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
            message: 'Ошибка перезапуска воркеров'
        })
    } finally {
        loading.resumeAllWorkers = false
    }
}

const confirmClearAllJobs = () => {
    $q.dialog({
        title: 'Подтверждение',
        message: 'Вы уверены, что хотите очистить ВСЮ очередь? Это действие нельзя отменить.',
        cancel: true,
        persistent: true
    }).onOk(() => {
        clearAllJobs()
    })
}

const clearAllJobs = async () => {
    loading.clearAllJobs = true
    try {
        const response = await fetch(route('admin.queue.clear-all-queues'), {
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
            message: 'Ошибка очистки очередей'
        })
    } finally {
        loading.clearAllJobs = false
    }
}

const restartAllWorkers = async () => {
    loading.restartAllWorkers = true
    try {
        const response = await fetch(route('admin.queue.restart-all-workers'), {
            method: 'POST',
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
            message: 'Ошибка перезапуска воркеров'
        })
    } finally {
        loading.restartAllWorkers = false
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