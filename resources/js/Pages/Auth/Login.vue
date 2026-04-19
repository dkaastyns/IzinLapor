<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Login" />

        <div class="w-full">
            <div class="mb-10 text-center sm:text-left">
                <div class="flex items-center gap-3 mb-4 motion-preset-slide-right motion-duration-500 justify-center sm:justify-start">
                    <div class="login-icon-badge">
                        <span class="text-xl">🔑</span>
                    </div>
                    <h2 class="text-[28px] sm:text-3xl font-black text-gray-900 tracking-tighter leading-tight">
                        Selamat Datang Kembali
                    </h2>
                </div>
                <p class="text-[15px] text-gray-400 font-medium leading-relaxed motion-preset-slide-right motion-duration-500 motion-delay-100">Silakan masuk untuk melanjutkan akses ke IzinLapor.</p>
            </div>

            <div v-if="status" class="mb-5 text-sm font-medium text-green-600 bg-green-50/80 border border-green-100 p-4 rounded-xl">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Group Email -->
                <div class="group">
                    <div class="flex justify-between items-center mb-2 px-1">
                        <label for="email" class="text-[12px] font-black text-indigo-950/70 uppercase tracking-[0.1em] group-focus-within:text-primary-600 transition-colors">Alamat Email</label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none transition-colors group-focus-within:text-primary-500 text-gray-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input
                            id="email"
                            type="email"
                            class="block w-full pl-14 pr-6 py-4 bg-white border border-gray-200/70 rounded-full text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:border-primary-500/50 focus:ring-4 focus:ring-primary-500/10 transition-all duration-300 shadow-sm"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="dika@student.sman11.sch.id"
                        />
                    </div>
                    <InputError class="mt-1.5 ml-4" :message="form.errors.email" />
                </div>

                <!-- Group Password -->
                <div class="group">
                    <div class="flex justify-between items-center mb-2 px-1">
                        <label for="password" class="text-[12px] font-black text-indigo-950/70 uppercase tracking-[0.1em] group-focus-within:text-primary-600 transition-colors">Kata Sandi</label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none transition-colors group-focus-within:text-primary-500 text-gray-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full pl-14 pr-14 py-4 bg-white border border-gray-200/70 rounded-full text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:border-primary-500/50 focus:ring-4 focus:ring-primary-500/10 transition-all duration-300 shadow-sm"
                            :class="!showPassword && form.password ? 'tracking-[0.25em]' : 'tracking-normal'"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••••"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-500 focus:outline-none transition-colors duration-200"
                        >
                            <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <InputError class="mt-1.5 ml-4" :message="form.errors.password" />
                </div>

                <div class="flex items-center pt-1 px-1">
                    <label class="flex items-center cursor-pointer group">
                        <div class="relative flex items-center justify-center">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="peer sr-only"
                            />
                            <div class="w-5 h-5 bg-white border-2 border-gray-200 rounded-lg peer-focus:ring-4 peer-focus:ring-primary-500/10 peer-checked:bg-primary-500 peer-checked:border-primary-500 transition-all duration-300 shadow-sm group-hover:border-primary-300"></div>
                            <svg class="absolute w-3 h-3 text-white pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="ml-3 text-[13px] text-gray-500 font-bold group-hover:text-primary-600 transition-colors">Ingat saya selamanya</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full bg-gradient-to-r from-primary-600 to-indigo-500 hover:from-primary-500 hover:to-indigo-400 text-white font-black py-4 rounded-full shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 active:scale-[0.98] transition-all duration-500 flex items-center justify-center gap-2 group relative overflow-hidden"
                        :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        
                        <svg v-if="form.processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span v-if="!form.processing" class="relative z-10 transition-transform duration-300 group-hover:-translate-x-1">Masuk Sekarang</span>
                        <span v-else class="relative z-10">Memproses...</span>
                        
                        <!-- Arrow Right Icon -->
                        <svg v-if="!form.processing" class="w-5 h-5 relative z-10 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>

                <div class="text-center pt-2">
                    <p class="text-[13.5px] text-gray-400 font-medium">
                        Belum punya akun?
                        <Link :href="route('register')" class="text-primary-600 font-black hover:underline underline-offset-4 transition">
                            Daftar di sini
                        </Link>
                    </p>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
