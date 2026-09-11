<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, User } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

const showPassword = ref(false);
</script>

<template>
    <Head title="Login Karyawan" />

    <div class="theme-karyawan flex min-h-svh bg-background">

        <!-- Panel kiri: navy + gambar gedung -->
        <div class="relative hidden w-1/2 overflow-hidden bg-primary lg:block">
            <div
                class="absolute inset-0 bg-cover bg-center opacity-40"
                style="background-image: url('/images/office-bg.jpg')"
            />
            <div class="absolute inset-0 bg-gradient-to-b from-primary/20 via-primary/15 to-primary/40" />

            <div class="relative flex h-full flex-col justify-between p-10 text-white">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-white">
                        <div class="size-5 rounded bg-primary" />
                    </div>
                    <span class="text-lg font-bold tracking-wide">PT. XXXXX</span>
                </div>

                <div>
                    <h1 class="text-4xl leading-tight font-bold">
                        Sistem Informasi<br />Absensi &amp; Penggajian
                    </h1>
                </div>

                <div class="flex gap-8">
                    <div>
                        <p class="text-2xl font-bold text-accent">100%</p>
                        <p class="text-sm text-white/70">Transparan &amp; Adil</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-accent">Real-time</p>
                        <p class="text-sm text-white/70">Update Terkini</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-accent">Aman</p>
                        <p class="text-sm text-white/70">Enkripsi Data</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel kanan: form login -->
        <div class="flex w-full flex-col justify-center px-6 py-12 sm:px-12 lg:w-1/2 lg:px-20">
            <div class="mx-auto w-full max-w-sm">
                <h2 class="text-2xl font-bold text-foreground">Login ke Akun Anda</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Masukkan username dan password yang telah diberikan oleh admin!
                </p>

                <Form
                    action="/karyawan/login"
                    method="post"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="mt-8 flex flex-col gap-5"
                >
                    <div class="grid gap-2">
                        <Label for="identifier">Username / Email Karyawan</Label>
                        <div class="relative">
                            <User class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                id="identifier"
                                name="identifier"
                                type="text"
                                required
                                autofocus
                                placeholder="username"
                                class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full rounded-md border bg-transparent py-2 pr-3 pl-10 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                            />
                        </div>
                        <InputError :message="errors.identifier" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Kata Sandi</Label>
                        <div class="relative">
                            <Lock class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                placeholder="Masukkan kata sandi"
                                class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex h-10 w-full rounded-md border bg-transparent py-2 pr-10 pl-10 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px]"
                            />
                            <button
                                type="button"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <Label for="remember" class="flex items-center space-x-2">
                        <Checkbox id="remember" name="remember" />
                        <span class="text-sm text-muted-foreground">Ingat username/email dan password</span>
                    </Label>

                    <Button type="submit" class="mt-1 w-full bg-primary hover:bg-primary/90" :disabled="processing">
                        {{ processing ? 'Memproses...' : 'LOGIN' }}
                    </Button>
                </Form>

                <p class="mt-6 text-center text-xs text-muted-foreground">
                    Mengalami kesulitan masuk? Silakan hubungi bagian IT perusahaan.
                </p>
            </div>
        </div>
    </div>
</template>