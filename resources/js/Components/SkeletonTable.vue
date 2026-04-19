<template>
    <div class="animate-pulse flex flex-col space-y-2.5 w-full">
        <div v-for="i in rows" :key="i" class="flex items-center justify-between bg-white/40 backdrop-blur-sm rounded-xl p-3.5 border border-white/50 shadow-sm">
            <div class="flex items-center gap-4 flex-1">
                <!-- Avatar or Number Placeholder -->
                <div class="h-9 w-9 bg-primary-200/40 rounded-lg shrink-0"></div>
                
                <div class="flex flex-col gap-2 w-full max-w-sm">
                    <!-- Title Placeholder -->
                    <div class="h-3 bg-primary-300/40 rounded-full w-3/4"></div>
                    <!-- Subtext Placeholder -->
                    <div class="h-2.5 bg-primary-200/30 rounded-full w-1/2"></div>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <!-- Status Badge Label Placeholder -->
                <div class="hidden sm:block h-6 w-20 bg-primary-200/40 rounded-full"></div>
                <!-- Date/Time Placeholder -->
                <div class="hidden md:block h-2 bg-primary-200/30 rounded-full w-24"></div>
                <!-- Actions Placeholder -->
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 bg-primary-200/40 rounded-lg"></div>
                    <div class="hidden sm:block h-8 w-8 bg-primary-200/40 rounded-lg"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    rows: {
        type: Number,
        default: 5
    }
});
</script>
