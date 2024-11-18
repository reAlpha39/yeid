import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        userData: null,
        accessToken: null,
        userAbilityRules: null,
        initialized: false
    }),

    getters: {
        isAuthenticated: state => !!state.userData && !!state.accessToken,
        getAbilityRules: state => state.userAbilityRules
    },

    actions: {
        setAuth(userData, token, abilityRules) {
            this.userData = userData
            this.accessToken = token
            this.userAbilityRules = abilityRules
        },

        clearAuth() {
            this.userData = null
            this.accessToken = null
            this.userAbilityRules = null
        },

        async login(credentials) {
            try {
                const { user, token } = await $api("/auth/login", {
                    method: "POST",
                    body: credentials,
                })

                const caslPermissions = convertPermissions(user.control_access)

                this.setAuth(user, token, caslPermissions)

                console.log(caslPermissions)

                return { success: true, user, token }
            } catch (error) {
                const errorMessage = error.response?._data?.message || 'An unexpected error occurred'
                const errorCode = error.response?.status

                console.error('Login error:', errorCode, errorMessage)

                return {
                    success: false,
                    error: {
                        message: errorMessage,
                        code: errorCode
                    }
                }
            }
        },

        async logout() {
            try {
                await $api("/auth/logout")

                return { success: true }
            } catch (error) {
                const errorMessage = error.response?._data?.message || 'An unexpected error occurred'
                const errorCode = error.response?.status

                console.error('Logout error:', errorCode, errorMessage)

                return {
                    success: false,
                    error: {
                        message: errorMessage,
                        code: errorCode
                    }
                }
            } finally {
                this.clearAuth()
            }
        }
    },

    persist: true // Enable persistence to localStorage
})
