import { ref, onMounted, onUnmounted } from 'vue'
import { apiClient } from './api'

export function useDocumentStatus(documentId, options = {}) {
    const status = ref(null)
    const document = ref(null) // Добавляем реактивные данные документа
    const isLoading = ref(false)
    const error = ref(null)
    const lastUpdated = ref(null)
    
    // Настройки по умолчанию
    const defaultOptions = {
        pollInterval: 3000, // 3 секунды
        autoStart: true,
        onStatusChange: null,
        onDocumentUpdate: null, // Новый callback для обновления документа
        onComplete: null,
        onFullComplete: null,
        onApproved: null,
        onError: null
    }
    
    const config = { ...defaultOptions, ...options }
    
    let pollTimer = null
    let hasCalledComplete = false // Флаг для предотвращения повторных вызовов
    let hasCalledFullComplete = false
    let hasCalledApproved = false
    let isInitialLoad = true // Флаг первоначальной загрузки
    
    /**
     * Проверить статус документа
     */
    const checkStatus = async () => {
        const currentDocumentId = typeof documentId === 'function' ? documentId() : documentId
        if (!currentDocumentId) return
        
        try {
            isLoading.value = true
            error.value = null
            
            const response = await apiClient.get(route('documents.status', currentDocumentId))
            
            const previousStatus = status.value?.status
            const previousDocument = document.value
            status.value = response
            document.value = response.document // Обновляем данные документа
            lastUpdated.value = new Date()
            
            // Вызываем callback при изменении статуса
            if (config.onStatusChange && previousStatus !== response.status) {
                config.onStatusChange(response, previousStatus)
            }
            
            // Вызываем callback при обновлении документа
            if (config.onDocumentUpdate && JSON.stringify(previousDocument) !== JSON.stringify(response.document)) {
                config.onDocumentUpdate(response.document, previousDocument)
            }
            
            // Вызываем callback при завершении базовой генерации (только один раз и не при первой загрузке)
            if (config.onComplete && response.status === 'pre_generated' && !hasCalledComplete && !isInitialLoad) {
                hasCalledComplete = true
                config.onComplete(response)
                // Не останавливаем опрос - может быть запущена полная генерация
            }
            
            // Вызываем callback при завершении полной генерации (только один раз и не при первой загрузке)
            if (config.onFullComplete && response.status === 'full_generated' && !hasCalledFullComplete && !isInitialLoad) {
                hasCalledFullComplete = true
                config.onFullComplete(response)
                stopPolling() // Останавливаем опрос при полной генерации
            }
            
            // Вызываем callback при утверждении (только один раз и не при первой загрузке)
            if (config.onApproved && response.status === 'approved' && !hasCalledApproved && !isInitialLoad) {
                hasCalledApproved = true
                config.onApproved(response)
                stopPolling() // Останавливаем опрос при утверждении
            }
            
            // Отмечаем, что первоначальная загрузка завершена
            isInitialLoad = false
            
            // Останавливаем опрос для финальных статусов
            if (['full_generated', 'pre_generation_failed', 'full_generation_failed', 'approved', 'rejected'].includes(response.status)) {
                // console.log('Статус документа финальный, останавливаем опрос:', response.status)  // Закомментировано для продакшена
                stopPolling()
                config.onComplete?.(response.status)
            }
            
        } catch (err) {
            error.value = err.message || 'Ошибка при проверке статуса'
            if (config.onError) {
                config.onError(err)
            }
            stopPolling() // Останавливаем опрос при ошибке API
        } finally {
            isLoading.value = false
        }
    }
    
    /**
     * Начать автоматическую проверку статуса
     */
    const startPolling = () => {
        const currentDocumentId = typeof documentId === 'function' ? documentId() : documentId
        if (!currentDocumentId) {
            // console.warn('Не удается начать опрос: documentId не задан')  // Закомментировано для продакшена
            return
        }
        
        if (pollTimer) return // Уже запущен
        
        // Сбрасываем флаги при новом запуске опроса
        hasCalledComplete = false
        hasCalledFullComplete = false
        hasCalledApproved = false
        isInitialLoad = true
        
        checkStatus() // Первая проверка сразу
        
        pollTimer = setInterval(() => {
            checkStatus()
        }, config.pollInterval)
    }
    
    /**
     * Остановить автоматическую проверку статуса
     */
    const stopPolling = () => {
        if (pollTimer) {
            clearInterval(pollTimer)
            pollTimer = null
        }
    }
    
    /**
     * Перезапустить проверку
     */
    const restart = () => {
        stopPolling()
        startPolling()
    }
    
    /**
     * Получить человекочитаемый статус
     */
    const getStatusText = (statusValue = null) => {
        // Используем данные из API, если доступны
        if (status.value?.status_label) {
            return status.value.status_label
        }
        
                 // Fallback для совместимости
        const currentStatus = statusValue || status.value?.status
        const statusMap = {
            'draft': 'Черновик',
            'pre_generating': 'Генерируется структура...',
            'pre_generated': 'Структура готова',
            'pre_generation_failed': 'Ошибка генерации структуры',
            'full_generating': 'Генерируется содержимое...',
            'full_generated': 'Полностью готов',
            'full_generation_failed': 'Ошибка полной генерации',
            'in_review': 'На проверке',
            'approved': 'Утвержден',
            'rejected': 'Отклонен'
        }
        
        return statusMap[currentStatus] || 'Неизвестно'
    }
    
    /**
     * Проверить, завершена ли базовая генерация
     */
    const isPreGenerationComplete = () => {
        return status.value?.status === 'pre_generated' && status.value?.structure_complete
    }
    
    /**
     * Проверить, завершена ли полная генерация
     */
    const isFullGenerationComplete = () => {
        return status.value?.status === 'full_generated'
    }
    
    /**
     * Проверить, идет ли процесс генерации
     */
    const isGenerating = () => {
        // Используем данные из API, если доступны
        if (status.value?.is_generating !== undefined) {
            return status.value.is_generating
        }
        // Fallback
        return ['pre_generating', 'full_generating'].includes(status.value?.status)
    }
    
    /**
     * Проверить, можно ли запустить полную генерацию
     */
    const canStartFullGeneration = () => {
        return status.value?.can_start_full_generation || status.value?.status === 'pre_generated'
    }
    
    /**
     * Проверить, есть ли ссылки в документе
     */
    const hasReferences = () => {
        return status.value?.has_references || false
    }
    
    /**
     * Проверить, ожидается ли генерация ссылок
     */
    const isWaitingForReferences = () => {
        return status.value?.status === 'pre_generated' && !hasReferences()
    }
    
    /**
     * Проверить, произошла ли ошибка генерации
     */
    const hasFailed = () => {
        return ['pre_generation_failed', 'full_generation_failed'].includes(status.value?.status)
    }
    
    /**
     * Проверить, утвержден ли документ
     */
    const isApproved = () => {
        return status.value?.status === 'approved'
    }
    
    /**
     * Проверить, является ли статус финальным
     */
    const isFinal = () => {
        if (status.value?.is_final !== undefined) {
            return status.value.is_final
        }
        // Fallback
        return ['approved', 'rejected', 'pre_generation_failed', 'full_generation_failed'].includes(status.value?.status)
    }
    
    // Автоматический запуск при монтировании компонента
    onMounted(() => {
        if (config.autoStart) {
            startPolling()
        }
    })
    
    // Очистка при размонтировании компонента
    onUnmounted(() => {
        stopPolling()
    })
    
    return {
        // Данные
        status,
        document, // Добавляем реактивные данные документа
        isLoading,
        error,
        lastUpdated,
        
        // Методы
        checkStatus,
        startPolling,
        stopPolling,
        restart,
        getStatusText,
        
        // Вычисляемые свойства
        isPreGenerationComplete,
        isFullGenerationComplete,
        isGenerating,
        canStartFullGeneration,
        hasFailed,
        isApproved,
        isFinal,
        hasReferences,
        isWaitingForReferences
    }
} 