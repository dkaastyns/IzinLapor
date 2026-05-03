<script setup>
import { Link } from '@inertiajs/vue3';
import FluidBackground from '@/Components/FluidBackground.vue';
</script>

<template>
  <div class="min-h-screen flex transition-colors duration-500 bg-gradient-surface relative overflow-hidden">
    <FluidBackground />
        
    <!-- Left Side - Branding -->
    <div class="hidden lg:flex lg:flex-col lg:w-1/2 bg-primary-500 relative overflow-hidden justify-between p-12 lg:p-16 z-10">
      <!-- Glass Logo at Top Left -->
      <div class="flex items-center gap-5 z-10 group/logo cursor-pointer transition-all duration-500 hover:scale-[1.02] origin-left">
        <div class="relative w-16 h-16 flex-shrink-0 animate-float-smooth">
          <!-- Premium Back Blob (Animated Squircle) -->
          <div class="absolute top-0 right-0 w-14 h-14 rounded-[1.25rem] bg-gradient-to-tr from-white/40 to-white/10 backdrop-blur-md border border-white/30 transition-all duration-700 group-hover/logo:rotate-[15deg] group-hover/logo:scale-110 shadow-lg group-hover/logo:translate-x-1 group-hover/logo:-translate-y-1" />
                    
          <!-- Front Glass Squircle -->
          <div class="absolute bottom-0 left-0 w-[3.75rem] h-[3.75rem] rounded-[1.25rem] bg-white/20 backdrop-blur-xl border border-white/50 shadow-[0_8px_32px_rgba(0,0,0,0.1)] flex items-center justify-center transition-all duration-500 group-hover/logo:-translate-y-2 group-hover/logo:bg-white/30 overflow-hidden relative">
            <!-- Shining Reflection Effect -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/50 to-transparent -translate-x-[150%] skew-x-[30deg] animate-[shine-sweep_3s_infinite_ease-in-out]" />

            <svg
              class="w-8 h-8 text-white drop-shadow-[0_2px_6px_rgba(0,0,0,0.3)] transition-transform duration-500 group-hover/logo:scale-110 relative z-10"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.2"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
              />
            </svg>
          </div>
        </div>
        <div class="flex flex-col">
          <span class="text-white font-black text-[28px] tracking-tight leading-none drop-shadow-[0_4px_10px_rgba(0,0,0,0.2)] transition-all duration-500 group-hover/logo:drop-shadow-[0_0_20px_rgba(255,255,255,0.4)]">
            IzinLapor
          </span>
          <span class="text-white/70 text-[11px] font-bold uppercase tracking-[0.25em] group-hover/logo:text-white/100 transition-colors mt-1.5 drop-shadow-sm">SMAN 11 SEMARANG</span>
        </div>
      </div>

      <!-- Main Content Area -->
      <div class="z-10 mt-16 flex-1 flex flex-col justify-center">
        <!-- Login Text -->
        <template v-if="route().current('login')">
          <h1 class="text-[2.5rem] lg:text-[3rem] font-bold text-white mb-6 leading-[1.1] drop-shadow-sm">
            Laporkan kerusakan<br>fasilitas sekolah<br>dengan mudah.
          </h1>
          <p class="text-primary-100 text-base max-w-md mb-12 leading-relaxed">
            Bersama kita jaga lingkungan belajar yang nyaman. Sistem Pengaduan Fasilitas Rusak untuk civitas akademika SMAN 11 Semarang.
          </p>
        </template>
                
        <!-- Register Text -->
        <template v-else>
          <h1 class="text-[2.5rem] lg:text-[3rem] font-bold text-white mb-6 leading-[1.1] drop-shadow-sm">
            SMAN 11 Semarang
          </h1>
          <p class="text-primary-100 text-base max-w-md mb-12 leading-relaxed">
            Laporkan kerusakan fasilitas sekolah dengan mudah. Bersama kita jaga lingkungan belajar yang nyaman.
          </p>
        </template>

        <!-- 3 Glass Cards -->
        <div class="grid grid-cols-2 gap-4 max-w-[440px]">
          <!-- Upload Foto -->
          <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 shadow-md flex items-center gap-4 transition-all duration-300 hover:bg-white/20 group/item">
            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center flex-shrink-0 transition-all duration-300 group-hover/item:bg-white/20 shadow-inner border border-white/5">
              <svg
                class="w-6 h-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
              /><circle
                cx="12"
                cy="13"
                r="3"
                stroke-width="2"
              /></svg>
            </div>
            <span class="text-white text-[14px] font-bold tracking-wide">Upload Foto</span>
          </div>
          <!-- Tracking Status -->
          <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 shadow-md flex items-center gap-4 transition-all duration-300 hover:bg-white/20 group/item">
            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center flex-shrink-0 transition-all duration-300 group-hover/item:bg-white/20 shadow-inner border border-white/5">
              <svg
                class="w-6 h-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
              /></svg>
            </div>
            <span class="text-white text-[14px] font-bold tracking-wide">Tracking Status</span>
          </div>
          <!-- Respon Cepat -->
          <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 shadow-md flex items-center gap-4 transition-all duration-300 hover:bg-white/20 group/item col-span-1">
            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center flex-shrink-0 transition-all duration-300 group-hover/item:bg-white/20 shadow-inner border border-white/5">
              <svg
                class="w-6 h-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              ><path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 10V3L4 14h7v7l9-11h-7z"
              /></svg>
            </div>
            <span class="text-white text-[14px] font-bold tracking-wide">Respon Cepat</span>
          </div>
        </div>
      </div>

      <!-- Ambient School Background Overlay -->
      <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <!-- Main School Background Photo (Disamarin & Diungu) -->
        <!-- IMPORTANT: User should save their school photo as public/images/school-bg.jpg -->
        <div 
          class="absolute inset-0 opacity-40 mix-blend-luminosity blur-[2px] scale-105 transition-all duration-1000"
          style="background-image: url('/images/school-bg.jpg'); background-size: cover; background-position: center;"
        />
                
        <!-- Gradient overlays for that rich purple glass feel -->
        <div class="absolute inset-0 bg-gradient-to-tr from-primary-600/90 via-primary-500/50 to-transparent mix-blend-multiply" />
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-primary-500/30 to-primary-600/95" />
      </div>

      <!-- Footer -->
      <div class="z-10 mt-auto pt-8">
        <p class="text-primary-200/80 text-xs">
          © 2026 SMAN 11 Semarang. Semua hak dilindungi.
        </p>
      </div>
    </div>

    <!-- Right Side - Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 relative overflow-y-auto z-10">
      <!-- Mobile Logo -->
      <div class="lg:hidden absolute top-8 left-0 right-0 flex justify-center z-10">
        <div class="flex items-center gap-3">
          <div class="relative w-11 h-11 group cursor-default">
            <div class="absolute top-0 right-0 w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-primary-400 shadow-sm transition-transform duration-700 group-hover:rotate-[15deg] group-hover:scale-110" />
            <div class="absolute bottom-0 left-0 w-[2.35rem] h-[2.35rem] rounded-xl bg-white/70 backdrop-blur-md border border-white/80 shadow-[0_4px_15px_rgba(0,0,0,0.1)] flex items-center justify-center transition-transform duration-500 group-hover:-translate-y-1 relative overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/80 to-transparent -translate-x-[150%] skew-x-[30deg] animate-[shine-sweep_3s_infinite_ease-in-out]" />
              <svg
                class="w-5 h-5 text-primary-700 drop-shadow-sm relative z-10 transition-transform duration-300 group-hover:scale-110"
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
          <span class="text-primary-700 font-bold text-lg drop-shadow-sm">IzinLapor</span>
        </div>
      </div>

      <div class="w-full max-w-[460px] pt-16 lg:pt-0 pb-8 animate-fade-in-up md:px-0 relative group/form">
        <!-- Outer Glow Effect -->
        <div class="absolute -inset-4 bg-primary-500/10 blur-3xl rounded-[3rem] opacity-0 group-hover/form:opacity-100 transition-opacity duration-1000" />

        <div class="relative bg-white/90 backdrop-blur-2xl border border-white/60 shadow-[0_32px_64px_-16px_rgba(0,0,0,0.12),0_16px_32px_-8px_rgba(0,0,0,0.08)] rounded-[2.5rem] p-8 sm:p-12 transition-all duration-500 hover:shadow-[0_48px_80px_-20px_rgba(0,0,0,0.18)] hover:-translate-y-1">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
