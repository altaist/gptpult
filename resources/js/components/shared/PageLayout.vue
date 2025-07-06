<template>
    <div class="page-layout">
        <page-header
            :title="title"
            :is-sticky="true"
            :left-btn-icon="leftBtnIcon"
            :left-btn-route="leftBtnRoute"
            :left-btn-go-back="leftBtnGoBack"
            :right-btn-icon="rightBtnIcon"
            :right-btn-route="rightBtnRoute"
            :logo-go-home="logoGoHome"
            :documents-count="documentsCount"
            :balance="balance"

            @click:left="emit('click:header:left')"
            @click:right="emit('click:header:right')"
            @click:title="emit('click:header:title')"
            />

        <page-container class="page-content">    
            <slot/>
        </page-container>

        <page-footer 
            :title="footerText"
            :is-sticky="true"
            :menu="footerMenu || localMenu"

            @click:left="emit('click:footer:left')"
            @click:right="emit('click:footer:right')"
            @click:menu="emit('click:footer:menu', $event)"
        />
        
        <!-- Отладочный компонент -->
        <logout-button-debug 
            :documents-count="documentsCount"
            :balance="balance"
        />
    </div>
</template>
<script setup>
import PageContainer from '@/components/shared/PageContainer.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import PageFooter from '@/components/shared/PageFooter.vue';
import LogoutButtonDebug from '@/components/debug/LogoutButtonDebug.vue';
import { onMounted } from 'vue';
import { checkAuth } from '@/composables/auth';

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    footerText: {
        type: String,
        default: ''
    },
    footerMenu: {
        type: Array,
        default: null
    },
    autoAuth: {
        type: Boolean,
        default: false
    },
    leftBtnIcon: {
        type: String,
        default: "fa-solid fa-arrow-left"
    },
    leftBtnRoute: {
        type: String
    },
    leftBtnGoBack: {
        type: Boolean,
        default: true
    },
    rightBtnIcon: {
        type: String,
        default: 'fa-solid fa-user'
    },
    rightBtnRoute: {
        type: String,
        default: '/lk'
    },
    logoGoHome: {
        type: Boolean,
        default: false
    },
    documentsCount: {
        type: Number,
        default: 0
    },
    balance: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['click:header:left', 'click:header:right', 'click:header:title', 'click:footer:left', 'click:footer:right', 'click:footer:title', 'click:footer:menu']);

const model = defineModel({
    type: String,
});

const localMenu = [
    {
        id: 1,
        label: 'Menu1',
        icon: 'fa-solid fa-home'
    },
    {
        id: 2,
        label: 'Menu2',
        icon: 'fa-solid fa-shop'
    },
    {
        id: 3,
        label: 'Menu3',
        icon: 'fa-solid fa-user'
    }
];

onMounted(async () => {
    if (props.autoAuth) {
        await checkAuth();
    }
});
</script>

<style scoped>
.page-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.page-content {
    flex: 1;
    margin-bottom: 100px;
    position: relative;
    z-index: 1;
}
</style>
