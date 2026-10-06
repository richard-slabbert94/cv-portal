<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const owner = computed(() => usePage().props.owner);
const initials = computed(() =>
    owner.value.name
        .split(/\s+/)
        .filter(Boolean)
        .map((word) => word[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);
const firstName = computed(() => owner.value.name.split(/\s+/)[0]);

const form = useForm({ pin: '' });

const submit = () => form.post('/unlock', { onFinish: () => form.reset('pin') });
</script>

<template>
    <Head title="Private CV" />

    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-teal-700 text-xl font-bold text-white">{{ initials }}</div>
                <h1 class="text-2xl font-bold tracking-tight">{{ owner.name }}</h1>
                <p class="mt-2 text-sm text-stone-600">This CV is private. Enter the access code you were given to view it.</p>
            </div>

            <form class="card space-y-4" @submit.prevent="submit">
                <div>
                    <label for="pin" class="label">Access code</label>
                    <input
                        id="pin"
                        v-model="form.pin"
                        type="text"
                        autocomplete="off"
                        autocapitalize="characters"
                        spellcheck="false"
                        autofocus
                        placeholder="XXXX-XXXX"
                        class="input text-center font-mono text-lg tracking-[0.3em] uppercase"
                    />
                    <p v-if="form.errors.pin" class="mt-2 text-sm text-red-700">{{ form.errors.pin }}</p>
                </div>
                <button type="submit" class="btn w-full" :disabled="form.processing || !form.pin">View CV</button>
            </form>

            <p v-if="owner.contactUrl" class="mt-6 text-center text-xs text-stone-500">
                Don't have a code? Reach out to {{ firstName }} on
                <a :href="owner.contactUrl" class="underline hover:text-teal-700" target="_blank" rel="noopener">{{ owner.contactLabel }}</a>.
            </p>
        </div>
    </main>
</template>
