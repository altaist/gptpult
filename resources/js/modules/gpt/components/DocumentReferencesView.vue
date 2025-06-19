<template>
    <div class="document-references" v-if="references && references.length">
        <q-card class="q-mt-md">
            <q-card-section>
                <div class="flex items-center justify-between q-mb-md">
                    <div class="text-h6">
                        <q-icon name="link" class="q-mr-sm" />
                        Полезные ссылки
                    </div>
                    <q-chip 
                        :label="`${references.length} ресурсов`" 
                        color="primary" 
                        text-color="white" 
                        size="sm"
                    />
                </div>
                
                <div class="text-body2 text-grey-7 q-mb-md">
                    Релевантные источники для изучения темы
                </div>

                <q-list separator>
                    <q-item 
                        v-for="(reference, index) in references" 
                        :key="index"
                        clickable
                        @click="openLink(reference.url)"
                        class="reference-item"
                    >
                        <q-item-section avatar>
                            <q-icon 
                                :name="getTypeIcon(reference.type)" 
                                :color="getTypeColor(reference.type)" 
                                size="md" 
                            />
                        </q-item-section>
                        
                        <q-item-section>
                            <q-item-label class="text-weight-medium reference-title">
                                {{ reference.title }}
                            </q-item-label>
                            
                            <q-item-label caption class="reference-description">
                                {{ reference.description }}
                            </q-item-label>
                            
                            <q-item-label caption class="text-grey-6 q-mt-xs">
                                <div class="row items-center q-gutter-sm">
                                    <q-chip 
                                        :label="getTypeLabel(reference.type)" 
                                        size="sm" 
                                        outline 
                                        :color="getTypeColor(reference.type)"
                                    />
                                    
                                    <span v-if="reference.author" class="text-caption">
                                        <q-icon name="person" size="xs" class="q-mr-xs" />
                                        {{ reference.author }}
                                    </span>
                                    
                                    <span v-if="reference.publication_date" class="text-caption">
                                        <q-icon name="event" size="xs" class="q-mr-xs" />
                                        {{ reference.publication_date }}
                                    </span>
                                </div>
                            </q-item-label>
                        </q-item-section>
                        
                        <q-item-section side>
                            <q-icon name="open_in_new" color="grey-5" />
                        </q-item-section>
                    </q-item>
                </q-list>
            </q-card-section>
        </q-card>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';
import { useQuasar } from 'quasar';

const props = defineProps({
    references: {
        type: Array,
        required: true,
        default: () => []
    }
});

const $q = useQuasar();

// Функция для открытия ссылки в новой вкладке
const openLink = (url) => {
    if (url) {
        window.open(url, '_blank', 'noopener,noreferrer');
    } else {
        $q.notify({
            type: 'negative',
            message: 'Ссылка недоступна',
            position: 'top'
        });
    }
};

// Функция для получения иконки типа ресурса
const getTypeIcon = (type) => {
    const icons = {
        'article': 'article',
        'pdf': 'picture_as_pdf',
        'book': 'menu_book',
        'website': 'language',
        'research_paper': 'science',
        'other': 'link'
    };
    return icons[type] || 'link';
};

// Функция для получения цвета типа ресурса
const getTypeColor = (type) => {
    const colors = {
        'article': 'blue',
        'pdf': 'red',
        'book': 'green',
        'website': 'purple',
        'research_paper': 'orange',
        'other': 'grey'
    };
    return colors[type] || 'grey';
};

// Функция для получения русского названия типа
const getTypeLabel = (type) => {
    const labels = {
        'article': 'Статья',
        'pdf': 'PDF',
        'book': 'Книга',
        'website': 'Сайт',
        'research_paper': 'Исследование',
        'other': 'Другое'
    };
    return labels[type] || 'Ресурс';
};
</script>

<style scoped>
.reference-item {
    transition: background-color 0.2s ease;
    border-radius: 8px;
    margin: 4px 0;
}

.reference-item:hover {
    background-color: #f5f5f5;
}

.reference-title {
    color: #1976d2;
    text-decoration: none;
}

.reference-description {
    margin-top: 4px;
    line-height: 1.4;
}

.document-references {
    margin-top: 1.5rem;
}
</style> 