<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <div class="bg-white/30 backdrop-blur-xl border border-white/50 shadow-2xl rounded-3xl p-8 animate-premium-pop">
            <div class="text-center mb-6">
                <!-- 3D Glass Lock Icon -->
                <div class="mx-auto mb-6 relative w-20 h-20 group">
                    <div class="absolute top-1 right-2 w-14 h-14 rounded-[1rem] bg-gradient-to-tr from-primary-500 to-accent-400 shadow-lg transition-transform duration-500 group-hover:scale-105 group-hover:rotate-6"></div>
                    <div class="absolute bottom-1 left-2 w-14 h-14 rounded-[1rem] bg-white/40 backdrop-blur-md border border-white/60 shadow-xl flex items-center justify-center transition-transform duration-500 group-hover:-translate-y-1">
                        <svg class="w-7 h-7 text-primary-900/80 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-primary-900 flex items-center justify-center gap-2">
                    Lupa Password
                    <div class="relative w-8 h-8 drop-shadow-sm inline-block animate-premium-pop" style="animation-delay: 0.2s;">
                        <div class="absolute top-0 right-0 w-6 h-6 rounded-full bg-gradient-to-tr from-yellow-400 to-yellow-300"></div>
                        <div class="absolute bottom-0 left-0 w-6 h-6 rounded-full bg-white/40 backdrop-blur-md border border-white/60 flex items-center justify-center shadow-sm">
                            <span class="text-sm">🔐</span>
                        </div>
                    </div>
                </h2>
                <p class="text-sm text-primary-800 mt-2 leading-relaxed">Masukkan alamat email Anda dan kami akan mengirimkan tautan untuk mengatur ulang password Anda.</p>
            </div>

            <div
                v-if="status"
                class="mb-6 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-primary-800 mb-1.5">Email</label>
                    <input
                        id="email"
                        type="email"
                        class="input-premium"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@example.com"
                    />
                    <InputError class="mt-1.5" :message="form.errors.email" />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="btn-primary w-full btn-shine select-none"
                        :class="{ 'opacity-60 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span v-if="!form.processing">Kirim Tautan Reset</span>
                        <span v-else>Memproses...</span>
                    </button>
                    
                    <p class="text-center text-sm text-primary-800 mt-5 font-medium">
                        Kembali ke 
                        <Link :href="route('login')" class="text-primary-700 font-bold hover:text-primary-900 transition underline decoration-2 underline-offset-2">
                            halaman login
                        </Link>
                    </p>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
