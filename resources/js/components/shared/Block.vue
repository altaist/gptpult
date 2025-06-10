<template>
    <div :class="['text-h6 q-pa-md q-py-lg vertical-middle', 'rounded-1', color, bgColor]" :style="'min-height:' + height">
        <div class="text-h4 q-mb-md" v-if="title">{{ title }}</div>
        <slot/>
    </div>
</template>
<script setup>

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    bgColor: {
        type: String,
        default: ''
    },
    color: {
        type: String
    },
    height: {
        type: String,
        default: '100px'
    }
});
const emit = defineEmits(['click']);
</script>
