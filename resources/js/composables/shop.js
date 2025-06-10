import { ref } from 'vue';
import { useCollection, removeFromArray } from './collection';
import { loadFromLocalStorage, saveToLocalStorage } from '@/utils/localstorage';


const emptyShop = {
    key: '',
    cart: {
        items: []
    },
    catalogs: {}
};

const shopData = ref(null);

export const useShop = (shopKey = 'shop.main') => {

    const addToCart = (product) => {
        if(!shopData.value) {
            return logError('Empty shop');
        }

        shopData.value.cart.items.push(product);
        return shopData.value.cart;
    }

    const removeFromCart = (product) => {
        const items = getCartItems();
        if(!items) {
            return null;
        }

        removeFromArray(items, product);

        return items;
    }

    const clearCart = () => {
        getCart().items = [];
    }

    const getCart = () => {
        return shopData.value?.cart;
    }

    const getCartItems = () => {
        return getCart()?.items;
    }

    const logError = (str) => {
        console.log(str);
        return null;
    }

    const initShop = () => {
        if(shopData.value) {
            return true;
        }

        loadLocal();
        if(!shopData.value) {
            shopData.value = emptyShop;
            shopData.key = shopKey
            return false;
        }
        return true;
    }

    const loadLocal = () => {
        shopData.value = loadFromLocalStorage(shopKey, null);
    }

    const updateLocal = () => {
        saveToLocalStorage(shopKey, shopData.value);
    }

    const update = () => {
        return updateLocal();
    }

    return {
        shopData,
        addToCart,
        removeFromCart,
        clearCart,
        getCart,
        getCartItems,
        initShop,
        loadLocal,
        updateLocal,
        update,
    }
}

const favoritesArr = ref(null);
export const useFavorites = (key = 'favorites') => {
    const favorites = useCollection(favoritesArr.value);

    const init = () => {
        if(!favoritesArr.value) {
            load();
        }
        if(!favoritesArr.value) {
            favoritesArr.value = [];
        }
    }

    const getFavorites = () => favoritesArr.value;

    const has = (item) => {
        return favorites.has(item);
    }

    const toggle = (item) => {
        if (has(item)) {
            remove(item);
            return false;
        } else {
            add(item);
            return true;
        }
    }

    const add = (product) => {
        favorites.add(product);
    }
    const remove = (product) => {
        favorites.remove(product);
    }
    const clear = () => {
        favorites.clear();
    }

    const load = async () => {
        return loadLocal();
    }

    const update = async () => {
        return updateLocal()
    }

    const loadLocal = () => {
        favoritesArr.value = loadFromLocalStorage(key);
    }

    const updateLocal = () => {
        saveToLocalStorage(key, favoritesArr.value);
    }
    init();


    return {
        getFavorites,
        has,
        toggle,
        add,
        remove,
        clear,
        load,
        update
    }
}



const cartArr = ref(null);
export const useCart = (key = 'cart') => {
    const cart = useCollection(cartArr.value);

    const init = () => {
        if(!cartArr.value) {
            load();
        }
        if(!cartArr.value) {
            cartArr.value = [];
        }

    }

    const getCart = () => cartArr.value;

    const has = (item) => {
        return cart.has(item);
    }

    const toggle = (item) => {
        if (has(item)) {
            remove(item);
            return false;
        } else {
            add(item);
            return true;
        }
    }

    const add = (product) => {
        cart.add(product);
    }
    const remove = (product) => {
        cart.remove(product);
    }
    const clear = () => {
        cart.clear();
    }

    const load = async () => {
        return loadLocal();
    }

    const update = async () => {
        return updateLocal()
    }

    const loadLocal = () => {
        cartArr.value = loadFromLocalStorage(key);
    }

    const updateLocal = () => {
        saveToLocalStorage(key, cartArr.value);
    }

    init();

    return {
        getCart,
        has,
        toggle,
        add,
        remove,
        clear,
        load,
        update
    }
}

