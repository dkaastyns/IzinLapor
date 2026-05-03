<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import SkeletonTable from '@/Components/SkeletonTable.vue';
import axios from 'axios';
import { resolveImageUrl, resolveComplaintImages } from '@/Utils/imageUrl';

const props = defineProps({
    stats: Object,
    recentComplaints: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const { t } = useI18n();

const isInitialLoad = ref(true);
onMounted(() => {
    setTimeout(() => { isInitialLoad.value = false; }, 600);
});

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

const categoryIcons = {
    'kelistrikan': '⚡',
    'sanitasi': '🚿',
    'mebel': '🪑',
    'kebersihan': '🧹',
    'infrastruktur': '🏗️',
    'keamanan': '🔒',
    'fasilitas elektrik': '⚡',
    'fasilitas air': '🚿',
};

// Status color mapping for card accent
const statusColorMap = {
    pending: { from: '#f59e0b', to: '#d97706', bg: 'rgba(245,158,11,0.08)', border: 'rgba(245,158,11,0.2)' },
    processing: { from: '#3b82f6', to: '#2563eb', bg: 'rgba(59,130,246,0.08)', border: 'rgba(59,130,246,0.2)' },
    resolved: { from: '#10b981', to: '#059669', bg: 'rgba(16,185,129,0.08)', border: 'rgba(16,185,129,0.2)' },
    rejected: { from: '#f43f5e', to: '#e11d48', bg: 'rgba(244,63,94,0.08)', border: 'rgba(244,63,94,0.2)' },
};

const getCategoryIcon = (complaint) => {
    const catName = (complaint.category?.parent?.name || complaint.category?.name || '').toLowerCase();
    for (const [key, icon] of Object.entries(categoryIcons)) {
        if (catName.includes(key)) return icon;
    }
    return '📋';
};

// Load More Logic
const allComplaints = computed(() => props.recentComplaints || []);
const displayedComplaints = computed(() => allComplaints.value.slice(0, 3));
const hasMore = computed(() => allComplaints.value.length > 3);

// Format Date
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
    showDetail.value = true;
    document.body.style.overflow = 'hidden';

    // Mark as read if this complaint has an unread notification
    if (!complaint.notified_at && complaint.status !== 'pending') {
        try {
            await axios.post(route('notifications.markSingleRead', complaint.id));
            complaint.notified_at = new Date().toISOString();
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

// Rating (in-detail modal)
const hoverRating = ref(0);
const ratingForm = ref(null);

const initRatingForm = (complaint) => {
    ratingForm.value = useForm({
        rating: 0,
        rating_comment: '',
    });
};

const setRating = (star) => {
    if (ratingForm.value) ratingForm.value.rating = star;
};

const getDisplayRating = () => {
    return hoverRating.value || (ratingForm.value?.rating ?? 0);
};

const submitRating = (complaint) => {
    if (!ratingForm.value || ratingForm.value.rating === 0) return;
    ratingForm.value.post(route('complaints.rating', complaint.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDetail();
        },
    });
};

const ratingLabel = (rating) => {
    const labels = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Baik', 'Sangat Baik'];
    return labels[rating] || '';
};

const ratingEmojis = ['', '😞', '😟', '😐', '😊', '😍'];

// Progress bar helpers
const estimationStatus = (complaint) => {
    if (!complaint.estimated_completion_date) return null;
    if (complaint.status === 'resolved' || complaint.status === 'rejected') return 'done';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const est = new Date(complaint.estimated_completion_date);
    est.setHours(0, 0, 0, 0);
    const diff = Math.round((est - today) / (1000 * 60 * 60 * 24));
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
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-xl font-bold text-primary-900">
        {{ t('dashboard') }}
      </h1>
    </template>

    <div class="max-w-6xl mx-auto space-y-8">
      <!-- Welcome Card Banner -->
      <div class="relative overflow-hidden rounded-[2rem] bg-[#533483] animate-fade-in-up border border-white/10 shadow-2xl group">
        <!-- Abstract Background Orbs -->
        <div class="absolute top-0 -left-1/4 w-[150%] h-[150%] bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#db2777]/40 via-[#7c3aed]/20 to-transparent mix-blend-screen opacity-70 blur-[80px] pointer-events-none group-hover:scale-105 transition-transform duration-1000" />
        <div class="absolute -bottom-1/2 right-0 w-full h-full bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#3b82f6]/30 via-[#8b5cf6]/20 to-transparent mix-blend-screen opacity-60 blur-[60px] pointer-events-none group-hover:-translate-x-4 transition-transform duration-1000" />
                
        <!-- Noise Overlay -->
        <div
          class="absolute inset-0 opacity-[0.03] mix-blend-overlay pointer-events-none"
          style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.85%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"
        />
                
        <!-- Glass Reflection Layers -->
        <div class="absolute inset-0 bg-gradient-to-tr from-white/5 via-transparent to-white/20 pointer-events-none" />
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent" />
        <div class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent" />

        <div class="p-8 sm:p-10 relative z-10 flex flex-col md:flex-row items-center justify-between gap-8 h-full">
          <div class="w-full md:max-w-[65%]">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-[10px] font-black uppercase tracking-[0.15em] mb-4 shadow-sm">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
              Selamat Datang Kembali
            </div>
            <h2 class="text-3xl sm:text-4xl font-black text-white mb-4 tracking-tight drop-shadow-md">
              Halo, {{ user.name }}!
            </h2>
            <p class="text-white/80 text-sm sm:text-base font-medium mb-8 max-w-xl leading-relaxed drop-shadow-sm">
              Ada kendala fasilitas sekolah hari ini? Laporkan sekarang untuk penanganan cepat dan efektif.
            </p>
            <Link
              :href="route('complaints.create')"
              class="inline-flex items-center justify-center gap-2 bg-white text-[#7c3aed] text-sm font-black px-7 py-3.5 rounded-xl hover:bg-[#f8f5ff] transition-all duration-300 hover:-translate-y-1 shadow-[0_8px_20px_-6px_rgba(255,255,255,0.4)]"
            >
              <svg
                class="w-5 h-5 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2.5"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 4v16m8-8H4"
              /></svg>
              Buat Laporan Baru
            </Link>
          </div>

          <!-- Modern Glass Dashboard Illustration -->
          <div class="hidden md:flex relative z-10 w-64 h-44 items-center justify-center group/ill">
            <div class="w-full h-full relative animate-float">
              <!-- Back shadow glow -->
              <div class="absolute inset-0 bg-white/20 blur-3xl opacity-0 group-hover:opacity-40 group-hover:scale-125 transition-all duration-1000 rounded-full pointer-events-none" />

              <!-- Back layer -->
              <div class="absolute inset-0 bg-white/5 rounded-2xl backdrop-blur-sm border border-white/10 shadow-2xl transform rotate-[8deg] scale-90 group-hover:rotate-[20deg] group-hover:scale-95 group-hover:-translate-y-6 group-hover:translate-x-4 transition-all duration-[800ms] ease-[cubic-bezier(0.23,1,0.32,1)] origin-bottom" />
                            
              <!-- Middle Layer (extra depth) -->
              <div class="absolute inset-0 bg-gradient-to-tr from-white/10 to-white/5 rounded-2xl backdrop-blur-md border border-white/20 shadow-xl transform rotate-3 scale-95 group-hover:rotate-[10deg] group-hover:scale-100 group-hover:-translate-y-3 group-hover:translate-x-2 transition-all duration-[800ms] ease-[cubic-bezier(0.23,1,0.32,1)] delay-[50ms] origin-bottom translate-x-2 translate-y-1" />

              <!-- Front layer -->
              <div class="absolute inset-0 bg-gradient-to-br from-white/30 to-white/5 rounded-2xl backdrop-blur-xl border border-white/40 shadow-[0_20px_50px_rgba(0,0,0,0.3)] transform -rotate-3 group-hover:rotate-0 group-hover:scale-105 group-hover:-translate-y-1 group-hover:-translate-x-1 transition-all duration-[800ms] ease-[cubic-bezier(0.23,1,0.32,1)] delay-[100ms] overflow-hidden flex flex-col front-glass-card group-hover:shadow-[0_30px_60px_rgba(0,0,0,0.5)]">
                <div class="h-8 bg-gradient-to-b from-white/20 to-transparent border-b border-white/20 flex items-center px-4 gap-2 relative z-10">
                  <div class="w-2.5 h-2.5 rounded-full bg-rose-400 drop-shadow-md shadow-[0_0_8px_rgba(251,113,133,0.8)]" />
                  <div class="w-2.5 h-2.5 rounded-full bg-amber-400 drop-shadow-md shadow-[0_0_8px_rgba(251,191,36,0.8)]" />
                  <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 drop-shadow-md shadow-[0_0_8px_rgba(52,211,153,0.8)]" />
                </div>
                <div class="p-4 flex-1 flex flex-col gap-3 relative z-10">
                  <div class="flex gap-3 items-center">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-white/50 to-white/10 animate-pulse border border-white/30 shadow-inner flex items-center justify-center">
                      <div class="w-4 h-4 rounded-full bg-white/50" />
                    </div>
                    <div class="flex-1 flex flex-col justify-center gap-2">
                      <div class="w-3/4 h-2.5 rounded bg-white/50" />
                      <div class="w-1/2 h-2.5 rounded bg-white/30" />
                    </div>
                  </div>
                  <div class="w-full h-16 rounded-xl bg-gradient-to-t from-white/20 to-white/5 flex items-end p-2.5 gap-2.5 mt-auto border border-white/20 shadow-inner">
                    <div class="w-1/3 h-[60%] bg-gradient-to-t from-white/40 to-white/10 rounded-sm shadow-[0_0_15px_rgba(255,255,255,0.3)] transform origin-bottom hover:scale-y-110 transition-transform cursor-pointer" />
                    <div class="w-1/3 h-[40%] bg-gradient-to-t from-white/30 to-white/5 rounded-sm transform origin-bottom hover:scale-y-110 transition-transform cursor-pointer" />
                    <div class="w-1/3 h-[80%] bg-gradient-to-t from-white/50 to-white/20 rounded-sm shadow-[0_0_15px_rgba(255,255,255,0.3)] transform origin-bottom hover:scale-y-110 transition-transform cursor-pointer relative overflow-hidden">
                      <div class="absolute inset-0 bg-gradient-to-b from-white/60 to-transparent" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total -->
        <div class="bg-white rounded-[1.5rem] p-6 shadow-[0_4px_24px_-8px_rgba(0,0,0,0.08)] hover:shadow-[0_8px_32px_-8px_rgba(0,0,0,0.12)] transition-all animate-fade-in-up stagger-1 relative overflow-hidden flex flex-col justify-between min-h-[170px] group border border-gray-100 ds-stat-card cursor-pointer">
          <!-- Layered Soft Blobs -->
          <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-violet-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none" />
          <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-violet-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none" />
                    
          <div class="flex items-start justify-between relative z-10 mb-6">
            <div class="w-11 h-11 rounded-[1.1rem] bg-violet-500 flex items-center justify-center shadow-lg shadow-violet-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
              <svg
                class="w-5 h-5 text-white"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              /></svg>
            </div>
            <span class="px-2.5 py-1 bg-white text-violet-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-violet-50 transition-colors duration-300 group-hover:bg-violet-50 text-center">TOTAL</span>
          </div>
                    
          <div class="relative z-10 mt-auto">
            <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-violet-500 transition-colors duration-300">
              LAPORAN TERKIRIM
            </p>
            <p class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-violet-900 transition-colors duration-300">
              {{ stats?.total || 0 }}
            </p>
          </div>
        </div>

        <!-- Pending -->
        <div class="bg-white rounded-[1.5rem] p-6 shadow-[0_4px_24px_-8px_rgba(0,0,0,0.08)] hover:shadow-[0_8px_32px_-8px_rgba(0,0,0,0.12)] transition-all animate-fade-in-up stagger-2 relative overflow-hidden flex flex-col justify-between min-h-[170px] group border border-gray-100 ds-stat-card cursor-pointer">
          <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-amber-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none" />
          <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-amber-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none" />
                    
          <div class="flex items-start justify-between relative z-10 mb-6">
            <div class="w-11 h-11 rounded-[1.1rem] bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
              <svg
                class="w-5 h-5 text-white"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              /></svg>
            </div>
            <span class="px-2.5 py-1 bg-white text-amber-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-amber-50 transition-colors duration-300 group-hover:bg-amber-50 text-center">MENUNGGU</span>
          </div>
                    
          <div class="relative z-10 mt-auto">
            <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-amber-500 transition-colors duration-300">
              BELUM DIVERIFIKASI
            </p>
            <p class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-amber-900 transition-colors duration-300">
              {{ stats?.pending || 0 }}
            </p>
          </div>
        </div>

        <!-- Processing -->
        <div class="bg-white rounded-[1.5rem] p-6 shadow-[0_4px_24px_-8px_rgba(0,0,0,0.08)] hover:shadow-[0_8px_32px_-8px_rgba(0,0,0,0.12)] transition-all animate-fade-in-up stagger-3 relative overflow-hidden flex flex-col justify-between min-h-[170px] group border border-gray-100 ds-stat-card cursor-pointer">
          <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-blue-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none" />
          <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-blue-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none" />
                    
          <div class="flex items-start justify-between relative z-10 mb-6">
            <div class="w-11 h-11 rounded-[1.1rem] bg-blue-500 flex items-center justify-center shadow-lg shadow-blue-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
              <svg
                class="w-5 h-5 text-white"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
              /><circle
                cx="12"
                cy="12"
                r="3"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
              /></svg>
            </div>
            <span class="px-2.5 py-1 bg-white text-blue-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-blue-50 transition-colors duration-300 group-hover:bg-blue-50 text-center">DIPROSES</span>
          </div>
                    
          <div class="relative z-10 mt-auto">
            <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-blue-500 transition-colors duration-300">
              DALAM PERBAIKAN
            </p>
            <p class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-blue-900 transition-colors duration-300">
              {{ stats?.processing || 0 }}
            </p>
          </div>
        </div>

        <!-- Resolved -->
        <div class="bg-white rounded-[1.5rem] p-6 shadow-[0_4px_24px_-8px_rgba(0,0,0,0.08)] hover:shadow-[0_8px_32px_-8px_rgba(0,0,0,0.12)] transition-all animate-fade-in-up stagger-4 relative overflow-hidden flex flex-col justify-between min-h-[170px] group border border-gray-100 ds-stat-card cursor-pointer">
          <div class="absolute top-0 right-0 bottom-0 w-[55%] bg-emerald-50/80 rounded-l-[5rem] transition-transform duration-700 group-hover:scale-110 origin-right pointer-events-none" />
          <div class="absolute top-0 -right-4 bottom-0 w-[55%] bg-emerald-100/40 rounded-l-[5rem] transition-transform duration-700 delay-75 group-hover:scale-125 origin-right pointer-events-none" />
                    
          <div class="flex items-start justify-between relative z-10 mb-6">
            <div class="w-11 h-11 rounded-[1.1rem] bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-6">
              <svg
                class="w-5 h-5 text-white"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              /></svg>
            </div>
            <span class="px-2.5 py-1 bg-white text-emerald-600 rounded-full text-[9px] font-black tracking-[0.08em] uppercase shadow-sm border border-emerald-50 transition-colors duration-300 group-hover:bg-emerald-50 text-center">SELESAI</span>
          </div>
                    
          <div class="relative z-10 mt-auto">
            <p class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest mb-1 group-hover:text-emerald-500 transition-colors duration-300">
              TELAH DISELESAIKAN
            </p>
            <p class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight leading-none group-hover:text-emerald-900 transition-colors duration-300">
              {{ stats?.resolved || 0 }}
            </p>
          </div>
        </div>
      </div>

      <!-- RIWAYAT PENGADUAN SAYA -->
      <div class="animate-fade-in-up stagger-5">
        <!-- Section Header Bar -->
        <div class="riwayat-section-bar mb-6">
          <div class="flex items-center gap-3">
            <div class="relative w-11 h-11 group cursor-default flex-shrink-0">
              <div class="absolute top-0 right-0 w-8 h-8 rounded-xl bg-primary-400/30 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
              <div class="absolute bottom-0 left-0 w-9 h-9 rounded-xl bg-primary-500/20 backdrop-blur-md border border-primary-300/50 flex items-center justify-center shadow-lg transition-transform duration-300 group-hover:-translate-y-1">
                <svg
                  class="w-5 h-5 text-primary-600 drop-shadow-sm"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
              </div>
            </div>
            <div>
              <h2 class="text-base font-black text-gray-900 tracking-tight leading-tight">
                Riwayat Laporan
              </h2>
              <p class="text-[11px] text-gray-400 font-medium">
                {{ allComplaints.length }} laporan ditemukan
              </p>
            </div>
          </div>
          <Link
            v-if="allComplaints.length > 0"
            :href="route('complaints.index')"
            class="riwayat-section-link"
          >
            Lihat Semua
            <svg
              class="w-3.5 h-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </Link>
        </div>

        <!-- Loading Skeleton -->
        <div
          v-if="isInitialLoad"
          class="grid grid-cols-1 lg:grid-cols-5 gap-5"
        >
          <div class="lg:col-span-3 bg-white p-5 rounded-2xl border border-gray-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] h-64 animate-pulse" />
          <div class="lg:col-span-2 space-y-5 flex flex-col h-full">
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] flex-1 animate-pulse min-h-[120px]" />
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] flex-1 animate-pulse min-h-[120px]" />
          </div>
        </div>

        <!-- Bento Grid Layout for Complaints -->
        <div
          v-else-if="allComplaints.length > 0"
          class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-stretch"
        >
          <!-- Left: Featured Card (1st item) -->
          <div
            v-if="displayedComplaints[0]" 
            class="lg:col-span-3 bg-white rounded-[1.25rem] overflow-hidden shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] border border-gray-100 hover:shadow-lg transition-all cursor-pointer group flex flex-col md:flex-row min-h-[260px] relative"
            @click="openDetail(displayedComplaints[0])"
          >
            <div
              class="absolute top-0 left-0 right-0 h-[3px] z-20"
              :class="displayedComplaints[0].status === 'resolved' ? 'bg-gradient-to-r from-emerald-400 to-emerald-500' : (displayedComplaints[0].status === 'pending' ? 'bg-gradient-to-r from-orange-400 to-orange-500' : 'bg-gradient-to-r from-blue-400 to-blue-500')"
            />
            <!-- Left image area -->
            <div class="w-full sm:w-[45%] h-56 sm:h-auto bg-gray-100 relative overflow-hidden flex-shrink-0">
              <!-- Image if exists -->
              <template v-if="displayedComplaints[0].image_paths?.length > 0 || displayedComplaints[0].image_path">
                <img
                  loading="lazy"
                  alt="gambar laporan"
                  :src="resolveImageUrl(displayedComplaints[0].image_paths?.[0] || displayedComplaints[0].image_path)"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                >
              </template>
              <!-- Placeholder if no image -->
              <div
                v-else
                class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-300"
              >
                <svg
                  class="w-16 h-16 opacity-50"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                ><path d="M4 4h16v16H4V4zm2 2v12h12V6H6zm2 3h8v2H8V9zm0 4h5v2H8v-2z" /></svg>
              </div>
              <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent sm:hidden" />
            </div>
                        
            <!-- Right content area -->
            <div class="p-6 flex flex-col justify-between flex-1 bg-white relative">
              <!-- Update Ribbon if needed -->
              <div
                v-if="!displayedComplaints[0].notified_at && displayedComplaints[0].status !== 'pending'"
                class="absolute -top-3 right-4 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white text-[9px] font-bold px-2 py-1 rounded shadow-md uppercase tracking-wider flex items-center gap-1 z-10 hidden sm:flex"
              >
                <svg
                  class="w-2.5 h-2.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                ><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M13 10V3L4 14h7v7l9-11h-7z"
                /></svg>
                Update
              </div>

              <div>
                <div class="flex items-center gap-2 mb-3.5 flex-wrap">
                  <span class="px-2.5 py-1 bg-indigo-50/80 text-indigo-700 rounded text-[9.5px] font-extrabold uppercase tracking-widest border border-indigo-100">
                    {{ displayedComplaints[0].category?.parent?.name || displayedComplaints[0].category?.name || 'FASILITAS' }}
                  </span>
                  <span :class="'px-2.5 py-1 rounded text-[9.5px] font-extrabold uppercase tracking-widest flex items-center gap-1 border ' + (displayedComplaints[0].status === 'resolved' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : (displayedComplaints[0].status === 'pending' ? 'bg-orange-50 text-orange-600 border-orange-100' : 'bg-blue-50 text-blue-600 border-blue-100'))">
                    <span class="w-1.5 h-1.5 rounded-full bg-current" />
                    {{ statusLabel[displayedComplaints[0].status] }}
                  </span>
                  <span
                    v-if="!displayedComplaints[0].notified_at && displayedComplaints[0].status !== 'pending'"
                    class="sm:hidden px-2.5 py-1 bg-indigo-600 text-white rounded text-[9.5px] font-bold uppercase tracking-widest shadow-sm"
                  >Update</span>
                </div>
                <h3 class="font-extrabold text-gray-900 text-base sm:text-lg mb-2.5 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-snug">
                  {{ displayedComplaints[0].title }}
                </h3>
                <p class="text-xs sm:text-[13px] text-gray-500 font-medium leading-relaxed line-clamp-3 md:line-clamp-4">
                  {{ displayedComplaints[0].description }}
                </p>
              </div>
                            
              <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mt-6 pt-5 border-t border-gray-100">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center flex-shrink-0">
                    <svg
                      class="w-4 h-4 text-gray-400"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    ><path
                      fill-rule="evenodd"
                      d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                      clip-rule="evenodd"
                    /></svg>
                  </div>
                  <span class="text-[11px] font-bold text-gray-600 leading-tight">
                    {{ displayedComplaints[0].admin_response ? 'Admin / Teknisi' : 'Belum Ditangani' }}<br>
                    <span class="font-medium text-gray-400 font-normal">Penanggung Jawab</span>
                  </span>
                </div>
                <div class="text-right sm:text-left">
                  <span class="block text-[10px] text-gray-400 font-medium mb-0.5">Dilaporkan</span>
                  <span class="text-[11.5px] text-gray-600 font-bold whitespace-nowrap">{{ formatDate(displayedComplaints[0].created_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Two smaller cards -->
          <div class="lg:col-span-2 flex flex-col gap-5 h-full">
            <template
              v-for="index in [1, 2]"
              :key="index"
            >
              <div
                v-if="displayedComplaints[index]" 
                class="flex-1 min-h-[130px] bg-white rounded-[1.25rem] p-5 lg:p-6 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] border border-gray-100 hover:shadow-lg hover:border-indigo-100 transition-all cursor-pointer group flex flex-col relative overflow-hidden"
                @click="openDetail(displayedComplaints[index])"
              >
                <div
                  class="absolute top-0 left-0 right-0 h-[3px] z-20"
                  :class="displayedComplaints[index].status === 'resolved' ? 'bg-gradient-to-r from-emerald-400 to-emerald-500' : (displayedComplaints[index].status === 'pending' ? 'bg-gradient-to-r from-orange-400 to-orange-500' : 'bg-gradient-to-r from-blue-400 to-blue-500')"
                />
                <div
                  v-if="!displayedComplaints[index].notified_at && displayedComplaints[index].status !== 'pending'"
                  class="absolute top-0 right-0 w-2 h-full bg-indigo-500 z-30"
                />
                                
                <div class="flex items-center gap-2 mb-3.5 flex-wrap pr-3">
                  <span class="px-2.5 py-1 bg-indigo-50/80 text-indigo-700 rounded text-[9px] font-extrabold uppercase tracking-widest border border-indigo-100">
                    {{ displayedComplaints[index].category?.parent?.name || displayedComplaints[index].category?.name || 'FASILITAS' }}
                  </span>
                  <span :class="'px-2.5 py-1 rounded text-[9px] font-extrabold uppercase tracking-widest flex items-center gap-1 border ' + (displayedComplaints[index].status === 'resolved' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : (displayedComplaints[index].status === 'pending' ? 'bg-orange-50 text-orange-600 border-orange-100' : 'bg-blue-50 text-blue-600 border-blue-100'))">
                    {{ statusLabel[displayedComplaints[index].status] }}
                  </span>
                </div>
                <h3 class="font-bold text-gray-900 text-sm mb-2 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-tight pr-3">
                  {{ displayedComplaints[index].title }}
                </h3>
                <p class="text-xs text-gray-500 font-medium leading-relaxed line-clamp-2 mb-4 flex-1 pr-3">
                  {{ displayedComplaints[index].description }}
                </p>
                <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between">
                  <span class="text-[10px] text-gray-400 font-medium">Dikirim: {{ formatDate(displayedComplaints[index].created_at) }}</span>
                  <div
                    v-if="displayedComplaints[index].status === 'resolved'"
                    class="text-[9px] text-emerald-600 font-bold"
                  >
                    Selesai pada: {{ formatDate(displayedComplaints[index].updated_at) }}
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else
          class="text-center py-20 px-6 relative group border border-transparent animate-fade-in-up mt-5"
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
            <h3 class="text-2xl font-black text-gray-900 mb-3 tracking-tight group-hover:text-primary-700 transition-colors">
              {{ t('no_complaint_yet') }}
            </h3>
            <p class="text-gray-500 font-medium text-sm max-w-sm mx-auto leading-relaxed group-hover:text-gray-600 transition-colors">
              Belum ada laporan yang dikirimkan. Buat laporan pertamamu untuk fasilitas sekolah dan bantu kami menjadi lebih baik.
            </p>
          </div>
        </div>

        <!-- Count Label -->
        <div
          v-if="allComplaints.length > 0"
          class="text-center mt-6"
        >
          <p class="text-xs font-bold text-gray-400 uppercase tracking-[0.2em]">
            Menampilkan {{ Math.min(2, allComplaints.length) }} dari {{ allComplaints.length }} Laporan
          </p>
        </div>
      </div>
    </div>

    <!-- DETAIL MODAL (Fullscreen Overlay) -->
    <Teleport to="body">
      <Transition name="detail-modal">
        <div
          v-if="showDetail && selectedComplaint"
          class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto"
          @click.self="closeDetail"
        >
          <!-- Blurred Overlay -->
          <div
            class="fixed inset-0 bg-gradient-to-br from-purple-100/60 via-pink-50/40 to-white/60 backdrop-blur-sm"
            @click="closeDetail"
          />

          <!-- Modal Content -->
          <div
            class="relative z-10 w-full max-w-5xl mx-4 my-8 bg-[#fef7fe] rounded-[2rem] shadow-2xl overflow-hidden ring-1 ring-black/5"
            @click.stop
          >
            <div class="p-6 md:p-10 max-h-[85vh] overflow-y-auto custom-scrollbar">
              <!-- Breadcrumb / Back Navigation -->
              <div class="mb-8 flex items-center gap-2">
                <button
                  class="w-10 h-10 flex items-center justify-center text-[#685688] hover:bg-[#eadef7] rounded-full transition-colors focus:outline-none"
                  @click="closeDetail"
                >
                  <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  ><path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                    d="M15 19l-7-7 7-7"
                  /></svg>
                </button>
                <span
                  class="text-[#625d67] text-sm font-semibold cursor-pointer hover:text-[#35313a] transition-colors"
                  @click="closeDetail"
                >Kembali ke Laporan</span>
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
                  <h1
                    class="text-3xl md:text-4xl font-extrabold text-[#35313a] tracking-tight leading-tight"
                    style="font-family: 'Manrope', sans-serif;"
                  >
                    <template v-if="selectedComplaint.category?.parent">
                      {{ selectedComplaint.category.parent.name }} >
                    </template>{{ selectedComplaint.title || selectedComplaint.category?.name }}
                  </h1>
                  <p class="text-[#625d67] mt-3 flex items-center gap-2 font-medium text-sm">
                    <svg
                      class="w-5 h-5 shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    ><path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    /></svg>
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
                    <h2
                      class="text-lg font-bold text-[#35313a] mb-4"
                      style="font-family: 'Manrope', sans-serif;"
                    >
                      Deskripsi Masalah
                    </h2>
                    <div class="bg-[#f3ebf5] p-5 rounded-xl text-[#35313a] leading-relaxed italic border-l-4 border-[#685688] font-medium text-sm shadow-inner">
                      "{{ selectedComplaint.description }}"
                    </div>
                  </section>

                  <!-- Attached Photo -->
                  <section
                    v-if="selectedComplaint.image_paths?.length > 0 || selectedComplaint.image_path"
                    class="bg-white p-7 rounded-[1.5rem] border border-[#ede6f0] shadow-sm"
                  >
                    <h2
                      class="text-lg font-bold text-[#35313a] mb-4"
                      style="font-family: 'Manrope', sans-serif;"
                    >
                      Foto Lampiran
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <template v-if="selectedComplaint.image_paths?.length > 0">
                        <div
                          v-for="(imgPath, idx) in selectedComplaint.image_paths"
                          :key="idx"
                          class="overflow-hidden rounded-xl bg-[#f3ebf5] aspect-video relative group cursor-zoom-in shadow-sm hover:shadow-md transition-shadow"
                          @click.stop="openLightbox(selectedComplaint.image_paths.map(p => resolveImageUrl(p)), idx)"
                        >
                          <img
                            loading="lazy"
                            alt="gambar laporan"
                            :src="resolveImageUrl(imgPath)"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                          >
                          <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition-colors duration-500" />
                        </div>
                      </template>
                      <template v-else-if="selectedComplaint.image_path">
                        <div
                          class="overflow-hidden rounded-xl bg-[#f3ebf5] aspect-video relative group cursor-zoom-in shadow-sm hover:shadow-md transition-shadow"
                          @click.stop="openLightbox([resolveImageUrl(selectedComplaint.image_path)], 0)"
                        >
                          <img
                            loading="lazy"
                            alt="gambar laporan"
                            :src="resolveImageUrl(selectedComplaint.image_path)"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                          >
                          <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition-colors duration-500" />
                        </div>
                      </template>
                    </div>
                  </section>

                  <!-- Admin Response -->
                  <section
                    v-if="selectedComplaint.admin_response"
                    class="bg-[#f3ebf5] p-7 rounded-[1.5rem] border border-[#dbc5fe] shadow-sm relative overflow-hidden"
                  >
                    <div class="absolute top-0 right-0 w-32 h-32 bg-[#dbc5fe] rounded-full blur-3xl opacity-30 -mr-10 -mt-10" />
                    <div class="relative z-10">
                      <div class="flex items-center gap-3 mb-6">
                        <div class="bg-[#dbc5fe] p-2.5 rounded-xl text-[#685688] shadow-sm border border-white/50">
                          <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          ><path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                          /></svg>
                        </div>
                        <h2
                          class="text-lg font-bold text-[#35313a]"
                          style="font-family: 'Manrope', sans-serif;"
                        >
                          Respon Admin
                        </h2>
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
                  <!-- Location Card (NO MAP/IMAGE) -->
                  <section class="bg-white p-7 rounded-[1.5rem] shadow-sm border border-[#ede6f0]">
                    <h2 class="text-[11px] font-extrabold text-[#625d67] uppercase tracking-[0.2em] mb-5">
                      Lokasi Kejadian
                    </h2>
                    <div class="flex items-start gap-4">
                      <div class="p-2.5 bg-[#f3ebf5] rounded-xl text-[#685688] shrink-0 mt-0.5 shadow-sm border border-white">
                        <svg
                          class="w-5 h-5"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2.5"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                        /><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2.5"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                        /></svg>
                      </div>
                      <div>
                        <p class="font-bold text-[#35313a] text-base leading-snug">
                          {{ selectedComplaint.location }}
                        </p>
                        <p
                          v-if="selectedComplaint.location_detail"
                          class="text-xs text-[#7e7983] font-medium mt-1.5 leading-relaxed"
                        >
                          {{ selectedComplaint.location_detail }}
                        </p>
                      </div>
                    </div>
                  </section>

                  <!-- Feedback & Rating Card -->
                  <section
                    v-if="selectedComplaint.status === 'resolved'"
                    class="bg-white p-7 rounded-[1.5rem] shadow-lg shadow-[#dbc5fe]/20 border border-[#dbc5fe]/40"
                  >
                    <h2 class="text-[11px] font-extrabold text-[#625d67] uppercase tracking-[0.2em] mb-6">
                      Penilaian Anda
                    </h2>
                                        
                    <!-- Already rated -->
                    <div v-if="selectedComplaint.rating">
                      <div class="flex justify-center gap-1.5 mb-6">
                        <svg
                          v-for="star in 5"
                          :key="star"
                          class="w-8 h-8 drop-shadow-sm transition-all"
                          :class="star <= selectedComplaint.rating ? 'text-[#7d516d]' : 'text-[#ede6f0]'"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        ><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                      </div>
                      <div
                        v-if="selectedComplaint.rating_comment"
                        class="space-y-2 mb-6"
                      >
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
                    <div
                      v-else
                      @click.stop
                    >
                      <template v-if="!ratingForm">
                        <p class="text-[13px] text-center text-[#564e63] font-medium mb-6 leading-relaxed">
                          Bagaimana penilaian Anda terhadap penanganan aduan ini?
                        </p>
                        <button
                          class="w-full bg-[#f3ebf5] text-[#685688] font-bold py-3.5 rounded-xl hover:bg-[#685688] hover:text-white transition-all shadow-sm"
                          @click="initRatingForm(selectedComplaint)"
                        >
                          Berikan Penilaian
                        </button>
                      </template>
                      <template v-else>
                        <div class="flex justify-center gap-1.5 mb-6">
                          <button
                            v-for="star in 5"
                            :key="star"
                            type="button"
                            class="focus:outline-none transition-transform hover:scale-110 active:scale-95"
                            @mouseenter="hoverRating = star"
                            @mouseleave="hoverRating = 0"
                            @click="setRating(star)"
                          >
                            <svg
                              class="w-8 h-8 drop-shadow-sm transition-colors duration-200"
                              :class="star <= getDisplayRating() ? 'text-[#7d516d]' : 'text-[#ede6f0]'"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            ><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                          </button>
                        </div>
                        <textarea
                          v-model="ratingForm.rating_comment"
                          class="w-full text-sm border border-transparent bg-[#f8f1fa] text-[#35313a] rounded-2xl p-4 focus:ring-2 focus:ring-[#685688]/30 focus:border-[#685688] resize-none outline-none font-medium placeholder-[#b6afbb] shadow-inner mb-4"
                          rows="3"
                          placeholder="Ceritakan pengalaman Anda... (opsional)"
                        />
                        <button
                          type="button"
                          :disabled="ratingForm.rating === 0 || ratingForm.processing"
                          class="w-full bg-gradient-to-br from-[#685688] to-[#5c4a7b] text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition-all disabled:opacity-50 shadow-md"
                          @click="submitRating(selectedComplaint)"
                        >
                          <span v-if="!ratingForm.processing">Kirim Feedback</span>
                          <span
                            v-else
                            class="animate-pulse"
                          >Mengirim...</span>
                        </button>
                      </template>
                    </div>
                  </section>

                  <!-- Report Metadata -->
                  <section class="p-4 px-2">
                    <div class="space-y-3.5">
                      <div class="flex justify-between items-center text-[13px]">
                        <span class="text-[#625d67] font-medium">Laporan dibuat</span>
                        <span class="font-bold text-[#35313a]">{{ formatDate(selectedComplaint.created_at) }}</span>
                      </div>
                      <div class="flex justify-between items-center text-[13px]">
                        <span class="text-[#625d67] font-medium">Terakhir diperbarui</span>
                        <span class="font-bold text-[#35313a]">{{ formatDate(selectedComplaint.updated_at) }}</span>
                      </div>
                      <div
                        v-if="selectedComplaint.estimated_completion_date"
                        class="flex justify-between items-center text-[13px]"
                      >
                        <span class="text-[#625d67] font-medium">Estimasi Selesai</span>
                        <span class="text-[#685688] font-bold px-2.5 py-0.5 bg-[#dbc5fe]/30 rounded-md">{{ formatEstDate(selectedComplaint.estimated_completion_date) }}</span>
                      </div>

                      <div
                        v-if="selectedComplaint.progress > 0 || selectedComplaint.status === 'processing'"
                        class="pt-4 mt-4 border-t border-gray-100"
                      >
                        <div class="flex justify-between items-center mb-2">
                          <span class="text-xs font-bold text-[#625d67] uppercase tracking-wider">Progress Penanganan</span>
                          <span class="text-xs font-black text-[#685688]">{{ selectedComplaint.progress ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-[#f3ebf5] rounded-full h-2.5 overflow-hidden shadow-inner border border-gray-200/50">
                          <div
                            class="h-full rounded-full transition-all duration-1000 ease-out bg-gradient-to-r from-[#685688] to-[#7d516d] relative"
                            :style="`width: ${selectedComplaint.progress ?? 0}%`"
                          >
                            <div
                              v-if="selectedComplaint.status === 'processing' && selectedComplaint.progress > 0 && selectedComplaint.progress < 100"
                              class="absolute inset-0 bg-[image:linear-gradient(45deg,rgba(255,255,255,0.2)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.2)_50%,rgba(255,255,255,0.2)_75%,transparent_75%,transparent)] bg-[length:1rem_1rem] animate-[progress-stripes_1s_linear_infinite]"
                            />
                          </div>
                        </div>
                      </div>
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
          <button
            class="absolute top-6 right-6 w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold transition-all duration-300 hover:scale-105"
            @click="closeLightbox"
          >
            ✕
          </button>

          <div
            v-if="lightboxImages.length > 1"
            class="absolute top-8 left-1/2 -translate-x-1/2 font-bold text-gray-400 tracking-widest text-xs uppercase"
          >
            {{ lightboxIndex + 1 }} / {{ lightboxImages.length }}
          </div>

          <button
            v-if="lightboxImages.length > 1"
            class="absolute left-6 w-14 h-14 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-2xl transition-all duration-300 hover:scale-105"
            @click="navLightbox(-1)"
          >
            ‹
          </button>

          <img
            loading="lazy"
            :src="lightboxImages[lightboxIndex]"
            :alt="'Foto ' + (lightboxIndex + 1)"
            class="max-w-[85vw] max-h-[80vh] object-contain rounded-2xl shadow-xl select-none bg-white border border-gray-100 p-2"
            style="animation: lightboxPop 0.35s cubic-bezier(0.22, 1, 0.36, 1);"
          >

          <button
            v-if="lightboxImages.length > 1"
            class="absolute right-6 w-14 h-14 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-2xl transition-all duration-300 hover:scale-105"
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
.front-glass-card::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(to right, transparent, rgba(255,255,255,0.4), transparent);
    transform: rotate(30deg) translateX(-150%);
    transition: transform 0s;
    pointer-events: none;
    z-index: 50;
}

.group:hover .front-glass-card::after {
    transform: rotate(30deg) translateX(150%);
    transition: transform 1.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ── Riwayat Card ── */
.riwayat-card {
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 1.25rem;
    padding: 1.5rem;
    padding-top: 1.75rem;
    position: relative;
    overflow: hidden;
    box-shadow:
        0 8px 32px -8px rgba(124, 58, 237, 0.06),
        inset 0 1px 0 0 rgba(255, 255, 255, 0.9);
    transition:
        transform 0.4s cubic-bezier(0.25, 1, 0.5, 1),
        box-shadow 0.4s cubic-bezier(0.25, 1, 0.5, 1),
        border-color 0.15s ease;
    animation: fadeInUp 0.45s cubic-bezier(0.4, 0, 0.2, 1) both;
}

.riwayat-card::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        radial-gradient(circle at 10% 15%, rgba(167, 139, 250, 0.07), transparent 40%),
        radial-gradient(circle at 90% 85%, rgba(244, 114, 182, 0.05), transparent 35%);
}

.riwayat-card > * {
    position: relative;
    z-index: 1;
}

.riwayat-card:hover {
    transform: translateY(-6px) scale(1.01);
    box-shadow:
        0 24px 48px -12px rgba(124, 58, 237, 0.14),
        0 8px 20px -4px rgba(124, 58, 237, 0.08),
        inset 0 1px 0 0 rgba(255, 255, 255, 1);
    border-color: rgba(167, 139, 250, 0.4);
}

/* Status accent bar at top */
.riwayat-card-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: 3px 3px 0 0;
    z-index: 2;
}

/* Category icon container */
.riwayat-cat-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid;
    flex-shrink: 0;
    transition: transform 0.3s ease;
}

.riwayat-card:hover .riwayat-cat-icon {
    transform: scale(1.05) rotate(-3deg);
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

/* ── Section Header Bar ── */
.riwayat-section-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 1.25rem;
    box-shadow:
        0 4px 20px -4px rgba(124, 58, 237, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.riwayat-section-link {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: rgba(139, 92, 246, 0.06);
    color: #7c3aed;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-radius: 0.75rem;
    border: 1px solid rgba(139, 92, 246, 0.1);
    transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

.riwayat-section-link:hover {
    background: rgba(139, 92, 246, 0.12);
    border-color: rgba(139, 92, 246, 0.2);
    transform: translateX(2px);
}

/* ── Card Skeleton ── */
.riwayat-card-skeleton {
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 1.25rem;
    padding: 1.5rem;
    padding-top: 1.75rem;
}

/* ── Empty State ── */
.riwayat-empty-state {
    text-align: center;
    padding: 3rem 2rem;
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
    width: 6rem;
    height: 6rem;
    margin: 0 auto;
}

.empty-orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}

.empty-orb-1 {
    width: 4rem;
    height: 4rem;
    background: linear-gradient(135deg, rgba(167, 139, 250, 0.3), rgba(244, 114, 182, 0.2));
    top: 0;
    right: 0;
    animation: float-smooth 5s ease-in-out infinite;
}

.empty-orb-2 {
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, rgba(96, 165, 250, 0.3), rgba(139, 92, 246, 0.2));
    bottom: 0.25rem;
    left: 0.25rem;
    animation: float-smooth 4s ease-in-out infinite reverse;
}

.empty-icon-wrap {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 1.25rem;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px -6px rgba(124, 58, 237, 0.12);
}

@keyframes float-smooth {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(3deg); }
}

/* ── Detail Card ── */
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

/* ── Detail Modal Transitions ── */
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
</style>
