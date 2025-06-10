<template>
    <div class="text-h5" @click="emit('page-title-click')">
        <slot/>
    </div>
</template>
<script setup>

const emit = defineEmits(['page-title-click']);
</script>
