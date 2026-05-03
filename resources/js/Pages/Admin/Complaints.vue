<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SkeletonTable from '@/Components/SkeletonTable.vue';
import axios from 'axios';
import { resolveImageUrl } from '@/Utils/imageUrl';

const props = defineProps({
    complaints: Object,
    categories: Array,
    filters: Object,
    overdueCount: { type: Number, default: 0 },
});

const { t } = useI18n();

const statusLabel = computed(() => ({
    pending: t('pending'),
    processing: t('in_progress'),
    resolved: t('completed'),
    rejected: t('rejected'),
}));

const statusOptions = computed(() => [
    { value: 'pending', label: t('pending'), color: 'amber' },
    { value: 'processing', label: t('in_progress'), color: 'blue' },
    { value: 'resolved', label: t('completed'), color: 'green' },
    { value: 'rejected', label: t('rejected'), color: 'red' },
]);

// Data
const localComplaints = ref([...(props.complaints.data ?? [])]);
const isSearching = ref(false);
const isInitialLoad = ref(true);

watch(() => props.complaints, (newVal) => {
    localComplaints.value = [...(newVal?.data ?? [])];
    isSearching.value = false;
}, { deep: true });

// Filters
const activeStatus = ref(props.filters?.status || 'all');
const searchQuery = ref(props.filters?.search || '');
const filterCategory = ref(props.filters?.category_id || '');

// Sorting (must be declared before applyFilters references it)
const sortBy = ref(props.filters?.sort || 'newest');
const sortOptions = computed(() => [
    { value: 'newest', label: t('newest'), icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
    { value: 'oldest', label: t('oldest'), icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>' },
    { value: 'status', label: t('status'), icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>' },
    { value: 'rating', label: t('table_rating'), icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>' },
]);
const statusOrder = { pending: 0, processing: 1, resolved: 2, rejected: 3 };

// Server-side sorted — no need for client-side sorting
const sortedComplaints = computed(() => localComplaints.value);

let searchTimeout;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    isSearching.value = true;
    searchTimeout = setTimeout(() => {
        router.get(route('admin.complaints'), {
            status: activeStatus.value !== 'all' ? activeStatus.value : undefined,
            search: searchQuery.value || undefined,
            category_id: filterCategory.value || undefined,
            sort: sortBy.value !== 'newest' ? sortBy.value : undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['complaints', 'filters'],
            onFinish: () => {
                if (!router.page.props.errors) {
                    isSearching.value = false;
                }
            }
        });
    }, 300);
};

watch([activeStatus, filterCategory], applyFilters);
watch(searchQuery, applyFilters);
watch(sortBy, applyFilters);

// Network status
const isOnline = ref(navigator.onLine);
const offlineToast = ref(null);
let offlineToastTimer = null;

const showOfflineToast = (type) => {
    offlineToast.value = type;
    clearTimeout(offlineToastTimer);
    offlineToastTimer = setTimeout(() => { offlineToast.value = null; }, 5000);
};

onMounted(() => {
    window.addEventListener('online',  () => { isOnline.value = true; });
    window.addEventListener('offline', () => { isOnline.value = false; });
    setTimeout(() => { isInitialLoad.value = false; }, 150);
});

onUnmounted(() => {
    clearTimeout(offlineToastTimer);
});

// Status Update Modal
const showModal = ref(false);
const selectedComplaint = ref(null);

const statusForm = useForm({
    status: '',
    admin_response: '',
    estimated_completion_date: '',
    progress: 0,
});

const today = new Date();
today.setHours(0, 0, 0, 0);

const isOverdue = (complaint) => {
    if (!complaint.estimated_completion_date) return false;
    if (complaint.status === 'resolved' || complaint.status === 'rejected') return false;
    const est = new Date(complaint.estimated_completion_date);
    est.setHours(0, 0, 0, 0);
    return est < today;
};

const remainingDays = (complaint) => {
    if (!complaint.estimated_completion_date) return null;
    const est = new Date(complaint.estimated_completion_date);
    est.setHours(0, 0, 0, 0);
    return Math.round((est - today) / (1000 * 60 * 60 * 24));
};

const formatEstDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
};

const openUpdateModal = (complaint) => {
    if (!isOnline.value) { showOfflineToast('update'); return; }
    selectedComplaint.value = complaint;
    statusForm.status = complaint.status;
    statusForm.admin_response = complaint.admin_response || '';
    statusForm.estimated_completion_date = complaint.estimated_completion_date || '';
    statusForm.progress = complaint.progress ?? 0;
    showModal.value = true;
};

const submitStatusUpdate = () => {
    if (!isOnline.value) { showOfflineToast('update'); return; }
    statusForm.patch(route('admin.complaints.status', selectedComplaint.value.id), {
        onSuccess: () => {
            showModal.value = false;
            selectedComplaint.value = null;
        },
    });
};

// Detail Panel
const showDetail = ref(false);
const detailComplaint = ref(null);

const openDetail = async (complaint) => {
    detailComplaint.value = complaint;
    showDetail.value = true;

    // Menandai notifikasi laporan individual sebagai sudah dibaca oleh admin
    if (!complaint.admin_notified_at && complaint.status === 'pending') {
        try {
            await axios.post(route('admin.notifications.markSingleRead', complaint.id));
            // Update state lokal agar badge "BARU" hilang langsung
            complaint.admin_notified_at = new Date().toISOString();
            const idx = localComplaints.value.findIndex(c => c.id === complaint.id);
            if (idx !== -1) {
                localComplaints.value[idx].admin_notified_at = complaint.admin_notified_at;
            }
            // Memberitahu layout agar badge lonceng berkurang
            window.dispatchEvent(new Event('admin-notification-read'));
        } catch {
            // Silently ignore
        }
    }
};

// Lightbox
const lightboxOpen = ref(false);
const lightboxImages = ref([]);
const lightboxIndex = ref(0);

const openLightbox = (images, index) => {
    lightboxImages.value = images;
    lightboxIndex.value = index;
    lightboxOpen.value = true;
    document.addEventListener('keydown', handleLightboxKey);
};

const closeLightbox = () => {
    lightboxOpen.value = false;
    document.removeEventListener('keydown', handleLightboxKey);
};

const navLightbox = (dir) => {
    const len = lightboxImages.value.length;
    lightboxIndex.value = (lightboxIndex.value + dir + len) % len;
};

const handleLightboxKey = (e) => {
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') navLightbox(1);
    if (e.key === 'ArrowLeft') navLightbox(-1);
};

// Delete
const confirmDelete = ref(false);
const deleteTarget = ref(null);

const openDeleteModal = (complaint) => {
    deleteTarget.value = complaint;
    confirmDelete.value = true;
};

const submitDelete = () => {
    if (!isOnline.value) { showOfflineToast('delete'); confirmDelete.value = false; return; }
    router.delete(route('admin.complaints.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { confirmDelete.value = false; deleteTarget.value = null; },
    });
};

// Helpers
const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

const formatDateShort = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
};

const formatRelativeTime = (dateStr) => {
    const now = new Date();
    const date = new Date(dateStr);
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    if (diffMins < 1) return 'Baru saja';
    if (diffMins < 60) return `${diffMins} mnt lalu`;
    if (diffHours < 24) return `${diffHours} jam lalu`;
    if (diffDays < 7) return `${diffDays} hari lalu`;
    return formatDateShort(dateStr);
};

const ratingLabel = (rating) => {
    const labels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'];
    return labels[rating] || '';
};

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function getAvatarColor(name) {
    if (!name) return 'bg-gray-300';
    const colors = [
        'bg-violet-500', 'bg-pink-500', 'bg-blue-500', 'bg-emerald-500',
        'bg-amber-500', 'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500',
        'bg-teal-500', 'bg-purple-500',
    ];
    const hash = name.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
    return colors[hash % colors.length];
}
</script>

<template>
  <Head :title="t('manage_complaints')" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col">
        <p class="text-[10px] font-bold text-primary-400 uppercase tracking-[0.2em]">
          PUSAT MANAJEMEN
        </p>
        <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">
          {{ t('manage_complaints') }}
        </h1>
      </div>
    </template>

    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Overdue Warning -->
      <Transition name="net-err">
        <div
          v-if="overdueCount > 0"
          class="flex items-center gap-3 p-4 rounded-2xl border bg-gradient-to-r from-amber-50 to-orange-50 border-amber-200 text-amber-800 animate-fade-in-up shadow-sm"
        >
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-sm flex-shrink-0">
            <svg
              class="w-5 h-5 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            ><path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
            /></svg>
          </div>
          <div class="flex-1">
            <p class="font-bold text-sm">
              {{ t('overdue_warning') }}
            </p>
            <p
              class="text-xs mt-0.5 text-amber-700"
              v-html="`Ada <strong>${overdueCount} laporan</strong> yang melewati estimasi penyelesaian.`"
            />
          </div>
        </div>
      </Transition>

      <!-- Offline Toast -->
      <Transition name="net-err">
        <div
          v-if="offlineToast"
          class="flex items-center gap-3 p-4 rounded-2xl border bg-red-50 border-red-200 text-red-700 animate-fade-in-up"
        >
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-400 to-rose-500 flex items-center justify-center shadow-sm flex-shrink-0">
            <svg
              class="w-5 h-5 text-white animate-pulse"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            ><path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M12 12h.01M8.464 8.464a5 5 0 000 7.072M5.636 5.636a9 9 0 000 12.728"
            /></svg>
          </div>
          <div class="flex-1">
            <p class="font-bold text-sm">
              Tidak ada koneksi internet
            </p>
            <p class="text-xs mt-0.5">
              <template v-if="offlineToast === 'update'">
                Perubahan status tidak dapat disimpan saat offline.
              </template>
              <template v-else>
                Laporan tidak dapat dihapus saat offline.
              </template>
            </p>
          </div>
          <button
            class="p-2 hover:bg-red-100 rounded-lg transition flex-shrink-0"
            @click="offlineToast = null"
          >
            <svg
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            ><path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            /></svg>
          </button>
        </div>
      </Transition>

      <!-- Search & Category Filter -->
      <div class="glass-card premium-panel p-5 animate-fade-in-up">
        <div class="flex flex-col lg:flex-row gap-4">
          <div class="flex-1 relative group">
            <svg
              class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-primary-500 transition-colors duration-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('search_placeholder')"
              class="w-full pl-12 pr-12 py-3.5 text-sm bg-white/70 border border-gray-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-primary-500/15 focus:border-primary-400 transition-all duration-300 shadow-sm hover:border-gray-300 outline-none"
            >
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 scale-90"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-90"
            >
              <button
                v-if="searchQuery"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 hover:bg-red-50 p-1.5 rounded-lg transition-all"
                @click="searchQuery = ''"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                ><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M6 18L18 6M6 6l12 12"
                /></svg>
              </button>
            </Transition>
          </div>
          <div class="w-full lg:w-52">
            <select
              v-model="filterCategory"
              class="input-premium py-3.5 text-sm"
            >
              <option value="">
                {{ t('all_categories') }}
              </option>
              <option
                v-for="cat in categories"
                :key="cat.id"
                :value="cat.id"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Status Filter Pills -->
        <div class="flex gap-2 mt-4 flex-wrap">
          <button
            v-for="tab in [{ value: 'all', label: t('all') }, ...statusOptions]"
            :key="tab.value"
            class="filter-pill"
            :class="activeStatus === tab.value
              ? (tab.value === 'all' ? 'filter-pill-active' : tab.value === 'pending' ? 'filter-pill-active bg-amber-500 border-amber-400' : tab.value === 'processing' ? 'filter-pill-active bg-blue-500 border-blue-400' : tab.value === 'resolved' ? 'filter-pill-active bg-emerald-500 border-emerald-400' : 'filter-pill-active bg-rose-500 border-rose-400')
              : 'filter-pill-inactive'"
            @click="activeStatus = tab.value"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Complaints Table -->
      <div
        class="glass-card premium-panel overflow-hidden animate-fade-in-up min-h-[400px]"
        style="animation-delay: 0.05s;"
      >
        <!-- Skeleton Loading -->
        <div
          v-if="isInitialLoad"
          class="p-6 transition-all duration-500"
        >
          <SkeletonTable :rows="6" />
        </div>

        <div v-else-if="localComplaints.length > 0 || isSearching">
          <!-- Sort Controls -->
          <div class="flex items-center gap-3 px-6 py-4 border-b border-white/40">
            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ t('sort_by') }}</span>
            <div class="flex gap-2 flex-wrap items-center">
              <button
                v-for="opt in sortOptions"
                :key="opt.value"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all duration-300 relative overflow-hidden group hover-lift shrink-0"
                :class="sortBy === opt.value 
                  ? 'bg-primary-500/15 border border-primary-300/50 text-primary-700 shadow-[inset_0_2px_10px_rgba(139,92,246,0.1),0_0_10px_rgba(139,92,246,0.1)] backdrop-blur-md'
                  : 'bg-white/40 border border-white/60 text-gray-500 shadow-sm hover:bg-white/70 hover:text-gray-700 hover:border-white/80 backdrop-blur-sm'"
                @click="sortBy = opt.value"
              >
                <!-- Shine effect for active -->
                <div
                  v-if="sortBy === opt.value"
                  class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent -translate-x-full animate-[shine-sweep_3s_infinite] pointer-events-none"
                />
                                
                <span
                  class="flex items-center justify-center transition-transform duration-300 group-hover:scale-110" 
                  :class="sortBy === opt.value ? 'text-primary-600 drop-shadow-sm' : 'text-gray-400 group-hover:text-primary-500'" 
                  v-html="opt.icon"
                />
                <span class="relative z-10">{{ opt.label }}</span>
              </button>
            </div>
          </div>

          <div class="relative overflow-x-auto min-h-[300px]">
            <!-- Searching overlay -->
            <Transition
              enter-active-class="transition duration-300 ease-out"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition duration-200 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div
                v-if="isSearching"
                class="absolute inset-0 z-20 bg-white/40 backdrop-blur-[2px] flex items-center justify-center"
              >
                <div class="px-5 py-3 bg-white/90 rounded-2xl shadow-xl flex items-center gap-3 transform -translate-y-4">
                  <svg
                    class="animate-spin text-primary-500 w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                  ><circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  /><path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  /></svg>
                  <span class="text-sm font-bold text-primary-800 tracking-wide">Memuat data...</span>
                </div>
              </div>
            </Transition>

            <table class="table-premium w-full">
              <thead>
                <tr>
                  <th class="tbl-th">
                    {{ t('table_id') }}
                  </th>
                  <th class="tbl-th">
                    {{ t('table_reporter') }}
                  </th>
                  <th class="tbl-th">
                    {{ t('table_title') }}
                  </th>
                  <th class="tbl-th hidden md:table-cell">
                    {{ t('table_category') }}
                  </th>
                  <th class="tbl-th hidden lg:table-cell">
                    {{ t('table_location') }}
                  </th>
                  <th class="tbl-th">
                    {{ t('table_status') }}
                  </th>
                  <th class="tbl-th hidden xl:table-cell">
                    {{ t('table_estimation') }}
                  </th>
                  <th class="tbl-th hidden lg:table-cell">
                    {{ t('table_rating') }}
                  </th>
                  <th class="tbl-th hidden md:table-cell">
                    {{ t('table_date') }}
                  </th>
                  <th class="tbl-th text-right">
                    {{ t('table_action') }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="complaint in sortedComplaints"
                  :key="complaint.id"
                  class="group hover:bg-white/50 transition-all duration-300 cursor-pointer"
                  :class="{ 'bg-amber-50/40': !complaint.admin_notified_at && complaint.status === 'pending' }"
                  @click="openDetail(complaint)"
                >
                  <td class="tbl-td font-mono text-xs text-primary-500 font-bold">
                    <div class="flex items-center gap-1.5">
                      <span>#{{ complaint.id }}</span>
                      <span
                        v-if="!complaint.admin_notified_at && complaint.status === 'pending'"
                        class="new-badge"
                      >BARU</span>
                    </div>
                  </td>
                  <td class="tbl-td">
                    <div class="flex items-center gap-2.5">
                      <div :class="[getAvatarColor(complaint.user?.name), 'w-8 h-8 rounded-lg flex items-center justify-center text-white text-[10px] font-bold shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3 flex-shrink-0']">
                        {{ getInitials(complaint.user?.name) }}
                      </div>
                      <span class="text-sm font-semibold text-gray-800 group-hover:text-primary-700 transition-colors truncate max-w-[120px]">{{ complaint.user?.name }}</span>
                    </div>
                  </td>
                  <td class="tbl-td max-w-[160px]">
                    <p class="font-semibold text-sm text-gray-700 truncate group-hover:text-gray-900 transition-colors">
                      {{ complaint.title }}
                    </p>
                  </td>
                  <td class="tbl-td hidden md:table-cell">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-primary-50 text-primary-600 border border-primary-100">
                      <span
                        v-if="complaint.category?.parent"
                        class="text-primary-400"
                      >{{ complaint.category.parent.name }} › </span>{{ complaint.category?.name }}
                    </span>
                  </td>
                  <td class="tbl-td text-xs text-gray-500 max-w-[100px] truncate hidden lg:table-cell">
                    <span class="flex items-center gap-1">
                      <svg
                        class="w-3 h-3 text-gray-400 flex-shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      ><path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                      /><path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                      /></svg>
                      {{ complaint.location }}
                    </span>
                  </td>
                  <td class="tbl-td whitespace-nowrap">
                    <span
                      :class="'badge badge-' + complaint.status"
                      class="text-[10px]"
                    >
                      {{ statusLabel[complaint.status] }}
                    </span>
                  </td>
                  <!-- Estimation -->
                  <td class="tbl-td text-xs hidden xl:table-cell min-w-[160px]">
                    <template v-if="complaint.estimated_completion_date">
                      <div class="flex justify-between items-end mb-1">
                        <span
                          v-if="isOverdue(complaint)"
                          class="text-red-600 font-extrabold text-[10px] uppercase tracking-wider"
                        >⚠️ Terlewati</span>
                        <span
                          v-else-if="complaint.status === 'resolved' || complaint.status === 'rejected'"
                          class="text-emerald-600 font-extrabold text-[10px] uppercase tracking-wider"
                        >✅ Selesai</span>
                        <span
                          v-else
                          class="text-blue-600 font-extrabold text-[10px] uppercase tracking-wider"
                        >🕐 {{ remainingDays(complaint) }} Hari Lagi</span>
                        <span class="text-gray-400 text-[10px] font-black">{{ complaint.progress ?? 0 }}%</span>
                      </div>
                      <div class="w-full bg-gray-100/80 rounded-full h-1.5 overflow-hidden">
                        <div
                          class="h-full rounded-full transition-all duration-1000 ease-out"
                          :class="{
                            'bg-gradient-to-r from-red-400 to-red-500': isOverdue(complaint),
                            'bg-gradient-to-r from-emerald-400 to-emerald-500': complaint.status === 'resolved' || complaint.status === 'rejected',
                            'bg-gradient-to-r from-blue-400 to-primary-500': !isOverdue(complaint) && complaint.status !== 'resolved' && complaint.status !== 'rejected'
                          }"
                          :style="`width: ${complaint.progress ?? 0}%`"
                        />
                      </div>
                      <p class="text-[9px] text-gray-400 font-medium mt-1 flex items-center gap-1">
                        <svg
                          class="w-2.5 h-2.5"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        /></svg>
                        Target: {{ formatEstDate(complaint.estimated_completion_date) }}
                      </p>
                    </template>
                    <span
                      v-else
                      class="text-gray-300"
                    >—</span>
                  </td>
                  <!-- Rating -->
                  <td class="tbl-td hidden lg:table-cell">
                    <div
                      v-if="complaint.rating"
                      class="flex items-center gap-0.5"
                    >
                      <svg
                        v-for="star in 5"
                        :key="star"
                        class="w-3 h-3"
                        :class="star <= complaint.rating ? 'text-amber-400' : 'text-gray-200'"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                      <span class="text-[10px] font-bold text-amber-600 ml-0.5">{{ complaint.rating }}/5</span>
                    </div>
                    <span
                      v-else
                      class="text-xs text-gray-300"
                    >—</span>
                  </td>
                  <td class="tbl-td text-xs text-gray-400 whitespace-nowrap hidden md:table-cell">
                    {{ formatRelativeTime(complaint.created_at) }}
                  </td>
                  <td
                    class="tbl-td text-right whitespace-nowrap"
                    @click.stop
                  >
                    <div class="flex items-center justify-end gap-0.5">
                      <button
                        class="action-btn text-primary-500 hover:bg-primary-50"
                        title="Lihat Detail"
                        @click.stop="openDetail(complaint)"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        /><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        /></svg>
                      </button>
                      <button
                        class="action-btn text-amber-500 hover:bg-amber-50"
                        title="Update Status"
                        @click.stop="openUpdateModal(complaint)"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        /></svg>
                      </button>
                      <button
                        class="action-btn text-red-400 hover:bg-red-50 hover:text-red-600"
                        title="Hapus"
                        @click.stop="openDeleteModal(complaint)"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        /></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else
          class="text-center py-24 px-6 relative group border border-transparent"
        >
          <div class="relative w-24 h-24 mx-auto mb-6 cursor-default">
            <!-- Floating Blobs behind the icon -->
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-gradient-to-tr from-primary-300/60 to-fuchsia-300/60 rounded-full blur-[12px] animate-[float_6s_ease-in-out_infinite] group-hover:scale-125 group-hover:-translate-y-2 group-hover:translate-x-2 transition-all duration-700" />
            <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-gradient-to-bl from-blue-300/60 to-primary-300/60 rounded-full blur-[12px] animate-[float_4s_ease-in-out_infinite_reverse] group-hover:scale-125 group-hover:translate-y-2 group-hover:-translate-x-2 transition-all duration-700" />
                        
            <!-- White Glass Icon Container -->
            <div class="absolute inset-0 bg-white/60 backdrop-blur-md border border-white/80 rounded-[1.5rem] flex items-center justify-center shadow-[0_8px_30px_rgb(0,0,0,0.04)] transform group-hover:-translate-y-2 group-hover:shadow-[0_15px_40px_rgb(0,0,0,0.08)] transition-all duration-500 ease-out z-10">
              <svg
                class="w-10 h-10 text-primary-500 transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
            </div>
          </div>

          <div class="relative z-10 transition-transform duration-500 group-hover:-translate-y-1">
            <h3 class="text-2xl font-black text-primary-900 mb-3 tracking-tight group-hover:text-primary-700 transition-colors">
              {{ t('no_complaints_found') }}
            </h3>
            <p class="text-primary-800/70 text-sm font-medium mb-8 max-w-sm mx-auto leading-relaxed group-hover:text-primary-800 transition-colors">
              {{ t('no_complaints_filter_desc') }}
            </p>
            <button
              v-if="searchQuery || filterCategory || activeStatus !== 'all'"
              class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-500 to-primary-600 text-white font-bold px-8 py-3.5 rounded-full hover:shadow-[0_8px_20px_-6px_rgba(124,58,237,0.5)] hover:-translate-y-1 transition-all duration-300"
              @click="searchQuery = ''; filterCategory = ''; activeStatus = 'all'"
            >
              {{ t('show_all') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="complaints.links?.length > 3"
        class="flex justify-between items-center px-2 pb-4"
      >
        <p class="text-xs text-gray-400 font-medium">
          Menampilkan {{ complaints.from }}–{{ complaints.to }} dari {{ complaints.total }} laporan
        </p>
        <div class="flex gap-1.5">
          <template
            v-for="(link, index) in complaints.links"
            :key="index"
          >
            <button
              v-if="link.url"
              class="pagination-btn"
              :class="link.active ? 'pagination-btn-active' : 'pagination-btn-inactive'"
              @click="router.get(link.url, { status: activeStatus !== 'all' ? activeStatus : undefined, search: searchQuery || undefined, category_id: filterCategory || undefined, sort: sortBy !== 'newest' ? sortBy : undefined }, { preserveScroll: true, preserveState: true })"
              v-html="link.label"
            />
            <div
              v-else
              class="pagination-btn pagination-btn-disabled"
              v-html="link.label"
            />
          </template>
        </div>
      </div>
    </div>

    <!-- DETAIL MODAL (Management Console) -->
    <Teleport to="body">
      <Transition
        name="modal"
        appear
      >
        <div
          v-if="showDetail"
          class="modal-overlay"
          @click.self="showDetail = false"
        >
          <div class="detail-modal">
            <!-- Header -->
            <div class="flex items-start justify-between mb-6">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <p class="text-[10px] font-bold text-primary-400 uppercase tracking-[0.15em]">
                    LAPORAN › DETAIL LAPORAN
                  </p>
                </div>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">
                  Konsol Manajemen
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">
                  Meninjau laporan <span class="font-bold text-primary-500">#RPT-{{ String(detailComplaint?.id).padStart(4, '0') }}</span>
                </p>
              </div>
              <button
                class="p-2 hover:bg-gray-100 rounded-xl transition-all duration-200 group"
                @click="showDetail = false"
              >
                <svg
                  class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                ><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                /></svg>
              </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <!-- Left: Report Content -->
              <div class="lg:col-span-2 space-y-5">
                <!-- Title & Status Card -->
                <div class="bg-white/60 backdrop-blur-md border border-white/80 rounded-2xl p-5">
                  <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex items-start gap-3">
                      <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg
                          class="w-5 h-5 text-white"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        /></svg>
                      </div>
                      <div>
                        <h3 class="text-base font-extrabold text-gray-900 leading-tight">
                          {{ detailComplaint?.title }}
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                          {{ detailComplaint?.location }} <span v-if="detailComplaint?.location_detail">· {{ detailComplaint?.location_detail }}</span>
                        </p>
                      </div>
                    </div>
                    <span :class="'badge badge-' + detailComplaint?.status">
                      {{ statusLabel[detailComplaint?.status] }}
                    </span>
                  </div>

                  <!-- Meta Info -->
                  <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50/80 rounded-xl">
                    <div>
                      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                        Pelapor
                      </p>
                      <p class="text-sm font-bold text-gray-800 mt-0.5">
                        {{ detailComplaint?.user?.name }}
                      </p>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                        Kategori
                      </p>
                      <p class="text-sm font-bold text-gray-800 mt-0.5">
                        <span v-if="detailComplaint?.category?.parent">{{ detailComplaint.category.parent.name }} › </span>{{ detailComplaint?.category?.name }}
                      </p>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                        Dilaporkan
                      </p>
                      <p class="text-sm font-bold text-gray-800 mt-0.5">
                        {{ formatDateShort(detailComplaint?.created_at) }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Description -->
                <div class="bg-white/60 backdrop-blur-md border border-white/80 rounded-2xl p-5">
                  <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">
                    DESKRIPSI LAPORAN
                  </p>
                  <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                    {{ detailComplaint?.description }}
                  </p>
                </div>

                <!-- Photos -->
                <div
                  v-if="detailComplaint?.image_paths?.length > 0"
                  class="bg-white/60 backdrop-blur-md border border-white/80 rounded-2xl p-5"
                >
                  <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">
                    FOTO BUKTI ({{ detailComplaint.image_paths.length }})
                  </p>
                  <div :class="detailComplaint.image_paths.length === 1 ? 'flex' : 'grid grid-cols-2 gap-3'">
                    <img
                      v-for="(imgPath, idx) in detailComplaint.image_paths"
                      :key="idx"
                      :src="resolveImageUrl(imgPath)"
                      :alt="'Foto ' + (idx + 1)"
                      class="rounded-xl object-cover border border-gray-200 cursor-zoom-in hover:opacity-90 transition-all duration-300 hover:scale-[1.02] shadow-sm"
                      :class="detailComplaint.image_paths.length === 1 ? 'max-h-52' : 'h-36 w-full'"
                      @click="openLightbox(detailComplaint.image_paths.map(p => resolveImageUrl(p)), idx)"
                    >
                  </div>
                </div>
                <div v-else-if="detailComplaint?.image_path">
                  <div class="bg-white/60 backdrop-blur-md border border-white/80 rounded-2xl p-5">
                    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">
                      FOTO BUKTI
                    </p>
                    <img
                      :src="resolveImageUrl(detailComplaint.image_path)"
                      alt="Foto"
                      class="max-h-52 rounded-xl object-cover border border-gray-200 cursor-zoom-in hover:opacity-90 transition-all duration-300 hover:scale-[1.02] shadow-sm"
                      @click="openLightbox([resolveImageUrl(detailComplaint.image_path)], 0)"
                    >
                  </div>
                </div>

                <!-- Admin Response -->
                <div
                  v-if="detailComplaint?.admin_response"
                  class="bg-primary-50/80 backdrop-blur-md border border-primary-100 rounded-2xl p-5"
                >
                  <p class="text-[10px] font-extrabold text-primary-500 uppercase tracking-widest mb-2">
                    💬 RESPON ADMIN
                  </p>
                  <p class="text-sm text-primary-800 leading-relaxed">
                    {{ detailComplaint.admin_response }}
                  </p>
                </div>
              </div>

              <!-- Right: Management Actions -->
              <div class="space-y-5">
                <!-- Management Actions Card -->
                <div class="bg-white/60 backdrop-blur-md border border-white/80 rounded-2xl p-5">
                  <h4 class="text-[10px] font-extrabold text-gray-900 uppercase tracking-widest mb-4">
                    AKSI MANAJEMEN
                  </h4>
                  <div class="space-y-2.5">
                    <button
                      class="w-full flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-bold text-sm shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300"
                      @click="showDetail = false; openUpdateModal(detailComplaint)"
                    >
                      <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      ><path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                      /></svg>
                      Ubah Status
                    </button>
                    <button
                      class="w-full flex items-center gap-3 px-4 py-3 bg-white border border-gray-200 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-50 hover:border-rose-200 hover:-translate-y-0.5 transition-all duration-300"
                      @click="showDetail = false; openDeleteModal(detailComplaint)"
                    >
                      <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      ><path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      /></svg>
                      Hapus Laporan
                    </button>
                  </div>
                </div>

                <!-- Estimation & Progress -->
                <div
                  v-if="detailComplaint?.estimated_completion_date"
                  class="bg-white/60 backdrop-blur-md border border-white/80 rounded-2xl p-5"
                >
                  <h4 class="text-[10px] font-extrabold text-gray-900 uppercase tracking-widest mb-3">
                    ESTIMASI PENYELESAIAN
                  </h4>
                  <div class="flex items-center justify-between mb-2">
                    <span
                      v-if="isOverdue(detailComplaint)"
                      class="text-red-600 font-bold text-xs"
                    >⚠️ Terlewati</span>
                    <span
                      v-else-if="detailComplaint.status === 'resolved' || detailComplaint.status === 'rejected'"
                      class="text-emerald-600 font-bold text-xs"
                    >✅ Selesai</span>
                    <span
                      v-else
                      class="text-blue-600 font-bold text-xs"
                    >🕐 {{ remainingDays(detailComplaint) }} Hari Lagi</span>
                    <span class="text-sm font-black text-gray-800">{{ detailComplaint.progress ?? 0 }}%</span>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden mb-2">
                    <div
                      class="h-full rounded-full transition-all duration-1000 ease-out"
                      :class="{
                        'bg-gradient-to-r from-red-400 to-red-500': isOverdue(detailComplaint),
                        'bg-gradient-to-r from-emerald-400 to-emerald-500': detailComplaint.status === 'resolved' || detailComplaint.status === 'rejected',
                        'bg-gradient-to-r from-blue-400 to-primary-500': !isOverdue(detailComplaint) && detailComplaint.status !== 'resolved' && detailComplaint.status !== 'rejected'
                      }"
                      :style="`width: ${detailComplaint.progress ?? 0}%`"
                    />
                  </div>
                  <p class="text-[10px] text-gray-500 font-medium">
                    Target: <span class="font-bold text-gray-700">{{ formatEstDate(detailComplaint.estimated_completion_date) }}</span>
                  </p>
                </div>

                <!-- Rating -->
                <div
                  v-if="detailComplaint?.rating"
                  class="bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-200 rounded-2xl p-5"
                >
                  <h4 class="text-[10px] font-extrabold text-amber-700 uppercase tracking-widest mb-3">
                    ⭐ RATING KEPUASAN
                  </h4>
                  <div class="flex items-center gap-1 mb-2">
                    <svg
                      v-for="star in 5"
                      :key="star"
                      class="w-5 h-5"
                      :class="star <= detailComplaint.rating ? 'text-amber-400' : 'text-gray-200'"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="text-sm font-bold text-amber-700 ml-1">{{ ratingLabel(detailComplaint.rating) }} ({{ detailComplaint.rating }}/5)</span>
                  </div>
                  <p
                    v-if="detailComplaint.rating_comment"
                    class="text-sm text-amber-800 italic leading-relaxed"
                  >
                    "{{ detailComplaint.rating_comment }}"
                  </p>
                </div>
                <div
                  v-else-if="detailComplaint?.status === 'resolved'"
                  class="bg-white/60 backdrop-blur-md border border-white/80 rounded-2xl p-5"
                >
                  <div class="flex items-center gap-2 text-xs text-gray-400">
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    ><path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                    /></svg>
                    Menunggu rating dari pelapor
                  </div>
                </div>

                <!-- Activity Timeline -->
                <div class="bg-white/60 backdrop-blur-md border border-white/80 rounded-2xl p-5">
                  <h4 class="text-[10px] font-extrabold text-gray-900 uppercase tracking-widest mb-4">
                    AKTIVITAS TERKINI
                  </h4>
                  <div class="space-y-3">
                    <div class="flex items-start gap-3">
                      <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg
                          class="w-3.5 h-3.5 text-blue-600"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        /></svg>
                      </div>
                      <div>
                        <p class="text-xs font-bold text-gray-800">
                          Laporan Dibuat
                        </p>
                        <p class="text-[10px] text-gray-400">
                          {{ formatDate(detailComplaint?.created_at) }}
                        </p>
                      </div>
                    </div>
                    <div
                      v-if="detailComplaint?.admin_response"
                      class="flex items-start gap-3"
                    >
                      <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg
                          class="w-3.5 h-3.5 text-purple-600"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        /></svg>
                      </div>
                      <div>
                        <p class="text-xs font-bold text-gray-800">
                          Admin Merespons
                        </p>
                        <p class="text-[10px] text-gray-400">
                          Respon telah diberikan
                        </p>
                      </div>
                    </div>
                    <div
                      v-if="detailComplaint?.status !== 'pending'"
                      class="flex items-start gap-3"
                    >
                      <div
                        class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                        :class="{
                          'bg-amber-100': detailComplaint?.status === 'processing',
                          'bg-emerald-100': detailComplaint?.status === 'resolved',
                          'bg-rose-100': detailComplaint?.status === 'rejected',
                        }"
                      >
                        <svg
                          class="w-3.5 h-3.5"
                          :class="{
                            'text-amber-600': detailComplaint?.status === 'processing',
                            'text-emerald-600': detailComplaint?.status === 'resolved',
                            'text-rose-600': detailComplaint?.status === 'rejected',
                          }"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        /></svg>
                      </div>
                      <div>
                        <p class="text-xs font-bold text-gray-800">
                          Status: {{ statusLabel[detailComplaint?.status] }}
                        </p>
                        <p class="text-[10px] text-gray-400">
                          Diperbarui oleh admin
                        </p>
                      </div>
                    </div>
                    <div
                      v-if="detailComplaint?.rating"
                      class="flex items-start gap-3"
                    >
                      <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg
                          class="w-3.5 h-3.5 text-amber-600"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        ><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                      </div>
                      <div>
                        <p class="text-xs font-bold text-gray-800">
                          Rating Diberikan: {{ detailComplaint.rating }}/5
                        </p>
                        <p class="text-[10px] text-gray-400">
                          Oleh pelapor
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- UPDATE STATUS MODAL (Reference Design) -->
    <Teleport to="body">
      <Transition
        name="modal"
        appear
      >
        <div
          v-if="showModal"
          class="modal-overlay"
          style="background: rgba(15, 23, 42, 0.2);"
          @click.self="showModal = false"
        >
          <div
            class="modal-content max-w-[500px] p-8 shadow-2xl relative"
            style="background: #efeff1; border: 1px solid rgba(255, 255, 255, 0.6); border-radius: 2rem;"
          >
            <!-- Header -->
            <div class="flex items-start justify-between mb-6">
              <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 shadow-sm">
                  MANAJEMEN TUGAS
                </p>
                <h3 class="text-[17px] font-extrabold text-gray-800 tracking-tight">
                  Update Status Laporan
                </h3>
              </div>
              <button
                class="p-1 hover:bg-gray-200/50 rounded-full transition text-gray-400 hover:text-gray-600"
                @click="showModal = false"
              >
                <svg
                  class="w-5 h-5 text-gray-500 font-bold"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                ><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M6 18L18 6M6 6l12 12"
                /></svg>
              </button>
            </div>

            <form
              class="space-y-6"
              @submit.prevent="submitStatusUpdate"
            >
              <!-- Offline warning -->
              <Transition name="net-err">
                <div
                  v-if="!isOnline"
                  class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"
                >
                  <svg
                    class="w-5 h-5 flex-shrink-0 animate-pulse"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M12 12h.01M8.464 8.464a5 5 0 000 7.072M5.636 5.636a9 9 0 000 12.728"
                  /></svg>
                  <span>Tidak ada koneksi — perubahan <strong>tidak dapat disimpan</strong> saat offline.</span>
                </div>
              </Transition>

              <!-- Top Row: Status & Date -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-wider">STATUS PEKERJAAN</label>
                  <div class="relative">
                    <select
                      v-model="statusForm.status"
                      class="w-full pl-4 pr-10 py-2.5 text-sm font-semibold text-gray-700 bg-white/90 border border-white rounded-xl shadow-sm appearance-none focus:ring-2 focus:ring-primary-500/30 outline-none transition-all"
                    >
                      <option
                        v-for="opt in statusOptions"
                        :key="opt.value"
                        :value="opt.value"
                      >
                        {{ opt.label }}
                      </option>
                    </select>
                    <svg
                      class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    ><path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2.5"
                      d="M19 9l-7 7-7-7"
                    /></svg>
                  </div>
                  <InputError
                    class="mt-1"
                    :message="statusForm.errors.status"
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-wider">ESTIMASI SELESAI</label>
                  <div class="relative">
                    <input
                      v-model="statusForm.estimated_completion_date"
                      type="date"
                      class="w-full pl-4 pr-10 py-2.5 text-sm font-semibold text-gray-700 bg-white/90 border border-white rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500/30 outline-none transition-all"
                      :min="new Date().toISOString().split('T')[0]"
                    >
                    <svg
                      class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    ><path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2.5"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    /></svg>
                  </div>
                  <InputError
                    class="mt-1"
                    :message="statusForm.errors.estimated_completion_date"
                  />
                </div>
              </div>

              <!-- Progress Slider Card -->
              <div class="bg-white/80 backdrop-blur-sm rounded-[1.5rem] p-5 shadow-sm border border-white">
                <div class="flex items-center justify-between mb-4">
                  <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">PROGRESS PENYELESAIAN</label>
                  <div class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[9px] font-bold uppercase tracking-wider h-5 flex items-center">
                    {{ statusForm.progress }}% SELESAI
                  </div>
                </div>
                                
                <div class="relative pb-2">
                  <div class="relative pt-2">
                    <input
                      v-model="statusForm.progress"
                      type="range"
                      min="0"
                      max="100"
                      step="5"
                      class="ref-progress-slider relative z-10"
                      :style="`background: linear-gradient(to right, #a855f7 0%, #d946ef ${statusForm.progress}%, #e5e7eb ${statusForm.progress}%, #e5e7eb 100%);`"
                      :disabled="statusForm.status === 'resolved' || statusForm.status === 'rejected'"
                      :class="{'opacity-50 cursor-not-allowed': statusForm.status === 'resolved' || statusForm.status === 'rejected'}"
                    >
                  </div>
                  <div class="flex justify-between mt-2 px-1 text-[10px] font-medium text-gray-400 italic">
                    <span>Tahap Awal</span>
                    <span>Serah Terima</span>
                  </div>
                  <p
                    v-if="statusForm.status === 'resolved' || statusForm.status === 'rejected'"
                    class="text-[9px] text-amber-600 mt-2 font-medium bg-amber-50/80 p-1.5 rounded-lg border border-amber-100/50"
                  >
                    ℹ️ Progress otomatis 100% untuk status Selesai/Ditolak.
                  </p>
                </div>
              </div>

              <!-- Admin Response -->
              <div>
                <label class="block text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-widest">TANGGAPAN ADMIN / CATATAN</label>
                <textarea
                  v-model="statusForm.admin_response"
                  class="w-full px-4 py-3 text-xs font-medium bg-white text-gray-700 border border-white rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500/30 outline-none resize-none transition-all placeholder:text-gray-400"
                  rows="3"
                  placeholder="Laporan telah diverifikasi..."
                />
                <InputError
                  class="mt-1"
                  :message="statusForm.errors.admin_response"
                />
              </div>

              <!-- Bukti Foto -->
              <div
                v-if="selectedComplaint?.image_paths?.length > 0 || selectedComplaint?.image_path"
                class="flex gap-3 pt-1 pb-2"
              >
                <template v-if="selectedComplaint?.image_paths?.length > 0">
                  <div
                    v-for="(imgPath, idx) in selectedComplaint.image_paths"
                    :key="idx"
                    class="w-14 h-14 rounded-2xl overflow-hidden border-2 border-white shadow-sm shrink-0 relative flex items-center justify-center group cursor-zoom-in"
                    @click="openLightbox(selectedComplaint.image_paths.map(p => resolveImageUrl(p)), idx)"
                  >
                    <img
                      :src="resolveImageUrl(imgPath)"
                      class="w-full h-full object-cover group-hover:scale-110 transition duration-300"
                      alt="Bukti Foto"
                    >
                  </div>
                </template>
                <template v-else-if="selectedComplaint?.image_path">
                  <div
                    class="w-14 h-14 rounded-2xl overflow-hidden border-2 border-white shadow-sm shrink-0 relative flex items-center justify-center group cursor-zoom-in"
                    @click="openLightbox([resolveImageUrl(selectedComplaint.image_path)], 0)"
                  >
                    <img
                      :src="resolveImageUrl(selectedComplaint.image_path)"
                      class="w-full h-full object-cover group-hover:scale-110 transition duration-300"
                      alt="Bukti Foto"
                    >
                  </div>
                </template>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-3 border-t border-gray-300/30 pt-5">
                <button
                  type="button"
                  class="px-7 py-2.5 rounded-full bg-white text-gray-600 font-extrabold text-xs hover:bg-gray-50 hover:text-gray-900 transition border border-gray-200 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.1)]"
                  @click="showModal = false"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  class="px-7 py-2.5 rounded-full bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white font-extrabold text-xs hover:shadow-lg hover:-translate-y-0.5 transition shadow-md flex items-center justify-center border border-white/20"
                  style="box-shadow: 0 6px 15px -3px rgba(168, 85, 247, 0.45);"
                  :class="{ 'opacity-60 cursor-not-allowed': statusForm.processing || !isOnline }"
                  :disabled="statusForm.processing || !isOnline"
                >
                  <svg
                    v-if="statusForm.processing"
                    class="animate-spin w-3 h-3 mr-2"
                    fill="none"
                    viewBox="0 0 24 24"
                  ><circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  /><path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  /></svg>
                  <span v-if="!statusForm.processing && isOnline">Simpan Perubahan</span>
                  <span v-else-if="statusForm.processing">Menyimpan...</span>
                  <span v-else>Offline</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- DELETE MODAL -->
    <Teleport to="body">
      <Transition
        name="modal"
        appear
      >
        <div
          v-if="confirmDelete"
          class="modal-overlay"
          @click.self="confirmDelete = false"
        >
          <div class="modal-content max-w-sm p-6 rounded-2xl">
            <div class="text-center">
              <div class="relative w-20 h-20 mx-auto mb-6 group cursor-default">
                <div class="absolute top-1 right-1 w-14 h-14 rounded-2xl bg-gradient-to-tr from-red-200 to-rose-200 shadow-sm transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
                <div class="absolute bottom-1 left-1 w-16 h-16 rounded-2xl bg-white/60 backdrop-blur-md border border-white/80 flex items-center justify-center shadow-lg transition-transform duration-300 group-hover:-translate-y-1">
                  <svg
                    class="w-8 h-8 text-red-500 drop-shadow-sm"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  /></svg>
                </div>
              </div>
              <h3 class="text-lg font-extrabold text-gray-900 mb-1">
                {{ t('delete_complaint') }}
              </h3>
              <p class="text-sm text-gray-500 mb-1">
                {{ t('delete_confirm_desc') }}
              </p>
              <div class="bg-gray-50 rounded-xl p-3 mb-6 border border-gray-100">
                <p class="text-sm font-semibold text-gray-700 truncate">
                  {{ deleteTarget?.title }}
                </p>
              </div>
              <div class="flex gap-3">
                <button
                  class="flex-1 py-3 bg-red-500 text-white font-bold tracking-wide rounded-xl hover:bg-red-600 transition text-xs uppercase"
                  @click="submitDelete"
                >
                  {{ t('yes_delete') }}
                </button>
                <button
                  class="flex-1 py-3 bg-gray-100 text-gray-700 font-bold tracking-wide rounded-xl hover:bg-gray-200 transition text-xs uppercase"
                  @click="confirmDelete = false"
                >
                  {{ t('cancel') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- LIGHTBOX -->
    <Teleport to="body">
      <Transition name="lightbox">
        <div
          v-if="lightboxOpen"
          class="fixed inset-0 z-[200] flex items-center justify-center bg-gray-900/70 backdrop-blur-sm"
          @click.self="closeLightbox"
        >
          <button
            class="absolute top-4 right-4 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white transition"
            @click="closeLightbox"
          >
            ✕
          </button>
          <div
            v-if="lightboxImages.length > 1"
            class="absolute top-4 left-1/2 -translate-x-1/2 text-white/60 text-sm font-medium"
          >
            {{ lightboxIndex + 1 }} / {{ lightboxImages.length }}
          </div>
          <button
            v-if="lightboxImages.length > 1"
            class="absolute left-4 w-12 h-12 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/25 text-white text-xl transition"
            @click="navLightbox(-1)"
          >
            ‹
          </button>
          <img
            :src="lightboxImages[lightboxIndex]"
            :alt="'Foto ' + (lightboxIndex + 1)"
            class="max-w-[90vw] max-h-[85vh] object-contain rounded-xl shadow-2xl select-none"
            style="animation: lightboxPop 0.35s cubic-bezier(0.22, 1, 0.36, 1);"
          >
          <button
            v-if="lightboxImages.length > 1"
            class="absolute right-4 w-12 h-12 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/25 text-white text-xl transition"
            @click="navLightbox(1)"
          >
            ›
          </button>
        </div>
      </Transition>
    </Teleport>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Table cells */
.tbl-th {
    @apply py-3.5 px-4 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest border-b border-gray-100;
}
.tbl-td {
    @apply py-3.5 px-4 text-sm;
}

/* Filter Pills */
.filter-pill {
    @apply px-5 py-2.5 rounded-xl text-sm font-semibold border transition-all duration-300;
}
.filter-pill-active {
    @apply bg-gradient-to-r from-primary-500 to-primary-600 text-white border-primary-400 shadow-lg -translate-y-0.5;
}
.filter-pill-inactive {
    @apply bg-white/60 backdrop-blur-md text-gray-500 border-white/80 hover:bg-white/80 hover:text-primary-600 hover:-translate-y-0.5;
}

/* Sort Pills */
.sort-pill {
    @apply px-3 py-1.5 rounded-lg text-[11px] font-semibold border transition-all duration-300 flex items-center gap-1.5;
}
.sort-pill-active {
    @apply bg-white/90 text-primary-700 border-white shadow-sm -translate-y-0.5;
    box-shadow: 0 4px 12px -2px rgba(139, 92, 246, 0.15), inset 0 1px 1px rgba(255, 255, 255, 1);
}
.sort-pill-inactive {
    @apply bg-white/40 text-gray-500 border-white/50 hover:bg-white/60 hover:text-primary-600;
}

/* Action Buttons */
.action-btn {
    @apply p-2 rounded-lg transition-all duration-200;
}

/* Pagination */
.pagination-btn {
    @apply px-3 py-2 text-[11px] font-extrabold rounded-lg transition-all duration-300 border h-9 min-w-[2.25rem] flex items-center justify-center;
}
.pagination-btn-active {
    @apply bg-gradient-to-r from-primary-500 to-primary-600 text-white border-primary-400;
    box-shadow: 0 6px 16px -4px rgba(124, 58, 237, 0.4);
    transform: translateY(-1px);
}
.pagination-btn-inactive {
    @apply bg-white/60 backdrop-blur-md text-gray-500 border-white/80 hover:bg-white hover:text-primary-600;
}
.pagination-btn-inactive:hover { transform: translateY(-1px); }
.pagination-btn-disabled {
    @apply bg-white/30 text-gray-300 border-white/30 cursor-not-allowed;
}

/* Detail Modal */
.detail-modal {
    width: 90%;
    max-width: 56rem;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(32px);
    -webkit-backdrop-filter: blur(32px);
    border: 1px solid rgba(255, 255, 255, 1);
    border-radius: 1.75rem;
    box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 0 rgba(255, 255, 255, 1),
        0 10px 30px rgba(124, 58, 237, 0.05);
}

/* Status Select Buttons */
.status-select-btn {
    @apply flex items-center gap-2 px-4 py-3 rounded-xl border-2 text-sm font-bold transition-all duration-300 cursor-pointer;
}
.status-select-active {
    @apply shadow-sm -translate-y-0.5;
}

/* Progress Slider */
.progress-slider {
    @apply w-full h-2.5 rounded-lg appearance-none cursor-pointer;
    background: linear-gradient(to right, #ddd6fe, #c4b5fd);
}
.progress-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
    border: 3px solid white;
    transition: transform 0.2s ease;
}
.progress-slider::-webkit-slider-thumb:hover {
    transform: scale(1.15);
}
.progress-slider:focus {
    outline: none;
}

/* Lightbox */
.lightbox-enter-active { transition: opacity 0.3s ease; }
.lightbox-leave-active { transition: opacity 0.22s ease; }
.lightbox-enter-from, .lightbox-leave-to { opacity: 0; }
@keyframes lightboxPop {
    from { opacity: 0; transform: scale(0.94) translateY(8px); }
    to   { opacity: 1; transform: scale(1); }
}

/* New Badge */
.new-badge {
    display: inline-flex;
    align-items: center;
    padding: 1px 6px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: white;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    border-radius: 6px;
    animation: newBadgePulse 2s ease-in-out infinite;
    box-shadow: 0 2px 8px -2px rgba(239, 68, 68, 0.4);
    line-height: 16px;
}
@keyframes newBadgePulse {
    0%, 100% { box-shadow: 0 2px 8px -2px rgba(239, 68, 68, 0.4); }
    50% { box-shadow: 0 2px 12px -1px rgba(239, 68, 68, 0.6); }
}

/* Custom scrollbar for detail modal */
.detail-modal::-webkit-scrollbar { width: 6px; }
.detail-modal::-webkit-scrollbar-track { background: transparent; }
.detail-modal::-webkit-scrollbar-thumb { background: rgba(196, 181, 253, 0.4); border-radius: 9999px; }
.detail-modal::-webkit-scrollbar-thumb:hover { background: rgba(167, 139, 250, 0.7); }
</style>
