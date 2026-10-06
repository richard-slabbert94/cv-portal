<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import CvDocument from '../../../Components/CvDocument.vue';

const props = defineProps({ version: Object });

const form = useForm({
    name: props.version.name,
    // Props are reactive proxies, which structuredClone() can't copy.
    content: JSON.parse(JSON.stringify(props.version.content)),
});
const c = computed(() => form.content);
const showPreview = ref(false);

// Textareas edit arrays as "one item per line"; comma fields as "a, b, c".
const lines = (arr) => (arr ?? []).join('\n');
const fromLines = (text) => text.split('\n').map((s) => s.trim()).filter(Boolean);
const csv = (arr) => (arr ?? []).join(', ');
const fromCsv = (text) => text.split(',').map((s) => s.trim()).filter(Boolean);

const blanks = {
    links: () => ({ label: '', url: '' }),
    experience: () => ({ role: '', company: '', location: '', start: '', end: '', summary: '', bullets: [], tech: [] }),
    skills: () => ({ group: '', items: [] }),
    projects: () => ({ name: '', url: '', description: '' }),
    education: () => ({ qualification: '', institution: '', start: '', end: '', notes: '' }),
    certifications: () => ({ name: '', issuer: '', year: '' }),
    references: () => ({ name: '', title: '', company: '', relationship: '', email: '', phone: '' }),
};
const add = (key) => form.content[key].push(blanks[key]());
const remove = (key, i) => form.content[key].splice(i, 1);
const move = (key, i, dir) => {
    const list = form.content[key];
    const j = i + dir;
    if (j < 0 || j >= list.length) return;
    [list[i], list[j]] = [list[j], list[i]];
};

// The photo uploads on its own, so picking one never submits half-edited CV content.
const photoForm = useForm({ photo: null });
const photoInput = ref(null);
const uploadPhoto = (event) => {
    photoForm.photo = event.target.files[0];
    if (!photoForm.photo) return;
    photoForm.post(`/admin/cv-versions/${props.version.id}/photo`, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            photoForm.reset();
            photoInput.value.value = '';
        },
    });
};
const removePhoto = () => {
    if (!confirm('Remove the profile picture?')) return;
    router.delete(`/admin/cv-versions/${props.version.id}/photo`, { preserveScroll: true, preserveState: true });
};

const errorList = computed(() => Object.entries(form.errors));
const submit = () =>
    props.version.id
        ? form.put(`/admin/cv-versions/${props.version.id}`, { preserveScroll: true })
        : form.post('/admin/cv-versions');
</script>

<template>
    <Head :title="version.id ? `Edit — ${version.name}` : 'New CV version'" />
    <AdminLayout>
        <form @submit.prevent="submit">
            <div class="sticky top-0 z-10 -mx-4 mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 bg-stone-100/95 px-4 py-3 backdrop-blur">
                <div class="flex items-center gap-3">
                    <Link href="/admin/cv-versions" class="text-sm text-stone-600 hover:text-stone-900">←</Link>
                    <input v-model="form.name" class="input w-64 font-semibold" placeholder="Version name, e.g. Backend-focused" />
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost" @click="showPreview = !showPreview">{{ showPreview ? 'Hide' : 'Show' }} preview</button>
                    <button class="btn" :disabled="form.processing">Save</button>
                </div>
            </div>

            <div v-if="errorList.length" class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-1 list-disc pl-5">
                    <li v-for="[field, msg] in errorList" :key="field">{{ msg }}</li>
                </ul>
            </div>

            <div class="grid gap-6" :class="showPreview ? 'xl:grid-cols-2' : ''">
                <div class="space-y-6">
                    <section class="card space-y-4">
                        <h2 class="font-semibold">Header</h2>
                        <div class="flex items-center gap-4">
                            <img v-if="version.photo_url" :src="version.photo_url" alt="" class="h-20 w-20 rounded-full object-cover ring-1 ring-stone-200" />
                            <div v-else class="flex h-20 w-20 items-center justify-center rounded-full bg-stone-100 text-xs text-stone-400">No photo</div>
                            <div class="space-y-1">
                                <label class="label">Profile picture</label>
                                <template v-if="version.id">
                                    <div class="flex gap-2">
                                        <label class="btn-ghost cursor-pointer" :class="{ 'opacity-50': photoForm.processing }">
                                            {{ photoForm.processing ? 'Uploading…' : version.photo_url ? 'Replace' : 'Upload' }}
                                            <input ref="photoInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" :disabled="photoForm.processing" @change="uploadPhoto" />
                                        </label>
                                        <button v-if="version.photo_url" type="button" class="btn-danger" @click="removePhoto">Remove</button>
                                    </div>
                                    <p class="text-xs text-stone-500">JPG, PNG or WebP, up to 5 MB. A square headshot works best.</p>
                                    <p v-if="photoForm.errors.photo" class="text-sm text-red-700">{{ photoForm.errors.photo }}</p>
                                </template>
                                <p v-else class="text-xs text-stone-500">Save this version first, then add a photo.</p>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label class="label">Name</label><input v-model="c.name" class="input" /></div>
                            <div><label class="label">Headline</label><input v-model="c.headline" class="input" /></div>
                            <div><label class="label">Location</label><input v-model="c.location" class="input" /></div>
                            <div><label class="label">Email</label><input v-model="c.email" class="input" /></div>
                            <div><label class="label">Phone</label><input v-model="c.phone" class="input" /></div>
                        </div>
                        <div>
                            <label class="label">Links</label>
                            <div v-for="(l, i) in c.links" :key="i" class="mb-2 flex gap-2">
                                <input v-model="l.label" class="input w-40" placeholder="GitHub" />
                                <input v-model="l.url" class="input" placeholder="https://…" />
                                <button type="button" class="btn-danger" @click="remove('links', i)">✕</button>
                            </div>
                            <button type="button" class="btn-ghost" @click="add('links')">+ Link</button>
                        </div>
                    </section>

                    <section class="card space-y-4">
                        <h2 class="font-semibold">Summary & key achievements</h2>
                        <div>
                            <label class="label">Summary (2–3 sentences)</label>
                            <textarea v-model="c.summary" rows="4" class="input" />
                        </div>
                        <div>
                            <label class="label">Key achievements — one per line (3 max)</label>
                            <textarea :value="lines(c.highlights)" rows="4" class="input" @change="c.highlights = fromLines($event.target.value)" />
                        </div>
                    </section>

                    <section class="card space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold">Experience</h2>
                            <button type="button" class="btn-ghost" @click="add('experience')">+ Role</button>
                        </div>
                        <div v-for="(job, i) in c.experience" :key="i" class="space-y-3 rounded-md border border-stone-200 p-4">
                            <div class="flex justify-end gap-1">
                                <button type="button" class="btn-ghost" @click="move('experience', i, -1)">↑</button>
                                <button type="button" class="btn-ghost" @click="move('experience', i, 1)">↓</button>
                                <button type="button" class="btn-danger" @click="remove('experience', i)">Remove</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div><label class="label">Role</label><input v-model="job.role" class="input" /></div>
                                <div><label class="label">Company</label><input v-model="job.company" class="input" /></div>
                                <div><label class="label">Location</label><input v-model="job.location" class="input" /></div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div><label class="label">Start</label><input v-model="job.start" class="input" placeholder="Jan 2022" /></div>
                                    <div><label class="label">End</label><input v-model="job.end" class="input" placeholder="Present" /></div>
                                </div>
                            </div>
                            <div><label class="label">Context line</label><input v-model="job.summary" class="input" /></div>
                            <div>
                                <label class="label">Achievements — one per line</label>
                                <textarea :value="lines(job.bullets)" rows="5" class="input" @change="job.bullets = fromLines($event.target.value)" />
                            </div>
                            <div>
                                <label class="label">Tech (comma separated)</label>
                                <input :value="csv(job.tech)" class="input" @change="job.tech = fromCsv($event.target.value)" />
                            </div>
                        </div>
                    </section>

                    <section class="card space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold">Skills</h2>
                            <button type="button" class="btn-ghost" @click="add('skills')">+ Group</button>
                        </div>
                        <div v-for="(s, i) in c.skills" :key="i" class="flex gap-2">
                            <input v-model="s.group" class="input w-40" placeholder="Backend" />
                            <input :value="csv(s.items)" class="input" placeholder="PHP, Laravel, MySQL" @change="s.items = fromCsv($event.target.value)" />
                            <button type="button" class="btn-danger" @click="remove('skills', i)">✕</button>
                        </div>
                    </section>

                    <section class="card space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold">Projects</h2>
                            <button type="button" class="btn-ghost" @click="add('projects')">+ Project</button>
                        </div>
                        <div v-for="(p, i) in c.projects" :key="i" class="space-y-2 rounded-md border border-stone-200 p-4">
                            <div class="flex gap-2">
                                <input v-model="p.name" class="input" placeholder="Name" />
                                <input v-model="p.url" class="input" placeholder="https://… (optional)" />
                                <button type="button" class="btn-danger" @click="remove('projects', i)">✕</button>
                            </div>
                            <textarea v-model="p.description" rows="2" class="input" placeholder="What it is and why it matters" />
                        </div>
                    </section>

                    <section class="card space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold">Education</h2>
                            <button type="button" class="btn-ghost" @click="add('education')">+ Education</button>
                        </div>
                        <div v-for="(e, i) in c.education" :key="i" class="grid gap-2 rounded-md border border-stone-200 p-4 sm:grid-cols-2">
                            <input v-model="e.qualification" class="input" placeholder="Qualification" />
                            <input v-model="e.institution" class="input" placeholder="Institution" />
                            <input v-model="e.start" class="input" placeholder="Start" />
                            <input v-model="e.end" class="input" placeholder="End" />
                            <input v-model="e.notes" class="input sm:col-span-2" placeholder="Notes (distinction, thesis…)" />
                            <button type="button" class="btn-danger justify-self-start" @click="remove('education', i)">Remove</button>
                        </div>
                    </section>

                    <section class="card space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold">Certifications</h2>
                            <button type="button" class="btn-ghost" @click="add('certifications')">+ Certification</button>
                        </div>
                        <div v-for="(cert, i) in c.certifications" :key="i" class="flex gap-2">
                            <input v-model="cert.name" class="input" placeholder="Name" />
                            <input v-model="cert.issuer" class="input w-40" placeholder="Issuer" />
                            <input v-model="cert.year" class="input w-24" placeholder="Year" />
                            <button type="button" class="btn-danger" @click="remove('certifications', i)">✕</button>
                        </div>
                    </section>

                    <section class="card space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold">References</h2>
                            <button type="button" class="btn-ghost" @click="add('references')">+ Reference</button>
                        </div>
                        <p v-if="!c.references.length" class="text-sm text-stone-500">No references — the section is left off the CV.</p>
                        <div v-for="(r, i) in c.references" :key="i" class="space-y-3 rounded-md border border-stone-200 p-4">
                            <div class="flex justify-end gap-1">
                                <button type="button" class="btn-ghost" @click="move('references', i, -1)">↑</button>
                                <button type="button" class="btn-ghost" @click="move('references', i, 1)">↓</button>
                                <button type="button" class="btn-danger" @click="remove('references', i)">Remove</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div><label class="label">Name</label><input v-model="r.name" class="input" /></div>
                                <div><label class="label">Job title</label><input v-model="r.title" class="input" placeholder="Head of Engineering" /></div>
                                <div><label class="label">Company</label><input v-model="r.company" class="input" /></div>
                                <div><label class="label">Relationship</label><input v-model="r.relationship" class="input" placeholder="Line manager at Acme" /></div>
                                <div><label class="label">Email</label><input v-model="r.email" class="input" /></div>
                                <div><label class="label">Phone</label><input v-model="r.phone" class="input" /></div>
                            </div>
                        </div>
                    </section>
                </div>

                <div v-if="showPreview" class="hidden xl:block">
                    <div class="sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto rounded-lg border border-stone-200 bg-white p-8 shadow-sm">
                        <CvDocument :cv="c" :photo-url="version.photo_url" />
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
