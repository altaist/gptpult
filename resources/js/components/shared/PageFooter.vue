<template>
    <div :class="['bg-white border full-width', 'q-mt-lg', isSticky ? 'fixed bottom' : '']" v-if="false">
        <div class="full-width" v-if="menu && menu.length>0">
            <q-btn-group spread class="q-py-sm">
                <q-btn 
                    v-for="item in menu"
                    :icon="item.icon" :label="item.label"
                    :text-color="item.textColor || 'black'" 
                    full-width fit stack 
                    @click="onMenuClick(item.id)"
                    />
            </q-btn-group>
        </div>

        <div class="q-pa-md" v-else>
            <div class="row">
                <div class="col"></div>
                <div class="col text-center">{{ title }}</div>
                <div class="col text-right"></div>
            </div>
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
    bgColor: {
        type: String,
        default: 'bg-white'
    },
    icon: {
        type: String,
        default: undefined
    },
    isSticky: {
        type: Boolean,
        default: false
    },
    menu: {
        type: Array,
        default: null
    }

});

const emit = defineEmits(['click:left', 'click:center', 'click:right', 'click:menu']);

const onMenuClick = (menuId) => {
    emit('click:menu', menuId);
}


</script>
