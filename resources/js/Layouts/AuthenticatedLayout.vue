<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import FluidBackground from '@/Components/FluidBackground.vue';

const showMobileSidebar = ref(false);
const page = usePage();

// Flash Message State
const flashMsg = ref('');
const showFlash = ref(false);
let flashTimeout = null;

watch(() => page.props.flash?.message, (newMsg) => {
    if (newMsg) {
        flashMsg.value = newMsg;
        showFlash.value = false; // Reset to re-trigger animation
        
        // Use nextTick equivalent to restart the toast
        setTimeout(() => {
            showFlash.value = true;
            if (flashTimeout) clearTimeout(flashTimeout);
            flashTimeout = setTimeout(() => {
                showFlash.value = false;
            }, 5000);
        }, 10);
    }
}, { immediate: true });

const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.is_admin);

const toggleSidebar = () => {
    showMobileSidebar.value = !showMobileSidebar.value;
};

const confirmingLogout = ref(false);

const confirmLogout = () => {
    confirmingLogout.value = true;
};

const closeModal = () => {
    confirmingLogout.value = false;
};

const logout = () => {
    router.post(route('logout'));
};

// Network status detection
const isOnline = ref(navigator.onLine);
const justReconnected = ref(false);
let reconnectTimer = null;

const handleOnline = () => {
    isOnline.value = true;
    justReconnected.value = true;
    clearTimeout(reconnectTimer);
    reconnectTimer = setTimeout(() => { justReconnected.value = false; }, 4000);
};

const handleOffline = () => {
    isOnline.value = false;
    justReconnected.value = false;
};

// User Notification polling
const unreadCount = ref(0);
let pollInterval = null;

const fetchUnreadCount = async () => {
    if (isAdmin.value) return;
    try {
        const { data } = await axios.get(route('notifications.unread'));
        unreadCount.value = data.count ?? 0;
    } catch {
        // Silently ignore errors (e.g. network offline)
    }
};

const handleBellClick = async () => {
    if (unreadCount.value > 0) {
        try {
            await axios.post(route('notifications.markRead'), {}, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
            });
            unreadCount.value = 0;
        } catch {
            // ignore
        }
    }
    router.visit(route('complaints.index'));
};

// Admin Toast Notification System
const adminUnreadCount = ref(0);
const adminToasts = ref([]);
let adminPollInterval = null;
let prevAdminCount = 0;
let toastIdCounter = 0;

const addToast = (toast) => {
    const id = ++toastIdCounter;
    adminToasts.value.unshift({ id, ...toast, visible: true });
    // Auto-remove after 8 seconds
    setTimeout(() => removeToast(id), 8000);
};

const removeToast = (id) => {
    const idx = adminToasts.value.findIndex(t => t.id === id);
    if (idx !== -1) {
        adminToasts.value[idx].visible = false;
        setTimeout(() => {
            adminToasts.value = adminToasts.value.filter(t => t.id !== id);
        }, 400);
    }
};

const fetchAdminUnreadCount = async () => {
    if (!isAdmin.value) return;
    try {
        const { data } = await axios.get(route('admin.notifications.unread'));
        const newCount = data.count ?? 0;

        // Show toast if count INCREASED (new complaints came in)
        if (newCount > prevAdminCount && prevAdminCount !== -1) {
            const diff = newCount - prevAdminCount;
            const latest = data.latest;
            addToast({
                count: diff,
                title: latest?.title ?? 'Laporan baru',
                userName: latest?.userName ?? 'Siswa',
                complaintId: latest?.id ?? null,
            });
        }

        prevAdminCount = newCount;
        adminUnreadCount.value = newCount;
    } catch {
        // Silently ignore
    }
};

const showUserNotifications = ref(false);
const showAdminNotifications = ref(false);

const toggleUserNotifications = () => {
    showUserNotifications.value = !showUserNotifications.value;
    if (showUserNotifications.value) showAdminNotifications.value = false;
};

const toggleAdminNotifications = () => {
    showAdminNotifications.value = !showAdminNotifications.value;
    if (showAdminNotifications.value) showUserNotifications.value = false;
};

const handleAdminBellClick = async () => {
    // If opening dropdown, don't visit yet. Just toggle.
    toggleAdminNotifications();
};

const handleAdminNotificationConfirm = async () => {
    if (adminUnreadCount.value > 0) {
        try {
            await axios.post(route('admin.notifications.markRead'), {}, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
            });
            adminUnreadCount.value = 0;
            prevAdminCount = 0;
            // Clear all toasts when admin clicks bell
            adminToasts.value.forEach(t => removeToast(t.id));
        } catch {
            // ignore
        }
    }
    showAdminNotifications.value = false;
    router.visit(route('admin.complaints'));
};

const handleUserNotificationConfirm = async () => {
    if (unreadCount.value > 0) {
        try {
            await axios.post(route('notifications.markRead'), {}, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }
            });
            unreadCount.value = 0;
        } catch {
            // ignore
        }
    }
    showUserNotifications.value = false;
    router.visit(route('complaints.index'));
};

// Mendengarkan event khusus dari halaman anak saat notifikasi individual ditandai dibaca
const handleNotificationRead = () => {
    if (unreadCount.value > 0) {
        unreadCount.value--;
    }
};

const handleAdminNotificationRead = () => {
    if (adminUnreadCount.value > 0) {
        adminUnreadCount.value--;
        prevAdminCount = adminUnreadCount.value;
    }
};

onMounted(() => {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    window.addEventListener('notification-read', handleNotificationRead);
    window.addEventListener('admin-notification-read', handleAdminNotificationRead);

    if (!isAdmin.value) {
        fetchUnreadCount();
        
        // Listen ke Websocket untuk update Real-time
        if (window.Echo) {
            window.Echo.channel(`user.${user.value.id}`)
                .listen('.complaint.status.updated', (e) => {
                    fetchUnreadCount();
                });
        }
    } else {
        prevAdminCount = -1;
        fetchAdminUnreadCount().then(() => {
            prevAdminCount = adminUnreadCount.value;
        });
        
        // Listen ke Websocket untuk update Real-time
        if (window.Echo) {
            window.Echo.channel('admin-notifications')
                .listen('.complaint.new', (e) => {
                    fetchAdminUnreadCount();
                });
        }
    }

    // Initialize Pusher Beams (dynamic import — safe on HTTP)
    if ('serviceWorker' in navigator && window.isSecureContext) {
        import('@pusher/push-notifications-web').then(({ Client }) => {
            try {
                const instanceId = import.meta.env.VITE_PUSHER_BEAMS_INSTANCE_ID || '8c16ff90-1b3d-4d0f-834e-d5fb5401078f';
                const beamsClient = new Client({ instanceId });

                beamsClient.start()
                  .then(() => {
                    const interest = isAdmin.value ? 'admin-notifications' : `user-${user.value.id}`;
                    return beamsClient.addDeviceInterest(interest);
                  })
                  .then(() => {
                    console.log('Pusher Beams: subscribed to ' + (isAdmin.value ? 'admin-notifications' : `user-${user.value.id}`));
                  })
                  .catch(err => console.warn('Pusher Beams subscription error:', err));
            } catch (e) {
                console.warn('Pusher Beams init error:', e);
            }
        }).catch(() => console.info('Pusher Beams: skipped (CSP restriction)'));
    } else {
        console.info('Pusher Beams: skipped (requires HTTPS)');
    }
});

onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
    window.removeEventListener('notification-read', handleNotificationRead);
    window.removeEventListener('admin-notification-read', handleAdminNotificationRead);
    clearTimeout(reconnectTimer);
    if (pollInterval) clearInterval(pollInterval);
    if (adminPollInterval) clearInterval(adminPollInterval);
});
</script>

<template>
  <div class="min-h-screen bg-gradient-surface transition-colors duration-500 relative overflow-hidden">
    <FluidBackground />
        
    <!-- ─ Network Status Banners ─ -->
    <!-- Offline Banner -->
    <Transition name="net-slide">
      <div
        v-if="!isOnline"
        class="fixed top-0 left-0 right-0 z-[999] flex items-center justify-center gap-3 py-3 px-6 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-rose-500 shadow-lg"
        style="backdrop-filter: blur(8px);"
      >
        <svg
          class="w-5 h-5 flex-shrink-0 animate-pulse"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M12 12h.01M8.464 8.464a5 5 0 000 7.072M5.636 5.636a9 9 0 000 12.728"
          />
        </svg>
        <span>
          Tidak ada koneksi internet —
          <template v-if="isAdmin">Aksi manajemen laporan tidak dapat dilakukan hingga koneksi pulih.</template>
          <template v-else>Periksa jaringan Anda sebelum mengirim laporan.</template>
        </span>
        <svg
          class="w-5 h-5 flex-shrink-0 animate-bounce"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
    </Transition>

    <!-- Reconnected Banner -->
    <Transition name="net-slide">
      <div
        v-if="justReconnected"
        class="fixed top-0 left-0 right-0 z-[999] flex items-center justify-center gap-3 py-3 px-6 text-sm font-semibold text-white bg-gradient-to-r from-emerald-500 to-teal-500 shadow-lg"
      >
        <svg
          class="w-5 h-5 flex-shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
        <span>Koneksi pulih — Anda kembali online!</span>
      </div>
    </Transition>

    <!-- Mobile Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showMobileSidebar"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30 md:hidden"
        @click="showMobileSidebar = false"
      />
    </Transition>

    <!-- Sidebar -->
    <aside
      class="sidebar"
      :class="{ 'open': showMobileSidebar }"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-6 py-6 border-b border-white/10 group cursor-default">
        <div class="relative w-12 h-12 flex-shrink-0 animate-float-smooth">
          <div class="absolute top-0 right-0 w-10 h-10 rounded-xl bg-gradient-to-tr from-white/30 to-white/5 backdrop-blur-md border border-white/20 transition-all duration-700 group-hover:rotate-[15deg] group-hover:scale-110 shadow-lg group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
          <div class="absolute bottom-0 left-0 w-[2.6rem] h-[2.6rem] rounded-xl bg-white/20 backdrop-blur-xl border border-white/40 flex items-center justify-center shadow-[0_4px_15px_rgba(0,0,0,0.1)] transition-all duration-500 group-hover:-translate-y-1 group-hover:bg-white/30 overflow-hidden relative">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent -translate-x-[150%] skew-x-[30deg] animate-[shine-sweep_3s_infinite_ease-in-out]" />
            <svg
              class="w-5 h-5 text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.3)] transition-transform duration-300 group-hover:scale-110 relative z-10"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
              />
            </svg>
          </div>
        </div>
        <div>
          <h1
            class="text-white font-extrabold text-2xl tracking-tight mb-0.5 transition-all duration-300 group-hover:drop-shadow-[0_0_15px_rgba(255,255,255,0.4)]"
            style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);"
          >
            IzinLapor
          </h1>
          <p class="text-primary-100 text-[10px] font-bold uppercase tracking-widest relative bottom-0.5 drop-shadow-sm">
            SMAN 11 SEMARANG
          </p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="py-4">
        <p class="px-6 mb-2 text-xs font-semibold text-primary-300 uppercase tracking-wider">
          {{ $t('menu') }}
        </p>

        <!-- Admin Navigation -->
        <template v-if="isAdmin">
          <Link
            :href="route('admin.dashboard')"
            class="sidebar-link group"
            :class="{ 'active': route().current('admin.dashboard') }"
          >
            <div class="relative w-8 h-8 mr-1.5 flex-shrink-0">
              <div class="absolute inset-0 rounded-lg bg-white/20 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
              <div class="absolute inset-0 rounded-lg bg-white/10 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:-translate-y-0.5">
                <svg
                  class="w-4 h-4 text-white drop-shadow-sm transition-transform duration-300 group-hover:scale-110"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                  />
                </svg>
              </div>
            </div>
            {{ $t('dashboard') }}
          </Link>
          <Link
            :href="route('admin.complaints')"
            class="sidebar-link group"
            :class="{ 'active': route().current('admin.complaints') }"
          >
            <div class="relative w-8 h-8 mr-1.5 flex-shrink-0">
              <div class="absolute inset-0 rounded-lg bg-white/20 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
              <div class="absolute inset-0 rounded-lg bg-white/10 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:-translate-y-0.5">
                <svg
                  class="w-4 h-4 text-white drop-shadow-sm transition-transform duration-300 group-hover:scale-110"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                  />
                </svg>
              </div>
            </div>
            {{ $t('complaint_list') }}
          </Link>
        </template>

        <!-- User Navigation -->
        <template v-else>
          <Link
            :href="route('dashboard')"
            class="sidebar-link group"
            :class="{ 'active': route().current('dashboard') }"
          >
            <div class="relative w-8 h-8 mr-1.5 flex-shrink-0">
              <div class="absolute inset-0 rounded-lg bg-white/20 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
              <div class="absolute inset-0 rounded-lg bg-white/10 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:-translate-y-0.5">
                <svg
                  class="w-4 h-4 text-white drop-shadow-sm transition-transform duration-300 group-hover:scale-110"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                  />
                </svg>
              </div>
            </div>
            {{ $t('dashboard') }}
          </Link>
          <Link
            :href="route('complaints.create')"
            class="sidebar-link group"
            :class="{ 'active': route().current('complaints.create') }"
          >
            <div class="relative w-8 h-8 mr-1.5 flex-shrink-0">
              <div class="absolute inset-0 rounded-lg bg-white/20 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
              <div class="absolute inset-0 rounded-lg bg-white/10 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:-translate-y-0.5">
                <svg
                  class="w-4 h-4 text-white drop-shadow-sm transition-transform duration-300 group-hover:scale-110"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
            </div>
            {{ $t('create_complaint') }}
          </Link>
          <Link
            :href="route('complaints.index')"
            class="sidebar-link group"
            :class="{ 'active': route().current('complaints.index') }"
          >
            <div class="relative w-8 h-8 mr-1.5 flex-shrink-0">
              <div class="absolute inset-0 rounded-lg bg-white/20 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
              <div class="absolute inset-0 rounded-lg bg-white/10 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:-translate-y-0.5">
                <svg
                  class="w-4 h-4 text-white drop-shadow-sm transition-transform duration-300 group-hover:scale-110"
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
              <!-- Notification dot on icon -->
              <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex items-center justify-center min-w-[16px] h-[16px] px-0.5 text-[9px] font-bold text-white bg-red-500 rounded-full shadow-md ring-1 ring-white/30 animate-bounce-subtle z-10"
              >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </div>
            <span class="flex-1">{{ $t('my_complaints') }}</span>
            <!-- Sidebar notification badge -->
            <span
              v-if="unreadCount > 0"
              class="sidebar-notif-badge"
            >
              {{ unreadCount }} baru
            </span>
          </Link>
        </template>

        <!-- Profile Section -->
        <p class="px-6 mt-6 mb-2 text-xs font-semibold text-primary-300 uppercase tracking-wider">
          {{ $t('account') }}
        </p>
        <Link
          :href="route('profile.edit')"
          class="sidebar-link group"
          :class="{ 'active': route().current('profile.edit') }"
        >
          <div class="relative w-8 h-8 mr-1.5 flex-shrink-0">
            <div class="absolute inset-0 rounded-lg bg-white/20 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
            <div class="absolute inset-0 rounded-lg bg-white/10 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:-translate-y-0.5">
              <svg
                class="w-4 h-4 text-white drop-shadow-sm transition-transform duration-300 group-hover:scale-110"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
            </div>
          </div>
          {{ $t('profile') }}
        </Link>
        <button
          class="sidebar-link w-full text-left group"
          @click="confirmLogout"
        >
          <div class="relative w-8 h-8 mr-1.5 flex-shrink-0">
            <div class="absolute inset-0 rounded-lg bg-white/20 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110" />
            <div class="absolute inset-0 rounded-lg bg-white/10 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:-translate-y-0.5">
              <svg
                class="w-4 h-4 text-white drop-shadow-sm transition-transform duration-300 group-hover:scale-110"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                />
              </svg>
            </div>
          </div>
          {{ $t('logout') }}
        </button>
      </nav>

      <!-- User Card at Bottom -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10 bg-transparent mb-2">
        <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors cursor-pointer group">
          <div class="relative w-10 h-10 flex-shrink-0">
            <!-- Soft Glow -->
            <div class="absolute inset-0 rounded-xl bg-white/20 blur-md opacity-40 group-hover:opacity-80 transition-opacity" />
            <!-- Avatar Base -->
            <div class="absolute inset-0 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/40 shadow-[0_4px_12px_rgba(0,0,0,0.05),inset_0_1px_2px_rgba(255,255,255,0.5)] transition-transform group-hover:scale-105">
              <span class="text-white font-extrabold text-sm drop-shadow-sm">{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
            </div>
            <!-- Online Status Dot -->
            <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-400 border-2 border-white/20 rounded-full shadow-sm z-10 backdrop-blur-sm" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-white text-sm font-extrabold truncate group-hover:text-primary-100 transition-colors">
              {{ user?.name }}
            </p>
            <p class="text-white/80 text-[10px] font-extrabold uppercase tracking-widest truncate flex items-center py-0.5 mt-0.5">
              <svg
                class="w-3.5 h-3.5 mr-1"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
              /></svg>
              {{ isAdmin ? 'Administrator' : 'Pengguna Terverifikasi' }}
            </p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="md:ml-[250px]">
      <!-- Top Bar -->
      <header class="sticky top-0 z-20 bg-white/40 backdrop-blur-xl border-b border-white/40 px-6 py-4 flex items-center justify-between transition-colors duration-300 shadow-[0_4px_30px_rgba(0,0,0,0.03)]">
        <!-- Mobile Menu Button -->
        <button
          class="md:hidden p-2 rounded-lg hover:bg-white/60 text-primary-700 transition"
          @click="toggleSidebar"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>

        <!-- Page Header -->
        <div class="flex-1">
          <slot name="header" />
        </div>

        <!-- User Actions Section -->
        <div class="flex items-center gap-4">
          <!-- Admin Pro Badge -->
          <div
            v-if="isAdmin"
            class="hidden sm:flex items-center px-4 py-1.5 rounded-full bg-primary-50/80 backdrop-blur-sm border border-primary-100 shadow-[inset_0_1px_1px_rgba(255,255,255,1)]"
          >
            <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2 shadow-[0_0_6px_rgba(16,185,129,0.5)]" />
            <span class="text-xs font-extrabold text-primary-700 uppercase tracking-widest">
              Administrator
            </span>
          </div>

          <!-- Bell Notification (User) -->
          <div
            v-if="!isAdmin"
            class="relative"
          >
            <button
              class="relative w-11 h-11 flex items-center justify-center rounded-xl bg-white shadow-[0_2px_10px_rgba(0,0,0,0.06),inset_0_1px_1px_rgba(255,255,255,1)] border border-primary-50 transition-all hover:shadow-[0_8px_20px_-4px_rgba(139,92,246,0.2)] hover:-translate-y-0.5 group"
              :title="unreadCount > 0 ? `${unreadCount} notifikasi baru` : 'Notifikasi'"
              @click="toggleUserNotifications"
            >
              <svg
                class="w-5 h-5 text-gray-400 transition-colors group-hover:text-primary-600"
                :class="{ 'text-primary-500 animate-[shake_0.5s_ease-in-out_infinite]': unreadCount > 0 }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
              </svg>
              <!-- Badge -->
              <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 scale-50"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-50"
              >
                <span
                  v-if="unreadCount > 0"
                  class="absolute -top-1.5 -right-1.5 flex items-center justify-center min-w-[20px] h-[20px] px-1 text-[10px] font-black text-white bg-gradient-to-tr from-accent-600 to-red-500 rounded-lg shadow-lg ring-2 ring-white"
                >
                  {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
              </Transition>
            </button>

            <!-- User Notification Dropdown -->
            <Transition
              enter-active-class="transition ease-out duration-200"
              enter-from-class="opacity-0 translate-y-2 scale-95"
              enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition ease-in duration-150"
              leave-from-class="opacity-100 translate-y-0 scale-100"
              leave-to-class="opacity-0 translate-y-2 scale-95"
            >
              <div
                v-if="showUserNotifications"
                class="absolute right-0 mt-3 w-72 origin-top-right z-[100]"
              >
                <div class="glass-card overflow-hidden shadow-2xl border-white/60">
                  <div class="p-4 border-b border-primary-100 bg-primary-50/30">
                    <h3 class="text-sm font-bold text-primary-900">
                      Notifikasi
                    </h3>
                  </div>
                  <div class="max-h-[300px] overflow-y-auto">
                    <div
                      v-if="unreadCount > 0"
                      class="p-4 hover:bg-white/40 transition cursor-pointer group"
                      @click="handleUserNotificationConfirm"
                    >
                      <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center flex-shrink-0 text-primary-600">
                          <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          ><path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                          /></svg>
                        </div>
                        <div>
                          <p class="text-xs font-bold text-gray-900 mb-0.5">
                            Update Status Laporan
                          </p>
                          <p class="text-xs text-gray-500 font-medium leading-relaxed">
                            Ada status baru pada laporan Anda. Klik untuk detail.
                          </p>
                        </div>
                      </div>
                    </div>
                    <div
                      v-else
                      class="p-8 text-center"
                    >
                      <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-3 opacity-40">
                        <svg
                          class="w-6 h-6 text-gray-400"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                        /></svg>
                      </div>
                      <p class="text-xs font-bold text-gray-400">
                        Tidak ada notifikasi baru
                      </p>
                    </div>
                  </div>
                  <div class="p-3 border-t border-primary-50 bg-white/40 text-center">
                    <button
                      class="text-[10px] font-bold text-primary-600 uppercase tracking-widest hover:text-primary-700 transition"
                      @click="handleUserNotificationConfirm"
                    >
                      Lihat Riwayat
                    </button>
                  </div>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Bell Notification (Admin) -->
          <div
            v-if="isAdmin"
            class="relative"
          >
            <button
              class="relative w-11 h-11 flex items-center justify-center rounded-xl bg-white shadow-[0_2px_10px_rgba(0,0,0,0.06),inset_0_1px_1px_rgba(255,255,255,1)] border border-orange-50 transition-all hover:shadow-[0_8px_20px_-4px_rgba(245,158,11,0.2)] hover:-translate-y-0.5 group"
              :title="adminUnreadCount > 0 ? `${adminUnreadCount} laporan baru masuk` : 'Notifikasi Laporan'"
              @click="toggleAdminNotifications"
            >
              <svg
                class="w-5 h-5 text-gray-400 transition-colors group-hover:text-orange-600"
                :class="{ 'text-orange-500 animate-[shake_0.5s_ease-in-out_infinite]': adminUnreadCount > 0 }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2.5"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
              </svg>
              <!-- Badge -->
              <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 scale-50"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-50"
              >
                <span
                  v-if="adminUnreadCount > 0"
                  class="absolute -top-1.5 -right-1.5 flex items-center justify-center min-w-[20px] h-[20px] px-1 text-[10px] font-black text-white bg-gradient-to-tr from-orange-500 to-rose-500 rounded-lg shadow-lg ring-2 ring-white"
                >
                  {{ adminUnreadCount > 9 ? '9+' : adminUnreadCount }}
                </span>
              </Transition>
            </button>

            <!-- Admin Notification Dropdown -->
            <Transition
              enter-active-class="transition ease-out duration-200"
              enter-from-class="opacity-0 translate-y-2 scale-95"
              enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition ease-in duration-150"
              leave-from-class="opacity-100 translate-y-0 scale-100"
              leave-to-class="opacity-0 translate-y-2 scale-95"
            >
              <div
                v-if="showAdminNotifications"
                class="absolute right-0 mt-3 w-72 origin-top-right z-[100]"
              >
                <div class="glass-card overflow-hidden shadow-2xl border-white/60">
                  <div class="p-4 border-b border-orange-100 bg-orange-50/30">
                    <h3 class="text-sm font-bold text-orange-900">
                      Notifikasi Laporan
                    </h3>
                  </div>
                  <div class="max-h-[300px] overflow-y-auto">
                    <div
                      v-if="adminUnreadCount > 0"
                      class="p-4 hover:bg-white/40 transition cursor-pointer group"
                      @click="handleAdminNotificationConfirm"
                    >
                      <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0 text-orange-600">
                          <svg
                            class="w-4 h-4"
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
                          <p class="text-xs font-bold text-gray-900 mb-0.5">
                            Laporan baru masuk
                          </p>
                          <p class="text-xs text-gray-500 font-medium leading-relaxed">
                            Ada pengaduan baru yang butuh review. Klik untuk detail.
                          </p>
                        </div>
                      </div>
                    </div>
                    <div
                      v-else
                      class="p-8 text-center"
                    >
                      <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-3 opacity-40">
                        <svg
                          class="w-6 h-6 text-gray-400"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        ><path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                        /></svg>
                      </div>
                      <p class="text-xs font-bold text-gray-400">
                        Belum ada laporan baru
                      </p>
                    </div>
                  </div>
                  <div class="p-3 border-t border-orange-50 bg-white/40 text-center">
                    <button
                      class="text-[10px] font-bold text-orange-600 uppercase tracking-widest hover:text-orange-700 transition"
                      @click="handleAdminNotificationConfirm"
                    >
                      Lihat Semua
                    </button>
                  </div>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Enhanced Top Right Avatar -->
          <div
            class="relative w-11 h-11 group cursor-pointer"
            @click="router.visit(route('profile.edit'))"
          >
            <!-- Soft Avatar Base -->
            <div class="absolute inset-0 rounded-xl bg-primary-50 flex items-center justify-center border border-primary-100/80 shadow-[inset_0_2px_4px_rgba(255,255,255,0.8),0_2px_8px_rgba(0,0,0,0.04)] group-hover:scale-105 group-hover:bg-primary-100/60 transition-all">
              <span class="text-primary-600 font-extrabold text-lg tracking-tight">{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
            </div>
          </div>
        </div>
      </header>

      <!-- Flash Messages -->
      <Transition
        enter-active-class="transition ease-out duration-300 [transition-timing-function:cubic-bezier(0.22,1,0.36,1)]"
        enter-from-class="opacity-0 translate-x-4"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0 translate-x-2"
      >
        <div
          v-if="showFlash"
          class="flash-message success group relative overflow-hidden pr-10"
        >
          <div class="w-7 h-7 rounded-full bg-primary-100 border border-primary-200 flex items-center justify-center flex-shrink-0 shadow-inner">
            <svg
              class="w-4 h-4 text-primary-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="3"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <span>{{ flashMsg }}</span>
                    
          <!-- Close button -->
          <button
            class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-primary-400 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors"
            title="Tutup"
            @click="showFlash = false"
          >
            <svg
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <!-- Progress Bar -->
          <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-primary-200/50">
            <div class="h-full bg-primary-500 flash-progress-bar" />
          </div>
        </div>
      </Transition>

      <main class="p-6">
        <Transition
          enter-active-class="transition-all duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
          enter-from-class="opacity-0 translate-y-4 blur-[2px]"
          enter-to-class="opacity-100 translate-y-0 blur-none"
          leave-active-class="transition-all duration-300 ease-in"
          leave-from-class="opacity-100 translate-y-0 blur-none"
          leave-to-class="opacity-0 -translate-y-4 blur-[2px]"
          mode="out-in"
        >
          <div :key="$page.url">
            <slot />
          </div>
        </Transition>
      </main>
    </div>

    <!-- Logout Confirmation Modal -->
    <Modal
      :show="confirmingLogout"
      @close="closeModal"
    >
      <div class="p-6">
        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mx-auto mb-4">
          <svg
            class="w-6 h-6 text-red-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
        </div>
                
        <h2 class="text-xl font-bold text-center text-primary-900 mb-2">
          {{ $t('logout') }}
        </h2>

        <p class="text-center text-sm text-gray-500 mb-6">
          {{ $t('confirm_delete') }}
        </p>

        <div class="flex justify-center gap-3">
          <button
            class="btn-secondary px-6"
            @click="closeModal"
          >
            {{ $t('cancel') }}
          </button>

          <button
            class="btn-danger px-6 shadow-red-500/20 shadow-lg hover:shadow-red-500/40"
            @click="logout"
          >
            {{ $t('logout') }}
          </button>
        </div>
      </div>
    </Modal>

    <!-- ── Admin Toast Notifications ─────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="isAdmin && adminToasts.length > 0"
        class="fixed bottom-6 right-6 z-[9999] flex flex-col-reverse gap-3 pointer-events-none"
        style="max-width: 360px; width: 100%;"
      >
        <TransitionGroup
          name="toast"
          tag="div"
          class="flex flex-col-reverse gap-3"
        >
          <div
            v-for="toast in adminToasts"
            :key="toast.id"
            class="admin-toast pointer-events-auto"
          >
            <!-- Toast Card -->
            <div
              class="relative flex items-start gap-3 bg-white/95 backdrop-blur-xl border border-orange-100 rounded-2xl p-4 shadow-[0_8px_32px_rgba(0,0,0,0.12)] ring-1 ring-orange-200/60"
            >
              <!-- Icon -->
              <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-sm">
                <svg
                  class="w-5 h-5 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                  />
                </svg>
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-orange-600 uppercase tracking-wider mb-0.5">
                  📋 Laporan Baru Masuk
                  <span
                    v-if="toast.count > 1"
                    class="ml-1 bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded-md"
                  >+{{ toast.count }}</span>
                </p>
                <p class="text-sm font-semibold text-gray-900 truncate leading-tight">
                  {{ toast.title }}
                </p>
                <p class="text-xs text-gray-500 font-medium mt-0.5">
                  dari <span class="font-semibold text-gray-700">{{ toast.userName }}</span>
                </p>

                <!-- Action Button -->
                <button
                  class="mt-2 text-xs font-bold text-orange-600 hover:text-orange-700 transition-colors flex items-center gap-1 group"
                  @click="handleAdminBellClick"
                >
                  Lihat Pengaduan
                  <svg
                    class="w-3 h-3 group-hover:translate-x-0.5 transition-transform"
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
                </button>
              </div>

              <!-- Close Button -->
              <button
                class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all"
                title="Tutup"
                @click="removeToast(toast.id)"
              >
                <svg
                  class="w-3.5 h-3.5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>

              <!-- Progress bar (auto-dismiss indicator) -->
              <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-orange-100 rounded-b-2xl overflow-hidden">
                <div class="h-full bg-gradient-to-r from-amber-400 to-orange-500 toast-progress" />
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* Toast slide-in animation */
.toast-enter-active {
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}
.toast-leave-active {
    transition: all 0.3s ease-in;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(80%) scale(0.95);
}

/* Progress bar shrink animation */
.toast-progress {
    animation: toast-shrink 8s linear forwards;
}

@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-3px) scale(1.05); }
}

.animate-bounce-subtle {
    animation: bounce-subtle 2s infinite ease-in-out;
}

@keyframes toast-shrink {
    from { width: 100%; }
    to   { width: 0%; }
}

/* Flash message progress bar animation */
.flash-progress-bar {
    animation: flash-shrink 5s linear forwards;
}

@keyframes flash-shrink {
    0% { width: 100%; }
    100% { width: 0%; }
}

/* Sidebar notification badge for unread count */
.sidebar-notif-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: white;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 9999px;
    box-shadow: 0 2px 8px -2px rgba(239, 68, 68, 0.6);
    animation: sidebar-badge-pulse 2s ease-in-out infinite;
    flex-shrink: 0;
}

@keyframes sidebar-badge-pulse {
    0%, 100% { box-shadow: 0 2px 8px -2px rgba(239, 68, 68, 0.6); transform: scale(1); }
    50% { box-shadow: 0 2px 14px -1px rgba(239, 68, 68, 0.8); transform: scale(1.05); }
}
</style>