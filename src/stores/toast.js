import { defineStore } from 'pinia'

// No-op toast store for performance
export const useToastStore = defineStore('toast', {
  state: () => ({
    toast: {
      message: '',
      type: '', // success, danger, warning, info
    },
  }),
  actions: {
    showToast() {},
    clearToast() {},
  },
})
