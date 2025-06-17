<template>
    <div class="page-header q-pa-md bg-white border">
        <div class="row items-center">
            <div class="col" v-if="leftBtnIcon">
                <btn :icon="leftBtnIcon" @click="onLeftBtnlick"/>
            </div>
            <div class="col text-center">
                <page-title @page-title-click="emit('click:title')">{{title}}</page-title>
            </div>
            <div class="col text-right" v-if="rightBtnIcon"><btn :icon="rightBtnIcon" @click="onRightBtnClick"/></div>
        </div>
    </div>
</template>
<script setup>
import { user } from '@/composables/auth';
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
    }

});

const emit = defineEmits(['click:left', 'click:right', 'click:title']);

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
</style>
