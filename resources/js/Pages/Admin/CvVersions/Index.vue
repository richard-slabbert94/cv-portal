<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({ versions: Array });
</script>

<template>
    <Head title="CV versions" />
    <AdminLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">CV versions</h1>
                <p class="text-sm text-stone-600">Keep a complete "Master", then duplicate and trim it for each type of role.</p>
            </div>
            <Link href="/admin/cv-versions/create" class="btn">New blank version</Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="v in versions" :key="v.id" class="card flex flex-col">
                <h2 class="font-semibold">{{ v.name }}</h2>
                <p class="mt-1 line-clamp-2 text-sm text-stone-600">{{ v.headline || '—' }}</p>
                <p class="mt-3 text-xs text-stone-500">Used by {{ v.access_codes_count }} code(s) · updated {{ v.updated_at }}</p>
                <div class="mt-4 flex gap-2 pt-2">
                    <Link :href="`/admin/cv-versions/${v.id}/edit`" class="btn-ghost">Edit</Link>
                    <button class="btn-ghost" @click="router.post(`/admin/cv-versions/${v.id}/duplicate`)">Duplicate</button>
                    <button v-if="!v.access_codes_count" class="btn-danger ml-auto" @click="router.delete(`/admin/cv-versions/${v.id}`)">Delete</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
