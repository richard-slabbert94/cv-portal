<script setup>
defineProps({
    cv: { type: Object, required: true },
    photoUrl: { type: String, default: null },
});

const dates = (item) => [item.start, item.end].filter(Boolean).join(' – ');
</script>

<template>
    <article class="text-[15px] leading-relaxed text-stone-800 print:text-[10.5pt]">
        <header class="flex flex-col items-start gap-4 border-b border-stone-200 pb-5 sm:flex-row sm:items-center sm:gap-6 print:flex-row print:items-center print:gap-6">
            <img v-if="photoUrl" :src="photoUrl" :alt="cv.name" class="h-24 w-24 shrink-0 rounded-full object-cover ring-1 ring-stone-200 print:h-[26mm] print:w-[26mm]" />
            <div class="min-w-0">
                <h1 class="text-3xl font-bold tracking-tight text-stone-900">{{ cv.name }}</h1>
                <p v-if="cv.headline" class="mt-1 text-lg font-medium text-teal-800">{{ cv.headline }}</p>
                <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-stone-600">
                    <span v-if="cv.location">{{ cv.location }}</span>
                    <a v-if="cv.email" :href="`mailto:${cv.email}`" class="hover:text-teal-700">{{ cv.email }}</a>
                    <span v-if="cv.phone">{{ cv.phone }}</span>
                    <a v-for="link in cv.links" :key="link.url" :href="link.url" target="_blank" rel="noopener" class="hover:text-teal-700">
                        {{ link.label }}
                    </a>
                </p>
            </div>
        </header>

        <section v-if="cv.summary" class="mt-5">
            <p class="text-stone-700">{{ cv.summary }}</p>
        </section>

        <section v-if="cv.highlights.length" class="avoid-break mt-5 rounded-md border-l-4 border-teal-700 bg-teal-50 px-4 py-3 print:bg-transparent">
            <h2 class="text-xs font-bold tracking-widest text-teal-900 uppercase">Key achievements</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 marker:text-teal-700">
                <li v-for="(h, i) in cv.highlights" :key="i">{{ h }}</li>
            </ul>
        </section>

        <section v-if="cv.experience.length" class="mt-7">
            <h2 class="section-title">Experience</h2>
            <div v-for="(job, i) in cv.experience" :key="i" class="avoid-break mt-4 first:mt-3">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                    <h3 class="font-semibold text-stone-900">
                        {{ job.role }} <span class="font-normal text-stone-500">·</span> {{ job.company }}
                    </h3>
                    <span class="text-sm text-stone-500 tabular-nums">{{ dates(job) }}</span>
                </div>
                <p v-if="job.location || job.summary" class="text-sm text-stone-500 italic">
                    {{ [job.location, job.summary].filter(Boolean).join(' — ') }}
                </p>
                <ul v-if="job.bullets?.length" class="mt-2 list-disc space-y-1 pl-5 marker:text-stone-400">
                    <li v-for="(b, j) in job.bullets" :key="j">{{ b }}</li>
                </ul>
                <p v-if="job.tech?.length" class="mt-2 flex flex-wrap gap-1.5">
                    <span v-for="t in job.tech" :key="t" class="rounded bg-stone-100 px-2 py-0.5 text-xs text-stone-700 print:border print:border-stone-300">{{ t }}</span>
                </p>
            </div>
        </section>

        <section v-if="cv.projects.length" class="mt-7">
            <h2 class="section-title">Projects</h2>
            <div v-for="(p, i) in cv.projects" :key="i" class="avoid-break mt-3">
                <h3 class="font-semibold text-stone-900">
                    <a v-if="p.url" :href="p.url" target="_blank" rel="noopener" class="hover:text-teal-700">{{ p.name }} ↗</a>
                    <template v-else>{{ p.name }}</template>
                </h3>
                <p v-if="p.description">{{ p.description }}</p>
            </div>
        </section>

        <div class="mt-7 grid gap-7 sm:grid-cols-2 print:grid-cols-2">
            <section v-if="cv.skills.length" class="avoid-break">
                <h2 class="section-title">Skills</h2>
                <dl class="mt-3 space-y-2">
                    <div v-for="(s, i) in cv.skills" :key="i">
                        <dt class="text-sm font-semibold text-stone-900">{{ s.group }}</dt>
                        <dd class="text-sm">{{ s.items.join(', ') }}</dd>
                    </div>
                </dl>
            </section>

            <div class="space-y-7">
                <section v-if="cv.education.length" class="avoid-break">
                    <h2 class="section-title">Education</h2>
                    <div v-for="(e, i) in cv.education" :key="i" class="mt-3">
                        <h3 class="text-sm font-semibold text-stone-900">{{ e.qualification }}</h3>
                        <p class="text-sm">{{ e.institution }}<span v-if="dates(e)" class="text-stone-500"> · {{ dates(e) }}</span></p>
                        <p v-if="e.notes" class="text-sm text-stone-600">{{ e.notes }}</p>
                    </div>
                </section>

                <section v-if="cv.certifications.length" class="avoid-break">
                    <h2 class="section-title">Certifications</h2>
                    <ul class="mt-3 space-y-1 text-sm">
                        <li v-for="(c, i) in cv.certifications" :key="i">
                            <span class="font-semibold text-stone-900">{{ c.name }}</span>
                            <span class="text-stone-500">{{ [c.issuer, c.year].filter(Boolean).join(', ') ? ` — ${[c.issuer, c.year].filter(Boolean).join(', ')}` : '' }}</span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <section v-if="cv.references?.length" class="mt-7">
            <h2 class="section-title">References</h2>
            <div class="mt-3 grid gap-x-7 gap-y-4 sm:grid-cols-2 print:grid-cols-2">
                <div v-for="(r, i) in cv.references" :key="i" class="avoid-break text-sm">
                    <h3 class="font-semibold text-stone-900">{{ r.name }}</h3>
                    <p v-if="r.title || r.company" class="text-stone-700">{{ [r.title, r.company].filter(Boolean).join(', ') }}</p>
                    <p v-if="r.relationship" class="text-stone-500 italic">{{ r.relationship }}</p>
                    <p v-if="r.email || r.phone" class="mt-0.5 flex flex-wrap gap-x-3 text-stone-600">
                        <a v-if="r.email" :href="`mailto:${r.email}`" class="hover:text-teal-700">{{ r.email }}</a>
                        <span v-if="r.phone">{{ r.phone }}</span>
                    </p>
                </div>
            </div>
        </section>
    </article>
</template>

<style scoped>
@reference '../../css/app.css';

.section-title {
    @apply border-b border-stone-200 pb-1 text-xs font-bold tracking-widest text-stone-500 uppercase;
}
</style>
