<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash);
const nav = [
    { href: '/admin/access-codes', label: 'Access codes' },
    { href: '/admin/cv-versions', label: 'CV versions' },
];
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div class="flex items-center gap-6">
                    <span class="font-bold tracking-tight">CV Admin</span>
                    <nav class="flex gap-1 text-sm">
                        <Link
                            v-for="item in nav"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-md px-3 py-1.5 font-medium"
                            :class="page.url.startsWith(item.href) ? 'bg-stone-100 text-stone-900' : 'text-stone-600 hover:text-stone-900'"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>
                </div>
                <button class="text-sm text-stone-600 hover:text-stone-900" @click="router.post('/admin/logout')">Log out</button>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <div v-if="flash.success" class="mb-6 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                {{ flash.success }}
                <span v-if="flash.pin">
                    PIN: <code class="rounded bg-white px-2 py-0.5 font-mono font-bold tracking-widest">{{ flash.pin }}</code>
                </span>
            </div>
            <slot />
        </main>
    </div>
</template>
