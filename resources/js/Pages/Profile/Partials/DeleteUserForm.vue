<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
  <section class="bg-gradient-to-br from-rose-50/80 to-red-50/40 backdrop-blur-md rounded-[2rem] p-8 border border-rose-100 shadow-sm">
    <header class="flex items-center gap-3 mb-4">
      <svg
        class="w-6 h-6 text-red-600"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2.5"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
      <h2 class="text-xl font-black text-red-700 tracking-tight">
        Hapus Akun
      </h2>
    </header>

    <p class="text-sm font-medium text-red-900/60 leading-relaxed mb-6">
      Setelah akun dihapus, tidak bisa dikembalikan. Pastikan keputusan Anda.
    </p>

    <DangerButton
      class="w-full"
      @click="confirmUserDeletion"
    >
      <svg
        class="w-4 h-4"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      ><path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
      /></svg>
      Hapus Akun
    </DangerButton>

    <Modal
      :show="confirmingUserDeletion"
      @close="closeModal"
    >
      <div class="p-8">
        <!-- Icon -->
        <div class="w-16 h-16 bg-white/60 rounded-2xl flex items-center justify-center mx-auto mb-6 ring-1 ring-white/80 shadow-sm backdrop-blur-md">
          <svg
            class="w-8 h-8 text-red-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          ><path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          /></svg>
        </div>

        <h2 class="text-xl font-extrabold text-gray-900 text-center tracking-tight mb-2">
          Apakah Anda yakin ingin menghapus akun ini?
        </h2>

        <p class="text-sm font-medium text-gray-600 text-center leading-relaxed mb-8">
          Setelah akun Anda dihapus, semua sumber daya dan data yang terkait akan terhapus secara permanen. Masukkan password Anda untuk mengonfirmasi.
        </p>

        <div>
          <InputLabel
            for="password"
            value="Password"
            class="sr-only"
          />
          <TextInput
            id="password"
            ref="passwordInput"
            v-model="form.password"
            type="password"
            class="input-premium border-red-200 bg-white/60 shadow-inner focus:border-red-500 focus:ring-red-200"
            placeholder="Masukkan password Anda"
            @keyup.enter="deleteUser"
          />
          <InputError
            :message="form.errors.password"
            class="mt-2 text-center"
          />
        </div>

        <div class="mt-8 flex flex-col gap-3 w-full">
          <button 
            type="button"
            :disabled="form.processing" 
            class="w-full py-3 px-4 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold text-sm rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 shadow-lg shadow-red-500/30 flex items-center justify-center gap-2 border border-red-400/30"
            @click="deleteUser"
          >
            <svg
              v-if="form.processing"
              class="w-4 h-4 animate-spin"
              fill="none"
              viewBox="0 0 24 24"
            ><circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            /><path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            /></svg>
            <span>{{ form.processing ? 'Menghapus...' : 'Ya, Hapus Akun' }}</span>
          </button>
          <button 
            type="button"
            class="w-full py-3 px-4 bg-white/50 hover:bg-white/80 text-gray-700 font-bold text-sm rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-red-300 border border-white/60" 
            @click="closeModal"
          >
            Batal
          </button>
        </div>
      </div>
    </Modal>
  </section>
</template>
