export const saveToLocalStorage = (key, data) => {
    localStorage.setItem(key, JSON.stringify(data))
}

export const loadFromLocalStorage = (key, def) => {
    try {
        const storedValue = JSON.parse(localStorage.getItem(key)) || def;
        return storedValue;
    } catch (error) {
        return def;
    }
}

export const saveLocale = (locale) => {
    saveToLocalStorage('locale', locale);
}

export const getStoredLocale = (def = null) => {
    return loadFromLocalStorage('locale', def)
}

export const saveUser = (user) => {
    saveToLocalStorage('user', user);
}

export const getStoredUser = (def = null) => {
    return loadFromLocalStorage('user', def)
}

export const saveSettings = (data) => {
    saveToLocalStorage('settings', user);
}

export const getStoredSettings = (def = null) => {
    return loadFromLocalStorage('settings', def)
}

