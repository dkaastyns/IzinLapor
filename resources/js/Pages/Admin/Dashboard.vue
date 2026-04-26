<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import SkeletonTable from '@/Components/SkeletonTable.vue';
import axios from 'axios';
import { resolveImageUrl } from '@/Utils/imageUrl';

// Props
const props = defineProps({
    stats: Object,
    recentComplaints: Object,
    categories: Array,
    filters: Object,
    worstFacilities: Array,
});

const { t } = useI18n();

// Animated counter
const animatedStats = ref({
    total: 0,
    pending: 0,
    processing: 0,
    resolved: 0,
    rejected: 0,
});

let _rafIds = [];
function animateCounter(key, target, duration = 800) {
    const start = animatedStats.value[key];
    const diff = target - start;
    if (diff === 0) { animatedStats.value[key] = target; return; }
    const startTime = performance.now();
    function step(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        animatedStats.value[key] = Math.round(start + diff * ease);
        if (progress < 1) { _rafIds.push(requestAnimationFrame(step)); }
    }
    _rafIds.push(requestAnimationFrame(step));
}

// Card hover state
const hoveredCard = ref(null);

function cardEnterStyle(name) {
    const isHovered = hoveredCard.value === name;
    return {
        transform: isHovered ? 'translateY(-4px) scale(1.01)' : 'translateY(0) scale(1)',
    };
}

// Status labels & colors
const statusLabel = computed(() => ({
    pending: t('pending'),
    processing: t('in_progress'),
    resolved: t('completed'),
    rejected: t('rejected'),
}));

const formatDate = (dateStr) => {
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
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatDateShort = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

// Active filter pill
const activeFilter = ref(null);

// Widget expand toggles
const showAllCategories = ref(false);
const showAllFacilities = ref(false);

const displayedCategories = computed(() => {
    if (!props.categories?.length) return [];
    return showAllCategories.value ? props.categories : props.categories.slice(0, 4);
});

const displayedFacilities = computed(() => {
    if (!props.worstFacilities?.length) return [];
    return showAllFacilities.value ? props.worstFacilities : props.worstFacilities.slice(0, 4);
});

// Sorting
const sortBy = ref(props.filters?.sort || 'newest');
const sortOptions = computed(() => [
    { value: 'newest', label: t('newest'), icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
    { value: 'oldest', label: t('oldest'), icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>' },
    { value: 'status', label: t('status'), icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>' },
    { value: 'rating', label: t('table_rating'), icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>' },
]);
const statusOrder = { pending: 0, processing: 1, resolved: 2, rejected: 3 };

// Local State
const localComplaints = ref([...(props.recentComplaints?.data ?? [])]);
const isSearching = ref(false);
const isInitialLoad = ref(true);
const tableVisible = ref(false);

watch(() => props.recentComplaints, (newVal) => {
    localComplaints.value = [...(newVal?.data ?? [])];
    isSearching.value = false;
}, { deep: true });

onMounted(() => {
    // Kick off counter animations — fast start
    Object.keys(animatedStats.value).forEach((key, i) => {
        setTimeout(() => {
            animateCounter(key, props.stats[key] ?? 0, 600);
        }, 30 + i * 50);
    });

    setTimeout(() => {
        isInitialLoad.value = false;
        tableVisible.value = true;
    }, 80);
});

onUnmounted(() => {
    _rafIds.forEach(id => cancelAnimationFrame(id));
    _rafIds = [];
});

// Server-side sorted & filtered — no client-side resorting needed
const filteredComplaints = computed(() => localComplaints.value);

function selectFilter(status) {
    activeFilter.value = activeFilter.value === status ? null : status;
    // Trigger server request with the new status filter
    applyDashboardFilters();
    if (activeFilter.value !== null) {
        setTimeout(() => {
            document.getElementById('complaints-table')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }, 50);
    }
}

// Search
const searchQuery = ref(props.filters?.search || '');
let searchTimeout;
const applyDashboardFilters = () => {
    clearTimeout(searchTimeout);
    isSearching.value = true;
    searchTimeout = setTimeout(() => {
        router.get(route('admin.dashboard'), {
            search: searchQuery.value || undefined,
            sort: sortBy.value !== 'newest' ? sortBy.value : undefined,
            status: activeFilter.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['recentComplaints', 'filters'],
            onFinish: () => {
                if (!router.page.props.errors) {
                    isSearching.value = false;
                }
            }
        });
    }, 400);
};
watch(searchQuery, applyDashboardFilters);
watch(sortBy, applyDashboardFilters);


// Computed helpers
const maxFacilityTotal = computed(() => {
    if (!props.worstFacilities?.length) return 1;
    return Math.max(...props.worstFacilities.map(f => f.total));
});

const maxCategoryCount = computed(() => {
    if (!props.categories?.length) return 1;
    return Math.max(...props.categories.map(c => c.complaints_count));
});

// Star rating helper
function getStars(rating) {
    const full = Math.floor(rating);
    const half = rating - full >= 0.5;
    const empty = 5 - full - (half ? 1 : 0);
    return { full, half, empty };
}

// Category color palette — unified indigo/blue tones
const categoryColors = [
    'from-indigo-500 to-indigo-600',
    'from-indigo-400 to-blue-500',
    'from-blue-500 to-indigo-500',
    'from-slate-500 to-indigo-500',
    'from-indigo-500 to-violet-500',
    'from-blue-400 to-indigo-500',
    'from-indigo-400 to-indigo-600',
    'from-slate-400 to-blue-500',
];

// Category bar solid colors — subtle gradient of indigo shades
const categoryBarColors = [
    'ds-bar ds-bar-1',
    'ds-bar ds-bar-2',
    'ds-bar ds-bar-3',
    'ds-bar ds-bar-4',
    'ds-bar ds-bar-5',
    'ds-bar ds-bar-6',
    'ds-bar ds-bar-7',
    'ds-bar ds-bar-8',
];

function getCategoryColor(index) {
    return categoryColors[index % categoryColors.length];
}

function getCategoryBarColor(index) {
    return categoryBarColors[index % categoryBarColors.length];
}

// Get initials from name
function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

// Avatar color based on name
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
            complaint.admin_notified_at = new Date().toISOString();
            const idx = localComplaints.value.findIndex(c => c.id === complaint.id);
            if (idx !== -1) {
                localComplaints.value[idx].admin_notified_at = complaint.admin_notified_at;
            }
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
};

const closeLightbox = () => {
    lightboxOpen.value = false;
};

const navLightbox = (dir) => {
    const len = lightboxImages.value.length;
    lightboxIndex.value = (lightboxIndex.value + dir + len) % len;
};

const formatDateFull = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex flex-col">
                    <p class="text-[10px] font-bold text-primary-400 uppercase tracking-[0.2em]">PUSAT MANAJEMEN</p>
                    <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">{{ t('admin_dashboard') }}</h1>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-7">

            <!-- STATS ROW -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 lg:gap-5">
                <!-- Total Reports -->
                <div
                    class="col-span-2 lg:col-span-1 ds-stat-card group cursor-pointer"
                    :class="activeFilter === null ? 'ring-2 ring-violet-400 ring-offset-2 ring-offset-[#f9f8ff]' : ''"
                    :style="cardEnterStyle('total')"
                    @mouseenter="hoveredCard = 'total'"
                    @mouseleave="hoveredCard = null"
                    @click="selectFilter(null)"
                    :title="t('all_complaints')"
                >
                    <!-- Layered Soft Blobs -->
                    <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-violet-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none"></div>
                    <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-violet-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none"></div>
                    
                    <div class="flex items-start justify-between relative z-10 mb-6">
                        <div class="w-11 h-11 rounded-[1.1rem] bg-violet-500 flex items-center justify-center shadow-lg shadow-violet-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <span class="px-2.5 py-1 bg-white text-violet-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-violet-50 transition-colors duration-300 group-hover:bg-violet-50 text-center">TOTAL</span>
                    </div>
                    <div class="relative z-10 mt-auto">
                        <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-violet-500 transition-colors duration-300">TOTAL PENGADUAN</p>
                        <p class="text-4xl lg:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-violet-900 transition-colors duration-300">{{ animatedStats.total.toLocaleString() }}</p>
                    </div>
                </div>

                <!-- Pending -->
                <div
                    class="ds-stat-card group cursor-pointer"
                    :class="activeFilter === 'pending' ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-[#f9f8ff]' : ''"
                    :style="cardEnterStyle('pending')"
                    @mouseenter="hoveredCard = 'pending'"
                    @mouseleave="hoveredCard = null"
                    @click="selectFilter('pending')"
                >
                    <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-amber-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none"></div>
                    <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-amber-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none"></div>
                    
                    <div class="flex items-start justify-between relative z-10 mb-6">
                        <div class="w-11 h-11 rounded-[1.1rem] bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="px-2.5 py-1 bg-white text-amber-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-amber-50 transition-colors duration-300 group-hover:bg-amber-50 text-center">MENUNGGU</span>
                    </div>
                    <div class="relative z-10 mt-auto">
                        <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-amber-500 transition-colors duration-300">BELUM DIVERIFIKASI</p>
                        <p class="text-4xl lg:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-amber-900 transition-colors duration-300">{{ animatedStats.pending.toLocaleString() }}</p>
                    </div>
                </div>

                <!-- Processing -->
                <div
                    class="ds-stat-card group cursor-pointer"
                    :class="activeFilter === 'processing' ? 'ring-2 ring-blue-400 ring-offset-2 ring-offset-[#f9f8ff]' : ''"
                    :style="cardEnterStyle('processing')"
                    @mouseenter="hoveredCard = 'processing'"
                    @mouseleave="hoveredCard = null"
                    @click="selectFilter('processing')"
                >
                    <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-blue-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none"></div>
                    <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-blue-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none"></div>
                    
                    <div class="flex items-start justify-between relative z-10 mb-6">
                        <div class="w-11 h-11 rounded-[1.1rem] bg-blue-500 flex items-center justify-center shadow-lg shadow-blue-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/></svg>
                        </div>
                        <span class="px-2.5 py-1 bg-white text-blue-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-blue-50 transition-colors duration-300 group-hover:bg-blue-50 text-center">DIPROSES</span>
                    </div>
                    <div class="relative z-10 mt-auto">
                        <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-blue-500 transition-colors duration-300">DALAM PERBAIKAN</p>
                        <p class="text-4xl lg:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-blue-900 transition-colors duration-300">{{ animatedStats.processing.toLocaleString() }}</p>
                    </div>
                </div>

                <!-- Resolved -->
                <div
                    class="ds-stat-card group cursor-pointer"
                    :class="activeFilter === 'resolved' ? 'ring-2 ring-emerald-400 ring-offset-2 ring-offset-[#f9f8ff]' : ''"
                    :style="cardEnterStyle('resolved')"
                    @mouseenter="hoveredCard = 'resolved'"
                    @mouseleave="hoveredCard = null"
                    @click="selectFilter('resolved')"
                >
                    <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-emerald-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none"></div>
                    <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-emerald-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none"></div>
                    
                    <div class="flex items-start justify-between relative z-10 mb-6">
                        <div class="w-11 h-11 rounded-[1.1rem] bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="px-2.5 py-1 bg-white text-emerald-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-emerald-50 transition-colors duration-300 group-hover:bg-emerald-50 text-center">SELESAI</span>
                    </div>
                    <div class="relative z-10 mt-auto">
                        <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-emerald-500 transition-colors duration-300">TELAH DISELESAIKAN</p>
                        <p class="text-4xl lg:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-emerald-900 transition-colors duration-300">{{ animatedStats.resolved.toLocaleString() }}</p>
                    </div>
                </div>

                <!-- Rejected -->
                <div
                    class="ds-stat-card group cursor-pointer"
                    :class="activeFilter === 'rejected' ? 'ring-2 ring-rose-400 ring-offset-2 ring-offset-[#f9f8ff]' : ''"
                    :style="cardEnterStyle('rejected')"
                    @mouseenter="hoveredCard = 'rejected'"
                    @mouseleave="hoveredCard = null"
                    @click="selectFilter('rejected')"
                >
                    <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-rose-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none"></div>
                    <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-rose-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none"></div>
                    
                    <div class="flex items-start justify-between relative z-10 mb-6">
                        <div class="w-11 h-11 rounded-[1.1rem] bg-rose-500 flex items-center justify-center shadow-lg shadow-rose-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </div>
                        <span class="px-2.5 py-1 bg-white text-rose-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-rose-50 transition-colors duration-300 group-hover:bg-rose-50 text-center">DITOLAK</span>
                    </div>
                    <div class="relative z-10 mt-auto">
                        <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-rose-500 transition-colors duration-300">DITOLAK</p>
                        <p class="text-4xl lg:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-rose-900 transition-colors duration-300">{{ animatedStats.rejected.toLocaleString() }}</p>
                    </div>
                </div>
            </div>

            <!-- RATING STRIP -->
            <div class="ds-rating-strip">
                <div class="absolute inset-0 bg-gradient-to-r from-amber-500 via-orange-400 to-emerald-400 rounded-2xl"></div>
                <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full bg-white/20 blur-xl"></div>
                <div class="absolute -bottom-4 -left-4 w-24 h-24 rounded-full bg-white/20 blur-lg"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <p class="text-4xl font-black text-white drop-shadow-lg">{{ stats.avg_rating || '—' }}</p>
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-0.5">
                                <template v-if="stats.avg_rating">
                                    <svg v-for="n in getStars(stats.avg_rating).full" :key="'f'+n" class="w-4 h-4 text-white drop-shadow" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <svg v-if="getStars(stats.avg_rating).half" class="w-4 h-4 text-white/50 drop-shadow" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <svg v-for="n in getStars(stats.avg_rating).empty" :key="'e'+n" class="w-4 h-4 text-white/25 drop-shadow" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </template>
                            </div>
                            <p class="text-white/80 text-xs font-bold">{{ t('average_rating') }} · {{ stats.rated_count || 0 }} {{ t('reviews') }}</p>
                        </div>
                    </div>
                    <!-- Quick metrics inline -->
                    <div class="hidden sm:flex ml-auto items-center gap-6">
                        <div class="text-center">
                            <p class="text-2xl font-black text-white">{{ stats.pending }}</p>
                            <p class="text-[10px] text-white/70 font-bold uppercase tracking-wider">Menunggu</p>
                        </div>
                        <div class="w-px h-8 bg-white/20"></div>
                        <div class="text-center">
                            <p class="text-2xl font-black text-white">{{ stats.total > 0 ? ((stats.resolved / stats.total) * 100).toFixed(0) : 0 }}%</p>
                            <p class="text-[10px] text-white/70 font-bold uppercase tracking-wider">Selesai</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTER PILLS -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="activeFilter = null"
                    class="ds-filter-pill"
                    :class="!activeFilter ? 'ds-filter-pill-active' : 'ds-filter-pill-inactive'"
                >
                    {{ t('all_complaints') }}
                </button>
                <button
                    @click="selectFilter('pending')"
                    class="ds-filter-pill"
                    :class="activeFilter === 'pending' ? 'ds-filter-pill-active bg-amber-500 border-amber-400 shadow-amber-200/60' : 'ds-filter-pill-inactive'"
                >
                    {{ t('pending') }}
                </button>
                <button
                    @click="selectFilter('processing')"
                    class="ds-filter-pill"
                    :class="activeFilter === 'processing' ? 'ds-filter-pill-active bg-blue-500 border-blue-400 shadow-blue-200/60' : 'ds-filter-pill-inactive'"
                >
                    {{ t('in_progress') }}
                </button>
                <button
                    @click="selectFilter('resolved')"
                    class="ds-filter-pill"
                    :class="activeFilter === 'resolved' ? 'ds-filter-pill-active bg-emerald-500 border-emerald-400 shadow-emerald-200/60' : 'ds-filter-pill-inactive'"
                >
                    {{ t('completed') }}
                </button>
                <button
                    @click="selectFilter('rejected')"
                    class="ds-filter-pill"
                    :class="activeFilter === 'rejected' ? 'ds-filter-pill-active bg-rose-500 border-rose-400 shadow-rose-200/60' : 'ds-filter-pill-inactive'"
                >
                    {{ t('rejected') }}
                </button>
            </div>

            <!-- INSIGHTS ROW (3 columns) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- ─── Category Distribution ─────────────────────── -->
                <div class="ds-panel p-6 flex flex-col">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="ds-widget-title">
                            <span class="ds-widget-dot bg-gradient-to-r from-violet-500 to-indigo-500"></span>
                            {{ t('by_category') }}
                        </h3>
                        <button
                            v-if="categories?.length > 4"
                            @click="showAllCategories = !showAllCategories"
                            class="ds-view-detail-btn group"
                        >
                            <span>{{ showAllCategories ? 'Tutup' : 'Lihat Detail' }}</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="showAllCategories ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                    <div class="space-y-4 flex-1">
                        <TransitionGroup name="widget-item">
                            <div v-for="(cat, catIdx) in displayedCategories" :key="cat.id" class="group">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-2.5 h-2.5 rounded-full flex-shrink-0 bg-gradient-to-br shadow-sm" :class="getCategoryColor(catIdx)"></div>
                                        <span class="text-[13px] font-semibold truncate max-w-[120px] text-gray-700 group-hover:text-primary-600 transition-colors cursor-default">{{ cat.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span v-if="cat.recent_complaints_count > 0" class="text-[9px] font-black text-rose-500 bg-rose-50 border border-rose-100 px-1.5 py-0.5 rounded-md">+{{ cat.recent_complaints_count }}</span>
                                        <span class="text-[13px] font-extrabold text-gray-800">{{ cat.complaints_count }}</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100/80 rounded-full h-2 overflow-hidden">
                                    <div
                                        class="h-2 rounded-full transition-[width] duration-500 ease-out"
                                        :class="getCategoryBarColor(catIdx)"
                                        :style="{ width: (stats.total > 0 ? (cat.complaints_count / stats.total * 100) : 0) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </TransitionGroup>
                    </div>
                    <!-- Collapsed count hint -->
                    <p v-if="!showAllCategories && categories?.length > 4" class="text-[10px] text-gray-400 font-medium mt-3 text-center">
                        +{{ categories.length - 4 }} kategori lainnya
                    </p>
                </div>

                <!-- ─── Worst Facility ────────────────────────────── -->
                <div class="ds-panel p-6 flex flex-col">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="ds-widget-title">
                            <span class="ds-widget-dot bg-gradient-to-r from-indigo-500 to-indigo-600"></span>
                            {{ t('worst_facility') }}
                        </h3>
                        <button
                            v-if="worstFacilities?.length > 4"
                            @click="showAllFacilities = !showAllFacilities"
                            class="ds-view-detail-btn group"
                        >
                            <span>{{ showAllFacilities ? 'Tutup' : 'Lihat Detail' }}</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="showAllFacilities ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>

                    <div v-if="worstFacilities?.length > 0" class="space-y-4 flex-1">
                        <TransitionGroup name="widget-item">
                            <div
                                v-for="(facility, index) in displayedFacilities"
                                :key="facility.location"
                                class="group ds-facility-item"
                                :style="{ animationDelay: (0.1 + index * 0.04) + 's' }"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span
                                            class="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center text-[9px] font-black shadow-sm"
                                            :class="index === 0 ? 'bg-gradient-to-br from-indigo-600 to-indigo-700 text-white shadow-indigo-300/50' : index === 1 ? 'bg-gradient-to-br from-indigo-400 to-indigo-500 text-white shadow-indigo-200/50' : 'bg-gray-100 text-gray-500'"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <span class="text-[13px] font-semibold truncate max-w-[120px] group-hover:text-primary-600 transition-colors" :class="index === 0 ? 'text-indigo-700 font-bold' : 'text-gray-700'">{{ facility.location }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span v-if="facility.recent_count > 0" class="text-[9px] font-black text-rose-500 bg-rose-50 border border-rose-100 px-1.5 py-0.5 rounded-md flex items-center gap-0.5">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                            +{{ facility.recent_count }}
                                        </span>
                                        <span class="text-[13px] font-extrabold text-gray-800">{{ facility.total }}</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div
                                        class="h-2 rounded-full transition-[width] duration-500 ease-out"
                                        :class="index === 0 ? 'ds-bar ds-bar-dark' : 'ds-bar ds-bar-1'"
                                        :style="{ width: (facility.total / maxFacilityTotal * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </TransitionGroup>
                    </div>

                    <div v-else class="text-center py-6 flex-1 flex items-center justify-center">
                        <p class="text-gray-400 text-sm font-medium">{{ t('no_report_data') }}</p>
                    </div>
                    <!-- Collapsed count hint -->
                    <p v-if="!showAllFacilities && worstFacilities?.length > 4" class="text-[10px] text-gray-400 font-medium mt-3 text-center">
                        +{{ worstFacilities.length - 4 }} lokasi lainnya
                    </p>
                </div>

                <!-- ─── Quick Summary ─────────────────────────────── -->
                <div class="ds-panel p-6 flex flex-col">
                    <h3 class="ds-widget-title mb-5">
                        <span class="ds-widget-dot bg-gradient-to-r from-emerald-500 to-teal-500"></span>
                        Ringkasan Cepat
                    </h3>
                    <div class="space-y-3 flex-1">
                        <div class="ds-quick-item group">
                            <div class="ds-quick-icon bg-gradient-to-br from-amber-400 to-orange-500">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-800">Menunggu Tindakan</p>
                                <p class="text-[10px] text-gray-400 font-medium">Laporan yang perlu ditinjau</p>
                            </div>
                            <span class="text-lg font-black text-amber-600">{{ stats.pending }}</span>
                        </div>
                        <div class="ds-quick-item group">
                            <div class="ds-quick-icon bg-gradient-to-br from-emerald-400 to-green-500">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-800">Tingkat Penyelesaian</p>
                                <p class="text-[10px] text-gray-400 font-medium">Rasio laporan selesai</p>
                            </div>
                            <span class="text-lg font-black text-emerald-600">{{ stats.total > 0 ? ((stats.resolved / stats.total) * 100).toFixed(0) : 0 }}%</span>
                        </div>
                        <div class="ds-quick-item group">
                            <div class="ds-quick-icon bg-gradient-to-br from-violet-400 to-purple-500">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-800">Kepuasan Pengguna</p>
                                <p class="text-[10px] text-gray-400 font-medium">{{ stats.rated_count || 0 }} ulasan masuk</p>
                            </div>
                            <span class="text-lg font-black text-violet-600">{{ stats.avg_rating || '—' }}<span class="text-xs font-bold text-gray-400">/5</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COMPLAINTS TABLE (full width) -->
            <div id="complaints-table" class="ds-panel overflow-hidden scroll-mt-6">
                <!-- Table Header -->
                <div class="flex items-center justify-between px-7 py-5 border-b border-gray-100/80">
                    <div>
                        <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-gradient-to-r from-violet-500 to-purple-500 animate-pulse shadow-sm shadow-violet-300"></span>
                            <span v-if="!activeFilter">{{ t('recent_complaints') }}</span>
                            <span v-else>
                                {{ t('complaint') }}
                                <span :class="{
                                    'text-amber-500': activeFilter === 'pending',
                                    'text-blue-500': activeFilter === 'processing',
                                    'text-emerald-500': activeFilter === 'resolved',
                                    'text-rose-500': activeFilter === 'rejected',
                                }">{{ statusLabel[activeFilter] }}</span>
                            </span>
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5 font-medium">Live feed laporan kerusakan fasilitas</p>
                    </div>
                    <Link :href="route('admin.complaints')" class="ds-link-arrow group">
                        {{ t('manage_full') }}
                        <svg class="w-3.5 h-3.5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </Link>
                </div>

                <div class="p-6 lg:p-7">
                    <!-- Sort & Search Controls -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ t('sort_by') }}</span>
                            <div class="flex gap-2 items-center">
                                <button
                                    v-for="opt in sortOptions"
                                    :key="opt.value"
                                    @click="sortBy = opt.value"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-300 relative overflow-hidden group hover-lift shrink-0"
                                    :class="sortBy === opt.value 
                                        ? 'bg-primary-500/15 border border-primary-300/50 text-primary-700 shadow-[inset_0_2px_10px_rgba(139,92,246,0.1),0_0_10px_rgba(139,92,246,0.1)] backdrop-blur-md'
                                        : 'bg-white/40 border border-white/60 text-gray-500 shadow-sm hover:bg-white/70 hover:text-gray-700 hover:border-white/80 backdrop-blur-sm'"
                                >
                                    <div v-if="sortBy === opt.value" class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent -translate-x-full animate-[shine-sweep_3s_infinite] pointer-events-none"></div>
                                    <span class="flex items-center justify-center transition-transform duration-300 group-hover:scale-110" 
                                          :class="sortBy === opt.value ? 'text-primary-600 drop-shadow-sm' : 'text-gray-400 group-hover:text-primary-500'" 
                                          v-html="opt.icon">
                                    </span>
                                    <span class="hidden sm:inline relative z-10">{{ opt.label }}</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="!activeFilter" class="relative w-full sm:w-72 group">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-primary-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="t('search_placeholder')"
                                class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-gray-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-primary-500/10 focus:border-primary-400 transition-all duration-300 shadow-sm hover:border-gray-300 outline-none"
                            />
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
                                    @click="searchQuery = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 hover:bg-red-50 p-1 rounded-md transition-all"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </Transition>
                        </div>
                    </div>

                    <!-- Table -->
                    <div v-if="isInitialLoad" class="p-4 transition-all duration-500">
                        <SkeletonTable :rows="5" />
                    </div>
                    <div v-else-if="filteredComplaints.length > 0 || isSearching" class="relative overflow-x-auto min-h-[250px]">
                        <!-- Searching dim overlay -->
                        <Transition
                            enter-active-class="transition duration-300 ease-out"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="transition duration-200 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <div v-if="isSearching" class="absolute inset-0 z-20 bg-white/60 backdrop-blur-[2px] flex items-center justify-center">
                                <div class="px-5 py-3 bg-white rounded-2xl shadow-xl flex items-center gap-3 transform -translate-y-4 border border-gray-100">
                                    <svg class="animate-spin text-primary-500 w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span class="text-sm font-bold text-primary-800 tracking-wide">Menyortir Data...</span>
                                </div>
                            </div>
                        </Transition>

                        <table class="ds-table w-full text-left">
                            <thead>
                                <tr>
                                    <th>{{ t('table_reporter') }}</th>
                                    <th>{{ t('table_title') }}</th>
                                    <th class="hidden md:table-cell">{{ t('table_category') }}</th>
                                    <th>{{ t('table_status') }}</th>
                                    <th class="hidden lg:table-cell">{{ t('table_date') }}</th>
                                </tr>
                            </thead>
                            <TransitionGroup tag="tbody" name="table-row">
                                <tr
                                    v-for="(complaint, idx) in filteredComplaints"
                                    :key="complaint.id"
                                    class="group border-b border-gray-50 last:border-0 transition-all duration-200 hover:bg-violet-50/40 cursor-pointer"
                                    :class="{ 'bg-amber-50/30': !complaint.admin_notified_at && complaint.status === 'pending' }"
                                    :style="{ animationDelay: idx * 0.04 + 's' }"
                                    @click="openDetail(complaint)"
                                >
                                    <!-- Reporter with Avatar -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="relative">
                                                <div :class="[getAvatarColor(complaint.user?.name), 'w-9 h-9 rounded-xl flex items-center justify-center text-white text-xs font-bold shadow-sm transition-transform duration-300 group-hover:scale-105']">
                                                    {{ getInitials(complaint.user?.name) }}
                                                </div>
                                                <span v-if="!complaint.admin_notified_at && complaint.status === 'pending'" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-gradient-to-br from-amber-400 to-red-500 border-2 border-white shadow-sm animate-pulse"></span>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-gray-800 group-hover:text-primary-700 transition-colors">{{ complaint.user?.name }}</span>
                                                <span v-if="!complaint.admin_notified_at && complaint.status === 'pending'" class="new-badge w-fit mt-0.5">BARU</span>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Title -->
                                    <td class="py-3.5 px-4 max-w-[220px]">
                                        <p class="text-sm text-gray-700 font-medium truncate group-hover:text-primary-700 transition-colors">{{ complaint.title }}</p>
                                        <p class="text-[11px] text-gray-400 font-medium mt-0.5 truncate">{{ complaint.description }}</p>
                                    </td>
                                    <!-- Category -->
                                    <td class="py-3.5 px-4 hidden md:table-cell">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-primary-50 text-primary-600 border border-primary-100">
                                            {{ complaint.category?.parent?.name || complaint.category?.name }}
                                        </span>
                                    </td>
                                    <!-- Status -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span :class="'badge badge-' + complaint.status" class="text-[10px]">
                                            {{ statusLabel[complaint.status] }}
                                        </span>
                                    </td>
                                    <!-- Date -->
                                    <td class="py-3.5 px-4 whitespace-nowrap hidden lg:table-cell">
                                        <span class="text-xs text-gray-400 font-medium">{{ formatDate(complaint.created_at) }}</span>
                                        <div class="flex items-center gap-1 mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                            <svg class="w-3 h-3 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                            <span class="text-[10px] text-primary-500 font-bold">Lihat Detail</span>
                                        </div>
                                    </td>
                                </tr>
                            </TransitionGroup>
                        </table>
                    </div>
                    <!-- Empty state -->
                    <div v-else class="text-center py-24 px-6 relative group border border-transparent">
                        <div class="relative w-24 h-24 mx-auto mb-6 cursor-default">
                            <!-- Floating Blobs behind the icon -->
                            <div class="absolute -top-4 -right-4 w-16 h-16 bg-gradient-to-tr from-primary-300/60 to-fuchsia-300/60 rounded-full blur-[12px] animate-[float_6s_ease-in-out_infinite] group-hover:scale-125 group-hover:-translate-y-2 group-hover:translate-x-2 transition-all duration-700"></div>
                            <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-gradient-to-bl from-blue-300/60 to-primary-300/60 rounded-full blur-[12px] animate-[float_4s_ease-in-out_infinite_reverse] group-hover:scale-125 group-hover:translate-y-2 group-hover:-translate-x-2 transition-all duration-700"></div>
                            
                            <!-- White Glass Icon Container -->
                            <div class="absolute inset-0 bg-white/60 backdrop-blur-md border border-white/80 rounded-[1.5rem] flex items-center justify-center shadow-[0_8px_30px_rgb(0,0,0,0.04)] transform group-hover:-translate-y-2 group-hover:shadow-[0_15px_40px_rgb(0,0,0,0.08)] transition-all duration-500 ease-out z-10">
                                <svg class="w-10 h-10 text-primary-500 transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>

                        <div class="relative z-10 transition-transform duration-500 group-hover:-translate-y-1">
                            <h3 class="text-2xl font-black text-primary-900 mb-3 tracking-tight group-hover:text-primary-700 transition-colors">
                                {{ activeFilter ? t('no_reports_matching_status', { status: statusLabel[activeFilter] }) : searchQuery ? t('no_reports_matching_search') : t('no_reports_yet') }}
                            </h3>
                            <p class="text-primary-800/70 text-sm font-medium mb-8 max-w-sm mx-auto leading-relaxed group-hover:text-primary-800 transition-colors">Kami belum menemukan laporan kerusakan dengan kriteria yang Anda cari.</p>
                            <button v-if="activeFilter || searchQuery" @click="activeFilter = null; searchQuery = ''" class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-500 to-primary-600 text-white font-bold px-8 py-3.5 rounded-full hover:shadow-[0_8px_20px_-6px_rgba(124,58,237,0.5)] hover:-translate-y-1 transition-all duration-300">
                                {{ t('show_all') }}
                            </button>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <template v-if="!activeFilter">
                        <div v-if="recentComplaints?.links?.length > 3" class="flex justify-between items-center mt-8 pb-2">
                            <p class="text-xs text-gray-400 font-medium">
                                Menampilkan {{ recentComplaints.from }}–{{ recentComplaints.to }} dari {{ recentComplaints.total }} laporan
                            </p>
                            <div class="flex gap-1.5">
                                <template v-for="(link, index) in recentComplaints.links" :key="index">
                                    <button
                                        v-if="link.url"
                                        @click="router.get(link.url, { search: searchQuery || undefined }, { preserveState: true, preserveScroll: true })"
                                        class="ds-page-btn"
                                        :class="link.active ? 'ds-page-btn-active' : 'ds-page-btn-inactive'"
                                        v-html="link.label"
                                    ></button>
                                    <div
                                        v-else
                                        class="ds-page-btn ds-page-btn-disabled"
                                        v-html="link.label"
                                    ></div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- DETAIL MODAL -->
        <Teleport to="body">
            <Transition name="modal" appear>
                <div v-if="showDetail" class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto bg-black/20 backdrop-blur-sm" @click.self="showDetail = false">
                    <div class="relative z-10 w-full max-w-3xl mx-4 my-8 bg-white rounded-[2rem] shadow-2xl overflow-hidden ring-1 ring-black/5" @click.stop>
                        <div class="p-6 md:p-8 max-h-[85vh] overflow-y-auto custom-scrollbar">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-6">
                                <div>
                                    <p class="text-[10px] font-bold text-primary-400 uppercase tracking-[0.15em] mb-1">DETAIL LAPORAN</p>
                                    <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">{{ detailComplaint?.title }}</h2>
                                    <p class="text-xs text-gray-400 mt-0.5">ID <span class="font-bold text-primary-500">#RPT-{{ String(detailComplaint?.id).padStart(4, '0') }}</span> · {{ formatDateFull(detailComplaint?.created_at) }}</p>
                                </div>
                                <button @click="showDetail = false" class="p-2 hover:bg-gray-100 rounded-xl transition-all duration-200 group">
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <!-- Meta Info -->
                            <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50/80 rounded-xl mb-6">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pelapor</p>
                                    <p class="text-sm font-bold text-gray-800 mt-0.5">{{ detailComplaint?.user?.name }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Kategori</p>
                                    <p class="text-sm font-bold text-gray-800 mt-0.5"><span v-if="detailComplaint?.category?.parent">{{ detailComplaint.category.parent.name }} › </span>{{ detailComplaint?.category?.name }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</p>
                                    <span :class="'badge badge-' + detailComplaint?.status" class="mt-1 inline-block">{{ statusLabel[detailComplaint?.status] }}</span>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="bg-gray-50 rounded-xl p-5 mb-6">
                                <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">DESKRIPSI LAPORAN</p>
                                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ detailComplaint?.description }}</p>
                            </div>

                            <!-- Location -->
                            <div class="flex items-start gap-3 mb-6 p-4 bg-gray-50 rounded-xl">
                                <svg class="w-5 h-5 text-primary-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ detailComplaint?.location }}</p>
                                    <p v-if="detailComplaint?.location_detail" class="text-xs text-gray-400 mt-0.5">{{ detailComplaint?.location_detail }}</p>
                                </div>
                            </div>

                            <!-- Photos -->
                            <div v-if="detailComplaint?.image_paths?.length > 0" class="mb-6">
                                <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">FOTO BUKTI ({{ detailComplaint.image_paths.length }})</p>
                                <div :class="detailComplaint.image_paths.length === 1 ? 'flex' : 'grid grid-cols-2 gap-3'">
                                    <img
                                        v-for="(imgPath, idx) in detailComplaint.image_paths"
                                        :key="idx"
                                        :src="resolveImageUrl(imgPath)"
                                        :alt="'Foto ' + (idx + 1)"
                                        class="rounded-xl object-cover border border-gray-200 cursor-zoom-in hover:opacity-90 transition-all duration-300 hover:scale-[1.02] shadow-sm"
                                        :class="detailComplaint.image_paths.length === 1 ? 'max-h-52' : 'h-36 w-full'"
                                        @click="openLightbox(detailComplaint.image_paths.map(p => resolveImageUrl(p)), idx)"
                                    />
                                </div>
                            </div>

                            <!-- Admin Response -->
                            <div v-if="detailComplaint?.admin_response" class="bg-primary-50/80 border border-primary-100 rounded-xl p-5 mb-6">
                                <p class="text-[10px] font-extrabold text-primary-500 uppercase tracking-widest mb-2">💬 RESPON ADMIN</p>
                                <p class="text-sm text-primary-800 leading-relaxed">{{ detailComplaint.admin_response }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 pt-4 border-t border-gray-100">
                                <Link :href="route('admin.complaints')" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-bold text-sm shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Kelola Lengkap
                                </Link>
                                <button @click="showDetail = false" class="px-6 py-3 bg-white border border-gray-200 text-gray-600 rounded-xl font-bold text-sm hover:bg-gray-50 transition-all duration-300">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Lightbox -->
        <Teleport to="body">
            <Transition name="lightbox">
                <div v-if="lightboxOpen" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm" @click.self="closeLightbox">
                    <button @click="closeLightbox" class="absolute top-6 right-6 w-12 h-12 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 text-white font-bold transition-all">✕</button>
                    <button v-if="lightboxImages.length > 1" @click="navLightbox(-1)" class="absolute left-6 w-14 h-14 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 text-white text-2xl transition-all">‹</button>
                    <img :src="lightboxImages[lightboxIndex]" :alt="'Foto ' + (lightboxIndex + 1)" class="max-w-[85vw] max-h-[80vh] object-contain rounded-2xl shadow-xl select-none" />
                    <button v-if="lightboxImages.length > 1" @click="navLightbox(1)" class="absolute right-6 w-14 h-14 flex items-center justify-center rounded-full bg-white/20 hover:bg-white/30 text-white text-2xl transition-all">›</button>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
/* STAT CARDS */
.ds-stat-card {
    @apply relative overflow-hidden rounded-[1.5rem] p-6 bg-white border border-gray-200/80 flex flex-col;
    min-height: 180px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 6px 20px -6px rgba(0,0,0,0.08);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    will-change: transform;
}

.ds-stat-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 16px 32px -10px rgba(99,102,241,0.12);
    border-color: rgba(199,210,254,0.5);
}

.ds-stat-blob {
    @apply absolute -top-14 -right-14 w-48 h-48 rounded-full pointer-events-none;
    transition: transform 0.5s ease-out;
    opacity: 0.7;
}

.ds-stat-card:hover .ds-stat-blob {
    transform: scale(1.1);
}

.ds-stat-icon {
    @apply w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg;
    transition: transform 0.25s ease;
}

.ds-stat-card:hover .ds-stat-icon {
    transform: scale(1.08) rotate(-2deg);
}

.ds-stat-pill {
    @apply px-2.5 py-1 rounded-full text-[9px] font-extrabold tracking-[0.1em] uppercase border shadow-sm;
}

/* RATING STRIP */
.ds-rating-strip {
    @apply relative overflow-hidden rounded-2xl px-6 py-5;
}

/* FILTER PILLS */
.ds-filter-pill {
    @apply px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider border select-none;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.ds-filter-pill:active {
    transform: scale(0.97);
}

.ds-filter-pill-active {
    @apply bg-gradient-to-r from-primary-500 to-primary-600 text-white border-primary-400;
    box-shadow: 0 4px 12px -3px rgba(139, 92, 246, 0.4);
    transform: translateY(-1px);
}

.ds-filter-pill-inactive {
    @apply bg-white text-gray-500 border-gray-200 hover:bg-gray-50 hover:text-primary-600 hover:border-primary-200;
}

.ds-filter-pill-inactive:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px -3px rgba(0, 0, 0, 0.06);
}

/* PANELS */
.ds-panel {
    @apply bg-white rounded-[1.5rem] border border-gray-200/80;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 6px 20px -6px rgba(0,0,0,0.08);
}

/* UNIFIED BAR COLORS */
.ds-bar {
    @apply rounded-full;
}
.ds-bar-1 { background: linear-gradient(90deg, #818cf8, #6366f1); }
.ds-bar-2 { background: linear-gradient(90deg, #93a3f8, #6366f1); }
.ds-bar-3 { background: linear-gradient(90deg, #a5b4fc, #818cf8); }
.ds-bar-4 { background: linear-gradient(90deg, #8b9cf8, #7c83f0); }
.ds-bar-5 { background: linear-gradient(90deg, #7c83f0, #6366f1); }
.ds-bar-6 { background: linear-gradient(90deg, #a5b4fc, #6366f1); }
.ds-bar-7 { background: linear-gradient(90deg, #93a3f8, #7c83f0); }
.ds-bar-8 { background: linear-gradient(90deg, #a5b4fc, #818cf8); }
.ds-bar-dark { background: linear-gradient(90deg, #4f46e5, #4338ca); }

/* WIDGET TITLE */
.ds-widget-title {
    @apply text-xs font-extrabold text-gray-900 uppercase tracking-widest flex items-center gap-2.5;
}

.ds-widget-dot {
    @apply w-2.5 h-2.5 rounded-full flex-shrink-0;
}

/* LINK ARROW */
.ds-link-arrow {
    @apply text-xs text-primary-500 font-bold hover:text-primary-600 transition uppercase tracking-wider flex items-center gap-1;
}

/* SORT PILLS */
.ds-sort-pill {
    @apply px-3 py-1.5 rounded-lg text-[11px] font-semibold border flex items-center gap-1.5;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.15s ease, color 0.15s ease;
}

.ds-sort-pill-active {
    @apply bg-white text-primary-700 border-gray-200 shadow-sm;
    transform: translateY(-1px);
}

.ds-sort-pill-inactive {
    @apply bg-transparent text-gray-400 border-transparent hover:bg-gray-50 hover:text-primary-600;
}

/* TABLE */
.ds-table thead th {
    @apply py-3.5 px-4 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest border-b border-gray-100;
}

.ds-table tbody td {
    /* inherits from template classes */
}

/* PAGINATION */
.ds-page-btn {
    @apply px-3 py-2 text-[11px] font-extrabold rounded-lg border h-9 min-w-[2.25rem] flex items-center justify-center;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.15s ease, color 0.15s ease;
}

.ds-page-btn-active {
    @apply bg-gradient-to-r from-primary-500 to-primary-600 text-white border-primary-400;
    box-shadow: 0 4px 12px -3px rgba(124, 58, 237, 0.35);
    transform: translateY(-1px);
}

.ds-page-btn-inactive {
    @apply bg-white text-gray-500 border-gray-200 hover:bg-gray-50 hover:text-primary-600;
}

.ds-page-btn-inactive:hover {
    transform: translateY(-1px);
}

.ds-page-btn-disabled {
    @apply bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed;
}

/* QUICK ITEM */
.ds-quick-item {
    @apply flex items-center gap-3 p-3.5 rounded-xl bg-gray-50/60 border border-gray-100;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
}

.ds-quick-item:hover {
    @apply bg-white border-gray-200;
    box-shadow: 0 3px 10px -3px rgba(0,0,0,0.06);
    transform: translateY(-1px);
}

.ds-quick-icon {
    @apply w-9 h-9 rounded-xl flex items-center justify-center shadow-sm flex-shrink-0;
    transition: transform 0.2s ease;
}

.ds-quick-item:hover .ds-quick-icon {
    transform: scale(1.06) rotate(-2deg);
}

/* VIEW DETAIL BUTTON */
.ds-view-detail-btn {
    @apply text-[10px] text-primary-500 font-bold uppercase tracking-wider flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-transparent;
    transition: transform 0.2s ease, background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.ds-view-detail-btn:hover {
    @apply bg-primary-50 border-primary-100 text-primary-600;
    transform: translateY(-1px);
}

/* FACILITY ITEM */
.ds-facility-item {
    animation: dsFadeUp 0.3s ease both;
    will-change: opacity, transform;
}

@keyframes dsFadeUp {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* WIDGET ITEM TRANSITIONS */
.widget-item-enter-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.widget-item-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.widget-item-enter-from {
    opacity: 0;
    transform: translateY(-6px);
}

.widget-item-leave-to {
    opacity: 0;
    transform: translateY(6px);
}

/* TABLE ROW TRANSITIONS */
.table-row-enter-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.table-row-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.table-row-enter-from {
    opacity: 0;
    transform: translateX(-8px);
}

.table-row-leave-to {
    opacity: 0;
    transform: translateX(8px);
}

/* NEW BADGE */
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
</style>
