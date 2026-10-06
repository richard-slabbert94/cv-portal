<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    code: Object,
    suggestedPin: String,
    versions: Array,
    logs: Array,
});

const form = useForm({
    company: props.code?.company ?? '',
    role: props.code?.role ?? '',
    pin: props.code?.pin ?? props.suggestedPin,
    cv_version_id: props.code?.cv_version_id ?? props.versions[0]?.id ?? null,
    cover_letter: props.code?.cover_letter ?? '',
    show_cover_letter: props.code?.show_cover_letter ?? true,
    notes: props.code?.notes ?? '',
    is_active: props.code?.is_active ?? true,
    expires_at: props.code?.expires_at ?? '',
});

const alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
const regenerate = () => {
    const chars = Array.from(crypto.getRandomValues(new Uint32Array(8)), (n) => alphabet[n % alphabet.length]).join('');
    form.pin = `${chars.slice(0, 4)}-${chars.slice(4)}`;
};

const submit = () => (props.code ? form.put(`/admin/access-codes/${props.code.id}`) : form.post('/admin/access-codes'));
</script>

<template>
    <Head :title="code ? `Edit — ${code.company}` : 'New access code'" />
    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold tracking-tight">{{ code ? `Edit access code — ${code.company}` : 'New access code' }}</h1>
            <Link href="/admin/access-codes" class="text-sm text-stone-600 hover:text-stone-900">← Back</Link>
        </div>

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="card space-y-4 lg:col-span-2">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Company</label>
                        <input v-model="form.company" class="input" />
                        <p v-if="form.errors.company" class="mt-1 text-sm text-red-700">{{ form.errors.company }}</p>
                    </div>
                    <div>
                        <label class="label">Role applied for</label>
                        <input v-model="form.role" class="input" />
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between gap-4">
                        <label class="label mb-0">Cover letter</label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.show_cover_letter" type="checkbox" /> Show cover letter
                        </label>
                    </div>
                    <p v-if="!form.show_cover_letter" class="mb-2 text-xs text-stone-500">Hidden — the company only sees the CV. The text below is kept so you can switch it back on.</p>
                    <textarea
                        v-model="form.cover_letter"
                        rows="18"
                        class="input font-serif leading-relaxed"
                        :class="{ 'opacity-50': !form.show_cover_letter }"
                        placeholder="Leave empty to show only the CV. Separate paragraphs with a blank line."
                    />
                    <p v-if="form.errors.cover_letter" class="mt-1 text-sm text-red-700">{{ form.errors.cover_letter }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="card space-y-4">
                    <div>
                        <label class="label">PIN</label>
                        <div class="flex gap-2">
                            <input v-model="form.pin" class="input font-mono font-semibold tracking-widest uppercase" />
                            <button type="button" class="btn-ghost" title="Generate a new PIN" @click="regenerate">↻</button>
                        </div>
                        <p v-if="form.errors.pin" class="mt-1 text-sm text-red-700">{{ form.errors.pin }}</p>
                        <p class="mt-1 text-xs text-stone-500">6–16 letters/digits. Dashes and case are ignored.</p>
                    </div>
                    <div>
                        <label class="label">CV version</label>
                        <select v-model="form.cv_version_id" class="input">
                            <option v-for="v in versions" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                        <p v-if="form.errors.cv_version_id" class="mt-1 text-sm text-red-700">{{ form.errors.cv_version_id }}</p>
                    </div>
                    <div>
                        <label class="label">Expires on (optional)</label>
                        <input v-model="form.expires_at" type="date" class="input" />
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" /> Active
                    </label>
                    <div>
                        <label class="label">Private notes</label>
                        <textarea v-model="form.notes" rows="3" class="input" placeholder="Recruiter name, job ad link, salary range…" />
                    </div>
                    <button class="btn w-full" :disabled="form.processing">{{ code ? 'Save changes' : 'Create access code' }}</button>
                </div>

                <div v-if="logs" class="card">
                    <h2 class="label">Recent views</h2>
                    <p v-if="!logs.length" class="text-sm text-stone-500">Not opened yet.</p>
                    <ul class="divide-y divide-stone-100 text-sm">
                        <li v-for="(log, i) in logs" :key="i" class="py-2">
                            <div class="font-medium">{{ log.at }}</div>
                            <div class="truncate text-xs text-stone-500" :title="log.user_agent">{{ log.ip }} · {{ log.user_agent }}</div>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
