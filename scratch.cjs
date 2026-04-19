const fs = require('fs');
const path = 'c:/laragon/www/pengaduan-sman11/resources/js/Pages/Complaints/Index.vue';
let content = fs.readFileSync(path, 'utf8');

const startIdx = content.indexOf('<!-- Modal Content -->');
const endIdx = content.indexOf('</Transition>', startIdx);

if (startIdx !== -1 && endIdx !== -1) {
    const newContent = `<!-- Modal Content -->
                    <div class="relative z-10 w-full max-w-5xl mx-4 my-8 bg-[#fef7fe] rounded-[2rem] shadow-2xl overflow-hidden ring-1 ring-black/5" @click.stop>
                        <div class="p-6 md:p-10 max-h-[85vh] overflow-y-auto custom-scrollbar">
                            <!-- Breadcrumb / Back Navigation -->
                            <div class="mb-8 flex items-center gap-2">
                                <button @click="closeDetail" class="w-10 h-10 flex items-center justify-center text-[#685688] hover:bg-[#eadef7] rounded-full transition-colors focus:outline-none">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                                </button>
                                <span class="text-[#625d67] text-sm font-semibold cursor-pointer hover:text-[#35313a] transition-colors" @click="closeDetail">Kembali ke Laporan</span>
                            </div>

                            <!-- Hero Section: Report Title & Status -->
                            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
                                <div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="bg-[#eadef7] text-[#564e63] px-4 py-1.5 rounded-full text-xs font-bold tracking-wide shadow-sm border border-[#dbc5fe]/50">
                                            {{ statusLabel[selectedComplaint.status] }}
                                        </span>
                                        <span class="text-[#625d67] font-bold text-sm tracking-wide">ID #{{ selectedComplaint.id }}</span>
                                    </div>
                                    <h1 class="text-3xl md:text-4xl font-extrabold text-[#35313a] tracking-tight leading-tight" style="font-family: 'Manrope', sans-serif;">
                                        <template v-if="selectedComplaint.category?.parent">{{ selectedComplaint.category.parent.name }} > </template>{{ selectedComplaint.title || selectedComplaint.category?.name }}
                                    </h1>
                                    <p class="text-[#625d67] mt-3 flex items-center gap-2 font-medium text-sm">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ formatDateFull(selectedComplaint.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Bento Grid Layout -->
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                <!-- Left Column: Details (8 cols) -->
                                <div class="md:col-span-8 space-y-6">
                                    <!-- Problem Description -->
                                    <section class="bg-white p-7 rounded-[1.5rem] shadow-sm border border-[#ede6f0]">
                                        <h2 class="text-lg font-bold text-[#35313a] mb-4" style="font-family: 'Manrope', sans-serif;">Deskripsi Masalah</h2>
                                        <div class="bg-[#f3ebf5] p-5 rounded-xl text-[#35313a] leading-relaxed italic border-l-4 border-[#685688] font-medium text-sm shadow-inner">
                                            "{{ selectedComplaint.description }}"
                                        </div>
                                    </section>

                                    <!-- Attached Photo -->
                                    <section v-if="selectedComplaint.image_paths?.length > 0 || selectedComplaint.image_path" class="bg-white p-7 rounded-[1.5rem] border border-[#ede6f0] shadow-sm">
                                        <h2 class="text-lg font-bold text-[#35313a] mb-4" style="font-family: 'Manrope', sans-serif;">Foto Lampiran</h2>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <template v-if="selectedComplaint.image_paths?.length > 0">
                                                <div v-for="(imgPath, idx) in selectedComplaint.image_paths" :key="idx" class="overflow-hidden rounded-xl bg-[#f3ebf5] aspect-video relative group cursor-zoom-in shadow-sm hover:shadow-md transition-shadow" @click.stop="openLightbox(selectedComplaint.image_paths.map(p => '/storage/' + p), idx)">
                                                    <img :src="'/storage/' + imgPath" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                                                    <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition-colors duration-500"></div>
                                                </div>
                                            </template>
                                            <template v-else-if="selectedComplaint.image_path">
                                                <div class="overflow-hidden rounded-xl bg-[#f3ebf5] aspect-video relative group cursor-zoom-in shadow-sm hover:shadow-md transition-shadow" @click.stop="openLightbox(['/storage/' + selectedComplaint.image_path], 0)">
                                                    <img :src="'/storage/' + selectedComplaint.image_path" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                                                    <div class="absolute inset-0 bg-black/5 group-hover:bg-transparent transition-colors duration-500"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </section>

                                    <!-- Admin Response -->
                                    <section v-if="selectedComplaint.admin_response" class="bg-[#f3ebf5] p-7 rounded-[1.5rem] border border-[#dbc5fe] shadow-sm relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-32 h-32 bg-[#dbc5fe] rounded-full blur-3xl opacity-30 -mr-10 -mt-10"></div>
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-3 mb-6">
                                                <div class="bg-[#dbc5fe] p-2.5 rounded-xl text-[#685688] shadow-sm border border-white/50">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                                </div>
                                                <h2 class="text-lg font-bold text-[#35313a]" style="font-family: 'Manrope', sans-serif;">Respon Admin</h2>
                                            </div>
                                            <div class="space-y-4">
                                                <div class="flex gap-4">
                                                    <div class="w-10 h-10 rounded-full border-2 border-white shadow-sm bg-gradient-to-br from-[#685688] to-[#7d516d] flex items-center justify-center text-white font-bold flex-shrink-0 text-sm">
                                                        A
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="text-[#35313a] font-bold text-sm mb-1.5 flex items-center flex-wrap gap-2">
                                                            Administrator 
                                                            <span class="text-[#625d67] font-medium text-xs">{{ formatDateFull(selectedComplaint.updated_at) }}</span>
                                                        </p>
                                                        <div class="bg-white p-5 rounded-2xl rounded-tl-none shadow-sm text-sm text-[#35313a] leading-relaxed font-medium border border-[#ede6f0]">
                                                            {{ selectedComplaint.admin_response }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <!-- Right Column: Meta & Feedback (4 cols) -->
                                <aside class="md:col-span-4 space-y-6">
                                    <!-- Location Card (NO MAP/IMAGE) -->
                                    <section class="bg-white p-7 rounded-[1.5rem] shadow-sm border border-[#ede6f0]">
                                        <h2 class="text-[11px] font-extrabold text-[#625d67] uppercase tracking-[0.2em] mb-5">Lokasi Kejadian</h2>
                                        <div class="flex items-start gap-4">
                                            <div class="p-2.5 bg-[#f3ebf5] rounded-xl text-[#685688] shrink-0 mt-0.5 shadow-sm border border-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#35313a] text-base leading-snug">{{ selectedComplaint.location }}</p>
                                                <p v-if="selectedComplaint.location_detail" class="text-xs text-[#7e7983] font-medium mt-1.5 leading-relaxed">{{ selectedComplaint.location_detail }}</p>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Feedback & Rating Card -->
                                    <section v-if="selectedComplaint.status === 'resolved'" class="bg-white p-7 rounded-[1.5rem] shadow-lg shadow-[#dbc5fe]/20 border border-[#dbc5fe]/40">
                                        <h2 class="text-[11px] font-extrabold text-[#625d67] uppercase tracking-[0.2em] mb-6">Penilaian Anda</h2>
                                        
                                        <!-- Already rated -->
                                        <div v-if="selectedComplaint.rating">
                                            <div class="flex justify-center gap-1.5 mb-6">
                                                <svg v-for="star in 5" :key="star" class="w-8 h-8 drop-shadow-sm transition-all" :class="star <= selectedComplaint.rating ? 'text-[#7d516d]' : 'text-[#ede6f0]'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            </div>
                                            <div v-if="selectedComplaint.rating_comment" class="space-y-2 mb-6">
                                                <label class="text-[10px] font-extrabold text-[#625d67] ml-2 uppercase tracking-wide">Komentar Tambahan</label>
                                                <div class="bg-[#f8f1fa] p-4 rounded-2xl text-sm text-[#564e63] leading-relaxed italic font-medium shadow-inner border border-white/50">
                                                    "{{ selectedComplaint.rating_comment }}"
                                                </div>
                                            </div>
                                            <div class="w-full bg-[#f3ebf5] text-[#685688] font-bold py-3 rounded-xl text-center text-xs shadow-sm">
                                                Terima kasih atas feedback Anda
                                            </div>
                                        </div>

                                        <!-- Not yet rated -->
                                        <div v-else @click.stop="initRatingForm(selectedComplaint)">
                                            <template v-if="!ratingForm">
                                                <p class="text-[13px] text-center text-[#564e63] font-medium mb-6 leading-relaxed">Bagaimana penilaian Anda terhadap penanganan aduan ini?</p>
                                                <button class="w-full bg-[#f3ebf5] text-[#685688] font-bold py-3.5 rounded-xl hover:bg-[#685688] hover:text-white transition-all shadow-sm">
                                                    Berikan Penilaian
                                                </button>
                                            </template>
                                            <template v-else>
                                                <div class="flex justify-center gap-1.5 mb-6">
                                                    <button v-for="star in 5" :key="star" type="button" class="focus:outline-none transition-transform hover:scale-110 active:scale-95" @mouseenter="hoverRating = star" @mouseleave="hoverRating = 0" @click="setRating(star)">
                                                        <svg class="w-8 h-8 drop-shadow-sm transition-colors duration-200" :class="star <= getDisplayRating() ? 'text-[#7d516d]' : 'text-[#ede6f0]'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    </button>
                                                </div>
                                                <textarea v-model="ratingForm.rating_comment" class="w-full text-sm border border-transparent bg-[#f8f1fa] text-[#35313a] rounded-2xl p-4 focus:ring-2 focus:ring-[#685688]/30 focus:border-[#685688] resize-none outline-none font-medium placeholder-[#b6afbb] shadow-inner mb-4" rows="3" placeholder="Ceritakan pengalaman Anda... (opsional)"></textarea>
                                                <button type="button" @click="submitRating(selectedComplaint)" :disabled="ratingForm.rating === 0 || ratingForm.processing" class="w-full bg-gradient-to-br from-[#685688] to-[#5c4a7b] text-white font-bold py-3.5 rounded-xl hover:opacity-90 transition-all disabled:opacity-50 shadow-md">
                                                    <span v-if="!ratingForm.processing">Kirim Feedback</span>
                                                    <span v-else class="animate-pulse">Mengirim...</span>
                                                </button>
                                            </template>
                                        </div>
                                    </section>

                                    <!-- Report Metadata -->
                                    <section class="p-4 px-2">
                                        <div class="space-y-3.5">
                                            <div class="flex justify-between items-center text-[13px]">
                                                <span class="text-[#625d67] font-medium">Laporan dibuat</span>
                                                <span class="font-bold text-[#35313a]">{{ formatDate(selectedComplaint.created_at) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center text-[13px]">
                                                <span class="text-[#625d67] font-medium">Terakhir diperbarui</span>
                                                <span class="font-bold text-[#35313a]">{{ formatDate(selectedComplaint.updated_at) }}</span>
                                            </div>
                                            <div v-if="selectedComplaint.estimated_completion_date" class="flex justify-between items-center text-[13px]">
                                                <span class="text-[#625d67] font-medium">Estimasi Selesai</span>
                                                <span class="text-[#685688] font-bold px-2.5 py-0.5 bg-[#dbc5fe]/30 rounded-md">{{ formatEstDate(selectedComplaint.estimated_completion_date) }}</span>
                                            </div>
                                        </div>
                                    </section>
                                </aside>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>`;
            
    const finalContent = content.substring(0, startIdx) + newContent + content.substring(endIdx + 13);
    fs.writeFileSync(path, finalContent, 'utf8');
    console.log('Successfully updated the file.');
} else {
    console.log('Could not find tags');
}
