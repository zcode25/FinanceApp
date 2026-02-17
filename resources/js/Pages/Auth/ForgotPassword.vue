<script setup>
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { Mail, ArrowRight, ArrowLeft } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head :title="__('forgot_password_q')" />

    <div class="min-h-screen bg-white flex items-center justify-center p-4 relative overflow-hidden font-sans">
        <!-- Background Artistic Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-indigo-100/30 rounded-full blur-[160px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-purple-100/20 rounded-full blur-[140px]"></div>
            
            <!-- Technical Grid Overlay -->
            <div class="absolute inset-0 opacity-[0.05] pointer-events-none" 
                 style="background-image: linear-gradient(#4f46e5 1px, transparent 1px), linear-gradient(90deg, #4f46e5 1px, transparent 1px); background-size: 40px 40px;">
            </div>
        </div>

        <div class="w-full max-w-md bg-white rounded-[2.5rem] p-8 md:p-12 relative z-10 border border-slate-100 shadow-2xl shadow-slate-200/50">
            <div class="text-center mb-10">
                <Link href="/" class="flex items-center justify-center gap-2 mb-8 group cursor-pointer transition-transform hover:scale-105 active:scale-95 duration-300">
                    <div class="flex items-center justify-center transition-all group-hover:scale-110 duration-300">
                        <img src="/img/logo_vibefinance.png" class="h-6 md:h-7 w-auto object-contain" alt="VibeFinance Logo">
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-lg md:text-xl tracking-tight text-slate-900" style="font-family: Outfit, sans-serif;">
                            <span class="font-semibold">Vibe</span><span class="font-light text-indigo-600">Finance</span>
                        </span>
                        <span class="text-[9px] font-medium text-slate-400">Powered by terasweb.id</span>
                    </div>
                </Link>
                <h1 class="text-3xl font-bold text-slate-900 leading-tight mb-2">{{ __('forgot_password_title') }}</h1>
                <p class="text-base font-medium text-slate-500">{{ __('forgot_password_subtitle') }}</p>
            </div>

            <div v-if="status" class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-600 text-xs font-bold text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('email_address') }}</label>
                    <div class="relative group">
                        <Mail class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                        <input 
                            v-model="form.email"
                            type="email" 
                            required 
                            autofocus
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm"
                            placeholder="name@example.com"
                        >
                    </div>
                    <div v-if="form.errors.email" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-3 py-4 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-xl shadow-indigo-100 transition-all hover:bg-indigo-700 hover:scale-[1.02] active:scale-[0.95] disabled:opacity-50 group/btn overflow-hidden relative"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover/btn:opacity-100 transition-opacity"></div>
                        <span class="relative z-10 flex items-center gap-2">
                            <span v-if="form.processing">{{ __('sending_link') }}...</span>
                            <template v-else>
                                {{ __('send_recovery_link') }} <ArrowRight class="w-5 h-5 group-hover/btn:translate-x-1 transition-transform" />
                            </template>
                        </span>
                    </button>
                </div>

                <div class="text-center pt-2">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-indigo-600 transition-colors group"
                    >
                        <ArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                        {{ __('back_to_login') }}
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
