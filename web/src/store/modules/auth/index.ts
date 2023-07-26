import { defineStore } from 'pinia'
import { getToken, removeToken, setToken } from './helper'
import { store } from '@/store'

interface SessionResponse {
  auth: boolean
  model: 'ChatGPTAPI' | 'ChatGPTUnofficialProxyAPI'
}

export interface AuthState {
  token: string | undefined
  session: SessionResponse | null
  loginRedirectUrl?: string
}

export const useAuthStore = defineStore('auth-store', {
  state: (): AuthState => ({
    token: getToken(),
    session: null,
  }),

  getters: {
    isChatGPTAPI(state): boolean {
      return state.session?.model === 'ChatGPTAPI'
    },
  },

  actions: {
    async getSession() {
      try {
        const data: SessionResponse = { auth: true, model: 'ChatGPTAPI' }
        this.session = { ...data }
        return Promise.resolve(data)
      }
      catch (error) {
        return Promise.reject(error)
      }
    },

    setToken(token: string) {
      this.token = token
      setToken(token)
    },

    removeToken() {
      this.token = undefined
      removeToken()
    },

    hasToken() {
      return !!this.token
    },

    setLoginRedirectUrl(url: string) {
      this.loginRedirectUrl = url
    },

    removeLoginRedirectUrl() {
      this.loginRedirectUrl = undefined
    },

    getRemoveLoginRedirectUrl() {
      const url = this.loginRedirectUrl
      this.loginRedirectUrl = undefined
      return url
    },
  },
})

export function useAuthStoreWithout() {
  return useAuthStore(store)
}
