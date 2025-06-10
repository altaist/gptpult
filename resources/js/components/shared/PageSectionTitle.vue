<template>
    <div class="text-h5 q-py-sm rounded-1" @click="emit('click')">
        <slot/>
    </div>
    <q-separator/>
</template>
<script setup>

const emit = defineEmits(['click']);
</script>
