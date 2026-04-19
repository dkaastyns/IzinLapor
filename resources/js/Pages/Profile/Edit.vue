<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Pengaturan Profil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-primary-900 tracking-tight leading-snug">
                        Pengaturan Profil
                    </h2>
                    <p class="text-sm font-medium text-gray-500 mt-1">
                        Kelola informasi pribadi dan keamanan akun Anda.
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-8 motion-preset-slide-up motion-duration-700 motion-ease-spring-smooth">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                    
                    <UpdatePasswordForm />
                </div>

                <!-- Right Column -->
                <div class="space-y-8 motion-preset-slide-left motion-duration-700 motion-delay-200">
                    <!-- Account Status Card -->
                    <!-- Account Status Card -->
                    <div class="bg-white/80 backdrop-blur-xl border border-primary-100 rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-lg transition-all duration-500 group">
                        <h3 class="text-[11px] font-black text-primary-500 uppercase tracking-[0.2em] mb-8">Status Akun</h3>
                        
                        <div class="space-y-6">
                            <!-- Role Item -->
                            <div class="flex items-center gap-4 group/item cursor-default">
                                <div class="w-12 h-12 rounded-2xl bg-primary-100/50 flex items-center justify-center text-primary-600 shadow-sm border border-primary-200 transition-all duration-500 group-hover/item:scale-110 group-hover/item:rotate-3 group-hover/item:bg-primary-100 group-hover/item:border-primary-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-0.5">Peran Akun</span>
                                    <span class="text-base font-black text-gray-800 leading-tight">
                                        {{ $page.props.auth.user.is_admin ? 'Administrator' : 'Pengguna Terverifikasi' }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Last Login Item -->
                            <div class="flex items-center gap-4 group/item cursor-default">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-100/50 flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-200 transition-all duration-500 group-hover/item:scale-110 group-hover/item:rotate-3 group-hover/item:bg-indigo-100 group-hover/item:border-indigo-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-0.5">Login Terakhir</span>
                                    <span class="text-base font-black text-gray-800 leading-tight">Hari ini, 09:42</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Completion Card -->
                    <div class="relative overflow-hidden rounded-[2rem] bg-[#533483] border border-white/10 shadow-2xl group transition-all duration-500 hover:shadow-primary-500/20 hover:-translate-y-1">
                        <!-- Abstract Background Orbs -->
                        <div class="absolute top-0 -left-1/4 w-[150%] h-[150%] bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#db2777]/30 via-[#7c3aed]/15 to-transparent mix-blend-screen opacity-70 blur-[60px] pointer-events-none group-hover:scale-110 transition-transform duration-1000"></div>
                        <div class="absolute -bottom-1/2 right-0 w-full h-full bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-[#3b82f6]/25 via-[#8b5cf6]/15 to-transparent mix-blend-screen opacity-60 blur-[50px] pointer-events-none group-hover:-translate-x-6 transition-transform duration-1000"></div>
                        
                        <!-- Noise Overlay -->
                        <div class="absolute inset-0 opacity-[0.04] mix-blend-overlay pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.85%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>
                        
                        <!-- Premium Shine Sweep -->
                        <div class="absolute inset-0 pointer-events-none overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full -skew-x-[35deg] animate-[shine-sweep_4s_infinite_ease-in-out]"></div>
                        </div>

                        <!-- Glass Reflection Layers -->
                        <div class="absolute inset-0 bg-gradient-to-tr from-white/5 via-transparent to-white/10 pointer-events-none"></div>

                        <div class="relative z-10 p-8">
                            <!-- Header with Icon -->
                            <div class="flex items-start justify-between mb-6">
                                <h3 class="text-2xl font-black text-white leading-tight tracking-tight drop-shadow-lg">
                                    Profil 100%<br>Lengkap
                                </h3>
                                <div class="w-12 h-12 rounded-2xl bg-emerald-400/20 backdrop-blur-xl border border-emerald-400/40 flex items-center justify-center shadow-[0_0_20px_rgba(52,211,153,0.3)] motion-preset-pop motion-delay-300">
                                    <svg class="w-6 h-6 text-emerald-400 drop-shadow-[0_0_8px_rgba(52,211,153,0.6)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                            </div>

                            <p class="text-xs font-bold text-white/80 mb-7 leading-relaxed drop-shadow-sm">
                                Akun Anda telah diatur sepenuhnya. Level keamanan sistem pada tingkat maksimal.
                            </p>

                            <!-- Progress Bar (100% filled with glowing effect) -->
                            <div class="w-full h-3 bg-black/30 rounded-full mb-7 overflow-hidden border border-white/10 p-0.5 shadow-inner">
                                <div class="h-full bg-gradient-to-r from-emerald-500 via-emerald-400 to-cyan-400 rounded-full 
                                            motion-preset-slide-right motion-duration-[1500ms] origin-left motion-ease-spring-smooth
                                            shadow-[0_0_15px_rgba(52,211,153,0.7)]" 
                                     style="width: 100%;"></div>
                            </div>

                            <!-- Secured Badge -->
                            <div class="w-full py-3.5 px-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 flex items-center justify-center gap-3 transition-all duration-500 hover:bg-emerald-500/10 group-hover:border-emerald-400/40 motion-preset-fade motion-delay-500 shadow-lg">
                                <div class="p-1.5 bg-emerald-500/20 rounded-lg">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                </div>
                                <span class="text-sm font-black text-white tracking-widest uppercase drop-shadow-md">Akun Terlindungi</span>
                            </div>
                        </div>
                    </div>

                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes shine-sweep {
    0% { transform: translateX(-100%) skewX(-35deg); }
    100% { transform: translateX(400%) skewX(-35deg); }
}
</style>
