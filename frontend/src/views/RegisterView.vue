<script setup>
import { reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const { loading, error } = storeToRefs(authStore)
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

async function handleSubmit() {
  try {
    await authStore.register(form)

    Object.assign(form, {
      name: '',
      email: '',
      phone: '',
      password: '',
      password_confirmation: '',
    })

    showPassword.value = false
    showPasswordConfirmation.value = false

    await router.push({
      name: 'dashboard',
      query: { registered: '1' },
    })
  } catch {
    // The store exposes the API error for the form.
  }
}

</script>

<template>
  <main dir="rtl" class="min-h-screen bg-slate-50 px-6 py-12 text-slate-900">
    <section class="mx-auto max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
      <RouterLink to="/" class="text-sm font-semibold text-teal-700 hover:text-teal-800">
        ProgramHub
      </RouterLink>

      <h1 class="mt-8 text-3xl font-bold text-slate-950">إنشاء حساب</h1>
      <p class="mt-2 text-sm text-slate-600">أنشئ حسابك للوصول إلى خدمات ProgramHub.</p>

      <div v-if="error" class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">
        {{ error }}
      </div>

      <form class="mt-8 space-y-5" @submit.prevent="handleSubmit">
        <div>
          <label for="name" class="mb-2 block text-sm font-semibold text-slate-700"> الاسم </label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            autocomplete="name"
            required
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          />
        </div>

        <div>
          <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
            البريد الإلكتروني
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            required
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          />
        </div>

        <div>
          <label for="phone" class="mb-2 block text-sm font-semibold text-slate-700">
            رقم الهاتف (اختياري)
          </label>
          <input
            id="phone"
            v-model="form.phone"
            type="tel"
            autocomplete="tel"
            class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
          />
        </div>

        <div>
          <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
            كلمة المرور
          </label>
          <div class="relative">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              minlength="8"
              required
              class="w-full rounded-lg border border-slate-300 px-4 py-3 pl-12 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
            />
            <button
              type="button"
              class="absolute left-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-500 transition hover:bg-slate-100 hover:text-teal-700"
              :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
              :title="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
              @click="showPassword = !showPassword"
            >
              <i
                :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                class="text-base"
                aria-hidden="true"
              ></i>
            </button>
          </div>
        </div>

        <div>
          <label
            for="password_confirmation"
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            تأكيد كلمة المرور
          </label>
          <div class="relative">
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              :type="showPasswordConfirmation ? 'text' : 'password'"
              autocomplete="new-password"
              minlength="8"
              required
              class="w-full rounded-lg border border-slate-300 px-4 py-3 pl-12 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
            />
            <button
              type="button"
              class="absolute left-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-500 transition hover:bg-slate-100 hover:text-teal-700"
              :aria-label="
                showPasswordConfirmation ? 'إخفاء تأكيد كلمة المرور' : 'إظهار تأكيد كلمة المرور'
              "
              :title="
                showPasswordConfirmation ? 'إخفاء تأكيد كلمة المرور' : 'إظهار تأكيد كلمة المرور'
              "
              @click="showPasswordConfirmation = !showPasswordConfirmation"
            >
              <i
                :class="showPasswordConfirmation ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                class="text-base"
                aria-hidden="true"
              ></i>
            </button>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-teal-700 px-5 py-3 font-semibold text-white transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ loading ? 'جارٍ إنشاء الحساب...' : 'إنشاء الحساب' }}
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-slate-600">
        لديك حساب بالفعل؟
        <RouterLink to="/login" class="font-semibold text-teal-700 hover:text-teal-800">
          تسجيل الدخول
        </RouterLink>
      </p>
    </section>
  </main>
</template>
