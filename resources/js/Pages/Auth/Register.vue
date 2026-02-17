<script setup>
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { Lock, Mail, User, ArrowRight, CheckCircle2, Rocket, Crown, Zap, ShieldCheck, Sparkles, Check, Eye, EyeOff } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { computed, ref } from 'vue';
import { __ } from '@/Plugins/i18n';
import { getLocalizedPlans } from '@/Utilities/plans';

// Import CSS
import '@/../css/landing.css';

const page = usePage();
const query = computed(() => new URLSearchParams(window.location.search));
const selectedPlanId = computed(() => {
    const plan = query.value.get('plan');
    return plan ? parseInt(plan) : 1;
});

const props = defineProps({
    plans: Array,
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const plans = computed(() => getLocalizedPlans(props.plans));
const selectedPlan = computed(() => plans.value.find(p => p.id === selectedPlanId.value) || plans.value[0]);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    plan: selectedPlanId.value,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="__('register_title')" />

    <div class="min-h-screen bg-white flex flex-col md:flex-row overflow-hidden font-sans">
        <!-- Left Side: Registration Form -->
        <div class="w-full md:w-1/2 lg:w-1/2 p-8 md:p-16 flex flex-col justify-center relative bg-white">
            <div class="max-w-md mx-auto w-full relative z-10">
                <Link href="/" class="flex items-center gap-2 mb-10 group cursor-pointer">
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

                <div class="mb-10">
                    <h1 class="text-3xl md:text-4xl font-bold text-slate-900 leading-tight mb-3">{{ __('register_title') }}</h1>
                    <p class="text-base text-slate-500 font-medium">{{ __('register_subtitle') }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('your_name') }}</label>
                        <div class="relative group">
                            <User class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                            <input 
                                v-model="form.name"
                                type="text" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm"
                                placeholder="John Doe"
                                @input="form.clearErrors('name')"
                            >
                        </div>
                        <div v-if="form.errors.name" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('email_address') }}</label>
                        <div class="relative group">
                            <Mail class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                            <input 
                                v-model="form.email"
                                type="email" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3.5 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm"
                                placeholder="name@example.com"
                                @input="form.clearErrors('email')"
                            >
                        </div>
                        <div v-if="form.errors.email" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('password') }}</label>
                            <div class="relative group">
                                <Lock class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                                <input 
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'" 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-12 py-3.5 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm"
                                    placeholder="••••••••"
                                    @input="form.clearErrors('password')"
                                >
                                <button 
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 transition-colors"
                                >
                                    <Eye v-if="!showPassword" class="w-4 h-4" />
                                    <EyeOff v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <div v-if="form.errors.password" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                                {{ form.errors.password }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('confirm_password') }}</label>
                            <div class="relative group">
                                <Lock class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                                <input 
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'" 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-12 py-3.5 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm"
                                    placeholder="••••••••"
                                    @input="form.clearErrors('password')"
                                >
                                <button 
                                    type="button"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 transition-colors"
                                >
                                    <Eye v-if="!showPasswordConfirmation" class="w-4 h-4" />
                                    <EyeOff v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-3 py-4 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-xl shadow-indigo-100 transition-all hover:bg-indigo-700 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 group/btn overflow-hidden relative"
                        >
                            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover/btn:opacity-100 transition-opacity"></div>
                            <span class="relative z-10 flex items-center gap-2">
                                <span v-if="form.processing">{{ __('creating_account') }}</span>
                                <template v-else>
                                    {{ (selectedPlan && selectedPlan.id !== 1) ? __('create_account_payment') : __('register_now') }} <ArrowRight class="w-5 h-5 group-hover/btn:translate-x-1 transition-transform" />
                                </template>
                            </span>
                        </button>
                    </div>
                </form>

                <div class="mt-10 pt-8 border-t border-slate-100 text-center text-[13px] font-bold text-slate-400">
                    {{ __('already_inner_circle') }} 
                    <Link :href="route('login')" prefetch class="text-indigo-600 hover:text-indigo-700 transition-colors ml-1 underline decoration-2 underline-offset-4">
                        {{ __('login') }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Right Side: Plan Overview -->
        <div class="hidden md:flex w-1/2 lg:w-1/2 bg-slate-50 p-12 lg:p-24 flex-col justify-center relative overflow-hidden">
            <!-- Background Decorations -->
            <div class="absolute top-[-10%] right-[-10%] w-[70%] h-[70%] bg-indigo-100/40 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-5%] left-[10%] w-[50%] h-[50%] bg-purple-100/30 rounded-full blur-[100px]"></div>
            
            <!-- Technical Grid Overlay -->
            <div class="absolute inset-0 opacity-[0.05] pointer-events-none" 
                 style="background-image: linear-gradient(#4f46e5 1px, transparent 1px), linear-gradient(90deg, #4f46e5 1px, transparent 1px); background-size: 40px 40px;">
            </div>

            <div class="relative z-10 max-w-lg">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-indigo-600 rounded-full text-[10px] font-bold mb-10 shadow-sm">
                    <Sparkles class="w-3.5 h-3.5" /> {{ __('selected_membership') }}
                </div>

                <div :class="[
                    'relative flex flex-col p-8 lg:p-10 pb-0 lg:pb-0 rounded-[2.5rem] md:rounded-[3rem] transition-all duration-700 backdrop-blur-2xl bg-white/80 border border-white/40 overflow-hidden group shadow-2xl shadow-slate-200/50',
                    selectedPlan.id === 3 ? 'ring-4 ring-emerald-500/10' : ''
                ]">
                    <!-- Background Decor -->
                    <div :class="[
                        'absolute -top-24 -right-24 w-48 h-48 rounded-full opacity-5 pointer-events-none group-hover:scale-150 transition-transform duration-1000',
                        selectedPlan.color === 'slate' ? 'bg-slate-500' :
                        selectedPlan.color === 'indigo' ? 'bg-indigo-500' :
                        selectedPlan.color === 'emerald' ? 'bg-emerald-500' : 'bg-purple-500'
                    ]"></div>

                    <!-- Card Header -->
                    <div class="mb-10 relative shrink-0 text-left">
                        <div :class="[
                            'w-14 h-14 rounded-2xl flex items-center justify-center mb-5 shadow-inner border transition-all duration-500',
                            selectedPlan.color === 'slate' ? 'bg-slate-50 border-slate-100 text-slate-500 shadow-slate-100/50' :
                            selectedPlan.color === 'indigo' ? 'bg-indigo-50 border-indigo-100 text-indigo-600 shadow-indigo-100/50 animate-tilt' :
                            selectedPlan.color === 'emerald' ? 'bg-emerald-50 border-emerald-100 text-emerald-600 shadow-emerald-100/50 animate-bounce-slow' :
                            'bg-purple-50 border-purple-100 text-purple-600 shadow-purple-100/50 animate-float'
                        ]">
                            <component :is="selectedPlan.icon" class="w-7 h-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-1 tracking-tight">{{ selectedPlan.name }}</h3>
                        <p class="text-[13px] font-bold text-slate-400 leading-snug">{{ selectedPlan.description }}</p>
                    </div>

                    <!-- Price Section -->
                    <div class="mb-8 p-4 bg-slate-50/50 rounded-2xl border border-slate-100/50 flex flex-wrap items-center justify-between gap-3 shrink-0">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-slate-900 tracking-tight">{{ selectedPlan.price }}</span>
                            <span class="text-slate-400 font-bold text-xs">{{ selectedPlan.period }}</span>
                        </div>
                        <div v-if="selectedPlan.popular" class="bg-emerald-600 text-white text-[9px] font-bold px-3 py-1 rounded-full shadow-lg h-fit">
                            {{ __('best_value') }}
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mb-10 text-left">
                        <h4 class="text-[11px] font-bold text-slate-400 mb-3 ml-1 text-left">{{ __('key_benefits') }}</h4>
                        <ul class="space-y-4">
                            <li v-for="feature in selectedPlan.features" :key="feature" class="flex items-start gap-3 group/feat">
                                <div class="mt-0.5 w-5 h-5 bg-white shadow-sm border border-slate-100 rounded-full flex items-center justify-center flex-shrink-0 transition-colors group-hover/feat:border-indigo-200">
                                    <Check class="w-3 h-3 text-emerald-500 stroke-[4]" />
                                </div>
                                <span class="text-[14px] font-bold text-slate-600 leading-tight">{{ feature }}</span>
                            </li>
                        </ul>
                    </div>


                </div>

                <div class="mt-6 flex items-center gap-3 opacity-50">
                    <ShieldCheck class="w-5 h-5 text-slate-400" />
                    <p class="text-[10px] font-semibold text-slate-400 tracking-wide">{{ __('ssl_encryption') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom animations if needed */
</style>
