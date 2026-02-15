<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Mail, Lock, ArrowRight, ShieldCheck, KeyRound } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 relative overflow-hidden font-sans">
        <!-- Background Artistic Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-indigo-100/30 rounded-full blur-[160px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-emerald-100/20 rounded-full blur-[140px]"></div>
        </div>

        <div class="w-full max-w-md bg-white rounded-3xl p-6 md:p-10 relative z-10 border border-slate-100 shadow-sm">
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-indigo-600 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-xl shadow-indigo-100 transition-all duration-700 ease-out group">
                    <span class="text-4xl font-bold text-white tracking-tighter group-hover:scale-110 transition-transform">F.</span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 leading-tight mb-2">Secure Reset</h1>
                <p class="text-sm font-medium text-slate-500 max-w-[280px] mx-auto leading-relaxed">Establish your new credentials to regain access.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">Email Address</label>
                    <div class="relative group">
                        <Mail class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                        <input 
                            v-model="form.email"
                            type="email" 
                            required 
                            readonly
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-slate-400 font-semibold text-sm cursor-not-allowed"
                            placeholder="Email address"
                        >
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">New Password</label>
                        <div class="relative group">
                            <Lock class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                            <input 
                                v-model="form.password"
                                type="password" 
                                required 
                                autocomplete="new-password"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm"
                                placeholder="••••••••"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">Confirm New Password</label>
                        <div class="relative group">
                            <ShieldCheck class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                            <input 
                                v-model="form.password_confirmation"
                                type="password" 
                                required 
                                autocomplete="new-password"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm"
                                placeholder="••••••••"
                            >
                        </div>
                    </div>
                </div>

                <div v-if="form.errors.email || form.errors.password" class="p-4 bg-rose-50 border border-rose-100 rounded-2xl">
                    <p v-if="form.errors.email" class="text-[11px] font-bold text-rose-500">{{ form.errors.email }}</p>
                    <p v-if="form.errors.password" class="text-[11px] font-bold text-rose-500 mt-1">{{ form.errors.password }}</p>
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-3 py-4 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-100 transition-all hover:bg-indigo-700 active:scale-95 disabled:opacity-50 group"
                    >
                        <span v-if="form.processing">Updating...</span>
                        <template v-else>
                            Apply New Password <KeyRound class="w-5 h-5 group-hover:rotate-12 transition-transform" />
                        </template>
                    </button>
                </div>
            </form>

            <div class="mt-10 pt-8 border-t border-slate-50 text-center">
                <p class="text-[10px] font-bold text-slate-400 tracking-tight">
                    Finance Terminal &copy; 2026. Security Protocol v1.4.2
                </p>
            </div>
        </div>
    </div>
</template>
