<template>
    <q-btn :label="label" :icon="icon" @click="emit('click')" flat :color="color"/>
</template>
<script setup>

const props = defineProps({
    label: {
        type: String,
        default: ''
    },
    icon: {
        type: String,
        default: ''
    },
    color: {
        type: String,
        default: 'primary'
    },
});
const emit = defineEmits(['click']);
</script>
