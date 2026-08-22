<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import programsService from '../services/programs'

const router = useRouter()
const auth = useAuthStore()

const programs = ref([])
const loading = ref(false)
const submitting = ref(false)
const deletingId = ref(null)
const error = ref(null)
const formError = ref(null)
const editingProgram = ref(null)
const toast = ref(null)

const emptyForm = () => ({
  title: '',
  type: '',
  description: '',
  location: '',
  delivery_mode: '',
  application_start: '',
  application_deadline: '',
  start_date: '',
  end_date: '',
  capacity: '',
  status: 'draft',
})

const form = reactive(emptyForm())

const currentOrganization = computed(() => auth.user?.organizations?.[0] ?? null)
const isEditing = computed(() => Boolean(editingProgram.value))
const organizationName = computed(() => currentOrganization.value?.name ?? 'الجهة المنظمة')
const publishedCount = computed(() => programs.value.filter((program) => program.status === 'published').length)
const draftCount = computed(() => programs.value.filter((program) => program.status === 'draft').length)

function resetForm() {
  Object.assign(form, emptyForm())
  editingProgram.value = null
  formError.value = null
}

function showToast(message, type = 'success') {
  toast.value = { message, type }

  window.setTimeout(() => {
    toast.value = null
  }, 4000)
}

function extractPrograms(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  return Array.isArray(payload?.data) ? payload.data : []
}

function getErrorMessage(exception, fallback = 'حدث خطأ غير متوقع. حاول مرة أخرى.') {
  const validationErrors = exception.response?.data?.errors

  if (validationErrors) {
    const firstError = Object.values(validationErrors).flat()[0]

    if (firstError) {
      return firstError
    }
  }

  return exception.response?.data?.message ?? fallback
}

function formatDateTimeInput(value) {
  if (!value) {
    return ''
  }

  return String(value).replace(' ', 'T').slice(0, 16)
}

function formatDateInput(value) {
  if (!value) {
    return ''
  }

  return String(value).slice(0, 10)
}

function formatDate(value, includeTime = false) {
  if (!value) {
    return 'غير محدد'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return 'غير محدد'
  }

  return new Intl.DateTimeFormat('ar-SA', {
    dateStyle: 'medium',
    ...(includeTime ? { timeStyle: 'short' } : {}),
  }).format(date)
}

function statusLabel(status) {
  return status === 'published' ? 'منشور' : 'مسودة'
}

function statusClasses(status) {
  return status === 'published'
    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
    : 'bg-amber-50 text-amber-700 ring-amber-600/20'
}

function buildPayload() {
  return {
    title: form.title.trim(),
    type: form.type.trim(),
    description: form.description.trim() || null,
    location: form.location.trim() || null,
    delivery_mode: form.delivery_mode.trim() || null,
    application_start: form.application_start || null,
    application_deadline: form.application_deadline || null,
    start_date: form.start_date || null,
    end_date: form.end_date || null,
    capacity: form.capacity === '' ? null : Number(form.capacity),
    ...(isEditing.value ? { status: form.status } : {}),
  }
}

async function loadPrograms() {
  if (!currentOrganization.value) {
    programs.value = []
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await programsService.organizationList(currentOrganization.value.id)
    programs.value = extractPrograms(response)
  } catch (exception) {
    error.value = getErrorMessage(exception, 'تعذر تحميل برامج الجهة.')
  } finally {
    loading.value = false
  }
}

function startEditing(program) {
  editingProgram.value = program
  Object.assign(form, {
    title: program.title ?? '',
    type: program.type ?? '',
    description: program.description ?? '',
    location: program.location ?? '',
    delivery_mode: program.delivery_mode ?? '',
    application_start: formatDateTimeInput(program.application_start),
    application_deadline: formatDateTimeInput(program.application_deadline),
    start_date: formatDateInput(program.start_date),
    end_date: formatDateInput(program.end_date),
    capacity: program.capacity ?? '',
    status: program.status ?? 'draft',
  })
  formError.value = null
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function submitForm() {
  if (!currentOrganization.value) {
    showToast('لا توجد جهة مرتبطة بهذا المستخدم.', 'error')
    return
  }

  submitting.value = true
  formError.value = null

  try {
    const payload = buildPayload()

    if (isEditing.value) {
      await programsService.update(editingProgram.value.id, payload)
      showToast('تم تحديث البرنامج بنجاح.')
    } else {
      await programsService.create(currentOrganization.value.id, payload)
      showToast('تم إنشاء البرنامج كمسودة بنجاح.')
    }

    resetForm()
    await loadPrograms()
  } catch (exception) {
    formError.value = getErrorMessage(exception, 'تعذر حفظ البرنامج.')
    showToast(formError.value, 'error')
  } finally {
    submitting.value = false
  }
}

async function toggleStatus(program) {
  const nextStatus = program.status === 'published' ? 'draft' : 'published'
  const actionLabel = nextStatus === 'published' ? 'نشر' : 'إيقاف نشر'

  try {
    await programsService.update(program.id, { status: nextStatus })
    await loadPrograms()
    showToast(`تم ${actionLabel} البرنامج بنجاح.`)
  } catch (exception) {
    showToast(getErrorMessage(exception, `تعذر ${actionLabel} البرنامج.`), 'error')
  }
}

async function removeProgram(program) {
  const confirmed = window.confirm(`هل أنت متأكد من حذف برنامج «${program.title}»؟ لا يمكن التراجع عن هذا الإجراء.`)

  if (!confirmed) {
    return
  }

  deletingId.value = program.id

  try {
    await programsService.remove(program.id)
    if (editingProgram.value?.id === program.id) {
      resetForm()
    }
    await loadPrograms()
    showToast('تم حذف البرنامج بنجاح.')
  } catch (exception) {
    showToast(getErrorMessage(exception, 'تعذر حذف البرنامج.'), 'error')
  } finally {
    deletingId.value = null
  }
}

onMounted(loadPrograms)
</script>

<template>
  <div dir="rtl" class="min-h-screen bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
    <Transition name="toast">
      <div
        v-if="toast"
        class="fixed inset-x-4 top-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-2xl border px-4 py-3 text-sm font-medium shadow-xl sm:inset-x-auto sm:right-6 sm:left-auto"
        :class="toast.type === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
        role="status"
      >
        <i
          class="mt-0.5 text-base"
          :class="toast.type === 'error' ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check'"
          aria-hidden="true"
        ></i>
        <span>{{ toast.message }}</span>
      </div>
    </Transition>

    <main class="mx-auto max-w-7xl space-y-6">
      <header class="flex flex-col gap-4 rounded-3xl bg-slate-950 px-6 py-7 text-white shadow-xl sm:flex-row sm:items-center sm:justify-between sm:px-8">
        <div>
          <button
            type="button"
            class="mb-4 inline-flex items-center gap-2 text-sm text-slate-300 transition hover:text-white"
            @click="router.push('/dashboard')"
          >
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            العودة إلى لوحة التحكم
          </button>
          <p class="mb-2 text-sm font-semibold text-teal-300">مساحة الجهة المنظمة</p>
          <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">إدارة برامج {{ organizationName }}</h1>
          <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
            أنشئ برامجك، حدّث بياناتها، وانشر الفرص المناسبة للمتقدمين من مكان واحد.
          </p>
        </div>
        <div class="hidden h-20 w-20 items-center justify-center rounded-3xl bg-teal-500/15 text-4xl text-teal-300 sm:flex">
          <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
        </div>
      </header>

      <div v-if="!currentOrganization" class="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-800 shadow-sm">
        <div class="flex items-start gap-3">
          <i class="fa-solid fa-triangle-exclamation mt-1" aria-hidden="true"></i>
          <div>
            <h2 class="font-bold">لا توجد جهة مرتبطة بالمستخدم</h2>
            <p class="mt-1 text-sm leading-6">يجب ربط حسابك بجهة منظمة حتى تتمكن من إدارة البرامج.</p>
          </div>
        </div>
      </div>

      <template v-else>
        <section class="grid gap-4 sm:grid-cols-3" aria-label="ملخص البرامج">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-slate-500">إجمالي البرامج</span>
              <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
              </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-slate-950">{{ programs.length }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-slate-500">برامج منشورة</span>
              <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
              </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-slate-950">{{ publishedCount }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-slate-500">مسودات</span>
              <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                <i class="fa-solid fa-file-pen" aria-hidden="true"></i>
              </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-slate-950">{{ draftCount }}</p>
          </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
          <div class="mb-6 flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-sm font-semibold text-teal-700">{{ isEditing ? 'تعديل برنامج' : 'برنامج جديد' }}</p>
              <h2 class="mt-1 text-xl font-bold text-slate-950">
                {{ isEditing ? `تعديل «${editingProgram.title}»` : 'إنشاء برنامج جديد' }}
              </h2>
            </div>
            <button
              v-if="isEditing"
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
              @click="resetForm"
            >
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
              إلغاء التعديل
            </button>
          </div>

          <form class="space-y-6" @submit.prevent="submitForm">
            <div v-if="formError" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
              <i class="fa-solid fa-circle-exclamation ml-2" aria-hidden="true"></i>
              {{ formError }}
            </div>

            <div class="grid gap-5 md:grid-cols-2">
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">عنوان البرنامج <span class="text-red-500">*</span></span>
                <input v-model="form.title" type="text" required maxlength="255" placeholder="مثال: برنامج تطوير المهارات الرقمية" class="form-input" />
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">نوع البرنامج <span class="text-red-500">*</span></span>
                <input v-model="form.type" type="text" required maxlength="100" placeholder="مثال: تدريب أو منحة" class="form-input" />
              </label>
              <label class="block md:col-span-2">
                <span class="mb-2 block text-sm font-semibold text-slate-700">وصف البرنامج</span>
                <textarea v-model="form.description" rows="4" placeholder="اكتب وصفاً واضحاً للبرنامج وأهدافه والفئة المستهدفة" class="form-input resize-y"></textarea>
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">الموقع</span>
                <input v-model="form.location" type="text" maxlength="255" placeholder="مثال: الرياض أو عن بُعد" class="form-input" />
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">نمط التنفيذ</span>
                <input v-model="form.delivery_mode" type="text" maxlength="100" placeholder="مثال: حضوري أو هجين" class="form-input" />
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">بداية استقبال الطلبات</span>
                <input v-model="form.application_start" type="datetime-local" class="form-input" />
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">آخر موعد للتقديم</span>
                <input v-model="form.application_deadline" type="datetime-local" class="form-input" />
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">تاريخ بداية البرنامج</span>
                <input v-model="form.start_date" type="date" class="form-input" />
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">تاريخ نهاية البرنامج</span>
                <input v-model="form.end_date" type="date" class="form-input" />
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">السعة الاستيعابية</span>
                <input v-model="form.capacity" type="number" min="1" placeholder="اختياري" class="form-input" />
              </label>
              <label v-if="isEditing" class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">حالة البرنامج</span>
                <select v-model="form.status" class="form-input">
                  <option value="draft">مسودة</option>
                  <option value="published">منشور</option>
                </select>
              </label>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
              <button type="button" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50" @click="resetForm">
                إعادة ضبط
              </button>
              <button type="submit" :disabled="submitting" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60">
                <i :class="submitting ? 'fa-solid fa-spinner fa-spin' : isEditing ? 'fa-solid fa-floppy-disk' : 'fa-solid fa-plus'" aria-hidden="true"></i>
                {{ submitting ? 'جارٍ الحفظ...' : isEditing ? 'حفظ التعديلات' : 'إنشاء البرنامج' }}
              </button>
            </div>
          </form>
        </section>

        <section class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-teal-700">سجل البرامج</p>
              <h2 class="mt-1 text-xl font-bold text-slate-950">برامج الجهة</h2>
            </div>
            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50" :disabled="loading" @click="loadPrograms">
              <i class="fa-solid fa-rotate-right" :class="loading ? 'fa-spin' : ''" aria-hidden="true"></i>
              تحديث
            </button>
          </div>

          <div v-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <i class="fa-solid fa-circle-exclamation ml-2" aria-hidden="true"></i>
            {{ error }}
          </div>

          <div v-if="loading" class="grid gap-4 md:grid-cols-2">
            <div v-for="placeholder in 4" :key="placeholder" class="h-56 animate-pulse rounded-3xl bg-slate-200"></div>
          </div>

          <div v-else-if="programs.length === 0" class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50 text-2xl text-teal-700">
              <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
            </div>
            <h3 class="mt-5 text-lg font-bold text-slate-950">لا توجد برامج حتى الآن</h3>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">أنشئ أول برنامج للجهة باستخدام النموذج أعلاه. سيُحفظ البرنامج كمسودة ويمكن نشره لاحقاً.</p>
          </div>

          <div v-else class="grid gap-4 md:grid-cols-2">
            <article v-for="program in programs" :key="program.id" class="flex flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset" :class="statusClasses(program.status)">
                    <i :class="program.status === 'published' ? 'fa-solid fa-circle-check' : 'fa-solid fa-file-pen'" aria-hidden="true"></i>
                    {{ statusLabel(program.status) }}
                  </span>
                  <h3 class="mt-4 text-lg font-bold leading-7 text-slate-950">{{ program.title }}</h3>
                  <p class="mt-1 text-sm font-medium text-teal-700">{{ program.type }}</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                  <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
                </span>
              </div>

              <p class="mt-4 min-h-12 text-sm leading-6 text-slate-600">
                {{ program.description || 'لا يوجد وصف لهذا البرنامج.' }}
              </p>

              <dl class="mt-5 grid grid-cols-2 gap-3 border-y border-slate-100 py-4 text-sm">
                <div>
                  <dt class="text-slate-400">الموقع</dt>
                  <dd class="mt-1 truncate font-semibold text-slate-700">{{ program.location || 'غير محدد' }}</dd>
                </div>
                <div>
                  <dt class="text-slate-400">آخر موعد</dt>
                  <dd class="mt-1 font-semibold text-slate-700">{{ formatDate(program.application_deadline, true) }}</dd>
                </div>
                <div>
                  <dt class="text-slate-400">بداية البرنامج</dt>
                  <dd class="mt-1 font-semibold text-slate-700">{{ formatDate(program.start_date) }}</dd>
                </div>
                <div>
                  <dt class="text-slate-400">السعة</dt>
                  <dd class="mt-1 font-semibold text-slate-700">{{ program.capacity || 'غير محددة' }}</dd>
                </div>
              </dl>

              <div class="mt-auto flex flex-wrap gap-2 pt-5">
                <button type="button" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-slate-100 px-3 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-200" @click="startEditing(program)">
                  <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                  تعديل
                </button>
                <button type="button" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-bold transition" :class="program.status === 'published' ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'" @click="toggleStatus(program)">
                  <i :class="program.status === 'published' ? 'fa-solid fa-eye-slash' : 'fa-solid fa-bullhorn'" aria-hidden="true"></i>
                  {{ program.status === 'published' ? 'إيقاف النشر' : 'نشر' }}
                </button>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50" :disabled="deletingId === program.id" @click="removeProgram(program)">
                  <i :class="deletingId === program.id ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-trash'" aria-hidden="true"></i>
                  حذف
                </button>
              </div>
            </article>
          </div>
        </section>
      </template>
    </main>
  </div>
</template>

<style scoped>
.form-input {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid rgb(226 232 240);
  background-color: rgb(248 250 252);
  padding: 0.75rem 0.875rem;
  font-size: 0.875rem;
  color: rgb(15 23 42);
  outline: none;
  transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
}

.form-input::placeholder {
  color: rgb(148 163 184);
}

.form-input:focus {
  border-color: rgb(13 148 136);
  background-color: white;
  box-shadow: 0 0 0 3px rgb(20 184 166 / 0.14);
}

.toast-enter-active,
.toast-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-0.5rem);
}

@media (prefers-reduced-motion: reduce) {
  .form-input,
  .toast-enter-active,
  .toast-leave-active {
    transition: none;
  }
}
</style>
