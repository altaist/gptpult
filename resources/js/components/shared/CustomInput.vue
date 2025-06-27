<template>
    <div class="custom-input-wrapper">
        <label v-if="label" class="custom-input-label">{{ label }}</label>
        
        <div class="custom-input-container" :class="{ 'has-error': error, 'is-focused': isFocused }">
            <textarea
                v-if="type === 'textarea'"
                ref="inputRef"
                v-model="modelValue"
                :placeholder="placeholder"
                :rows="rows || 4"
                :disabled="disabled"
                :readonly="readonly"
                class="custom-input custom-textarea"
                @input="handleInput"
                @focus="handleFocus"
                @blur="handleBlur"
            />
            <input
                v-else
                ref="inputRef"
                v-model="modelValue"
                :type="type"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                class="custom-input"
                @input="handleInput"
                @focus="handleFocus"
                @blur="handleBlur"
            />
        </div>
        
        <!-- Сообщение об ошибке -->
        <div v-if="error" class="error-message">
            <q-icon name="error" size="14px" />
            <span>{{ error }}</span>
        </div>
        
        <!-- Дополнительный текст -->
        <div v-if="hint && !error" class="hint-message">
            {{ hint }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    type: {
        type: String,
        default: 'text'
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    hint: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    autofocus: {
        type: Boolean,
        default: false
    },
    rows: {
        type: Number,
        default: 4
    }
});

const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'input']);

const inputRef = ref(null);
const isFocused = ref(false);

const modelValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const handleInput = (event) => {
    emit('input', event);
};

const handleFocus = (event) => {
    isFocused.value = true;
    emit('focus', event);
};

const handleBlur = (event) => {
    isFocused.value = false;
    emit('blur', event);
};

onMounted(() => {
    if (props.autofocus) {
        inputRef.value?.focus();
    }
});
</script>

<style scoped>
.custom-input-wrapper {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.custom-input-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 4px;
}

.custom-input-container {
    position: relative;
    display: flex;
    align-items: stretch;
}

.custom-input {
    width: 100%;
    padding: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: #ffffff;
    font-size: 15px;
    font-family: inherit;
    line-height: 1.5;
    color: #1f2937;
    transition: all 0.2s ease;
    outline: none;
    resize: none;
}

.custom-textarea {
    min-height: 120px;
    resize: vertical;
    font-family: inherit;
    /* Кастомный скролл */
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

/* Кастомный скролл для WebKit браузеров */
.custom-textarea::-webkit-scrollbar {
    width: 8px;
}

.custom-textarea::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.custom-textarea::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.custom-textarea::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.custom-textarea::-webkit-scrollbar-thumb:active {
    background: #64748b;
}

.custom-input::placeholder {
    color: #9ca3af;
    opacity: 1;
}

.custom-input:hover:not(:disabled):not(:readonly) {
    border-color: #3b82f6;
    background: #fafbfc;
}

.custom-input:focus:not(:disabled):not(:readonly) {
    border-color: #3b82f6;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.custom-input:disabled {
    background: #f9fafb;
    color: #9ca3af;
    cursor: not-allowed;
    border-color: #e5e7eb;
}

.custom-input:readonly {
    background: #f9fafb;
    cursor: default;
}

.custom-input-container.has-error .custom-input {
    border-color: #ef4444;
    background: #fef2f2;
}

.custom-input-container.has-error .custom-input:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-message {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #ef4444;
    font-weight: 500;
}

.hint-message {
    font-size: 13px;
    color: #6b7280;
    font-weight: 400;
    line-height: 1.4;
}

/* Адаптивность */
@media (max-width: 768px) {
    .custom-input {
        padding: 14px;
        font-size: 14px;
    }
    
    .custom-textarea {
        min-height: 100px;
    }
}

@media (max-width: 480px) {
    .custom-input {
        padding: 12px;
        border-radius: 10px;
    }
    
    .custom-textarea {
        min-height: 80px;
    }
}
</style> 