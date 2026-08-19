import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'

const TOKEN_KEY = 'programhub_token'
const USER_KEY = 'programhub_user'

function readStoredUser() {
  const storedUser = localStorage.getItem(USER_KEY)

  if (!storedUser) {
    return null
  }

  try {
    return JSON.parse(storedUser)
  } catch {
    localStorage.removeItem(USER_KEY)
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY))
  const user = ref(readStoredUser())
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => Boolean(token.value && user.value))

  function persistAuth(data) {
    token.value = data.token
    user.value = data.user
    localStorage.setItem(TOKEN_KEY, data.token)
    localStorage.setItem(USER_KEY, JSON.stringify(data.user))
  }

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  function setError(exception) {
    error.value = exception.response?.data?.message ?? 'An unexpected error occurred.'
  }

  async function login(credentials) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/login', credentials)
      persistAuth(response.data)
      return response.data
    } catch (exception) {
      setError(exception)
      throw exception
    } finally {
      loading.value = false
    }
  }

  async function register(payload) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/register', payload)
      persistAuth(response.data)
      return response.data
    } catch (exception) {
      setError(exception)
      throw exception
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) {
      return null
    }

    try {
      const response = await api.get('/user')
      user.value = response.data.user ?? response.data
      localStorage.setItem(USER_KEY, JSON.stringify(user.value))
      return user.value
    } catch (exception) {
      if (exception.response?.status === 401) {
        clearAuth()
      }

      throw exception
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/logout')
      }
    } finally {
      clearAuth()
    }
  }

  return {
    token,
    user,
    loading,
    error,
    isAuthenticated,
    login,
    register,
    fetchUser,
    logout,
    clearAuth,
  }
})
