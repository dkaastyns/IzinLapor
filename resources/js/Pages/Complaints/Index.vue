<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import SkeletonTable from '@/Components/SkeletonTable.vue';
import axios from 'axios';
import { resolveImageUrl, resolveComplaintImages } from '@/Utils/imageUrl';

const props = defineProps({
    complaints: Object,
    statusCounts: Object,
    filters: Object,
});

const activeFilter = ref(props.filters?.status || 'all');

const { t } = useI18n();

const statusLabel = computed(() => ({
    pending: t('pending'),
    processing: t('in_progress'),
    resolved: t('completed'),
    rejected: t('rejected'),
}));

const statusIcon = {
    pending: '⏳',
    processing: '🔄',
    resolved: '✅',
    rejected: '❌',
};

/* ... categoryIcons & getCategoryIcon remain unchanged below ... */

const categoryIcons = {
    // KELISTRIRIKAN
    'kelistrikan': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-yellow-500 drop-shadow-[0_0_8px_rgba(234,179,8,0.5)] transition-all duration-500 group-hover:scale-110 group-hover:rotate-12 animate-[pulse_3s_ease-in-out_infinite]"><path d="M13 2 L3 14 H12 L12 22 L22 10 H13 L13 2 Z"/></svg>',
    'lampu': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-yellow-400 drop-shadow-[0_0_8px_rgba(250,204,21,0.6)] transition-all duration-500 group-hover:scale-110 group-hover:rotate-12 animate-[pulse_2s_ease-in-out_infinite]"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.9 1.2 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
    'kontak': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-yellow-600 transition-all duration-500 group-hover:scale-110"><rect width="18" height="12" x="3" y="6" rx="2"/><circle cx="9" cy="12" r="2"/><circle cx="15" cy="12" r="2"/></svg>',
    'kabel': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-yellow-500 transition-all duration-500 group-hover:rotate-6"><path d="M12 2 L12 22"/><path d="M17 5 L17 19"/><path d="M7 5 L7 19"/></svg>',

    // AIR & SANITASI
    'air': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-cyan-500 drop-shadow-[0_0_8px_rgba(6,182,212,0.5)] transition-all duration-500 group-hover:scale-110 group-hover:-rotate-12 animate-[bounce_4s_ease-in-out_infinite]"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5s-3 3.5-3 5.5a7 7 0 0 0 7 7Z"/></svg>',
    'sanitasi': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-cyan-500 transition-all duration-500 group-hover:scale-110"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7 6.3 7 6.3s-2.15 2.76-3.29 3.69C2.57 10.93 2 12.03 2 13.19c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>',
    'keran': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-cyan-400 transition-all duration-500 group-hover:-translate-y-1"><path d="M12 10v4"/><path d="M4 14h16"/><path d="M2 18h20"/><path d="M12 5V2"/><path d="M12 22v.01"/></svg>',
    'toilet': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-cyan-600 transition-all duration-500 group-hover:scale-110"><path d="M7 2 h10 v4 a2 2 0 0 1 -2 2 H9 a2 2 0 0 1 -2 -2 V2 Z"/><path d="M18 20 a2 2 0 0 1 -2 2 H8 a2 2 0 0 1 -2 -2 V8 h12 v12 Z"/></svg>',
    'saluran': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-indigo-400 transition-all duration-500 group-hover:translate-x-1 animate-pulse"><path d="M21 15 a2 2 0 0 1 -2 2 H7 l -4 4 V5 a2 2 0 0 1 2 -2 h14 a2 2 0 0 1 2 2 z"/></svg>',

    // KEBERSIHAN
    'kebersihan': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-emerald-500 drop-shadow-[0_0_8px_rgba(16,185,129,0.5)] transition-all duration-500 group-hover:rotate-[15deg] group-hover:scale-110"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>',
    'sampah': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-emerald-600 transition-all duration-500 group-hover:-translate-y-1"><path d="M3 6h18"/><path d="M19 6 v14 a2 2 0 0 1 -2 2 H7 a2 2 0 0 1 -2 -2 V6"/><path d="M8 6 V4 a2 2 0 0 1 2 -2 h4 a2 2 0 0 1 2 2 v2"/></svg>',
    'kotor': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-emerald-400 transition-all duration-500 group-hover:translate-x-1"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>',
    'bau': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-lime-500 transition-all duration-500 animate-pulse"><path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"/><path d="M9.6 4.6A2 2 0 1 1 11 8H2"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2"/></svg>',

    // INFRASTRUKTUR
    'atap': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-orange-600 transition-all duration-500 group-hover:-translate-y-1"><path d="M2 12 l 10 -9 l 10 9 M2 12 v8 a2 2 0 0 0 2 2 h16 a2 2 0 0 0 2 -2 v-8 M12 6 l 0 6"/><path d="M12 16 v.01" class="text-cyan-400"/></svg>',
    'plafon': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-orange-400 transition-all duration-500 group-hover:scale-110"><path d="M3 5 h18 v14 H3 Z M3 10 h18 M12 5 v14"/><path d="M15 12 l 3 3 M7 7 l 3 3" class="text-orange-600 stroke-[3]"/></svg>',
    'jalan': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-orange-600 transition-all duration-500 group-hover:rotate-6"><path d="M3 19 21 5 M3 5 21 19"/></svg>',
    'infrastruktur': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-orange-500 transition-all duration-500 group-hover:-translate-y-1"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/></svg>',

    // FASILITAS UMUM
    'fasilitas': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-amber-600 transition-all duration-500 group-hover:scale-110"><path d="M22 10 a2 2 0 0 0 -2 -2 h-4 a2 2 0 0 0 -2 2 v10 a2 2 0 0 0 2 2 h4 a2 2 0 0 0 2 -2 Z"/><path d="M6 2 h12 v12 H6 Z"/></svg>',
    'kursi': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-amber-500 transition-all duration-500 group-hover:-translate-y-1"><path d="M7 6V2h10v4"/><path d="M4 16v-6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6"/><path d="M4 16h16v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3Z"/><path d="M8 22v-3"/><path d="M16 22v-3"/></svg>',
    'meja': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-amber-700 transition-all duration-500 group-hover:rotate-3"><path d="M3 9v12"/><path d="M21 9v12"/><path d="M3 13h18"/><path d="M12 9V5a2 2 0 0 1 2-2h6"/><rect width="20" height="4" x="2" y="5" rx="1"/></svg>',
    'pintu': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-amber-400 transition-all duration-500 group-hover:-translate-x-1"><path d="M13 4 h3 a2 2 0 0 1 2 2 v14"/><path d="M2 20 h3"/><path d="M13 20 h9"/><path d="M10 12 v.01"/><path d="M13 4.5 a1 1 0 0 1 -1.24 .97 L5 20 V5.5 a2 2 0 0 1 1.5 -1.94 l 4 -1 A2 2 0 0 1 13 4.5 Z"/></svg>',
    'jendela': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-amber-400 transition-all duration-500 group-hover:scale-110"><path d="M3 3h18v18H3V3Z"/><path d="M3 12h18"/><path d="M12 3v18"/></svg>',
    
    // KEAMANAN
    'keamanan': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-rose-500 transition-all duration-500 group-hover:rotate-6"><path d="M12 22 s8-4 8-10 V5 l-8-3 -8 3 v7 c 0 6 8 10 8 10 z"/></svg>',
    'cctv': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-rose-400 transition-all duration-500 group-hover:rotate-12"><path d="M16 10 l 4.5 -2.2 A1 1 0 0 1 22 8.6 v 6.7 a1 1 0 0 1 -1.4 .9 L16 14 v-4 z"/><rect width="14" height="12" x="2" y="6" rx="2"/></svg>',
    'pagar': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-rose-600 transition-all duration-500 group-hover:-translate-y-1"><path d="M3 20 V4 h2 v16"/><path d="M9 20 V4 h2 v16"/><path d="M15 20 V4 h2 v16"/><path d="M21 20 V4 h2 v16"/><path d="M2 10 h20"/></svg>',
    
    // PERALATAN ELEKTRONIK
    'elektronik': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-blue-500 transition-all duration-500 group-hover:-translate-y-1"><rect width="18" height="12" x="3" y="4" rx="2"/><path d="M12 16 v4"/><path d="M8 20 h8"/></svg>',
    'ac': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-sky-400 transition-all duration-1000 group-hover:rotate-180"><path d="M3 10 v8 a2 2 0 0 0 2 2 h14 a2 2 0 0 0 2 -2 v-8 Z M3 14 h18 M3 10 l 18 0"/><path d="M12 2 v4"/><path d="M7 3 l 2 2"/><path d="M17 3 l -2 2"/></svg>',
    'proyektor': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-blue-400 transition-all duration-500 group-hover:-rotate-3 animate-pulse"><circle cx="8" cy="12" r="3"/><path d="M11 12h9"/><rect width="20" height="12" x="2" y="6" rx="2"/></svg>',
    'komputer': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-blue-600 transition-all duration-500 group-hover:scale-110"><rect width="14" height="10" x="5" y="4" rx="2"/><rect width="20" height="4" x="2" y="16" rx="1"/><path d="M7 20 h10"/></svg>',
    
    // LINGKUNGAN
    'lingkungan': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-green-500 transition-all duration-500 group-hover:-translate-y-1"><path d="M11 20 A7 7 0 0 1 9.8 6.1 C15.5 5 17 21 11 20 Z"/><path d="M13 20 c 6 1 4.5 -15 -1.2 -13.9 A7 7 0 0 1 13 20 Z"/></svg>',
    'pohon': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-green-600 transition-all duration-500 group-hover:rotate-12 group-hover:scale-95"><path d="m12 19 8 -10 h -4 v -6 h -8 v 6 h -4 z"/></svg>',
    'drainase': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-cyan-600 transition-all duration-500 group-hover:-rotate-12"><path d="M12 22 v-4"/><path d="M12 14 s-4-6-4-10 a4 4 0 0 1 8 0 c 0 4-4 10-4 10 Z"/></svg>',

    // UMUM / LAINNYA
    'default': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-indigo-500 transition-all duration-500 group-hover:scale-110"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>'
};

const getCategoryIcon = (complaint) => {
    const catName = (complaint.category?.parent?.name ?? '').toLowerCase();
    const subCatName = (complaint.category?.name ?? '').toLowerCase();

    // Prioritize specific subcategory keywords
    const subKeys = [
        'atap', 'plafon', 'lampu', 'kontak', 'kabel', 'keran', 'toilet', 'saluran', 
        'sampah', 'kotor', 'bau', 'jalan', 'kursi', 'meja', 'pintu', 'jendela', 
        'cctv', 'pagar', 'ac', 'proyektor', 'komputer', 'pohon', 'drainase'
    ];

    for (const key of subKeys) {
        if (subCatName.includes(key)) return categoryIcons[key];
    }

    // fallback to main category keywords
    const mainKeys = [
        'kelistrikan', 'air', 'sanitasi', 'kebersihan', 'infrastruktur', 
        'fasilitas', 'keamanan', 'elektronik', 'lingkungan'
    ];

    for (const key of mainKeys) {
        if (catName.includes(key) || subCatName.includes(key)) return categoryIcons[key];
    }

    return categoryIcons['default'];
};

// Data and Scrolling
const localComplaints = ref([...(props.complaints?.data ?? [])]);
const isInitialLoad = ref(true);

watch(() => props.complaints, (newVal) => {
    localComplaints.value = [...(newVal?.data ?? [])];
}, { deep: true });

// Server-side filtered — no client-side filtering needed
const filteredComplaints = computed(() => localComplaints.value);

// Sorting
const sortBy = ref(props.filters?.sort || 'newest');
const sortOptions = [
    { value: 'newest', label: 'Terbaru' },
    { value: 'oldest', label: 'Terlama' },
    { value: 'status', label: 'Status' },
];

// Server-side filter/sort requests
let filterTimeout;
const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('complaints.index'), {
            status: activeFilter.value !== 'all' ? activeFilter.value : undefined,
            sort: sortBy.value !== 'newest' ? sortBy.value : undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            only: ['complaints', 'statusCounts', 'filters'],
        });
    }, 150);
};

// Watch filter and sort changes — trigger server requests
watch(activeFilter, applyFilters);
watch(sortBy, applyFilters);

onMounted(() => {
    setTimeout(() => { isInitialLoad.value = false; }, 400);
});

// Format Dates
const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const formatDateFull = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
};

// Detail Modal
const selectedComplaint = ref(null);
const showDetail = ref(false);

const openDetail = async (complaint) => {
    selectedComplaint.value = complaint;
    showRatingForm.value = false;
    ratingValue.value = 0;
    ratingComment.value = '';
    hoverRating.value = 0;
    showDetail.value = true;
    document.body.style.overflow = 'hidden';

    // Mark as read if this complaint has an unread notification
    if (!complaint.notified_at && complaint.status !== 'pending') {
        try {
            await axios.post(route('notifications.markSingleRead', complaint.id));
            // Update local state so the "Baru" badge disappears immediately
            complaint.notified_at = new Date().toISOString();
            const idx = localComplaints.value.findIndex(c => c.id === complaint.id);
            if (idx !== -1) {
                localComplaints.value[idx].notified_at = complaint.notified_at;
            }
            // Memberitahu layout agar badge lonceng berkurang
            window.dispatchEvent(new Event('notification-read'));
        } catch {
            // Silently ignore
        }
    }
};

const closeDetail = () => {
    showDetail.value = false;
    document.body.style.overflow = '';
    setTimeout(() => {
        selectedComplaint.value = null;
        showRatingForm.value = false;
        ratingValue.value = 0;
        ratingComment.value = '';
        hoverRating.value = 0;
    }, 400);
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

// Handle ESC to close detail
const handleDetailKey = (e) => {
    if (e.key === 'Escape' && showDetail.value && !lightboxOpen.value) closeDetail();
};
onMounted(() => {
    document.addEventListener('keydown', handleDetailKey);
});
onUnmounted(() => {
    document.removeEventListener('keydown', handleDetailKey);
    document.body.style.overflow = '';
});

// Estimation helpers
const todayUser = new Date();
todayUser.setHours(0, 0, 0, 0);

const estimationStatus = (complaint) => {
    if (!complaint.estimated_completion_date) return null;
    if (complaint.status === 'resolved' || complaint.status === 'rejected') return 'done';
    const est = new Date(complaint.estimated_completion_date);
    est.setHours(0, 0, 0, 0);
    const diff = Math.round((est - todayUser) / (1000 * 60 * 60 * 24));
    if (diff < 0) return 'overdue';
    if (diff === 0) return 'today';
    return diff;
};

const formatEstDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
};

// Rating
const hoverRating = ref(0);
const showRatingForm = ref(false);
const ratingValue = ref(0);
const ratingComment = ref('');
const ratingSubmitting = ref(false);
const ratingEmojis = ['', '😞', '😟', '😐', '😊', '😍'];

const initRatingForm = () => {
    showRatingForm.value = true;
    ratingValue.value = 0;
    ratingComment.value = '';
};

const setRating = (star) => {
    ratingValue.value = star;
};

const getDisplayRating = () => {
    return hoverRating.value || ratingValue.value;
};

const submitRating = async (complaint) => {
    if (ratingValue.value === 0 || ratingSubmitting.value) return;
    ratingSubmitting.value = true;
    try {
        await axios.post(route('complaints.rating', complaint.id), {
            rating: ratingValue.value,
            rating_comment: ratingComment.value,
        });
        // Update local data to reflect the rating immediately
        const idx = localComplaints.value.findIndex(c => c.id === complaint.id);
        if (idx !== -1) {
            localComplaints.value[idx].rating = ratingValue.value;
            localComplaints.value[idx].rating_comment = ratingComment.value;
        }
        if (selectedComplaint.value && selectedComplaint.value.id === complaint.id) {
            selectedComplaint.value.rating = ratingValue.value;
            selectedComplaint.value.rating_comment = ratingComment.value;
        }
        // Reset form
        showRatingForm.value = false;
        ratingValue.value = 0;
        ratingComment.value = '';
    } catch (err) {
        console.error('Rating submit error:', err);
    } finally {
        ratingSubmitting.value = false;
    }
};

const ratingLabel = (rating) => {
    const labels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'];
    return labels[rating] || '';
};

// Filter Config
const filterConfig = {
    all: { label: 'Semua', color: 'primary', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>' },
    pending: { label: 'Menunggu', color: 'amber', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg>' },
    processing: { label: 'Diproses', color: 'blue', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>' },
    resolved: { label: 'Selesai', color: 'emerald', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>' },
    rejected: { label: 'Ditolak', color: 'rose', icon: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>' },
};

// Status color mapping for card accent
const statusColorMap = {
    pending: { from: '#f59e0b', to: '#d97706', bg: 'rgba(245,158,11,0.08)', border: 'rgba(245,158,11,0.2)' },
    processing: { from: '#3b82f6', to: '#2563eb', bg: 'rgba(59,130,246,0.08)', border: 'rgba(59,130,246,0.2)' },
    resolved: { from: '#10b981', to: '#059669', bg: 'rgba(16,185,129,0.08)', border: 'rgba(16,185,129,0.2)' },
    rejected: { from: '#f43f5e', to: '#e11d48', bg: 'rgba(244,63,94,0.08)', border: 'rgba(244,63,94,0.2)' },
};
</script>

<template>
    <Head :title="t('complaint_history')" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">{{ t('complaint_history') }}</h1>
        </template>

        <div class="riwayat-page max-w-6xl mx-auto space-y-8">

            <!-- ═══════════════════════════════════════════════════
                 HERO SECTION
                 ═══════════════════════════════════════════════════ -->
            <!-- Hero Banner -->
            <div class="relative overflow-hidden rounded-[2rem] bg-[#533483] animate-fade-in-up border border-white/10 shadow-2xl group mb-8">
                <!-- Abstract Background Orbs -->
                <div class="absolute top-0 -left-1/4 w-[150%] h-[150%] bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#db2777]/40 via-[#7c3aed]/20 to-transparent mix-blend-screen opacity-70 blur-[80px] pointer-events-none group-hover:scale-105 transition-transform duration-1000"></div>
                <div class="absolute -bottom-1/2 right-0 w-full h-full bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#3b82f6]/30 via-[#8b5cf6]/20 to-transparent mix-blend-screen opacity-60 blur-[60px] pointer-events-none group-hover:-translate-x-4 transition-transform duration-1000"></div>
                
                <!-- Noise Overlay -->
                <div class="absolute inset-0 opacity-[0.03] mix-blend-overlay pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.85%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>
                
                <!-- Glass Reflection Layers -->
                <div class="absolute inset-0 bg-gradient-to-tr from-white/5 via-transparent to-white/20 pointer-events-none"></div>
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>

                <div class="p-8 sm:p-10 relative z-10 flex flex-col justify-between h-full">
                    <!-- Top row: Title + CTA -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-10">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-[10px] font-black uppercase tracking-[0.15em] mb-4 shadow-sm">
                                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                                Laporan Pengaduan
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight leading-tight drop-shadow-md mb-2">
                                Riwayat Pengaduan
                            </h2>
                            <p class="text-white/80 text-sm sm:text-base font-medium max-w-lg leading-relaxed drop-shadow-sm">
                                Pantau status perbaikan fasilitas sekolah secara real-time.
                            </p>
                        </div>
                        <Link :href="route('complaints.create')" class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-white border border-white/30 backdrop-blur-md text-sm font-black px-7 py-3.5 rounded-xl transition-all duration-300 hover:-translate-y-1 shadow-[0_8px_20px_-6px_rgba(0,0,0,0.2)] flex-shrink-0 self-start lg:self-center">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Buat Pengaduan
                        </Link>
                    </div>

                    <!-- Stat Ring Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 lg:gap-6">
                        <!-- Total -->
                        <div class="relative flex flex-col items-center justify-center gap-2 p-5 bg-white/10 backdrop-blur-md border border-white/20 rounded-[1.25rem] hover:bg-white/20 hover:border-white/40 transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group hover:-translate-y-2 shadow-[0_4px_15px_rgba(0,0,0,0.1)] hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.3)] cursor-default overflow-hidden">
                            <!-- Shine Sweep -->
                            <div class="absolute inset-y-0 -inset-x-[150%] bg-gradient-to-r from-transparent via-white/20 to-transparent transform -skew-x-[30deg] -translate-x-[200%] group-hover:duration-1000 group-hover:translate-x-[200%] transition-transform ease-[cubic-bezier(0.23,1,0.32,1)] z-0 pointer-events-none"></div>
                            
                            <!-- Card Content -->
                            <div class="flex items-center gap-4 w-full relative z-10">
                                <div class="relative w-12 h-12 flex-shrink-0 transition-transform duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:scale-110 group-hover:rotate-[15deg]">
                                    <svg class="w-full h-full -rotate-90 group-hover:-rotate-[75deg] transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]" viewBox="0 0 48 48">
                                        <circle cx="24" cy="24" r="20" fill="none" class="stroke-white/10 group-hover:stroke-white/20 transition-colors duration-700" stroke-width="3" />
                                        <circle cx="24" cy="24" r="20" fill="none" stroke="url(#gradTotalGlass)" stroke-width="3" stroke-linecap="round" stroke-dasharray="126" stroke-dashoffset="0" class="drop-shadow-[0_0_8px_rgba(233,213,255,0)] group-hover:drop-shadow-[0_0_8px_rgba(233,213,255,0.6)] transition-all duration-700 ease-out" />
                                        <defs><linearGradient id="gradTotalGlass" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#c4b5fd"/><stop offset="100%" stop-color="#e9d5ff"/></linearGradient></defs>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center text-purple-200 transition-transform duration-700 group-hover:-rotate-[15deg] group-hover:scale-[1.15]">
                                        <svg class="w-5 h-5 drop-shadow-md group-hover:text-white transition-colors duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1 text-left">
                                    <p class="text-2xl lg:text-3xl font-black text-white leading-none drop-shadow-sm group-hover:scale-110 origin-left transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:text-purple-100">{{ statusCounts.total }}</p>
                                    <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest mt-1 group-hover:text-white/90 transition-colors duration-300">Total</p>
                                </div>
                            </div>
                        </div>

                        <!-- Menunggu -->
                        <div class="relative flex flex-col items-center justify-center gap-2 p-5 bg-white/10 backdrop-blur-md border border-white/20 rounded-[1.25rem] hover:bg-white/20 hover:border-white/40 transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group hover:-translate-y-2 shadow-[0_4px_15px_rgba(0,0,0,0.1)] hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.3)] cursor-default overflow-hidden">
                            <div class="absolute inset-y-0 -inset-x-[150%] bg-gradient-to-r from-transparent via-white/20 to-transparent transform -skew-x-[30deg] -translate-x-[200%] group-hover:duration-1000 group-hover:translate-x-[200%] transition-transform ease-[cubic-bezier(0.23,1,0.32,1)] z-0 pointer-events-none"></div>
                            
                            <div class="flex items-center gap-4 w-full relative z-10">
                                <div class="relative w-12 h-12 flex-shrink-0 transition-transform duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:scale-110 group-hover:rotate-[15deg]">
                                    <svg class="w-full h-full -rotate-90 group-hover:-rotate-[75deg] transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]" viewBox="0 0 48 48">
                                        <circle cx="24" cy="24" r="20" fill="none" class="stroke-white/10 group-hover:stroke-white/20 transition-colors duration-700" stroke-width="3" />
                                        <circle cx="24" cy="24" r="20" fill="none" stroke="url(#gradPendingGlass)" stroke-width="3" stroke-linecap="round" :stroke-dasharray="126" :stroke-dashoffset="statusCounts.total > 0 ? 126 - (statusCounts.pending / statusCounts.total) * 126 : 126" class="drop-shadow-[0_0_8px_rgba(251,191,36,0)] group-hover:drop-shadow-[0_0_8px_rgba(251,191,36,0.6)] transition-all duration-700 ease-out" />
                                        <defs><linearGradient id="gradPendingGlass" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#fcd34d"/><stop offset="100%" stop-color="#fbbf24"/></linearGradient></defs>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center text-amber-200 transition-transform duration-700 group-hover:-rotate-[15deg] group-hover:scale-[1.15]">
                                        <svg class="w-5 h-5 drop-shadow-md group-hover:text-white transition-colors duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1 text-left">
                                    <p class="text-2xl lg:text-3xl font-black text-white leading-none drop-shadow-sm group-hover:scale-110 origin-left transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:text-amber-100">{{ statusCounts.pending }}</p>
                                    <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest mt-1 group-hover:text-white/90 transition-colors duration-300">Menunggu</p>
                                </div>
                            </div>
                        </div>

                        <!-- Diproses -->
                        <div class="relative flex flex-col items-center justify-center gap-2 p-5 bg-white/10 backdrop-blur-md border border-white/20 rounded-[1.25rem] hover:bg-white/20 hover:border-white/40 transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group hover:-translate-y-2 shadow-[0_4px_15px_rgba(0,0,0,0.1)] hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.3)] cursor-default overflow-hidden">
                            <div class="absolute inset-y-0 -inset-x-[150%] bg-gradient-to-r from-transparent via-white/20 to-transparent transform -skew-x-[30deg] -translate-x-[200%] group-hover:duration-1000 group-hover:translate-x-[200%] transition-transform ease-[cubic-bezier(0.23,1,0.32,1)] z-0 pointer-events-none"></div>
                            
                            <div class="flex items-center gap-4 w-full relative z-10">
                                <div class="relative w-12 h-12 flex-shrink-0 transition-transform duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:scale-110 group-hover:rotate-[15deg]">
                                    <svg class="w-full h-full -rotate-90 group-hover:-rotate-[75deg] transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]" viewBox="0 0 48 48">
                                        <circle cx="24" cy="24" r="20" fill="none" class="stroke-white/10 group-hover:stroke-white/20 transition-colors duration-700" stroke-width="3" />
                                        <circle cx="24" cy="24" r="20" fill="none" stroke="url(#gradProcessingGlass)" stroke-width="3" stroke-linecap="round" :stroke-dasharray="126" :stroke-dashoffset="statusCounts.total > 0 ? 126 - (statusCounts.processing / statusCounts.total) * 126 : 126" class="drop-shadow-[0_0_8px_rgba(96,165,250,0)] group-hover:drop-shadow-[0_0_8px_rgba(96,165,250,0.6)] transition-all duration-700 ease-out" />
                                        <defs><linearGradient id="gradProcessingGlass" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#93c5fd"/><stop offset="100%" stop-color="#60a5fa"/></linearGradient></defs>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center text-blue-200 transition-transform duration-700 group-hover:-rotate-[15deg] group-hover:scale-[1.15]">
                                        <svg class="w-5 h-5 drop-shadow-md group-hover:text-white transition-colors duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1 text-left">
                                    <p class="text-2xl lg:text-3xl font-black text-white leading-none drop-shadow-sm group-hover:scale-110 origin-left transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:text-blue-100">{{ statusCounts.processing }}</p>
                                    <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest mt-1 group-hover:text-white/90 transition-colors duration-300">Diproses</p>
                                </div>
                            </div>
                        </div>

                        <!-- Selesai -->
                        <div class="relative flex flex-col items-center justify-center gap-2 p-5 bg-white/10 backdrop-blur-md border border-white/20 rounded-[1.25rem] hover:bg-white/20 hover:border-white/40 transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group hover:-translate-y-2 shadow-[0_4px_15px_rgba(0,0,0,0.1)] hover:shadow-[0_15px_30px_-5px_rgba(0,0,0,0.3)] cursor-default overflow-hidden">
                            <div class="absolute inset-y-0 -inset-x-[150%] bg-gradient-to-r from-transparent via-white/20 to-transparent transform -skew-x-[30deg] -translate-x-[200%] group-hover:duration-1000 group-hover:translate-x-[200%] transition-transform ease-[cubic-bezier(0.23,1,0.32,1)] z-0 pointer-events-none"></div>
                            
                            <div class="flex items-center gap-4 w-full relative z-10">
                                <div class="relative w-12 h-12 flex-shrink-0 transition-transform duration-700 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:scale-110 group-hover:rotate-[15deg]">
                                    <svg class="w-full h-full -rotate-90 group-hover:-rotate-[75deg] transition-all duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]" viewBox="0 0 48 48">
                                        <circle cx="24" cy="24" r="20" fill="none" class="stroke-white/10 group-hover:stroke-white/20 transition-colors duration-700" stroke-width="3" />
                                        <circle cx="24" cy="24" r="20" fill="none" stroke="url(#gradResolvedGlass)" stroke-width="3" stroke-linecap="round" :stroke-dasharray="126" :stroke-dashoffset="statusCounts.total > 0 ? 126 - (statusCounts.resolved / statusCounts.total) * 126 : 126" class="drop-shadow-[0_0_8px_rgba(52,211,153,0)] group-hover:drop-shadow-[0_0_8px_rgba(52,211,153,0.6)] transition-all duration-700 ease-out" />
                                        <defs><linearGradient id="gradResolvedGlass" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#6ee7b7"/><stop offset="100%" stop-color="#34d399"/></linearGradient></defs>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center text-emerald-200 transition-transform duration-700 group-hover:-rotate-[15deg] group-hover:scale-[1.15]">
                                        <svg class="w-5 h-5 drop-shadow-md group-hover:text-white transition-colors duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                </div>
                                <div class="flex-1 text-left">
                                    <p class="text-2xl lg:text-3xl font-black text-white leading-none drop-shadow-sm group-hover:scale-110 origin-left transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] group-hover:text-emerald-100">{{ statusCounts.resolved }}</p>
                                    <p class="text-[10px] font-bold text-white/70 uppercase tracking-widest mt-1 group-hover:text-white/90 transition-colors duration-300">Selesai</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════
                 FILTER TABS
                 ═══════════════════════════════════════════════════ -->
            <div class="animate-fade-in-up stagger-1">
                <div class="riwayat-filter-bar shadow-sm">
                    <button
                        v-for="(cfg, key) in filterConfig"
                        :key="key"
                        @click="activeFilter = key"
                        class="riwayat-filter-tab group relative overflow-hidden flex items-center justify-center gap-2"
                        :class="{ active: activeFilter === key }"
                    >
                        <div v-if="activeFilter === key" class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent -translate-x-full animate-[shine-sweep_3s_infinite] pointer-events-none z-0"></div>
                        <span class="relative z-10 flex items-center justify-center w-6 h-6 rounded-lg backdrop-blur-md border border-white/40 shadow-sm transition-transform duration-300 group-hover:scale-110"
                              :class="{
                                 'bg-violet-100 text-violet-600': key === 'all',
                                 'bg-amber-100 text-amber-600': key === 'pending',
                                 'bg-[rgba(59,130,246,0.15)] text-blue-600': key === 'processing',
                                 'bg-emerald-100 text-emerald-600': key === 'resolved',
                                 'bg-rose-100 text-rose-600': key === 'rejected',
                              }" v-html="cfg.icon"></span>
                        <span class="relative z-10 mx-0.5">{{ key === 'all' ? t('all') : statusLabel[key] }}</span>
                        <span v-if="key !== 'all'" class="relative z-10 riwayat-filter-count shadow-sm border border-white/50" :class="'count-' + cfg.color">
                            {{ key === 'pending' ? statusCounts.pending : key === 'processing' ? statusCounts.processing : key === 'resolved' ? statusCounts.resolved : statusCounts.rejected }}
                        </span>
                        <span v-else class="relative z-10 riwayat-filter-count count-primary shadow-sm border border-white/50">{{ statusCounts.total }}</span>
                    </button>
                </div>
            </div>


            <!-- ═══════════════════════════════════════════════════
                 SORTING BAR
                 ═══════════════════════════════════════════════════ -->
            <div class="flex items-center justify-between animate-fade-in-up stagger-1 mb-2">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-[0.15em]">Urutkan</span>
                    <div class="flex gap-1.5">
                        <button
                            v-for="opt in sortOptions"
                            :key="opt.value"
                            @click="sortBy = opt.value"
                            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-all duration-300 border"
                            :class="sortBy === opt.value
                                ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-500/20'
                                : 'bg-white/70 text-gray-500 border-gray-200 hover:bg-white hover:border-primary-300 hover:text-primary-600'"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>
                <p v-if="complaints.total" class="text-[10px] font-bold text-gray-400 uppercase tracking-wider hidden sm:block">
                    {{ complaints.total }} laporan
                </p>
            </div>

            <!-- ═══════════════════════════════════════════════════
                 COMPLAINT CARDS GRID
                 ═══════════════════════════════════════════════════ -->

            <!-- Loading Skeleton -->
            <div v-if="isInitialLoad" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 animate-fade-in-up stagger-2">
                <div v-for="i in 6" :key="i" class="riwayat-card-skeleton">
                    <div class="h-1.5 rounded-full w-full mb-5" style="background: linear-gradient(90deg, rgba(139,92,246,0.1), rgba(244,114,182,0.1));"></div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 animate-pulse"></div>
                        <div class="flex-1">
                            <div class="h-2.5 bg-gray-100 rounded w-2/3 mb-2 animate-pulse"></div>
                            <div class="h-2 bg-gray-50 rounded w-1/3 animate-pulse"></div>
                        </div>
                    </div>
                    <div class="h-4 bg-gray-100 rounded w-4/5 mb-3 animate-pulse"></div>
                    <div class="h-3 bg-gray-50 rounded w-1/2 mb-2 animate-pulse"></div>
                    <div class="h-3 bg-gray-50 rounded w-2/3 mb-5 animate-pulse"></div>
                    <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                        <div class="h-2.5 bg-gray-50 rounded w-1/3 animate-pulse"></div>
                        <div class="w-8 h-8 rounded-full bg-gray-50 animate-pulse"></div>
                    </div>
                </div>
            </div>

            <div v-else-if="filteredComplaints?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Complaint Card -->
                <div
                    v-for="(complaint, index) in filteredComplaints"
                    :key="complaint.id"
                    class="riwayat-card group cursor-pointer"
                    :class="{ 'riwayat-card-updated': !complaint.notified_at && complaint.status !== 'pending' }"
                    :style="{ animationDelay: (index * 0.04) + 's' }"
                    @click="openDetail(complaint)"
                >
                    <!-- Status accent bar at top -->
                    <div class="riwayat-card-accent"
                         :style="{
                             background: `linear-gradient(90deg, ${statusColorMap[complaint.status]?.from}, ${statusColorMap[complaint.status]?.to}, ${statusColorMap[complaint.status]?.from})`,
                             backgroundSize: '200% 100%'
                         }"
                    ></div>

                    <!-- Ambient background glow -->
                    <div class="riwayat-card-glow" :style="{ background: `radial-gradient(circle at center, ${statusColorMap[complaint.status]?.from}15, transparent 70%)` }"></div>

                    <!-- Update indicator badge -->
                    <div v-if="!complaint.notified_at && complaint.status !== 'pending'" class="update-ribbon shadow-lg shadow-primary-500/20">
                        <svg class="w-3 h-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        Baru
                    </div>

                    <!-- Card Header: Category icon + status badge -->
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="riwayat-cat-icon transition-all duration-500 group-hover:shadow-[0_0_15px_rgba(0,0,0,0.05)]"
                                 :style="{ background: statusColorMap[complaint.status]?.bg, borderColor: statusColorMap[complaint.status]?.border }"
                            >
                                <span class="flex items-center justify-center select-none" v-html="getCategoryIcon(complaint)"></span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.12em] mb-0.5">
                                    <template v-if="complaint.category?.parent">{{ complaint.category.parent.name }} ›</template>
                                </p>
                                <p class="text-xs font-bold text-gray-600">{{ complaint.category?.name }}</p>
                            </div>
                        </div>
                        <span :class="'badge badge-' + complaint.status" class="text-[9px] py-1 px-2.5 flex-shrink-0">
                            ● {{ statusLabel[complaint.status] }}
                        </span>
                    </div>

                    <!-- Title -->
                    <h3 class="text-[15px] font-extrabold text-gray-900 mb-3 leading-snug group-hover:text-primary-700 transition-colors duration-300 line-clamp-2">
                        {{ complaint.title }}
                    </h3>

                    <!-- Description preview -->
                    <p v-if="complaint.description" class="text-xs text-gray-400 font-medium mb-4 line-clamp-2 leading-relaxed">
                        {{ complaint.description }}
                    </p>

                    <!-- Location & Date -->
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] text-gray-400 font-semibold mb-4">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ complaint.location }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ formatDate(complaint.created_at) }}
                        </span>
                    </div>

                    <!-- Bottom: Status note + arrow -->
                    <div class="flex items-center justify-between pt-3.5 border-t border-gray-100/60">
                        <p class="text-[11px] text-gray-400 font-medium italic truncate max-w-[75%]">
                            <template v-if="!complaint.notified_at && complaint.status !== 'pending'">
                                <span class="text-primary-600 font-bold not-italic">Status diperbarui oleh admin</span>
                            </template>
                            <template v-else-if="complaint.admin_response">
                                "{{ complaint.admin_response.substring(0, 40) }}{{ complaint.admin_response.length > 40 ? '...' : '' }}"
                            </template>
                            <template v-else-if="complaint.status === 'pending'">
                                Menunggu verifikasi admin...
                            </template>
                            <template v-else-if="complaint.status === 'processing'">
                                Sedang dalam proses penanganan
                            </template>
                            <template v-else-if="complaint.status === 'resolved'">
                                Sudah diperbaiki oleh tim teknis
                            </template>
                            <template v-else>
                                {{ statusLabel[complaint.status] }}
                            </template>
                        </p>
                        <div class="riwayat-card-arrow group-hover:translate-x-1">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-600 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Empty State -->
            <div v-else-if="!isInitialLoad" class="text-center py-20 px-6 relative group border border-transparent animate-fade-in-up mt-5">
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
                    <h3 class="text-2xl font-black text-gray-900 mb-3 tracking-tight group-hover:text-primary-700 transition-colors">{{ t('no_complaint_yet') }}</h3>
                    <p class="text-gray-500 font-medium text-sm max-w-sm mx-auto leading-relaxed group-hover:text-gray-600 transition-colors">
                        Kami belum menemukan laporan dengan filter yang Anda gunakan.
                    </p>
                </div>
                
                <button v-if="activeFilter !== 'all'" @click="activeFilter = 'all'" class="inline-flex items-center gap-2 px-6 py-3 bg-white/80 backdrop-blur-md text-primary-700 font-bold text-xs rounded-2xl border border-white/90 hover:bg-white transition-all duration-300 hover:-translate-y-0.5 shadow-sm uppercase tracking-wider relative z-10 mt-6">
                    {{ t('show_all') }}
                </button>
            </div>

            <!-- ═══════════════════════════════════════════════════
                 PAGINATION + COUNT
                 ═══════════════════════════════════════════════════ -->
                <div v-if="!isInitialLoad && localComplaints.length > 0" class="text-center mt-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-[0.2em] mb-4">
                        Menampilkan {{ localComplaints.length }} dari {{ complaints.total }} Laporan
                    </p>
                </div>
                <div v-if="complaints.links?.length > 3" class="flex justify-center flex-wrap gap-2 mt-2 pb-4">
                    <template v-for="(link, index) in complaints.links" :key="index">
                        <button
                            v-if="link.url"
                            @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                            class="riwayat-page-btn"
                            :class="link.active ? 'active' : ''"
                            v-html="link.label"
                        ></button>
                        <div
                            v-else
                            class="riwayat-page-btn disabled"
                            v-html="link.label"
                        ></div>
                    </template>
                </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════
             DETAIL MODAL (Fullscreen Overlay)
             ═══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="detail-modal">
                <div
                    v-if="showDetail && selectedComplaint"
                    class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto"
                    @click.self="closeDetail"
                >
                    <!-- Blurred Overlay -->
                    <div class="fixed inset-0 bg-gradient-to-br from-purple-100/60 via-pink-50/40 to-white/60 backdrop-blur-sm" @click="closeDetail"></div>

                    <!-- Modal Content -->
                    <div class="relative z-10 w-full max-w-5xl mx-4 my-8 bg-[#fef7fe] rounded-[2rem] shadow-2xl overflow-hidden ring-1 ring-black/5" @click.stop>
                        <div class="p-6 md:p-10 max-h-[85vh] overflow-y-auto custom-scrollbar">
                            <!-- Breadcrumb / Back Navigation -->
                            <div class="mb-8 flex items-center gap-2">
                                <button @click="closeDetail" class="w-10 h-10 flex items-center justify-center text-[#685688] hover:bg-[#eadef7] rounded-full transition-colors focus:outline-none">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                                </button>
                                <span class="text-[#625d67] text-sm font-semibold cursor-pointer hover:text-[#35313a] transition-colors" @click="closeDetail">Kembali ke Laporan</span>
                            </div>

                            <!-- Hero Section: Report Title & Status -->
                            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
                                <div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="bg-[#eadef7] text-[#564e63] px-4 py-1.5 rounded-full text-xs font-bold tracking-wide shadow-sm border border-[#dbc5fe]/50">
                                            {{ statusLabel[selectedComplaint.status] }}
                                        </span>
                                        <span class="text-[#625d67] font-bold text-sm tracking-wide">ID #{{ selectedComplaint.id }}</span>
                                    </div>
                                    <h1 class="text-3xl md:text-4xl font-extrabold text-[#35313a] tracking-tight leading-tight" style="font-family: 'Manrope', sans-serif;">
                                        <template v-if="selectedComplaint.category?.parent">{{ selectedComplaint.category.parent.name }} > </template>{{ selectedComplaint.title || selectedComplaint.category?.name }}
                                    </h1>
                                    <p class="text-[#625d67] mt-3 flex items-center gap-2 font-medium text-sm">
                                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ formatDateFull(selectedComplaint.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Bento Grid Layout -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                <!-- Left Column: Details (8 cols) -->
                                <div class="md:col-span-8 space-y-6">
                                    <!-- Problem Description -->
                                    <section class="bg-white p-7 rounded-[1.5rem] shadow-sm border border-[#ede6f0]">
                                        <h2 class="text-lg font-bold text-[#35313a] mb-4" style="font-family: 'Manrope', sans-serif;">Deskripsi Masalah</h2>
                                        <div class="bg-[#f3ebf5] p-5 rounded-xl text-[#35313a] leading-relaxed italic border-l-4 border-[#685688] font-medium text-sm shadow-inner">
                                            "{{ selectedComplaint.description }}"
                                        </div>
                                    </section>

                                    <!-- Attached Photo -->
                                    <section v-if="selectedComplaint.image_paths?.length > 0 || selectedComplaint.image_path" class="bg-white p-7 rounded-[1.5rem] border border-[#ede6f0] shadow-sm">
                                        <h2 class="text-lg font-bold text-[#35313a] mb-4" style="font-family: 'Manrope', sans-serif;">Foto Lampiran</h2>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <template v-if="selectedComplaint.image_paths?.length > 0">
                                                <div v-for="(imgPath, idx) in selectedComplaint.image_paths" :key="idx" class="overflow-hidden rounded-xl bg-[#f3ebf5] aspect-video relative group cursor-zoom-in shadow-sm hover:shadow-md transition-shadow" @click.stop="openLightbox(selectedComplaint.image_paths.map(p => resolveImageUrl(p)), idx)">
                                                    <img :src="resolveImageUrl(imgPath)" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                                                    <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition-colors duration-500"></div>
                                                </div>
                                            </template>
                                            <template v-else-if="selectedComplaint.image_path">
                                                <div class="overflow-hidden rounded-xl bg-[#f3ebf5] aspect-video relative group cursor-zoom-in shadow-sm hover:shadow-md transition-shadow" @click.stop="openLightbox([resolveImageUrl(selectedComplaint.image_path)], 0)">
                                                    <img :src="resolveImageUrl(selectedComplaint.image_path)" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                                                    <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition-colors duration-500"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </section>

                                    <!-- Admin Response -->
                                    <section v-if="selectedComplaint.admin_response" class="bg-[#f3ebf5] p-7 rounded-[1.5rem] border border-[#dbc5fe] shadow-sm relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-32 h-32 bg-[#dbc5fe] rounded-full blur-3xl opacity-30 -mr-10 -mt-10"></div>
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-3 mb-6">
                                                <div class="bg-[#dbc5fe] p-2.5 rounded-xl text-[#685688] shadow-sm border border-white/50">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                                </div>
                                                <h2 class="text-lg font-bold text-[#35313a]" style="font-family: 'Manrope', sans-serif;">Respon Admin</h2>
                                            </div>
                                            <div class="space-y-4">
                                                <div class="flex gap-4">
                                                    <div class="w-10 h-10 rounded-full border-2 border-white shadow-sm bg-gradient-to-br from-[#685688] to-[#7d516d] flex items-center justify-center text-white font-bold flex-shrink-0 text-sm">
                                                        A
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="text-[#35313a] font-bold text-sm mb-1.5 flex items-center flex-wrap gap-2">
                                                            Administrator 
                                                            <span class="text-[#625d67] font-medium text-xs">{{ formatDateFull(selectedComplaint.updated_at) }}</span>
                                                        </p>
                                                        <div class="bg-white p-5 rounded-2xl rounded-tl-none shadow-sm text-sm text-[#35313a] leading-relaxed font-medium border border-[#ede6f0]">
                                                            {{ selectedComplaint.admin_response }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Right Column: Meta & Feedback (4 cols) -->
                                <aside class="md:col-span-4 space-y-6">
                                    
                                    <!-- Progress Timeline (New) -->
                                    <section v-if="selectedComplaint.status !== 'rejected'" class="bg-white p-7 rounded-[1.5rem] shadow-sm border border-[#ede6f0]">
                                        <h2 class="text-[11px] font-extrabold text-[#625d67] uppercase tracking-[0.2em] mb-7 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-[#685688]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Progress Timeline
                                        </h2>
                                        <div class="relative ml-2">
                                            <!-- Vertical Line Background -->
                                            <div class="absolute left-[-1px] top-2 bottom-4 w-[3px] bg-[#f3ebf5] rounded-full"></div>
                                            <!-- Active Vertical Line -->
                                            <div class="absolute left-[-1px] top-2 w-[3px] bg-gradient-to-b from-[#685688] to-[#9d7bb3] rounded-full transition-all duration-1000 ease-in-out" 
                                                 :style="{ height: selectedComplaint.status === 'resolved' ? '100%' : (selectedComplaint.status === 'processing' ? '50%' : '0%') }"></div>

                                            <!-- Step 1: Diterima -->
                                            <div class="relative pl-7 pb-8 group">
                                                <div class="absolute left-[-5px] top-1 w-4 h-4 rounded-full border-[3px] border-white bg-[#685688] shadow-sm ring-1 ring-[#685688]/20 z-10 transition-transform group-hover:scale-110"></div>
                                                
                                                <h4 class="text-[14px] font-bold text-[#35313a] leading-none mb-1">Laporan Masuk</h4>
                                                <div class="flex items-center gap-2 text-xs text-[#625d67] font-medium mb-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    {{ formatDateFull(selectedComplaint.created_at) }}
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-relaxed font-medium">Berhasil tercatat di sistem informasi</p>
                                            </div>

                                            <!-- Step 2: Diproses -->
                                            <div class="relative pl-7 pb-8 group">
                                                <div class="absolute left-[-5px] top-1 w-4 h-4 rounded-full border-[3px] border-white transition-all shadow-sm ring-1 z-10 group-hover:scale-110"
                                                     :class="['processing', 'resolved'].includes(selectedComplaint.status) ? 'bg-[#685688] ring-[#685688]/20' : 'bg-[#eadef7] ring-transparent'"></div>
                                                     
                                                <!-- Active Ping Effect -->
                                                <div v-if="selectedComplaint.status === 'processing'" class="absolute left-[-5px] top-1 w-4 h-4 rounded-full border-[3px] border-white bg-[#685688] animate-ping opacity-60 z-0"></div>
                                                
                                                <h4 class="text-[14px] font-bold leading-none mb-1 transition-colors" 
                                                    :class="['processing', 'resolved'].includes(selectedComplaint.status) ? 'text-[#35313a]' : 'text-gray-400'">Diproses oleh Tim</h4>
                                                
                                                <div v-if="selectedComplaint.status === 'processing' || selectedComplaint.status === 'resolved'" class="flex items-center gap-2 text-xs text-[#625d67] font-medium mb-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                                    <span class="italic">{{ selectedComplaint.status === 'resolved' ? 'Pengerjaan lapangan selesai' : 'Sedang dalam pengerjaan teknisi' }}</span>
                                                </div>
                                                
                                                <div v-if="selectedComplaint.status === 'processing' && selectedComplaint.progress" class="mt-3 w-full h-2 bg-[#f3ebf5] rounded-full overflow-hidden shadow-inner border border-white/50">
                                                    <div class="h-full bg-gradient-to-r from-[#685688] to-[#9d7bb3] rounded-full transition-all duration-1000 relative" :style="`width: ${selectedComplaint.progress}%`">
                                                        <div class="absolute inset-0 bg-[image:linear-gradient(45deg,rgba(255,255,255,0.2)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.2)_50%,rgba(255,255,255,0.2)_75%,transparent_75%,transparent)] bg-[length:1rem_1rem] animate-[progress-stripes_1s_linear_infinite]"></div>
                                                    </div>
                                                </div>
                                                <div v-if="selectedComplaint.status === 'processing' && selectedComplaint.progress" class="flex justify-between items-center mt-1.5">
                                                    <span class="text-[10px] items-center text-[#685688] font-black tracking-wider flex gap-1">
                                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                        PROGRESS
                                                    </span>
                                                    <span class="text-[10px] text-[#625d67] font-bold">{{ selectedComplaint.progress }}%</span>
                                                </div>
                                            </div>

                                            <!-- Step 3: Selesai -->
                                            <div class="relative pl-7 group">
                                                <div class="absolute left-[-5px] top-1 w-4 h-4 rounded-full border-[3px] border-white transition-all shadow-sm ring-1 z-10 group-hover:scale-110"
                                                     :class="selectedComplaint.status === 'resolved' ? 'bg-[#10b981] ring-[#10b981]/20' : 'bg-[#eadef7] ring-transparent'"></div>
                                                     
                                                <!-- Active Ping Effect for Resolved -->
                                                <div v-if="selectedComplaint.status === 'resolved'" class="absolute left-[-5px] top-1 w-4 h-4 rounded-full border-[3px] border-white bg-[#10b981] animate-ping opacity-40 z-0"></div>

                                                <h4 class="text-[14px] font-bold leading-none mb-1 transition-colors" 
                                                    :class="selectedComplaint.status === 'resolved' ? 'text-[#35313a]' : 'text-gray-400'">Tugas Selesai</h4>
                                                    
                                                <div v-if="selectedComplaint.status === 'resolved'" class="flex items-center gap-2 text-xs text-[#10b981] font-bold mb-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    {{ formatDateFull(selectedComplaint.updated_at) }}
                                                </div>
                                                <p class="text-[11px] transition-colors leading-relaxed font-medium" :class="selectedComplaint.status === 'resolved' ? 'text-gray-500' : 'text-gray-300'">Penanganan telah diselesaikan dengan baik</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Estimated Completion Date if processing and estimated -->
                                        <div v-if="selectedComplaint.status === 'processing' && selectedComplaint.estimated_completion_date" class="mt-6 pt-5 border-t border-dashed border-[#ede6f0]">
                                            <div class="bg-[#f8f1fa] rounded-xl p-3.5 flex items-center gap-3">
                                                <div class="p-2 bg-white rounded-lg text-[#685688] shadow-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </div>
                                                <div>
                                                    <p class="text-[10px] text-[#625d67] font-bold uppercase tracking-wider mb-0.5">Estimasi Selesai</p>
                                                    <p class="text-xs text-[#35313a] font-extrabold">{{ formatEstDate(selectedComplaint.estimated_completion_date) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Rejected State (New) -->
                                    <section v-else class="bg-gradient-to-br from-[#fff1f2] to-[#ffe4e6] p-7 rounded-[1.5rem] border border-[#fecdd3] shadow-sm relative overflow-hidden group">
                                        <div class="absolute -right-6 -top-6 text-rose-500/5 group-hover:scale-110 group-hover:rotate-12 transition-transform duration-700">
                                            <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                        </div>
                                        <div class="relative z-10 flex flex-col items-center text-center">
                                            <div class="w-14 h-14 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center mb-4 shadow-inner shadow-rose-200/50 relative transform transition-transform duration-300 hover:scale-110">
                                                <!-- Ping effect -->
                                                <div class="absolute inset-0 rounded-full bg-rose-400 animate-ping opacity-25"></div>
                                                <svg class="w-6 h-6 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </div>
                                            <h3 class="text-rose-700 font-extrabold text-lg mb-1.5" style="font-family: 'Manrope', sans-serif;">Laporan Ditolak</h3>
                                            <p class="text-rose-600/80 text-xs font-semibold px-2 mb-5">Laporan ini tidak dapat diproses lebih lanjut oleh sistem.</p>
                                            
                                            <div class="bg-white/70 p-4 rounded-xl border border-rose-100/60 w-full backdrop-blur-md shadow-sm relative text-left transition-all hover:bg-white/90">
                                                <span class="block text-[10px] font-extrabold text-rose-500 uppercase tracking-widest mb-2 flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>ALASAN PENOLAKAN</span>
                                                <p class="text-[#35313a] text-[13px] font-bold leading-relaxed italic border-l-2 border-rose-400 pl-3">"{{ selectedComplaint.admin_response || 'Laporan tidak relevan atau merupakan duplikat. Hubungi admin untuk detail lebih lanjut.' }}"</p>
                                                <div class="mt-3 text-[10px] text-rose-400 font-bold flex items-center gap-1.5 pt-3 border-t border-rose-100 border-dashed">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    Terakhir diperbarui: {{ formatDate(selectedComplaint.updated_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Location Card (NO MAP/IMAGE) -->
                                    <section class="bg-white p-7 rounded-[1.5rem] shadow-sm border border-[#ede6f0]">
                                        <h2 class="text-[11px] font-extrabold text-[#625d67] uppercase tracking-[0.2em] mb-5">Lokasi Kejadian</h2>
                                        <div class="flex items-start gap-4">
                                            <div class="p-2.5 bg-[#f3ebf5] rounded-xl text-[#685688] shrink-0 mt-0.5 shadow-sm border border-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#35313a] text-base leading-snug">{{ selectedComplaint.location }}</p>
                                                <p v-if="selectedComplaint.location_detail" class="text-xs text-[#7e7983] font-medium mt-1.5 leading-relaxed">{{ selectedComplaint.location_detail }}</p>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Feedback & Rating Card -->
                                    <section v-if="selectedComplaint.status === 'resolved'" class="bg-white p-7 rounded-[1.5rem] shadow-lg shadow-[#dbc5fe]/20 border border-[#dbc5fe]/40">
                                        <h2 class="text-[11px] font-extrabold text-[#625d67] uppercase tracking-[0.2em] mb-6">Penilaian Anda</h2>
                                        
                                        <!-- Already rated -->
                                        <div v-if="selectedComplaint.rating">
                                            <div class="flex justify-center gap-1.5 mb-6">
                                                <svg v-for="star in 5" :key="star" class="w-8 h-8 drop-shadow-sm transition-all" :class="star <= selectedComplaint.rating ? 'text-[#7d516d]' : 'text-[#ede6f0]'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            </div>
                                            <div v-if="selectedComplaint.rating_comment" class="space-y-2 mb-6">
                                                <label class="text-[10px] font-extrabold text-[#625d67] ml-2 uppercase tracking-wide">Komentar Tambahan</label>
                                                <div class="bg-[#f8f1fa] p-4 rounded-2xl text-sm text-[#564e63] leading-relaxed italic font-medium shadow-inner border border-white/50">
                                                    "{{ selectedComplaint.rating_comment }}"
                                                </div>
                                            </div>
                                            <div class="w-full bg-[#f3ebf5] text-[#685688] font-bold py-3 rounded-xl text-center text-xs shadow-sm">
                                                Terima kasih atas feedback Anda
                                            </div>
                                        </div>

                                        <!-- Not yet rated -->
                                        <div v-else @click.stop>
                                            <template v-if="!showRatingForm">
                                                <p class="text-[13px] text-center text-[#564e63] font-medium mb-6 leading-relaxed">Bagaimana penilaian Anda terhadap penanganan aduan ini?</p>
                                                <button @click.stop="initRatingForm()" class="w-full bg-[#f3ebf5] text-[#685688] font-bold py-3.5 rounded-xl hover:bg-[#685688] hover:text-white transition-all shadow-sm">
                                                    Berikan Penilaian
                                                </button>
                                            </template>
                                            <template v-else>
                                                <div class="flex justify-center gap-1.5 mb-3">
                                                    <button v-for="star in 5" :key="star" type="button" class="focus:outline-none transition-transform hover:scale-110 active:scale-95" @mouseenter="hoverRating = star" @mouseleave="hoverRating = 0" @click.stop="setRating(star)">
                                                        <svg class="w-8 h-8 drop-shadow-sm transition-colors duration-200" :class="star <= getDisplayRating() ? 'text-[#7d516d]' : 'text-[#ede6f0]'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    </button>
                                                </div>
                                                <p v-if="ratingValue > 0" class="text-center text-xs font-bold text-[#685688] mb-4">{{ ratingEmojis[ratingValue] }} {{ ratingLabel(ratingValue) }}</p>
                                                <textarea v-model="ratingComment" class="w-full text-sm border border-transparent bg-[#f8f1fa] text-[#35313a] rounded-2xl p-4 focus:ring-2 focus:ring-[#685688]/30 focus:border-[#685688] resize-none outline-none font-medium placeholder-[#b6afbb] shadow-inner mb-4" rows="3" placeholder="Ceritakan pengalaman Anda... (opsional)" @click.stop></textarea>
                                                <button type="button" @click.stop="submitRating(selectedComplaint)" :disabled="ratingValue === 0 || ratingSubmitting" class="w-full bg-gradient-to-br from-[#685688] to-[#5c4a7b] text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition-all disabled:opacity-50 shadow-md">
                                                    <span v-if="!ratingSubmitting">Kirim Feedback</span>
                                                    <span v-else class="animate-pulse">Mengirim...</span>
                                                </button>
                                            </template>
                                        </div>
                                    </section>

                                </aside>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Lightbox Image Preview -->
        <Teleport to="body">
            <Transition name="lightbox">
                <div
                    v-if="lightboxOpen"
                    class="fixed inset-0 z-[200] flex items-center justify-center bg-white/60 backdrop-blur-[2px]"
                    @click.self="closeLightbox"
                >
                    <button @click="closeLightbox" class="absolute top-6 right-6 w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold transition-all duration-300 hover:scale-105">✕</button>

                    <div v-if="lightboxImages.length > 1" class="absolute top-8 left-1/2 -translate-x-1/2 font-bold text-gray-400 tracking-widest text-xs uppercase">
                        {{ lightboxIndex + 1 }} / {{ lightboxImages.length }}
                    </div>

                    <button v-if="lightboxImages.length > 1" @click="navLightbox(-1)" class="absolute left-6 w-14 h-14 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-2xl transition-all duration-300 hover:scale-105">‹</button>

                    <img
                        :src="lightboxImages[lightboxIndex]"
                        :alt="'Foto ' + (lightboxIndex + 1)"
                        class="max-w-[85vw] max-h-[80vh] object-contain rounded-2xl shadow-xl select-none bg-white border border-gray-100 p-2"
                        style="animation: lightboxPop 0.35s cubic-bezier(0.22, 1, 0.36, 1);"
                    />

                    <button v-if="lightboxImages.length > 1" @click="navLightbox(1)" class="absolute right-6 w-14 h-14 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-2xl transition-all duration-300 hover:scale-105">›</button>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ══════════════════════════════════════════════════
   FILTER BAR
   ══════════════════════════════════════════════════ */
.riwayat-filter-bar {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 1.25rem;
    box-shadow:
        0 4px 20px -4px rgba(124, 58, 237, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.riwayat-filter-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.625rem 1.25rem;
    border-radius: 0.875rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    background: transparent;
    border: 1px solid transparent;
    transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    cursor: pointer;
}

.riwayat-filter-tab:hover {
    color: #6d28d9;
    background: rgba(139, 92, 246, 0.05);
}

.riwayat-filter-tab.active {
    color: #6d28d9;
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 1);
    box-shadow:
        0 4px 16px -4px rgba(124, 58, 237, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 1);
    transform: translateY(-1px);
}

.riwayat-filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.35rem;
    border-radius: 9999px;
    font-size: 0.6rem;
    font-weight: 800;
}

.count-primary { background: rgba(139, 92, 246, 0.1); color: #7c3aed; }
.count-amber { background: rgba(245, 158, 11, 0.1); color: #d97706; }
.count-blue { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
.count-emerald { background: rgba(16, 185, 129, 0.1); color: #059669; }
.count-rose { background: rgba(244, 63, 94, 0.1); color: #e11d48; }

/* ══════════════════════════════════════════════════
   COMPLAINT CARDS
   ══════════════════════════════════════════════════ */
.riwayat-card {
    background: rgba(255, 255, 255, 0.45);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 1.75rem;
    padding: 1.75rem;
    position: relative;
    overflow: hidden;
    transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
    box-shadow: 
        0 4px 20px -5px rgba(0, 0, 0, 0.05),
        inset 0 1px 1px rgba(255, 255, 255, 0.8);
    transform-style: preserve-3d;
    perspective: 1000px;
    animation: fadeInUp 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
}

.riwayat-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 1px;
    background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.1) 40%, rgba(255,255,255,0) 60%, rgba(255,255,255,0.4));
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
    z-index: 3;
}

.riwayat-card::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(circle at 10% 15%, rgba(167, 139, 250, 0.12), transparent 45%),
        radial-gradient(circle at 90% 85%, rgba(244, 114, 182, 0.08), transparent 40%);
    z-index: 0;
}

.riwayat-card > * {
    position: relative;
    z-index: 2;
}

.riwayat-card:hover {
    transform: translateY(-8px) rotateX(2deg) rotateY(-1deg);
    box-shadow:
        0 30px 60px -15px rgba(124, 58, 237, 0.18),
        0 15px 30px -10px rgba(124, 58, 237, 0.1),
        inset 0 1px 0 0 rgba(255, 255, 255, 1);
    border-color: rgba(167, 139, 250, 0.5);
}

.riwayat-card-glow {
    position: absolute;
    inset: 0;
    z-index: 1;
    opacity: 0;
    transition: opacity 0.5s var(--motion-smooth);
    pointer-events: none;
}

.riwayat-card:hover .riwayat-card-glow {
    opacity: 1;
}

/* Status accent bar at top */
.riwayat-card-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    border-radius: 4px 4px 0 0;
    z-index: 4;
    animation: accentSlide 3s linear infinite;
}

@keyframes accentSlide {
    0% { background-position: 0% 50%; }
    100% { background-position: 200% 50%; }
}

/* Category icon container */
.riwayat-cat-icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid;
    flex-shrink: 0;
    transition: all 0.4s var(--motion-snappy);
    box-shadow: inset 0 1px 2px rgba(255,255,255,0.4);
}

.riwayat-card:hover .riwayat-cat-icon {
    transform: scale(1.1) rotate(-8deg) translateY(-2px);
    box-shadow: 0 8px 16px -4px rgba(0,0,0,0.1);
}

/* Arrow button */
.riwayat-card-arrow {
    width: 2rem;
    height: 2rem;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.05);
}

.riwayat-card:hover .riwayat-card-arrow {
    background: rgba(139, 92, 246, 0.08);
    border-color: rgba(167, 139, 250, 0.3);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
}

/* Skeleton */
.riwayat-card-skeleton {
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 1.25rem;
    padding: 1.5rem;
    padding-top: 1.75rem;
}

/* ── Updated Card Glow ── */
.riwayat-card-updated {
    border-color: rgba(139, 92, 246, 0.35) !important;
    box-shadow:
        0 0 0 1px rgba(139, 92, 246, 0.15),
        0 8px 24px -6px rgba(139, 92, 246, 0.15),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.9) !important;
    animation: updateCardGlow 3s ease-in-out infinite !important;
}

@keyframes updateCardGlow {
    0%, 100% {
        box-shadow:
            0 0 0 1px rgba(139, 92, 246, 0.15),
            0 8px 24px -6px rgba(139, 92, 246, 0.15),
            inset 0 1px 0 0 rgba(255, 255, 255, 0.9);
    }
    50% {
        box-shadow:
            0 0 0 2px rgba(139, 92, 246, 0.25),
            0 8px 30px -4px rgba(139, 92, 246, 0.25),
            inset 0 1px 0 0 rgba(255, 255, 255, 0.9);
    }
}

/* ── Update Ribbon Badge ── */
.update-ribbon {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: white;
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    border-radius: 8px;
    margin-bottom: 8px;
    box-shadow: 0 2px 8px -2px rgba(124, 58, 237, 0.5);
    animation: ribbonPulse 2.5s ease-in-out infinite;
    text-transform: uppercase;
}

@keyframes ribbonPulse {
    0%, 100% { box-shadow: 0 2px 8px -2px rgba(124, 58, 237, 0.5); }
    50% { box-shadow: 0 2px 14px -1px rgba(124, 58, 237, 0.7); }
}

/* CTA Card removed – button is in hero section */

/* ══════════════════════════════════════════════════
   EMPTY STATE
   ══════════════════════════════════════════════════ */
.riwayat-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 1.75rem;
    position: relative;
    overflow: hidden;
    box-shadow:
        0 12px 40px -10px rgba(124, 58, 237, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.empty-illustration {
    position: relative;
    width: 8rem;
    height: 8rem;
    margin: 0 auto;
}

.empty-orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}

.empty-orb-1 {
    width: 5rem;
    height: 5rem;
    background: linear-gradient(135deg, rgba(167, 139, 250, 0.3), rgba(244, 114, 182, 0.2));
    top: 0;
    right: 0;
    animation: float-smooth 5s ease-in-out infinite;
}

.empty-orb-2 {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, rgba(96, 165, 250, 0.3), rgba(139, 92, 246, 0.2));
    bottom: 0.5rem;
    left: 0.5rem;
    animation: float-smooth 4s ease-in-out infinite reverse;
}

.empty-icon-wrap {
    position: absolute;
    bottom: 0.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 5.5rem;
    height: 5.5rem;
    border-radius: 1.5rem;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px -6px rgba(124, 58, 237, 0.12);
}

/* ══════════════════════════════════════════════════
   PAGINATION
   ══════════════════════════════════════════════════ */
.riwayat-page-btn {
    padding: 0.625rem 1rem;
    font-size: 0.75rem;
    font-weight: 800;
    border-radius: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.8);
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(12px);
    color: #64748b;
    min-width: 2.75rem;
    height: 2.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.riwayat-page-btn:hover:not(.disabled) {
    background: rgba(255, 255, 255, 0.9);
    color: #7c3aed;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px -2px rgba(124, 58, 237, 0.15);
}

.riwayat-page-btn.active {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
    border-color: #7c3aed;
    box-shadow: 0 8px 20px -6px rgba(124, 58, 237, 0.5);
    transform: translateY(-2px);
}

.riwayat-page-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: rgba(255, 255, 255, 0.3);
}

/* ══════════════════════════════════════════════════
   DETAIL CARD
   ══════════════════════════════════════════════════ */
.detail-card {
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(32px);
    -webkit-backdrop-filter: blur(32px);
    border: 1px solid rgba(255, 255, 255, 0.9);
    border-radius: 1.75rem;
    padding: 2rem;
    box-shadow:
        0 30px 60px -15px rgba(124, 58, 237, 0.12),
        0 10px 30px -5px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 0 rgba(255, 255, 255, 1);
    position: relative;
    overflow: hidden;
}

.detail-card::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(circle at 12% 10%, rgba(167, 139, 250, 0.1), transparent 45%),
        radial-gradient(circle at 90% 82%, rgba(244, 114, 182, 0.06), transparent 40%);
}

.detail-card > * {
    position: relative;
    z-index: 1;
}

/* ── Feedback Card ── */
.riwayat-feedback-card {
    background: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow:
        0 8px 24px -8px rgba(0, 0, 0, 0.04),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.8);
}

/* ══════════════════════════════════════════════════
   DETAIL MODAL TRANSITIONS
   ══════════════════════════════════════════════════ */
.detail-modal-enter-active {
    transition: opacity 0.35s cubic-bezier(0.25, 1, 0.5, 1);
}

.detail-modal-enter-active .detail-topbar,
.detail-modal-enter-active .detail-card {
    animation: detail-slide-up 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.detail-modal-leave-active {
    transition: opacity 0.25s ease;
}

.detail-modal-leave-active .detail-topbar,
.detail-modal-leave-active .detail-card {
    transition: transform 0.25s ease, opacity 0.25s ease;
}

.detail-modal-enter-from {
    opacity: 0;
}

.detail-modal-leave-to {
    opacity: 0;
}

.detail-modal-leave-to .detail-topbar,
.detail-modal-leave-to .detail-card {
    transform: translateY(20px) scale(0.97);
    opacity: 0;
}

@keyframes detail-slide-up {
    0% {
        opacity: 0;
        transform: translateY(30px) scale(0.96);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* ── Line Clamp ── */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ── Lightbox ── */
.lightbox-enter-active { transition: all 0.3s ease-out; }
.lightbox-leave-active { transition: all 0.2s ease-in; }
.lightbox-enter-from, .lightbox-leave-to { opacity: 0; }
@keyframes lightboxPop {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* ── Responsive ── */
@media (max-width: 640px) {
    .riwayat-hero {
        padding: 1.75rem;
        border-radius: 1.25rem;
    }

    .riwayat-filter-bar {
        gap: 0.35rem;
        padding: 0.35rem;
    }

    .riwayat-filter-tab {
        padding: 0.5rem 0.875rem;
        font-size: 0.65rem;
    }
}
</style>
