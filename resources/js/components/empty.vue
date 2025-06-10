<template>
</template>
<script setup>

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
});

const emit = defineEmits(['click']);
const model = defineModel({
    type: String,
});

</script>
