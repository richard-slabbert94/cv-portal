<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import CvDocument from '../../Components/CvDocument.vue';

const props = defineProps({
    cv: Object,
    photoUrl: String,
    company: String,
    role: String,
    coverLetter: String,
    preview: Boolean,
});

const tab = ref(props.coverLetter ? 'letter' : 'cv');
const paragraphs = (text) => text.split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean);

const print = () => window.print();
const lock = () => router.post('/lock');
</script>

<template>
    <Head :title="tab === 'letter' ? `Cover letter — ${company}` : 'CV'" />

    <div v-if="preview" class="no-print bg-amber-100 px-4 py-2 text-center text-sm text-amber-900">
        Admin preview — this is exactly what {{ company }} sees. Views are not logged.
        <Link href="/admin/access-codes" class="ml-2 font-semibold underline">Back to admin</Link>
    </div>

    <nav class="no-print sticky top-0 z-10 border-b border-stone-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <p class="text-sm text-stone-600">
                Prepared for <span class="font-semibold text-stone-900">{{ company }}</span>
                <span v-if="role"> · {{ role }}</span>
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <div v-if="coverLetter" class="flex rounded-md border border-stone-300 p-0.5 text-sm">
                    <button
                        v-for="t in [{ id: 'letter', label: 'Cover letter' }, { id: 'cv', label: 'CV' }]"
                        :key="t.id"
                        class="rounded px-3 py-1 font-medium"
                        :class="tab === t.id ? 'bg-teal-700 text-white' : 'text-stone-600 hover:bg-stone-100'"
                        @click="tab = t.id"
                    >
                        {{ t.label }}
                    </button>
                </div>
                <button class="btn-ghost" @click="print">Download PDF</button>
                <button v-if="!preview" class="btn-ghost" @click="lock">Lock</button>
            </div>
        </div>
    </nav>

    <main class="px-4 py-8 print:p-0">
        <div class="print-sheet mx-auto max-w-4xl rounded-lg border border-stone-200 bg-white p-8 shadow-sm sm:p-12">
            <CvDocument v-if="tab === 'cv'" :cv="cv" :photo-url="photoUrl" />

            <article v-else class="mx-auto max-w-2xl text-[15px] leading-relaxed text-stone-800 print:text-[11pt]">
                <header class="mb-8 border-b border-stone-200 pb-5">
                    <h1 class="text-2xl font-bold tracking-tight text-stone-900">{{ cv.name }}</h1>
                    <p class="mt-1 text-sm text-stone-600">{{ [cv.email, cv.phone, cv.location].filter(Boolean).join(' · ') }}</p>
                </header>
                <p v-for="(p, i) in paragraphs(coverLetter)" :key="i" class="mb-4 whitespace-pre-line">{{ p }}</p>
            </article>
        </div>
        <p class="no-print mt-4 text-center text-xs text-stone-500">
            Tip: "Download PDF" opens your browser's print dialog — choose "Save as PDF".
        </p>
    </main>
</template>
