/**
 * Утилита для логирования с учетом окружения
 * В продакшене (APP_ENV=production) логи не выводятся
 */

const isProduction = import.meta.env.MODE === 'production' || 
                     import.meta.env.VITE_APP_ENV === 'production';

export const logger = {
  log: (...args) => {
    if (!isProduction) {
      console.log(...args);
    }
  },
  
  error: (...args) => {
    if (!isProduction) {
      console.error(...args);
    }
  },
  
  warn: (...args) => {
    if (!isProduction) {
      console.warn(...args);
    }
  },
  
  info: (...args) => {
    if (!isProduction) {
      console.info(...args);
    }
  },
  
  debug: (...args) => {
    if (!isProduction) {
      console.debug(...args);
    }
  }
};

export default logger; 