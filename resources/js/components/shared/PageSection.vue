<template>
    <div class="q-mb-xl" :style="'min-height:' + height">
        <div class="q-my-md" v-if="title">
            <page-section-title @click="emit('click:title')">{{ title }}</page-section-title>
        </div>
        <div class="q-my-md">
            <slot />
        </div>
    </div>
</template>
<script setup>
import PageSectionTitle from '@/components/shared/PageSectionTitle.vue';


const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    height: {
        type: String,
        default: ''
    }
});
const emit = defineEmits(['click:title']);
</script>
