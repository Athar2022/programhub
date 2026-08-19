<template>
  <main dir="rtl" class="min-h-screen bg-slate-50 text-slate-900">
    <section class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-6 py-16">
      <div class="max-w-2xl">
        <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">
          ProgramHub
        </p>
        <h1 class="text-4xl font-bold leading-tight text-slate-950 sm:text-6xl">
          منصة واحدة لإدارة البرامج والمنح والتدريب
        </h1>
        <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
          نظّم برامج مؤسستك، واستقبل الطلبات، وتابع المراجعات والاشتراكات من مساحة عمل واحدة.
        </p>
        <div class="mt-8 flex flex-wrap gap-4">
          <RouterLink
            to="/login"
            class="rounded-lg bg-teal-700 px-5 py-3 font-semibold text-white transition hover:bg-teal-800"
          >
            تسجيل الدخول
          </RouterLink>
          <RouterLink
            to="/register"
            class="rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 transition hover:border-teal-600 hover:text-teal-700"
          >
            إنشاء حساب جديد
          </RouterLink>
        </div>
      </div>
    </section>
  </main>
</template>
