<template>
  <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
    <!-- Fluid gradient blobs -->
    <div class="absolute -top-[20%] -left-[10%] w-[70vw] h-[70vw] bg-primary-400/30 rounded-full mix-blend-multiply filter blur-[100px] animate-blob-1" />
    <div class="absolute top-[20%] -right-[10%] w-[60vw] h-[60vw] bg-accent-400/20 rounded-full mix-blend-multiply filter blur-[100px] animate-blob-2" />
    <div class="absolute -bottom-[20%] left-[20%] w-[80vw] h-[80vw] bg-blue-400/20 rounded-full mix-blend-multiply filter blur-[120px] animate-blob-3" />

    <!-- Floating particles (stars/dust) -->
    <div class="absolute inset-0">
      <div
        v-for="i in 30"
        :key="i"
        class="absolute rounded-full opacity-0 animate-star"
        :class="i % 3 === 0 ? 'bg-primary-300' : 'bg-white'"
        :style="{
          width: Math.random() * 4 + 2 + 'px',
          height: Math.random() * 4 + 2 + 'px',
          left: Math.random() * 100 + '%',
          top: Math.random() * 100 + '%',
          animationDelay: Math.random() * 5 + 's',
          animationDuration: Math.random() * 15 + 10 + 's'
        }"
      />

      <div
        v-for="i in 15"
        :key="`g-${i}`"
        class="absolute rounded-full opacity-0 animate-float-particle mix-blend-screen filter blur-[2px]"
        :class="i % 2 === 0 ? 'bg-accent-300' : 'bg-primary-200'"
        :style="{
          width: Math.random() * 20 + 10 + 'px',
          height: Math.random() * 20 + 10 + 'px',
          left: Math.random() * 100 + '%',
          top: Math.random() * 100 + '%',
          animationDelay: Math.random() * 10 + 's',
          animationDuration: Math.random() * 20 + 15 + 's'
        }"
      />
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
/* Keyframes for the blobs and particles are defined in app.css */
</style>
