<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    phone: user.phone,
    email: user.email,
});

const showConfirm = ref(false);

const submit = () => {
    form.patch(route('profile.update'), {
        onSuccess: () => {
            showConfirm.value = false;
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <h2 class="text-xl font-bold text-gray-900 tracking-tight">
                Informasi Pribadi
            </h2>
        </header>

        <form @submit.prevent="showConfirm = true" class="space-y-6 relative z-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Full Name -->
                <div>
                    <InputLabel for="name" value="NAMA LENGKAP" class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2 ml-1" />
                    <TextInput
                        id="name"
                        type="text"
                        class="input-premium bg-white/80 w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Nama lengkap Anda"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- Phone Number -->
                <div>
                    <InputLabel for="phone" value="NOMOR TELEPON" class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2 ml-1" />
                    <TextInput
                        id="phone"
                        type="text"
                        class="input-premium bg-white/80 w-full"
                        v-model="form.phone"
                        required
                        autocomplete="tel"
                        placeholder="+62 8..."
                    />
                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>
            </div>

            <!-- Email Address -->
            <div>
                <InputLabel for="email" value="ALAMAT EMAIL" class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2 ml-1" />
                <TextInput
                    id="email"
                    type="email"
                    class="input-premium bg-white/80 w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="nama@izinlapor.sch.id"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Alamat email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    >
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-emerald-600"
                >
                    Link verifikasi baru telah dikirim ke alamat email Anda.
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-4 pt-4">
                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 -translate-x-2"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0 translate-x-2"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm font-bold text-emerald-600 uppercase tracking-wider text-center sm:text-left">
                        Tersimpan.
                    </p>
                </Transition>

                <PrimaryButton type="submit" class="w-full sm:w-auto" :disabled="form.processing">
                    Simpan Profil
                </PrimaryButton>
            </div>
        </form>

        <!-- Konfirmasi Modals dan Logika Tetap Sama -->
        <Modal :show="showConfirm" @close="showConfirm = false" maxWidth="md">
            <div class="p-8">
                <div class="w-16 h-16 bg-white/60 rounded-2xl flex items-center justify-center mx-auto mb-6 ring-1 ring-white/80 shadow-sm backdrop-blur-md">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                
                <h3 class="text-xl font-extrabold text-gray-900 text-center mb-2 tracking-tight">Simpan Perubahan?</h3>
                <p class="text-sm font-medium text-gray-600 text-center mb-8 leading-relaxed">
                    Apakah Anda yakin ingin memperbarui informasi profil Anda?
                </p>

                <div class="flex flex-col gap-3">
                    <button 
                        type="button"
                        @click="submit" 
                        :disabled="form.processing"
                        class="w-full py-3 px-4 bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white font-bold text-sm rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 shadow-lg shadow-primary-500/30 flex items-center justify-center gap-2 border border-primary-400/30"
                    >
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Ya, Simpan' }}</span>
                    </button>
                    <button 
                        type="button"
                        @click="showConfirm = false" 
                        class="w-full py-3 px-4 bg-white/50 hover:bg-white/80 text-gray-700 font-bold text-sm rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-300 border border-white/60"
                    >
                        Batal
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
