<template>
    <div class="page-header q-pa-md bg-white border">
        <div class="max-width-container">
            <div class="row items-center">
                <div class="col">
                    <div class="logo-container" @click="onLogoClick">
                        <i class="fas fa-tv"></i>
                        <span class="logo-text">GPT Пульт</span>
                    </div>
                </div>
                <div class="col text-center">
                    <page-title v-if="title" @page-title-click="emit('click:title')">{{title}}</page-title>
                </div>
                <div class="col text-right" v-if="rightBtnIcon"><btn :icon="rightBtnIcon" @click="onRightBtnClick"/></div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { user } from '@/composables/auth';
import { router } from '@inertiajs/vue3';
import Btn from '@/components/shared/Btn.vue';
import PageTitle from '@/components/shared/PageTitle.vue';

const props = defineProps({
    title: {
        type: String
    },
    color: {
        type: String,
        default: 'primary'
    },

    leftBtnIcon: {
        type: String,
    },
    leftBtnRoute: {
        type: String
    },
    leftBtnGoBack: {
        type: Boolean,
        default: true
    },

    rightBtnIcon: {
        type: String
    },
    rightBtnRoute: {
        type: String
    },
    
    logoGoHome: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click:left', 'click:right', 'click:title']);

const onLogoClick = () => {
    if (props.logoGoHome) {
        //router.visit('/');
        window.location.href = '/';
    } else {
        window.history.back();
    }
};

const onLeftBtnlick = () => {
    if(props.leftBtnRoute) {
        return redirect(props.leftBtnRoute);
    }
    if(props.leftBtnGoBack) {
        return goBack();
    }
    return emit("click:left")
}

const onRightBtnClick = () => {
    if(props.rightBtnRoute) {
        return redirect(props.rightBtnRoute);
    }
    return emit("click:right")
}
</script>

<style scoped>
.page-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    width: 100%;
}

.logo-container {
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 8px 12px;
    border-radius: 12px;
    width: fit-content;
}

.logo-container:hover {
    background-color: rgba(59, 130, 246, 0.05);
    transform: translateX(-2px);
}

.logo-container i {
    font-size: 20px;
    color: #3b82f6;
    margin-right: 8px;
    transition: all 0.3s ease;
}

.logo-text {
    font-family: 'Bowler', 'Inter', sans-serif;
    font-weight: 800;
    font-size: 16px;
    color: #3b82f6;
    line-height: 1;
    transition: all 0.3s ease;
}

.logo-container:hover i,
.logo-container:hover .logo-text {
    color: #2563eb;
}

@media (max-width: 768px) {
    .logo-container {
        padding: 6px 8px;
    }
    
    .logo-container i {
        font-size: 18px;
        margin-right: 6px;
    }
    
    .logo-text {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .logo-text {
        display: block;
        font-size: 14px;
    }
    
    .logo-container i {
        margin-right: 8px;
    }

    .logo-container {
        padding: 6px 10px;
    }
}
</style>

