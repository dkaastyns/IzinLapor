<template>
  <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
    <!-- Fluid gradient blobs — hanya render jika user tidak prefer reduced motion -->
    <template v-if="!prefersReducedMotion">
      <div class="absolute -top-[20%] -left-[10%] w-[70vw] h-[70vw] bg-primary-400/30 rounded-full mix-blend-multiply filter blur-[80px] animate-blob-1" />
      <div class="absolute top-[20%] -right-[10%] w-[60vw] h-[60vw] bg-accent-400/20 rounded-full mix-blend-multiply filter blur-[80px] animate-blob-2" />
      <div class="absolute -bottom-[20%] left-[20%] w-[80vw] h-[80vw] bg-blue-400/20 rounded-full mix-blend-multiply filter blur-[100px] animate-blob-3" />

      <!-- Floating particles — dikurangi di mobile/low-end -->
      <div class="absolute inset-0">
        <div
          v-for="p in starParticles"
          :key="p.key"
          class="absolute rounded-full opacity-0 animate-star"
          :class="p.isPrimary ? 'bg-primary-300' : 'bg-white'"
          :style="p.style"
        />

        <div
          v-for="p in glowParticles"
          :key="p.key"
          class="absolute rounded-full opacity-0 animate-float-particle mix-blend-screen filter blur-[2px]"
          :class="p.isEven ? 'bg-accent-300' : 'bg-primary-200'"
          :style="p.style"
        />
      </div>
    </template>

    <!-- Static radial gradient fallback untuk reduced-motion -->
    <template v-else>
      <div class="absolute inset-0 bg-gradient-radial from-primary-200/30 via-transparent to-transparent" />
    </template>
  </div>
</template>

<script setup>
// Deteksi preferensi reduced-motion & kemampuan hardware
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isLowEnd = (navigator.hardwareConcurrency ?? 4) <= 2;

// Tentukan jumlah partikel berdasarkan kemampuan device
const starCount = prefersReducedMotion ? 0 : isLowEnd ? 10 : 30;
const glowCount = prefersReducedMotion ? 0 : isLowEnd ? 5 : 15;

// Pre-generate nilai random di setup() — bukan di template binding
// agar tidak di-recalculate setiap re-render
const starParticles = Array.from({ length: starCount }, (_, i) => ({
  key: `s-${i}`,
  isPrimary: i % 3 === 0,
  style: {
    width: (Math.random() * 4 + 2) + 'px',
    height: (Math.random() * 4 + 2) + 'px',
    left: (Math.random() * 100) + '%',
    top: (Math.random() * 100) + '%',
    animationDelay: (Math.random() * 5) + 's',
    animationDuration: (Math.random() * 15 + 10) + 's',
  },
}));

const glowParticles = Array.from({ length: glowCount }, (_, i) => ({
  key: `g-${i}`,
  isEven: i % 2 === 0,
  style: {
    width: (Math.random() * 20 + 10) + 'px',
    height: (Math.random() * 20 + 10) + 'px',
    left: (Math.random() * 100) + '%',
    top: (Math.random() * 100) + '%',
    animationDelay: (Math.random() * 10) + 's',
    animationDuration: (Math.random() * 20 + 15) + 's',
  },
}));
</script>

<style scoped>
/* Keyframes for the blobs and particles are defined in app.css */
</style>
