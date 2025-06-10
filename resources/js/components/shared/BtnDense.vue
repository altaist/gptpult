<template>
    <q-btn :label="label" dense @click="emit('click')"/>
</template>
<script setup>

const props = defineProps({
    label: {
        type: String,
        default: ''
    },
});
const emit = defineEmits(['click']);
</script>
