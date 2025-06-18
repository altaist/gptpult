<template>
    <div class="document-contents">
        <div class="flex items-center justify-between q-mb-md">
            <div class="text-h6">Содержание</div>
            <q-btn 
                v-if="editable"
                icon="edit" 
                flat 
                round 
                size="sm" 
                @click="$emit('edit-contents', contents)"
                class="q-ml-auto"
            />
        </div>
        
        <q-list bordered separator>
            <template v-for="(topic, index) in contents" :key="index">
                <q-item>
                    <q-item-section>
                        <q-item-label class="text-weight-medium">
                            {{ index + 1 }}. {{ topic.title }}
                        </q-item-label>
                        
                        <q-item-label caption v-if="topic.subtopics && topic.subtopics.length">
                            <q-list dense>
                                <q-item v-for="(subtopic, subIndex) in topic.subtopics" :key="subIndex">
                                    <q-item-section>
                                        <q-item-label>
                                            {{ index + 1 }}.{{ subIndex + 1 }} {{ subtopic.title }}
                                        </q-item-label>
                                        <q-item-label caption v-if="subtopic.description">
                                            {{ subtopic.description }}
                                        </q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-item-label>
                    </q-item-section>
                </q-item>
            </template>
        </q-list>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    contents: {
        type: Array,
        required: true,
        default: () => []
    },
    
    editable: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['edit-contents']);
</script>

<style scoped>
.document-contents {
    margin-top: 2rem;
}
</style> 