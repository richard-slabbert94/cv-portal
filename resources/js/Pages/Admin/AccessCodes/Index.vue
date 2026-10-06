<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({ codes: Array });

const destroy = (code) => {
    router.delete(`/admin/access-codes/${code.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Access codes" />
    <AdminLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Access codes</h1>
                <p class="text-sm text-stone-600">One code per application. Each code unlocks one CV version and an optional cover letter.</p>
            </div>
            <Link href="/admin/access-codes/create" class="btn">New access code</Link>
        </div>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-stone-200 bg-stone-50 text-xs tracking-wide text-stone-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Company</th>
                        <th class="px-4 py-3">PIN</th>
                        <th class="px-4 py-3">CV version</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Views</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    <tr v-if="!codes.length">
                        <td colspan="6" class="px-4 py-10 text-center text-stone-500">No access codes yet.</td>
                    </tr>
                    <tr v-for="code in codes" :key="code.id" class="align-top">
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ code.company }}</div>
                            <div class="text-stone-500">{{ code.role }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono font-semibold tracking-widest whitespace-nowrap">{{ code.pin }}</td>
                        <td class="px-4 py-3">
                            {{ code.cv_version }}
                            <div v-if="code.has_cover_letter" class="text-xs text-stone-500">+ cover letter</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                :class="code.is_usable ? 'bg-teal-100 text-teal-800' : 'bg-stone-200 text-stone-600'"
                            >
                                {{ code.is_usable ? 'Active' : code.is_active ? 'Expired' : 'Disabled' }}
                            </span>
                            <div v-if="code.expires_at" class="mt-1 text-xs text-stone-500">until {{ code.expires_at }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="font-semibold">{{ code.view_count }}</span>
                            <div v-if="code.last_viewed_at" class="text-xs text-stone-500">last {{ code.last_viewed_at }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a :href="`/admin/access-codes/${code.id}/preview`" class="btn-ghost mr-2">Preview</a>
                            <Link :href="`/admin/access-codes/${code.id}/edit`" class="btn-ghost mr-2">Edit</Link>
                            <button class="btn-danger" @click="destroy(code)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
