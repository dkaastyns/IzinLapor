<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="btn-secondary btn-shine select-none disabled:opacity-25 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    >
        <slot />
    </button>
</template>
