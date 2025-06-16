import { ref, onMounted, onUnmounted } from 'vue'
import { apiClient } from './api'

export function useDocumentStatus(documentId, options = {}) {
    const status = ref(null)
    const isLoading = ref(false)
    const error = ref(null)
    const lastUpdated = ref(null)
    
    // Настройки по умолчанию
    const defaultOptions = {
        pollInterval: 3000, // 3 секунды
        autoStart: true,
        onStatusChange: null,
        onComplete: null,
        onFullComplete: null,
        onApproved: null,
        onError: null
    }
    
    const config = { ...defaultOptions, ...options }
    
    let pollTimer = null
    
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
            status.value = response
            lastUpdated.value = new Date()
            
            // Вызываем callback при изменении статуса
            if (config.onStatusChange && previousStatus !== response.status) {
                config.onStatusChange(response, previousStatus)
            }
            
            // Вызываем callback при завершении базовой генерации
            if (config.onComplete && response.status === 'pre_generated') {
                config.onComplete(response)
                // Не останавливаем опрос - может быть запущена полная генерация
            }
            
            // Вызываем callback при завершении полной генерации
            if (config.onFullComplete && response.status === 'full_generated') {
                config.onFullComplete(response)
                stopPolling() // Останавливаем опрос при полной генерации
            }
            
            // Вызываем callback при утверждении
            if (config.onApproved && response.status === 'approved') {
                config.onApproved(response)
                stopPolling() // Останавливаем опрос при утверждении
            }
            
            // Останавливаем опрос для финальных статусов
            if (response.is_final) {
                stopPolling()
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
            console.warn('Не удается начать опрос: documentId не задан')
            return
        }
        
        if (pollTimer) return // Уже запущен
        
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
        isFinal
    }
} 