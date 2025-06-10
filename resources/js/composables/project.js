import { ref } from 'vue';
import { loadFromLocalStorage, saveToLocalStorage } from '@/utils/localstorage';


export const settings = ref({
    user: null,
    theme: {

    },
    isStyler: false,
    isSeller: false
})


export const useProject = (projectKey = 'default') => {
    
    const load = () => {
        settings.value = loadFromLocalStorage(projectKey);
    }

    const update = () => {
        saveToLocalStorage(projectKey, settings);
    }

    return {
        settings,
        load,
        update
    }


}


