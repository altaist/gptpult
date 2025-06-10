<template>
    <div class="q-pa-md sticky top bg-white border">
        <div class="row items-center">
            <div class="col" v-if="leftBtnIcon">
                <btn :icon="leftBtnIcon" @click="onLeftBtnlick"/>
            </div>
            <div class="col text-center"><page-title>{{title}}</page-title></div>
            <div class="col text-right" v-if="rightBtnIcon"><btn :icon="rightBtnIcon" @click="onRightBtnClick"/></div>
        </div>
    </div>
</template>
<script setup>

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
