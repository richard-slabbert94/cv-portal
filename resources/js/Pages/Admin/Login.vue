<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/admin/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Admin login" />
    <main class="flex min-h-screen items-center justify-center px-4">
        <form class="card w-full max-w-sm space-y-4" @submit.prevent="submit">
            <h1 class="text-lg font-bold">Admin login</h1>
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" class="input" autocomplete="username" autofocus />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-700">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" class="input" autocomplete="current-password" />
            </div>
            <label class="flex items-center gap-2 text-sm text-stone-600">
                <input v-model="form.remember" type="checkbox" /> Remember me
            </label>
            <button class="btn w-full" :disabled="form.processing">Log in</button>
        </form>
    </main>
</template>
