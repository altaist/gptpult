<template>
    <div class="document-view">
        <div class="text-h5 q-mb-md">{{ document.topic }}</div>
        
        <q-card class="q-mb-md">
            <q-card-section>
                <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                        <div class="text-subtitle2">Тип документа</div>
                        <div>{{ document.document_type?.name }}</div>
                    </div>
                </div>
            </q-card-section>

            <q-card-section v-if="document.structure?.theses">
                <div class="text-subtitle2">Тезисы</div>
                <div class="q-mt-sm">{{ document.structure.theses }}</div>
            </q-card-section>
        </q-card>

        <document-contents-view 
            v-if="document.structure?.contents"
            :contents="document.structure.contents"
        />
    </div>
</template>

<script setup>
import { defineProps } from 'vue';
import DocumentContentsView from './DocumentContentsView.vue';

const props = defineProps({
    document: {
        type: Object,
        required: true
    }
});
</script>

<style scoped>
.document-view {
    max-width: 1200px;
    margin: 0 auto;
}
</style> 