<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    categories: Array, // parent categories with children
});

// Predefined location options (grouped by room/area type)
const locationOptions = [
    { group: 'Gedung Guru & Administrasi', items: [
        'Gedung Guru', 'Ruang Kepala Sekolah', 'Ruang TU', 'Ruang BK'
    ]},
    { group: 'Gedung Teori Kelas 10', items: [
        'Kelas 10A', 'Kelas 10B', 'Kelas 10C', 'Kelas 10D', 'Kelas 10E', 'Kelas 10F'
    ]},
    { group: 'Gedung Teori Kelas 11', items: [
        'Kelas 11A', 'Kelas 11B', 'Kelas 11C', 'Kelas 11D', 'Kelas 11E', 'Kelas 11F'
    ]},
    { group: 'Gedung Teori Kelas 12', items: [
        'Kelas 12A', 'Kelas 12B', 'Kelas 12C', 'Kelas 12D', 'Kelas 12E', 'Kelas 12F'
    ]},
    { group: 'Laboratorium & Ruang Khusus', items: [
        'Lab Komputer', 'Lab IPA', 'Perpustakaan', 'Ruang Multimedia'
    ]},
    { group: 'Fasilitas Sanitasi', items: [
        'Toilet Lantai 1', 'Toilet Lantai 2', 'Toilet Lantai 3', 'Toilet Guru'
    ]},
    { group: 'Fasilitas Umum', items: [
        'Kantin', 'Mushola', 'Gudang Olahraga', 'Pos Satpam', 'Parkiran'
    ]},
    { group: 'Area Luar & Olahraga', items: [
        'Area Terbuka', 'Lapangan Upacara', 'Lapangan Basket', 'Taman Sekolah', 'Koridor/Selasar'
    ]},
];

// Form State
const category_id = ref('');        // parent category
const sub_category_id = ref('');    // child category (actual stored)
const title = ref('');
const description = ref('');
const location = ref('');
const location_detail = ref('');

console.log('INIT:', {
  title: title.value,
  category: category_id.value,
  sub: sub_category_id.value,
  desc: description.value
});

const { t } = useI18n();

// Step Wizard
const currentStep = ref(1);
const totalSteps = 3;

const steps = [
    { num: 1, label: 'DETAIL', icon: 'detail' },
    { num: 2, label: 'FOTO', icon: 'foto' },
    { num: 3, label: 'LOKASI', icon: 'lokasi' },
];

const stepDetailComplete = computed(() => {
    return (
        title.value.trim().length >= 5 &&
        category_id.value !== '' &&
        description.value.trim().length >= 20
    );
});

const stepFotoComplete = computed(() =>
    imagePreviews.value.length >= 1
);

const stepLokasiComplete = computed(() =>
    location.value.trim() !== ''
);

const isStepComplete = (num) => {
    if (num === 1) return stepDetailComplete.value;
    if (num === 2) return stepFotoComplete.value;
    if (num === 3) return stepLokasiComplete.value;
    return false;
};

const goToStep = (num) => {
    // boleh mundur
    if (num < currentStep.value) {
        currentStep.value = num;
        return;
    }

    // ke step 2 harus selesai step 1
    if (num === 2 && stepDetailComplete.value) {
        currentStep.value = num;
        return;
    }

    // ke step 3 harus selesai step 2
    if (num === 3 && stepFotoComplete.value) {
        currentStep.value = num;
        return;
    }
};

const handleStepClick = (num) => {
    if (num < currentStep.value) {
        currentStep.value = num;
        return;
    }

    if (num === 2 && stepDetailComplete.value) {
        currentStep.value = 2;
        return;
    }

    if (num === 3 && stepFotoComplete.value) {
        currentStep.value = 3;
        return;
    }
};

const nextStep = () => {
    // Step 1 → harus lengkap dulu
    if (currentStep.value === 1) {
        console.log('CHECK STEP 1:', stepDetailComplete.value);
        if (!stepDetailComplete.value) return;
    }

    // Step 2 → harus ada foto
    if (currentStep.value === 2) {
        console.log('CHECK STEP 2:', stepFotoComplete.value);
        if (!stepFotoComplete.value) return;
    }

    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

watch(currentStep, (val) => {
    // kalau pindah ke step 2 tapi detail belum lengkap → BALIK
    if (val === 2 && !stepDetailComplete.value) {
        currentStep.value = 1;
    }

    // kalau pindah ke step 3 tapi foto belum ada → BALIK
    if (val === 3 && !stepFotoComplete.value) {
        currentStep.value = 2;
    }
});

// Cascading sub-categories
const selectedParent = computed(() =>
    props.categories.find(c => c.id === Number(category_id.value))
);

const subCategories = computed(() =>
    selectedParent.value?.children ?? []
);

const hasSubCategories = computed(() => subCategories.value.length > 0);

// Reset sub-category when parent changes
watch(category_id, () => {
    sub_category_id.value = '';
});

// The actual category_id to submit (sub if available, else parent)
const finalCategoryId = computed(() => {
    if (hasSubCategories.value && sub_category_id.value) {
        return sub_category_id.value;
    }
    return category_id.value;
});

// Image state
const imagePreviews = ref([]);
const isDragging = ref(false);

// Submission state
const processing = ref(false);
const errors = ref({});
const networkError = ref(null);
const fileSizePopup = ref(false);

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const addFiles = (files) => {
    const remaining = 3 - imagePreviews.value.length;
    let hasOversizedFile = false;

    Array.from(files).slice(0, remaining).forEach(file => {
        if (file && file.type.startsWith('image/')) {
            if (file.size > 1 * 1024 * 1024) { // 1 MB limit
                hasOversizedFile = true;
            } else {
                imagePreviews.value.push({
                    file,
                    preview: URL.createObjectURL(file),
                    name: file.name,
                    size: file.size,
                });
            }
        }
    });

    if (hasOversizedFile) {
        fileSizePopup.value = true;
        
        // Auto close the popup after 5 seconds
        setTimeout(() => {
            fileSizePopup.value = false;
        }, 5000);
    }
};

const handleFileChange = (e) => {
    addFiles(e.target.files);
    e.target.value = '';
};

const handleDrop = (e) => {
    isDragging.value = false;
    addFiles(e.dataTransfer.files);
};

const removeImage = (index) => {
    URL.revokeObjectURL(imagePreviews.value[index].preview);
    imagePreviews.value.splice(index, 1);
};

const canSubmit = computed(() =>
    finalCategoryId.value &&
    title.value.trim() !== '' &&
    description.value.trim().length >= 20 &&
    location.value.trim() !== '' &&
    imagePreviews.value.length >= 1 &&
    // If parent has sub-categories, one must be selected
    (!hasSubCategories.value || sub_category_id.value)
);

const submit = () => {
    if (!canSubmit.value || processing.value) return;

    if (!navigator.onLine) {
        networkError.value = 'offline';
        return;
    }

    networkError.value = null;

    const formData = new FormData();
    formData.append('category_id', finalCategoryId.value);
    formData.append('title', title.value);
    formData.append('description', description.value);
    formData.append('location', location.value);
    if (location_detail.value.trim()) {
        formData.append('location_detail', location_detail.value);
    }
    imagePreviews.value.forEach(item => {
        if (item.file instanceof File) {
            formData.append('images[]', item.file, item.file.name);
        }
    });

    processing.value = true;
    errors.value = {};

    router.post(route('complaints.store'), formData, {
        forceFormData: true,
        onError: (e) => {
            processing.value = false;
            if (!navigator.onLine) {
                networkError.value = 'offline';
            } else if (Object.keys(e).length === 0) {
                networkError.value = 'server';
            } else {
                errors.value = e;
            }
        },
        onFinish: () => { processing.value = false; },
    });
};
</script>

<template>
    <Head :title="t('create_complaint_title')" />

    <!-- Pop-up Peringatan Ukuran File -->
    <Transition name="net-slide">
        <div v-if="fileSizePopup" class="fixed top-20 right-6 sm:top-8 sm:right-8 z-[200] flex items-center gap-4 p-4 pr-6 rounded-2xl bg-white/80 backdrop-blur-xl border border-red-100 shadow-[0_20px_40px_-10px_rgba(225,29,72,0.15)] animate-fade-in-up">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-tr from-red-100 to-rose-50 flex items-center justify-center border border-red-200">
                <svg class="w-6 h-6 text-red-500 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/></svg>
            </div>
            <div>
                <p class="font-extrabold text-gray-900 text-[15px] mb-0.5 tracking-tight">Upload Ditolak</p>
                <p class="text-xs text-gray-500 font-medium">Ukuran foto melebih batas maksimal <span class="font-bold text-red-500">1 MB</span>.</p>
            </div>
            <button @click="fileSizePopup = false" class="ml-4 text-gray-400 hover:text-red-500 bg-gray-50 hover:bg-red-50 p-1.5 rounded-full transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </Transition>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('complaints.index')" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-gray-100 text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition shadow-sm">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">{{ t('create_complaint_title') }}</h1>
            </div>
        </template>

        <div class="max-w-5xl mx-auto">
            <!-- Main Card -->
            <div class="complaint-form-card premium-panel border border-white/60 rounded-3xl overflow-hidden animate-fade-in-up transition-colors duration-300">
                
                <!-- Header Section -->
                <div class="text-center pt-10 pb-6 px-8">
                    <h2 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight mb-3">Buat Pengaduan</h2>
                    <p class="text-sm font-medium text-gray-500 max-w-lg mx-auto leading-relaxed">
                        Sampaikan kendala fasilitas sekolah Anda untuk penanganan yang cepat dan transparan.
                    </p>
                </div>

                <!-- Step Indicator -->
                <div class="flex items-center justify-center gap-0 px-8 pb-8 pt-2">
                    <template v-for="(step, idx) in steps" :key="step.num">
                        <!-- Step Circle -->
                        <button
                            @click="handleStepClick(step.num)"
                            class="flex flex-col items-center gap-2 group relative z-10 cursor-pointer"
                        >
                            <div
                                class="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-500 shadow-md border-2"
                                :class="{
                                    'bg-gradient-to-br from-primary-500 to-primary-700 border-primary-400 text-white shadow-[0_8px_20px_-4px_rgba(124,58,237,0.5)] scale-110': currentStep === step.num,
                                    'bg-gradient-to-br from-primary-400 to-primary-600 border-primary-300 text-white shadow-[0_4px_12px_-2px_rgba(124,58,237,0.3)]': isStepComplete(step.num) && currentStep !== step.num,
                                    'bg-white/80 border-gray-200 text-gray-400 shadow-sm': !isStepComplete(step.num) && currentStep !== step.num,
                                }"
                            >
                                <!-- Completed Check -->
                                <svg v-if="isStepComplete(step.num) && currentStep !== step.num" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                                <!-- Detail Icon -->
                                <svg v-else-if="step.icon === 'detail'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <!-- Foto Icon -->
                                <svg v-else-if="step.icon === 'foto'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <!-- Lokasi Icon -->
                                <svg v-else-if="step.icon === 'lokasi'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span
                                class="text-[10px] font-extrabold uppercase tracking-[0.15em] transition-colors duration-300"
                                :class="{
                                    'text-primary-700': currentStep === step.num,
                                    'text-primary-500': isStepComplete(step.num) && currentStep !== step.num,
                                    'text-gray-400': !isStepComplete(step.num) && currentStep !== step.num,
                                }"
                            >
                                {{ step.label }}
                            </span>
                        </button>
                        <!-- Connector Line -->
                        <div
                            v-if="idx < steps.length - 1"
                            class="flex-1 h-1 mx-2 rounded-full transition-all duration-500 mb-6 max-w-[120px]"
                            :class="{
                                'bg-gradient-to-r from-primary-500 to-primary-400 shadow-[0_0_8px_rgba(124,58,237,0.3)]': isStepComplete(steps[idx].num),
                                'bg-gray-200': !isStepComplete(steps[idx].num),
                            }"
                        ></div>
                    </template>
                </div>

                <form @submit.prevent="submit">
                    <!-- Step 1: Detail (Title + Category + Description) -->
                    <Transition
                        enter-active-class="transition-all duration-400 ease-out"
                        enter-from-class="opacity-0 translate-x-4"
                        enter-to-class="opacity-100 translate-x-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 translate-x-0"
                        leave-to-class="opacity-0 -translate-x-4"
                    >
                        <div v-if="currentStep === 1" class="px-8 sm:px-12 pb-8">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                                <!-- Left Column: Form Fields -->
                                <div class="space-y-6">
                                    <!-- Title -->
                                    <div>
                                        <label for="title" class="block text-sm font-bold text-gray-800 mb-2.5">
                                            Judul Laporan <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="title" type="text"
                                            class="input-premium bg-white/70 shadow-sm"
                                            v-model="title" required
                                            :placeholder="t('report_title_placeholder')"
                                        />
                                        <InputError class="mt-2" :message="errors.title" />
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label for="category_id" class="block text-sm font-bold text-gray-800 mb-2.5">
                                            Kategori Fasilitas <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <select id="category_id" v-model="category_id" class="input-premium bg-white/70 shadow-sm appearance-none pr-10" required>
                                                <option value="" disabled>Pilih Kategori</option>
                                                <optgroup label="── Kategori Utama ──">
                                                    <option v-for="cat in categories.filter(c => !['Keamanan','Peralatan Elektronik','Lingkungan','Lainnya'].includes(c.name))" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                </optgroup>
                                                <optgroup label="── Kategori Tambahan ──">
                                                    <option v-for="cat in categories.filter(c => ['Keamanan','Peralatan Elektronik','Lingkungan'].includes(c.name))" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                </optgroup>
                                                <optgroup label="── Lainnya ──">
                                                    <option v-for="cat in categories.filter(c => c.name === 'Lainnya')" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                </optgroup>
                                            </select>
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </div>
                                        </div>
                                        <InputError class="mt-2" :message="errors.category_id" />
                                    </div>

                                    <!-- Sub-Category -->
                                    <Transition
                                        enter-active-class="transition-all duration-300 ease-out"
                                        enter-from-class="opacity-0 -translate-y-2 max-h-0"
                                        enter-to-class="opacity-100 translate-y-0 max-h-40"
                                        leave-active-class="transition-all duration-200 ease-in"
                                        leave-from-class="opacity-100 translate-y-0 max-h-40"
                                        leave-to-class="opacity-0 -translate-y-2 max-h-0"
                                    >
                                        <div v-if="hasSubCategories" class="overflow-hidden">
                                            <label for="sub_category_id" class="block text-sm font-bold text-gray-800 mb-2.5">
                                                {{ t('sub_category_label') }} <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <select id="sub_category_id" v-model="sub_category_id" class="input-premium bg-white/70 shadow-sm appearance-none pr-10" required>
                                                    <option value="" disabled>{{ t('select_sub_category') }}</option>
                                                    <option v-for="sub in subCategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                                </select>
                                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- Right Column: Description -->
                                <div>
                                    <label for="description" class="block text-sm font-bold text-gray-800 mb-2.5">
                                        Deskripsi Kerusakan <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="description"
                                        class="input-premium bg-white/70 shadow-sm resize-none leading-relaxed"
                                        v-model="description"
                                        required minlength="20"
                                        :placeholder="t('description_placeholder')"
                                        rows="8"
                                        style="min-height: 220px;"
                                    ></textarea>
                                    <div class="flex items-center justify-between mt-2">
                                        <InputError :message="errors.description" />
                                        <span
                                            class="text-xs font-bold tracking-wider ml-auto"
                                            :class="description.length < 20 ? 'text-red-400' : 'text-emerald-500'"
                                        >{{ description.length }}/20 min</span>
                                    </div>
                                    <p v-if="description.length > 0 && description.length < 20" class="text-xs font-medium text-red-500 mt-1">
                                        ⚠️ Deskripsi masih butuh {{ 20 - description.length }} karakter lagi
                                    </p>
                                </div>
                            </div>

                            <!-- Step 1 Navigation -->
                            <div class="flex justify-end mt-8">
                                <button
                                    type="button"
                                    @click="() => { if (!stepDetailComplete.value) return; nextStep(); }"
                                    :disabled="!stepDetailComplete"
                                    class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-primary-500 to-primary-700 text-white font-bold text-sm rounded-2xl shadow-[0_8px_20px_-4px_rgba(124,58,237,0.4)] hover:shadow-[0_12px_28px_-4px_rgba(124,58,237,0.5)] hover:-translate-y-0.5 transition-all duration-300"
                                >
                                    Lanjut ke Foto
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                                </button>
                            </div>
                        </div>
                    </Transition>

                    <!-- Step 2: Foto (Upload Evidence Photos) -->
                    <Transition
                        enter-active-class="transition-all duration-400 ease-out"
                        enter-from-class="opacity-0 translate-x-4"
                        enter-to-class="opacity-100 translate-x-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 translate-x-0"
                        leave-to-class="opacity-0 -translate-x-4"
                    >
                        <div v-if="currentStep === 2" class="px-8 sm:px-12 pb-8">
                            <div class="max-w-2xl mx-auto">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-bold text-gray-800">Upload Foto Bukti <span class="text-red-500">*</span></h3>
                                    <span class="text-xs font-bold uppercase tracking-wider px-3 py-1.5 rounded-full" :class="imagePreviews.length >= 1 ? 'text-emerald-600 bg-emerald-50 border border-emerald-200' : 'text-gray-400 bg-gray-50 border border-gray-200'">
                                        {{ imagePreviews.length }}/3 {{ t('photos_selected') }}
                                    </span>
                                </div>

                                <!-- Drop zone -->
                                <div
                                    v-if="imagePreviews.length < 3"
                                    class="upload-zone-premium group"
                                    :class="{
                                        'border-primary-400 bg-primary-50/80': isDragging,
                                        'border-red-300 bg-red-50/50': errors.images,
                                    }"
                                    @dragover.prevent="isDragging = true"
                                    @dragleave="isDragging = false"
                                    @drop.prevent="handleDrop"
                                    @click="$refs.fileInput.click()"
                                >
                                    <div class="flex flex-col items-center justify-center py-8 px-4">
                                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center mb-4 shadow-sm group-hover:shadow-md group-hover:scale-105 transition-all duration-300">
                                            <svg class="w-8 h-8 text-primary-500 group-hover:text-primary-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-gray-800 mb-1.5">
                                            Pilih file atau seret ke sini
                                        </p>
                                        <p class="text-xs text-gray-400 font-medium mb-4">
                                            PNG, JPG up to 1MB
                                        </p>
                                        <span class="inline-flex px-5 py-2 bg-gradient-to-r from-primary-500 to-primary-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-[0_4px_12px_-2px_rgba(124,58,237,0.4)] group-hover:shadow-[0_8px_20px_-4px_rgba(124,58,237,0.5)] transition-all duration-300">
                                            Browse File
                                        </span>
                                    </div>
                                </div>

                                <!-- Uploaded Files List -->
                                <div v-if="imagePreviews.length > 0" class="mt-5 space-y-3">
                                    <TransitionGroup
                                        enter-active-class="transition-all duration-300 ease-out"
                                        enter-from-class="opacity-0 translate-y-2"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition-all duration-200 ease-in"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-2"
                                        tag="div"
                                        class="space-y-3"
                                    >
                                        <div v-for="(item, index) in imagePreviews" :key="item.preview" class="flex items-center gap-4 p-3 bg-white/70 backdrop-blur-sm rounded-2xl border border-white/80 shadow-sm hover:shadow-md transition-all duration-300 group">
                                            <img :src="item.preview" alt="Preview" class="w-12 h-12 rounded-xl object-cover border border-gray-100 shadow-sm" />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-gray-900 truncate">{{ item.name }}</p>
                                                <p class="text-xs text-gray-400 font-medium">{{ formatFileSize(item.size) }} · <span class="text-emerald-500 font-bold">Upload Berhasil</span></p>
                                            </div>
                                            <button
                                                type="button"
                                                @click.stop="removeImage(index)"
                                                class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all duration-300"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </TransitionGroup>
                                </div>

                                <input
                                    ref="fileInput" type="file"
                                    accept="image/jpg,image/jpeg,image/png"
                                    multiple class="hidden"
                                    @change="handleFileChange"
                                />

                                <InputError class="mt-2" :message="errors.images" />

                                <!-- Hint when no photo selected -->
                                <p v-if="imagePreviews.length === 0" class="text-xs font-bold text-red-500 uppercase tracking-wider mt-3 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                                    </svg>
                                    {{ t('must_upload_photo') }}
                                </p>
                            </div>

                            <!-- Step 2 Navigation -->
                            <div class="flex justify-between mt-8">
                                <button
                                    type="button"
                                    @click="prevStep"
                                    class="inline-flex items-center gap-2 px-6 py-3 text-gray-500 font-bold text-sm rounded-2xl border border-gray-200 bg-white/60 hover:bg-white/90 hover:text-gray-700 transition-all duration-300"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                                    Kembali
                                </button>
                                <button
                                    type="button"
                                    @click="() => { if (!stepFotoComplete.value) return; nextStep(); }""
                                    :disabled="!stepFotoComplete"
                                    class="inline-flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-primary-500 to-primary-700 text-white font-bold text-sm rounded-2xl shadow-[0_8px_20px_-4px_rgba(124,58,237,0.4)] hover:shadow-[0_12px_28px_-4px_rgba(124,58,237,0.5)] hover:-translate-y-0.5 transition-all duration-300"
                                >
                                    Lanjut ke Lokasi
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                                </button>
                            </div>
                        </div>
                    </Transition>

                    <!-- Step 3: Lokasi (Location Selection + Submit) -->
                    <Transition
                        enter-active-class="transition-all duration-400 ease-out"
                        enter-from-class="opacity-0 translate-x-4"
                        enter-to-class="opacity-100 translate-x-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 translate-x-0"
                        leave-to-class="opacity-0 -translate-x-4"
                    >
                        <div v-if="currentStep === 3" class="px-8 sm:px-12 pb-8">
                            <div class="max-w-2xl mx-auto space-y-6">
                                <!-- Location Dropdown -->
                                <div>
                                    <label for="location" class="block text-sm font-bold text-gray-800 mb-2.5">
                                        {{ t('location_label') }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select
                                            id="location"
                                            v-model="location"
                                            class="input-premium bg-white/70 shadow-sm appearance-none pr-10"
                                            required
                                        >
                                            <option value="" disabled>{{ t('select_location') }}</option>
                                            <optgroup v-for="group in locationOptions" :key="group.group" :label="group.group">
                                                <option v-for="item in group.items" :key="item" :value="item">{{ item }}</option>
                                            </optgroup>
                                        </select>
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </div>
                                    <InputError class="mt-2" :message="errors.location" />
                                </div>

                                <!-- Detail Lokasi -->
                                <div>
                                    <label for="location_detail" class="block text-sm font-bold text-gray-800 mb-2.5">
                                        {{ t('location_detail_label') }}
                                        <span class="text-xs text-gray-400 font-medium ml-1">(Opsional)</span>
                                    </label>
                                    <textarea
                                        id="location_detail"
                                        class="input-premium bg-white/70 shadow-sm resize-y leading-relaxed"
                                        v-model="location_detail"
                                        :placeholder="t('location_detail_placeholder')"
                                        maxlength="500"
                                        rows="3"
                                    ></textarea>
                                    <div class="flex items-center justify-between mt-2">
                                        <InputError :message="errors.location_detail" />
                                        <span class="text-xs font-medium text-gray-400 ml-auto">{{ location_detail.length }}/500</span>
                                    </div>
                                </div>

                                <!-- Network Error Alert -->
                                <Transition name="net-err">
                                    <div
                                        v-if="networkError"
                                        class="flex items-start gap-4 p-5 rounded-2xl border animate-fade-in-up"
                                        :class="networkError === 'offline'
                                            ? 'bg-red-50 border-red-100 text-red-700'
                                            : 'bg-amber-50 border-amber-100 text-amber-700'"
                                    >
                                        <div class="flex-shrink-0 mt-0.5">
                                            <svg v-if="networkError === 'offline'" class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M12 12h.01M8.464 8.464a5 5 0 000 7.072M5.636 5.636a9 9 0 000 12.728" />
                                            </svg>
                                            <svg v-else class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-extrabold text-sm mb-1 tracking-tight">
                                                {{ networkError === 'offline' ? '📡 Tidak ada koneksi internet' : '⚠️ Gagal mengirim laporan' }}
                                            </p>
                                            <p class="text-xs font-medium leading-relaxed opacity-90">
                                                {{ networkError === 'offline'
                                                    ? 'Koneksi internet Anda terputus. Pastikan Wi-Fi atau data seluler aktif, lalu coba kirim kembali.'
                                                    : 'Koneksi terputus saat mengunggah data. Periksa jaringan Anda dan coba lagi — data form tidak hilang.'
                                                }}
                                            </p>
                                            <button
                                                type="button"
                                                @click="networkError = null; submit()"
                                                class="mt-3 text-xs font-bold uppercase tracking-wider underline underline-offset-4 hover:opacity-70 transition"
                                            >Coba Kirim Ulang →</button>
                                        </div>
                                        <button type="button" @click="networkError = null" class="flex-shrink-0 mt-0.5 hover:opacity-60 transition">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </div>
                                </Transition>

                                <!-- Completion checklist -->
                                <div v-if="!canSubmit" class="p-5 bg-white/50 backdrop-blur-sm rounded-2xl border border-white/70">
                                    <p class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                        Checklist Kelengkapan
                                    </p>
                                    <div class="space-y-2 text-xs font-bold uppercase tracking-wide text-gray-400">
                                        <p v-if="!category_id" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-red-50 text-red-400 flex items-center justify-center text-[10px]">✕</span> Pilih kategori utama</p>
                                        <p v-if="category_id" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-[10px]">✓</span> <span class="text-emerald-500">Kategori dipilih</span></p>
                                        <p v-if="hasSubCategories && !sub_category_id" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-red-50 text-red-400 flex items-center justify-center text-[10px]">✕</span> Pilih sub-kategori</p>
                                        <p v-if="!title.trim()" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-red-50 text-red-400 flex items-center justify-center text-[10px]">✕</span> Isi judul laporan</p>
                                        <p v-if="title.trim()" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-[10px]">✓</span> <span class="text-emerald-500">Judul terisi</span></p>
                                        <p v-if="description.trim().length < 20" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-red-50 text-red-400 flex items-center justify-center text-[10px]">✕</span> Deskripsi minimal 20 karakter <span class="text-gray-300 normal-case ml-1">({{ description.trim().length }})</span></p>
                                        <p v-if="description.trim().length >= 20" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-[10px]">✓</span> <span class="text-emerald-500">Deskripsi lengkap</span></p>
                                        <p v-if="imagePreviews.length === 0" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-red-50 text-red-400 flex items-center justify-center text-[10px]">✕</span> Upload minimal 1 foto bukti</p>
                                        <p v-if="imagePreviews.length >= 1" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-[10px]">✓</span> <span class="text-emerald-500">{{ imagePreviews.length }} foto diunggah</span></p>
                                        <p v-if="!location.trim()" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-red-50 text-red-400 flex items-center justify-center text-[10px]">✕</span> Pilih lokasi</p>
                                        <p v-if="location.trim()" class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center text-[10px]">✓</span> <span class="text-emerald-500">Lokasi dipilih</span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3 Navigation: Submit -->
                            <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4 mt-8 max-w-2xl mx-auto">
                                <button
                                    type="button"
                                    @click="prevStep"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 text-gray-500 font-bold text-sm rounded-2xl border border-gray-200 bg-white/60 hover:bg-white/90 hover:text-gray-700 transition-all duration-300"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="w-full sm:flex-1 sm:max-w-xs px-8 py-4 bg-gradient-to-r from-primary-500 via-primary-600 to-accent-500 text-white font-black text-sm uppercase tracking-wider rounded-2xl shadow-[0_10px_30px_-6px_rgba(124,58,237,0.5)] hover:shadow-[0_16px_40px_-6px_rgba(124,58,237,0.6)] hover:-translate-y-1 transition-all duration-300 flex justify-center items-center gap-2 relative overflow-hidden"
                                    :class="{ 'opacity-50 cursor-not-allowed hover:-translate-y-0 hover:shadow-none saturate-50': processing || !canSubmit }"
                                    :disabled="processing || !canSubmit"
                                >
                                    <!-- Shine effect -->
                                    <div class="absolute inset-0 overflow-hidden rounded-2xl">
                                        <div class="absolute top-0 -left-[150%] w-[80%] h-[300%] bg-gradient-to-r from-transparent via-white/20 to-transparent rotate-[35deg] animate-[shine-sweep_8s_ease-in-out_infinite]"></div>
                                    </div>
                                    <svg v-if="processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span v-if="!processing" class="relative z-10">Kirim Laporan</span>
                                    <span v-else class="relative z-10">{{ t('submitting') }}</span>
                                    <svg v-if="!processing" class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </form>

                <!-- Trust Badges Footer -->
                <div class="border-t border-gray-100/80 bg-gradient-to-b from-white/30 to-white/60 mt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100/80">
                        <!-- Privasi Terjaga -->
                        <div class="flex items-center gap-3.5 px-6 py-5 sm:py-6">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">Privasi Terjaga</p>
                                <p class="text-xs text-gray-500 font-medium leading-relaxed">Data Anda akan diproses secara rahasia untuk keperluan perbaikan.</p>
                            </div>
                        </div>
                        <!-- Respon Cepat -->
                        <div class="flex items-center gap-3.5 px-6 py-5 sm:py-6">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">Respon Cepat</p>
                                <p class="text-xs text-gray-500 font-medium leading-relaxed">Tim sarana prasarana akan menindaklanjuti dalam 1×24 jam kerja.</p>
                            </div>
                        </div>
                        <!-- Status Realtime -->
                        <div class="flex items-center gap-3.5 px-6 py-5 sm:py-6">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-100 to-pink-200 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">Status Realtime</p>
                                <p class="text-xs text-gray-500 font-medium leading-relaxed">Dapatkan update status perbaikan langsung di dashboard Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.complaint-form-card {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    box-shadow:
        0 25px 60px -15px rgba(124, 58, 237, 0.08),
        0 10px 30px -10px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.9),
        inset 0 -1px 0 0 rgba(255, 255, 255, 0.3);
}

.upload-zone-premium {
    border: 2px dashed rgba(196, 181, 253, 0.6);
    border-radius: 1.25rem;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(245, 243, 255, 0.5), rgba(255, 255, 255, 0.7));
    transition: all 0.35s cubic-bezier(0.25, 1, 0.5, 1);
}

.upload-zone-premium:hover {
    border-color: rgba(139, 92, 246, 0.5);
    background: linear-gradient(135deg, rgba(245, 243, 255, 0.8), rgba(255, 255, 255, 0.9));
    transform: translateY(-2px);
    box-shadow: 0 20px 40px -10px rgba(124, 58, 237, 0.15);
}
</style>
