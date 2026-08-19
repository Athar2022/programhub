<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)
const showRegistrationSuccess = ref(route.query.registered === '1')

onMounted(async () => {
  if (!showRegistrationSuccess.value) {
    return
  }

  window.setTimeout(() => {
    showRegistrationSuccess.value = false
  }, 4000)

  await router.replace({ name: 'dashboard' })
})

async function handleLogout() {
  await authStore.logout()
  await router.push('/login')
}
</script>


<template>
  <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
    <Transition
  enter-active-class="transition duration-300 ease-out"
  enter-from-class="translate-y-[-12px] opacity-0"
  enter-to-class="translate-y-0 opacity-100"
  leave-active-class="transition duration-300 ease-in"
  leave-from-class="translate-y-0 opacity-100"
  leave-to-class="translate-y-[-12px] opacity-0"
>
  <div
    v-if="showRegistrationSuccess"
    class="fixed right-4 top-4 z-50 flex w-[calc(100%-2rem)] max-w-sm items-center gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-emerald-800 shadow-lg ring-1 ring-emerald-100 sm:right-6 sm:top-6"
    role="alert"
    aria-live="polite"
  >
    <i class="fa-solid fa-circle-check text-lg text-emerald-600" aria-hidden="true"></i>
    <span class="flex-1 font-semibold">تم إنشاء الحساب بنجاح، مرحبًا بك في ProgramHub.</span>
    <button
      type="button"
      class="rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
      aria-label="إغلاق الإشعار"
      title="إغلاق الإشعار"
      @click="showRegistrationSuccess = false"
    >
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
  </div>
</Transition>

    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <RouterLink to="/dashboard" class="text-xl font-bold text-teal-700">
          ProgramHub
        </RouterLink>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-400 hover:text-red-600"
          @click="handleLogout"
        >
          تسجيل الخروج
        </button>
      </div>
    </header>

    <section class="mx-auto max-w-6xl px-6 py-12">
      <div
        v-if="showRegistrationSuccess"
        class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"
        role="status"
      >
        <i class="fa-solid fa-circle-check text-lg" aria-hidden="true"></i>
        <span class="font-semibold">تم إنشاء الحساب بنجاح، مرحبًا بك في ProgramHub.</span>
      </div>

      <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm font-semibold text-teal-700">مساحة العمل</p>
        <h1 class="mt-3 text-3xl font-bold text-slate-950">مرحبًا {{ user?.name }}</h1>
        <p class="mt-3 text-slate-600">
          تم تسجيل دخولك بنجاح. سنضيف هنا إدارة البرامج والطلبات والإشعارات تدريجيًا.
        </p>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
          <div class="rounded-xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">البريد الإلكتروني</p>
            <p class="mt-2 font-semibold text-slate-900">{{ user?.email }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">الدور</p>
            <p class="mt-2 font-semibold text-slate-900">{{ user?.role }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">حالة الحساب</p>
            <p class="mt-2 font-semibold text-emerald-700">{{ user?.status }}</p>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
