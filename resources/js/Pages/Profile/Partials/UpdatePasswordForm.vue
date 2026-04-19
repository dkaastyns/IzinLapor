<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Visibility toggles
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Confirmation modal
const showConfirmModal = ref(false);

// Password match logic
const passwordsMatch = computed(() => {
    if (!form.password || !form.password_confirmation) return null; // null = not enough input
    return form.password === form.password_confirmation;
});

const passwordStrength = computed(() => {
    const p = form.password;
    if (!p) return null;
    let score = 0;
    if (p.length >= 8) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[a-z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    return score; // 0-5
});

const strengthLabel = computed(() => {
    const s = passwordStrength.value;
    if (s === null) return '';
    if (s <= 1) return 'Sangat Lemah';
    if (s === 2) return 'Lemah';
    if (s === 3) return 'Cukup';
    if (s === 4) return 'Kuat';
    return 'Sangat Kuat';
});

const strengthColor = computed(() => {
    const s = passwordStrength.value;
    if (s === null) return '';
    if (s <= 1) return 'bg-red-500';
    if (s === 2) return 'bg-orange-400';
    if (s === 3) return 'bg-yellow-400';
    if (s === 4) return 'bg-emerald-400';
    return 'bg-emerald-500';
});

const strengthTextColor = computed(() => {
    const s = passwordStrength.value;
    if (s === null) return '';
    if (s <= 1) return 'text-red-500';
    if (s === 2) return 'text-orange-500';
    if (s === 3) return 'text-yellow-600';
    if (s === 4) return 'text-emerald-500';
    return 'text-emerald-600';
});

const canSubmit = computed(() => {
    return form.current_password && form.password && form.password_confirmation && passwordsMatch.value === true && !form.processing;
});

const openConfirm = () => {
    if (!canSubmit.value) return;
    showConfirmModal.value = true;
};

const closeConfirm = () => {
    showConfirmModal.value = false;
};

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showConfirmModal.value = false;
            showCurrentPassword.value = false;
            showNewPassword.value = false;
            showConfirmPassword.value = false;
        },
        onError: () => {
            showConfirmModal.value = false;
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="bg-white/60 backdrop-blur-md rounded-[2rem] p-8 shadow-sm border border-white/60 relative group">
        <!-- Subtle background glow -->
        <div class="absolute inset-0 bg-gradient-to-br from-primary-100/10 to-transparent rounded-[2rem] pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        
        <header class="flex items-center gap-3 mb-8 relative z-10">
            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h2 class="text-xl font-bold text-gray-900 tracking-tight">
                Ubah Password
            </h2>
        </header>

        <form @submit.prevent="openConfirm" class="space-y-6 relative z-10">
            <!-- Current Password -->
            <div>
                <InputLabel for="current_password" value="PASSWORD SAAT INI" class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2 ml-1" />
                <div class="relative">
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="showCurrentPassword ? 'text' : 'password'"
                        class="input-premium bg-white/80 w-full pr-12"
                        autocomplete="current-password"
                        placeholder="••••••••••••"
                    />
                    <button 
                        type="button" 
                        @click="showCurrentPassword = !showCurrentPassword" 
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 transition-all duration-300 focus:outline-none"
                        tabindex="-1"
                    >
                        <!-- Eye Open -->
                        <svg v-if="!showCurrentPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <!-- Eye Closed -->
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l4.242 4.242M15 12a3 3 0 01-3 3m0 0l6.12 6.12"/></svg>
                    </button>
                </div>
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- New Password -->
                <div>
                    <InputLabel for="password" value="PASSWORD BARU" class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2 ml-1" />
                    <div class="relative">
                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            :type="showNewPassword ? 'text' : 'password'"
                            class="input-premium bg-white/80 w-full pr-12"
                            autocomplete="new-password"
                            placeholder="Masukkan password baru"
                        />
                        <button 
                            type="button" 
                            @click="showNewPassword = !showNewPassword" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 transition-all duration-300 focus:outline-none"
                            tabindex="-1"
                        >
                            <svg v-if="!showNewPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l4.242 4.242M15 12a3 3 0 01-3 3m0 0l6.12 6.12"/></svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password" class="mt-2" />

                    <!-- Password Strength Indicator -->
                    <div v-if="form.password" class="mt-3 space-y-1.5">
                        <div class="flex gap-1">
                            <div v-for="i in 5" :key="i" class="h-1.5 flex-1 rounded-full transition-all duration-500" :class="i <= passwordStrength ? strengthColor : 'bg-gray-200'"></div>
                        </div>
                        <p class="text-[10px] font-bold uppercase tracking-wider transition-colors duration-300" :class="strengthTextColor">{{ strengthLabel }}</p>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <InputLabel for="password_confirmation" value="KONFIRMASI PASSWORD BARU" class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2 ml-1" />
                    <div class="relative">
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            class="input-premium bg-white/80 w-full pr-12"
                            :class="{
                                'ring-2 ring-emerald-400/50 border-emerald-400': passwordsMatch === true,
                                'ring-2 ring-red-400/50 border-red-400': passwordsMatch === false,
                            }"
                            autocomplete="new-password"
                            placeholder="Ulangi password baru"
                        />
                        <button 
                            type="button" 
                            @click="showConfirmPassword = !showConfirmPassword" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 transition-all duration-300 focus:outline-none"
                            tabindex="-1"
                        >
                            <svg v-if="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l4.242 4.242M15 12a3 3 0 01-3 3m0 0l6.12 6.12"/></svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" class="mt-2" />

                    <!-- Match Indicator -->
                    <Transition
                        enter-active-class="transition ease-out duration-300"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-200"
                        leave-to-class="opacity-0 translate-y-1"
                    >
                        <div v-if="passwordsMatch !== null" class="mt-2.5 flex items-center gap-2">
                            <!-- Match icon -->
                            <div v-if="passwordsMatch" class="flex items-center gap-1.5 text-emerald-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                <span class="text-[11px] font-bold uppercase tracking-wider">Password cocok</span>
                            </div>
                            <!-- No match icon -->
                            <div v-else class="flex items-center gap-1.5 text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                <span class="text-[11px] font-bold uppercase tracking-wider">Password tidak cocok</span>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-4 pt-4">
                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 -translate-x-2"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0 translate-x-2"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-bold text-emerald-600 uppercase tracking-wider text-center sm:text-left flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Password Berhasil Diubah!
                    </p>
                </Transition>

                <PrimaryButton type="submit" class="w-full sm:w-auto" :disabled="!canSubmit">
                    Simpan Password
                </PrimaryButton>
            </div>
        </form>
    </section>

    <!-- Confirmation Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showConfirmModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeConfirm"></div>
                
                <!-- Modal Card -->
                <Transition
                    enter-active-class="transition ease-out duration-300 delay-100"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div v-if="showConfirmModal" class="relative glass-card shadow-2xl w-full max-w-md overflow-hidden border border-white/60">
                        <div class="p-8 text-center">
                            <!-- Icon -->
                            <div class="w-16 h-16 bg-white/60 rounded-2xl flex items-center justify-center mx-auto mb-6 ring-1 ring-white/80 shadow-sm backdrop-blur-md">
                                <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>

                            <h3 class="text-xl font-extrabold text-gray-900 mb-2 tracking-tight">Konfirmasi Perubahan Password</h3>
                            <p class="text-sm text-gray-600 font-medium leading-relaxed mb-8">
                                Anda yakin ingin mengubah password akun Anda? Pastikan Anda mengingat password baru yang telah dimasukkan.
                            </p>

                            <div class="flex flex-col gap-3">
                                <button 
                                    type="button"
                                    @click="updatePassword" 
                                    :disabled="form.processing"
                                    class="w-full py-3 px-4 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-bold text-sm rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 shadow-lg shadow-primary-500/30 flex items-center justify-center gap-2 border border-primary-400/30"
                                >
                                    <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>{{ form.processing ? 'Menyimpan...' : 'Ya, Ubah Password' }}</span>
                                </button>
                                <button 
                                    type="button"
                                    @click="closeConfirm" 
                                    class="w-full py-3 px-4 bg-white/50 hover:bg-white/80 text-gray-700 font-bold text-sm rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-300 border border-white/60"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
