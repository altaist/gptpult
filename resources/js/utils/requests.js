import { ref } from 'vue'
import axios from 'axios';

export const loading = ref(false);
export const request = async (url, data, method = 'post', onError = errorHandler) => {
    try {
        loading.value = true;
        const result = await axios({
            method,
            url,
            data
        });
        if (result.status != 200) {
            // return null;
        }
        const resultData = result.data ? result.data : null;
        return resultData;
    } catch (error) {
        loading.value = false;
        onError(error);
        return null;
    } finally {
        loading.value = false;
    }
}
export const requestPost = (url, data = {}) => request(url, data);
export const requestPut = (url, data = {}) => request(url, data, 'put');
export const requestDelete = (url, data = {}) => request(url, data, 'delete');
export const requestGet = (url, data = {}) => request(url, data, 'get');


export const errorHandler = (error) => {
    console.log('Error handler', error);
    if (error.response && error.response.data) {
        return error.response.data;
    }

    return error;
}

export const useLaravelErrors = (e) => {
    const errorsData = e || {};
    const getError = (key, index = 0) => {
        const error = errorsAll(key);
        if (!Array.isArray(error) || index < 0 || index >= error.length) return error || null;
        return error[index];
    }

    const errorsAll = (key) => {
        if(!errorsData.value) return null;
        return errorsData.value[key];
    }

    const checkError = (key) => {
        return getError(key) != null;
    }

    return {
        err: getError,
        errcheck: checkError
    }
}





