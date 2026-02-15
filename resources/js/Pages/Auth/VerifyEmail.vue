<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, ArrowRight, LogOut } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Email Verification" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 relative overflow-hidden font-sans">
        <!-- Background Artistic Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-indigo-100/30 rounded-full blur-[160px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-purple-100/20 rounded-full blur-[140px]"></div>
        </div>

        <div class="w-full max-w-md bg-white rounded-3xl p-6 md:p-10 relative z-10 border border-slate-100 shadow-sm">
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-indigo-600 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-xl shadow-indigo-100 transition-all duration-700 ease-out group">
                    <Mail class="w-10 h-10 text-white" />
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 leading-tight mb-4">Verify Your Email</h1>
                <p class="text-sm font-medium text-slate-500 max-w-sm mx-auto leading-relaxed">
                    Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
                </p>
            </div>

            <div v-if="verificationLinkSent" class="mb-8 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl">
                <p class="text-sm font-bold text-emerald-600 text-center">
                    A new verification link has been sent to the email address you provided during registration.
                </p>
            </div>

            <div class="space-y-4">
                <form @submit.prevent="submit">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-3 py-4 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-100 transition-all hover:bg-indigo-700 active:scale-95 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Sending...</span>
                        <template v-else>
                            Resend Verification Email <ArrowRight class="w-5 h-5" />
                        </template>
                    </button>
                </form>

                <div class="flex items-center justify-center pt-4">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors"
                    >
                        <LogOut class="w-4 h-4" />
                        Sign Out
                    </Link>
                </div>
            </div>

            <div class="mt-10 pt-8 border-t border-slate-50 text-center">
                <p class="text-xs font-bold text-slate-400 leading-relaxed">
                    If you didn't receive the email, we will gladly send you another.
                </p>
            </div>
        </div>
    </div>
</template>
