<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Login Admin',
        description: 'Masuk untuk mengelola TimePay',
    },
});
</script>

<template>
    <Head title="Login Admin" />

    <Form
        action="/admin/login"
        method="post"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-2">
            <Label for="username">
                Username
            </Label>

            <Input
                id="username"
                type="text"
                name="username"
                required
                autofocus
                :tabindex="1"
                autocomplete="username"
                placeholder="Masukkan username"
            />

            <InputError :message="errors.username" />
        </div>

        <div class="grid gap-2">
            <Label for="password">
                Password
            </Label>

            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="Masukkan password"
            />

            <InputError :message="errors.password" />
        </div>


        <Button
            type="submit"
            class="mt-2 w-full"
            :tabindex="4"
            :disabled="processing"
        >
            <Spinner v-if="processing" />
            Login
        </Button>
    </Form>
</template>