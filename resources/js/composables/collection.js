

export const useFilters = () => {

    const filters = useCollection();

    const add = (filter) => {
        return filters.add(filter);
    }
    const remove = (filter) => {
        return filters.remove(filter);
    }

    const applyFilters = (items) => {
        items.reduce((prev, cur) => prev && applyFilter(cur));
    }

    const applyFilter = (item, filter) => {
        if(!filter) return true;
        const field = filter.field;
        const filterValue = filter.val;
        const op = filter.op || '=';

        const itemValue = ( field ? item[field] : item ) || null;
        if(itemValue === null) return true;

        switch (op) {
            case '=':
                return itemValue == filterValue
            case '!=':
                return itemValue != filterValue
            case '<':
                return itemValue < filterValue
            case '<=':
                return itemValue <= filterValue
            case '>':
                return itemValue > filterValue
            case '>=':
                return itemValue >= filterValue
            default:
                return false;
        }

    }

    return {
        filters,
        add,
        remove,
        applyFilter,
        applyFilters
    }
}

export const removeFromArray = (arr, item, field) => {
    if(!item) return null;
    const index = getArrIndex(arr, item, field);
    if(index<0) return null;
    return removeFromArrayByIndex(arr, index);
}

export const removeFromArrayByIndex = (arr, index) => {
    return arr?.splice(index, 1);
}

export const getArrIndex = (arr, item, field) => {
    return arr.findIndex((i) => (field === null ? i == item : i[field] == item[field]))
}

export const getArrIndexById = (arr, id, field) => {
    return arr.findIndex((i) => (field === null ? i == id : i[field] == id))
}



export const useCollection = (arr = [], key = 'collection', keyField = 'id') => {
    const items = arr;

    const has = (item, field = keyField) => {
        return getIndex(item, field) >= 0;
    }

    const get = (item, field = keyField) => {
        return getByIndex(getIndex(item, field));
    }

    const getById = (id, field = keyField) => {
        return getByIndex(getIndexById(id, field));
    }

    const getByIndex = (index) => {
        if(!items || index<0 || index>items.length) return false;

        return items[index] || null;
    }

    const getIndex = (item, field) => {
        return getArrIndex(items, item, field);
    }

    const getIndexById = (id, field) => {
        return getArrIndexById(items, id, field);
    }

    const getAll = (top = null) => {
        return items?.slice(top || items.length);
    }

    const getFiltered = (filtersManager = null, top = null) => {
        if(!filtersManager) return getAll(top);

        return filtersManager.applyFilters(items);
    }

    const add = (item) => {
        items.push(item);
    }

    const remove = (item, field = keyField) => {
        removeFromArray(items, item, field);
    }

    const removeByIndex = (index) => {
        return removeFromArrayByIndex(items, index);
    }

    const clear = () => {
        items.splice(0);
    }

    return {
        items,
        has,
        getAll,
        getFiltered,
        get,
        getById,
        getByIndex,
        getIndex,
        add,
        remove,
        removeByIndex,
        clear
    }
}

