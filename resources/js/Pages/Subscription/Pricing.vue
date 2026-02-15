<script setup>
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
import { ref, computed, onMounted } from 'vue';
import { 
    Check, Zap, Rocket, Crown, ArrowRight, 
    ShieldCheck, Sparkles, CheckCircle2
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const page = usePage();
const __ = (key, replacements = {}) => {
    let translation = page.props.translations?.[key] || key;
    Object.keys(replacements).forEach(r => {
        translation = translation.replace(`:${r}`, replacements[r]);
    });
    return translation;
};

const props = defineProps({
    plans: Array,
    intended_plan: String,
    auth: Object,
    is_premium: Boolean,
    subscription_until: String,
    current_plan_id: Number,
    days_remaining: Number,
});

const promoCode = ref('');
const promoError = ref('');
const isProcessing = ref(false);

const user = computed(() => props.auth?.user || page.props.auth?.user);

const formatPrice = (price) => {
    if (price === 0) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
};

const checkout = (plan) => {
    if (!user.value) {
        router.visit(route('login'));
        return;
    }

    router.visit(route('subscription.checkout.index', { plan }));
};

onMounted(() => {
    // Auto-trigger disabled to allow promo code entry
});

const plansData = [
    {
        id: 1,
        name: 'Starter',
        period: '/forever',
        description: __('plan_starter_desc'),
        icon: CheckCircle2,
        color: 'slate',
        features: [
            __('feature_3_wallets'),
            __('feature_3_categories'),
            __('feature_3_months'),
            __('feature_dashboard'),
            __('feature_standard_budgeting')
        ],
        buttonText: __('get_started')
    },
    {
        id: 2,
        name: 'Professional',
        period: '/month',
        description: __('plan_pro_desc'),
        icon: Rocket,
        color: 'indigo',
        features: [
            __('feature_unlimited_wallets'),
            __('feature_ai_recs'),
            __('feature_full_history'),
            __('feature_exports'),
            __('feature_ledger')
        ],
        buttonText: __('go_professional')
    },
    {
        id: 3,
        name: 'Master',
        period: '/year',
        description: __('plan_master_desc'),
        icon: Crown,
        color: 'emerald',
        popular: true,
        features: [
            __('feature_pro_included'),
            __('feature_unlimited_categories'),
            __('feature_priority_support'),
            __('feature_verified_badge'),
            __('feature_save_17')
        ],
        buttonText: __('upgrade_to_master')
    },
    {
        id: 4,
        name: 'Lifetime',
        period: 'once',
        description: __('plan_lifetime_desc'),
        icon: Zap,
        color: 'purple',
        features: [
            __('feature_master_included'),
            __('feature_lifetime_updates'),
            __('feature_founder_status'),
            __('feature_early_access'),
            __('feature_one_time')
        ],
        buttonText: __('buy_lifetime')
    }
];

const plans = computed(() => {
    // Ensure we handle both array and object formats (Inertia sometimes sends collections as objects)
    const dbPlans = Array.isArray(props.plans) ? props.plans : Object.values(props.plans || {});
    
    return plansData.map(pData => {
        // Robust lookup with ID normalization
        const dbPlan = dbPlans.find(p => 
            parseInt(p.id) === parseInt(pData.id)
        );
        
        return {
            ...pData,
            name: dbPlan?.name ?? pData.name,
            price: formatPrice(dbPlan?.price ?? 0),
            rawPrice: dbPlan?.price ?? 0
        };
    });
});

const selectedPlan = computed(() => plans.value.find(p => String(p.id) === String(props.intended_plan)) || plans.value[0]);

const marketingInsight = computed(() => {
    switch (Number(props.current_plan_id)) {
        case 1: 
            return {
                title: __('boost_growth'),
                text: __('go_professional_desc'),
                cta: __('go_professional'),
                targetId: 2
            };
        case 2:
            return {
                title: __('level_up_master'),
                text: __('level_up_master_desc'),
                cta: __('upgrade_to_master'),
                targetId: 3
            };
        case 3:
            return {
                title: __('go_lifetime'),
                text: __('go_lifetime_desc'),
                cta: __('buy_lifetime'),
                targetId: 4
            };
        case 4:
            return {
                title: __('founder_status'),
                text: __('founder_status_desc'),
                cta: __('view_perks'),
                targetId: null
            };
        default:
            return {
                title: __('secure_future'),
                text: __('secure_future_desc'),
                cta: __('browse_plans'),
                targetId: 2
            };
    }
});

const handleUpgrade = () => {
    if (marketingInsight.value.targetId) {
        checkout(marketingInsight.value.targetId);
    }
};
</script>

<template>
    <Head :title="__('premium_membership')" />

    <!-- Dynamic Mesh Gradient Background -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-200/30 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-200/20 rounded-full blur-[120px] animate-delay-1000 animate-pulse"></div>
        <div class="absolute top-[20%] right-[10%] w-[30%] h-[30%] bg-emerald-100/20 rounded-full blur-[120px]"></div>
    </div>

    <!-- Guest / Public Layout -->
    <div v-if="!user" class="min-h-screen bg-transparent text-slate-900 font-sans selection:bg-indigo-100 selection:text-indigo-900 relative z-10">
        <!-- Navigation -->
        <nav class="bg-white border-b border-slate-100 px-6 py-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-vibrant-indigo flex items-center justify-center shadow-lg shadow-indigo-100">
                        <span class="font-bold text-white text-xl">V</span>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-slate-900">Vibe<span class="text-indigo-600">Finance</span></span>
                </Link>
                <div class="flex items-center gap-6">
                    <Link :href="route('login')" class="text-sm font-bold text-slate-600 hover:text-indigo-600 transition-colors">{{ __('login') }}</Link>
                    <Link :href="route('register')" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all hover:scale-[1.02] active:scale-95">
                        {{ __('get_started') }}
                    </Link>
                </div>
            </div>
        </nav>

        <div class="max-w-[1500px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center mb-16">
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-full text-[11px] font-bold mb-4">
                        <Sparkles class="w-3.5 h-3.5" /> {{ __('premium_access') }}
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4 tracking-tight">
                        {{ __('elevate_mastery_start') }} <span class="text-indigo-600">{{ __('financial_mastery') }}</span>
                    </h1>
                    <p class="text-base text-slate-500 max-w-xl mx-auto leading-relaxed">
                        {{ __('unlock_advanced_analytics') }}
                    </p>
                </div>
            </div>

            <div v-if="intended_plan && String(intended_plan) !== '1'" class="max-w-md mx-auto">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-2xl shadow-indigo-100/50 border border-indigo-50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-6 opacity-5 pointer-events-none">
                        <component :is="selectedPlan.icon" class="w-24 h-24" />
                    </div>

                    <div class="relative z-10">
                        <h2 class="text-xl font-bold text-slate-900 mb-1">{{ __('order_summary') }}</h2>
                        <p class="text-xs text-slate-500 font-medium mb-8">{{ __('order_review_desc') }}</p>

                        <div class="flex items-center gap-4 mb-8 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                             <div :class="[
                                'w-12 h-12 rounded-xl flex items-center justify-center shadow-md',
                                selectedPlan.color === 'indigo' ? 'bg-indigo-600 text-white' :
                                selectedPlan.color === 'emerald' ? 'bg-emerald-600 text-white' : 
                                selectedPlan.color === 'purple' ? 'bg-purple-600 text-white' : 'bg-slate-900 text-white'
                            ]">
                                <component :is="selectedPlan.icon" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">{{ __('membership_label', { plan: selectedPlan.name }) }}</h3>
                                <p class="text-[11px] font-semibold text-slate-400">{{ selectedPlan.price }} {{ selectedPlan.period }}</p>
                            </div>
                        </div>

                        <div class="space-y-3 mb-8">
                            <label class="block text-xs font-bold text-slate-500 ml-1">{{ __('promo_code_label') }}</label>
                            <div class="flex gap-2">
                                <input 
                                    v-model="promoCode"
                                    type="text"
                                    class="flex-grow bg-slate-50 border border-slate-200 rounded-xl px-6 py-3.5 text-sm font-bold placeholder-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold"
                                    :placeholder="__('promo_code_placeholder')"
                                >
                            </div>
                            <p v-if="promoError" class="text-[10px] font-bold text-rose-500 ml-1">{{ promoError }}</p>
                        </div>

                        <div class="border-t border-slate-100 pt-6 mb-8">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-400">{{ __('total_pay') }}</span>
                                <span class="text-xl font-bold text-slate-900">{{ selectedPlan.price }}</span>
                            </div>
                            <p class="text-[10px] font-medium text-slate-400 leading-relaxed">{{ __('secure_payment_midtrans') }}</p>
                        </div>

                        <div class="flex flex-col gap-3">
                            <button 
                                @click="checkout(intended_plan)"
                                :disabled="isProcessing"
                                class="w-full py-4 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-2 active:scale-[0.98] disabled:opacity-50"
                            >
                                <span v-if="isProcessing">{{ __('processing') }}</span>
                                <template v-else>
                                    {{ __('complete_payment') }} <ArrowRight class="w-4 h-4" />
                                </template>
                            </button>
                            <Link :href="route('subscription.index')" class="text-center text-xs font-bold text-slate-400 hover:text-indigo-600 transition-colors">
                                {{ __('change_plan') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
                <!-- Pricing Card Loop -->
                <div 
                    v-for="plan in plans" 
                    :key="plan.id"
                    :class="[
                        'relative flex flex-col p-8 rounded-[2.5rem] transition-all duration-500 hover:scale-[1.02] border h-full',
                        plan.popular 
                            ? 'bg-white border-indigo-200 shadow-2xl shadow-indigo-100 ring-2 ring-indigo-500/20' 
                            : 'bg-white border-slate-100 shadow-xl shadow-slate-200/50',
                    ]"
                >
                    <div v-if="plan.popular" class="absolute top-0 right-10 -translate-y-1/2 bg-indigo-600 text-white text-[10px] font-bold px-4 py-1.5 rounded-full mb-4 shadow-lg">
                        {{ __('best_value') }}
                    </div>

                    <div class="mb-8">
                        <div :class="[
                            'w-14 h-14 rounded-2xl flex items-center justify-center mb-6 shadow-lg',
                            plan.color === 'indigo' ? 'bg-indigo-50 text-indigo-600' :
                            plan.color === 'emerald' ? 'bg-emerald-50 text-emerald-600' : 
                            plan.color === 'purple' ? 'bg-purple-50 text-purple-600' : 'bg-slate-50 text-slate-500'
                        ]">
                            <component :is="plan.icon" class="w-7 h-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">{{ plan.name }}</h3>
                        <p class="text-sm font-medium text-slate-500 leading-relaxed line-clamp-2 h-10">{{ plan.description }}</p>
                    </div>

                    <div class="mb-8 flex items-baseline gap-1.5">
                        <span class="text-3xl font-bold text-slate-900 tracking-tight">{{ plan.price }}</span>
                        <span class="text-slate-400 font-bold text-[11px]">{{ plan.period }}</span>
                    </div>

                    <ul class="space-y-2.5 mb-10 flex-grow">
                        <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-3">
                            <div class="mt-0.5 w-5 h-5 bg-emerald-50 rounded-full flex items-center justify-center flex-shrink-0">
                                <Check class="w-3.5 h-3.5 text-emerald-600 stroke-[3]" />
                            </div>
                            <span class="text-sm font-semibold text-slate-600 leading-snug">{{ feature }}</span>
                        </li>
                    </ul>

                    <button 
                        @click="String(plan.id) === '1' ? router.visit(route('register')) : checkout(plan.id)"
                        :class="[
                            'w-full py-4 px-6 rounded-2xl font-bold text-sm flex items-center justify-center gap-3 transition-all duration-300 active:scale-95',
                            plan.popular 
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700' 
                                : 'bg-slate-900 text-white hover:bg-slate-800'
                        ]"
                    >
                        {{ plan.buttonText }} <ArrowRight class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Authenticated / Internal Layout -->
    <Layout v-else>
        <div class="max-w-[1500px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-24 lg:py-12 relative z-10">
            <!-- Compact Hero Layout: 2 Columns for Title & Card -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-20 items-center mb-10 px-0 lg:px-4">
                <!-- Left: Title & Intro -->
                <div class="text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 lg:px-4 lg:py-2 bg-indigo-50 text-indigo-600 rounded-full text-[10px] lg:text-xs font-bold mb-4 lg:mb-6 shadow-sm border border-indigo-100/50">
                        <Sparkles class="w-3.5 h-3.5 lg:w-4 h-4" /> {{ __('premium_access') }}
                    </div>
                    <h1 class="text-3xl md:text-5xl lg:text-[50px] font-bold text-slate-900 mb-4 lg:mb-6 tracking-tight leading-[1.1]">
                        {{ __('elevate_mastery_start') }} <br class="hidden lg:block" />
                        <span class="text-vibrant-indigo bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-indigo-400 capitalize">{{ __('financial_mastery') }}</span>
                    </h1>
                    <p class="text-base lg:text-lg text-slate-500 font-medium max-w-xl mx-auto lg:mx-0 leading-relaxed mb-6">
                        {{ __('unlock_advanced_analytics') }}
                    </p>
                </div>

                <!-- Right: Subscription Intelligence Card (Functional Dashboard) -->
                <div v-if="user" class="relative group">
                    <!-- Subtle Glow Backdrop -->
                    <div class="absolute -inset-4 bg-gradient-to-r from-indigo-500/10 to-purple-500/10 rounded-[2.5rem] lg:rounded-[3rem] blur-3xl opacity-50"></div>
                    
                    <!-- The Card (Intelligence View) -->
                    <div 
                        class="relative w-full max-w-lg mx-auto lg:ml-auto bg-white/70 rounded-[2rem] lg:rounded-[2.8rem] p-5 lg:p-6 text-slate-900 shadow-[0_40px_80px_-20px_rgba(79,70,229,0.1)] border border-white/60 backdrop-blur-3xl overflow-hidden flex flex-col gap-4"
                    >
                        <!-- Top Decor -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-indigo-50/50 rounded-bl-[5rem] -mr-10 -mt-10 blur-2xl"></div>

                        <!-- 1. Header: Quick Profile & Active Status -->
                        <div class="flex items-center justify-between mb-4 relative z-10">
                            <div class="flex items-center gap-4">
                    <div :class="[
                        'w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg transition-all duration-500 perspective-2000',
                        !is_premium ? 'bg-slate-600 shadow-slate-200' :
                        current_plan_id === 2 ? 'bg-indigo-600 shadow-indigo-200 animate-tilt' : 
                        current_plan_id === 3 ? 'bg-emerald-600 shadow-emerald-200 animate-bounce-slow' : 
                        'bg-purple-600 shadow-purple-200 animate-float'
                    ]">
                        <component 
                            :is="!is_premium ? ShieldCheck : (plansData.find(p => p.id === current_plan_id)?.icon || Crown)" 
                            class="w-7 h-7 text-white" 
                            :class="[
                                current_plan_id === 2 ? 'animate-pulse' : 
                                current_plan_id === 3 ? 'drop-shadow-[0_0_8px_rgba(255,255,255,0.5)]' : 
                                current_plan_id === 4 ? 'animate-spin-slow' : ''
                            ]"
                        />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-xl font-bold text-slate-900 leading-none">{{ user.name }}</h3>
                            <span v-if="is_premium" 
                                :class="[
                                    'px-2 py-0.5 text-[9px] font-bold rounded-full border',
                                    current_plan_id === 2 ? 'bg-indigo-50 text-indigo-600 border-indigo-100' :
                                    current_plan_id === 3 ? 'bg-emerald-50 text-emerald-600 border-emerald-100' :
                                    'bg-purple-50 text-purple-600 border-purple-100'
                                ]"
                            >
                                {{ plansData.find(p => p.id === current_plan_id)?.name || 'Pro' }}
                            </span>
                        </div>
                        <p class="text-[11px] font-bold text-slate-400 mb-1">
                            {{ is_premium ? (plansData.find(p => p.id === current_plan_id)?.name + ' ' + __('access_active')) : __('limited_free_access') }}
                        </p>
                    </div>
                </div>
                            <div :class="[
                                'hidden md:flex px-4 py-2 rounded-2xl border text-[10px] font-bold items-center gap-2',
                                is_premium ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-slate-50 border-slate-200 text-slate-400'
                            ]">
                                <div v-if="is_premium" class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                {{ is_premium ? __('active_access') : __('limited_access') }}
                            </div>
                        </div>

                        <!-- 2. Main Stats: Countdown & Expiry -->
            <div class="grid grid-cols-2 gap-4 lg:gap-6 mb-4 relative z-10">
                <!-- Countdown Card -->
                <div class="p-4 lg:p-6 bg-slate-50/50 rounded-3xl lg:rounded-[2rem] border border-slate-200 shadow-sm group/stat hover:bg-white transition-all duration-300">
                    <p class="text-[9px] lg:text-[10px] font-bold text-slate-400 mb-2 tracking-wider">{{ __('time_remaining') }}</p>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl lg:text-4xl font-bold text-slate-900">
                            {{ days_remaining > 3650 ? '∞' : days_remaining }}
                        </span>
                        <span class="text-[10px] lg:text-xs font-bold text-slate-400">
                            {{ days_remaining > 3650 ? '' : __('days') }}
                        </span>
                    </div>
                </div>
                <!-- Expiry Date Card -->
                <div class="p-4 lg:p-6 bg-slate-50/50 rounded-3xl lg:rounded-[2rem] border border-slate-200 shadow-sm group/stat hover:bg-white transition-all duration-300">
                    <p class="text-[9px] lg:text-[10px] font-bold text-slate-400 mb-2 tracking-wider">{{ __('valid_thru') }}</p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm lg:text-base font-bold text-indigo-600">
                            {{ subscription_until || __('no_expiry') }}
                        </span>
                    </div>
                </div>
            </div>

                        <!-- 3. Smart Marketing Nudge (The Insight) -->
                        <div class="relative bg-gradient-to-br from-indigo-900 to-indigo-800 rounded-[2.2rem] p-6 text-white shadow-xl shadow-indigo-100/50 overflow-hidden group/insight">
                            <div class="absolute top-0 right-0 p-4 opacity-10 scale-150 rotate-12 group-hover/insight:scale-[2] transition-transform duration-700">
                                <Sparkles class="w-12 h-12 text-white" />
                            </div>
                            <div class="relative z-10">
                                <h4 class="text-sm font-bold mb-1 flex items-center gap-2">
                                    <Zap class="w-3.5 h-3.5 text-amber-400 fill-amber-400" />
                                    {{ marketingInsight.title }}
                                </h4>
                                <p class="text-xs font-semibold text-indigo-100/80 mb-4 leading-relaxed max-w-[80%]">
                                    {{ marketingInsight.text }}
                                </p>
                                <button 
                                    @click="handleUpgrade"
                                    v-if="current_plan_id != 4"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-white text-indigo-900 rounded-xl text-[10px] font-bold hover:bg-indigo-50 transition-all shadow-md active:scale-95"
                                >
                                    {{ marketingInsight.cta }}
                                    <ArrowRight class="w-4 h-4" />
                                </button>
                                <div v-else class="text-xs font-bold text-emerald-300">
                                    {{ __('best_plan_active') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8 items-stretch">
                <div 
                    v-for="plan in plans" 
                    :key="plan.id"
                    :class="[
                        'relative flex flex-col p-6 lg:p-8 rounded-3xl lg:rounded-[2.5rem] transition-all duration-700 border-t-8 h-full backdrop-blur-2xl glass-card overflow-hidden group',
                        // Top Accent Colors
                        plan.color === 'slate' ? 'border-t-slate-300' :
                        plan.color === 'indigo' ? 'border-t-indigo-500' : 
                        plan.color === 'emerald' ? 'border-t-emerald-500' : 'border-t-purple-500',
                        
                        // Active Plan Styling (Glow & Scale)
                        Number(plan.id) === Number(current_plan_id) 
                            ? (plan.color === 'indigo' ? 'ring-4 ring-indigo-500/10 scale-[1.03] shadow-[0_20px_50px_-12px_rgba(79,70,229,0.3)] z-10' :
                               plan.color === 'emerald' ? 'ring-4 ring-emerald-500/10 scale-[1.03] shadow-[0_20px_50px_-12px_rgba(16,185,129,0.3)] z-10' :
                               plan.color === 'purple' ? 'ring-4 ring-purple-500/10 scale-[1.03] shadow-[0_20px_50px_-12px_rgba(168,85,247,0.3)] z-10' :
                               'ring-4 ring-slate-400/10 scale-[1.03] shadow-2xl z-10')
                            : 'hover:scale-[1.02] shadow-xl shadow-slate-200/50',
                        
                        // Included/Claimed Styling (Soft Success Tint)
                        (current_plan_id && Number(plan.id) < Number(current_plan_id)) ? 'bg-emerald-50/40 border-t-emerald-200/50' : 'bg-white/80'
                    ]"
                >
                    <!-- Background Decor -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 rounded-full opacity-5 pointer-events-none group-hover:scale-150 transition-transform duration-1000"
                        :class="[
                            plan.color === 'indigo' ? 'bg-indigo-500' :
                            plan.color === 'emerald' ? 'bg-emerald-500' : 
                            plan.color === 'purple' ? 'bg-purple-500' : 'bg-slate-500'
                        ]"
                    ></div>

                    <div v-if="Number(plan.id) === Number(current_plan_id)" class="absolute top-4 right-6 bg-indigo-600/10 text-indigo-600 text-[10px] font-bold px-3 py-1 rounded-full border border-indigo-200/50 flex items-center gap-2">
                        <div class="w-1 h-1 bg-indigo-600 rounded-full animate-ping"></div>
                        {{ __('active_plan') }}
                    </div>
                    <div v-else-if="plan.popular && (!current_plan_id || Number(plan.id) > Number(current_plan_id))" class="absolute top-4 right-6 bg-amber-500 text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg shadow-amber-200 border border-amber-400">
                        {{ __('best_value') }}
                    </div>

                    <!-- Card Header -->
                    <div class="mb-8 relative shrink-0">
                        <div :class="[
                            'w-14 h-14 rounded-2xl flex items-center justify-center mb-6 shadow-inner border transition-all duration-500',
                            plan.color === 'indigo' ? 'bg-indigo-50 border-indigo-100 text-indigo-600 shadow-indigo-100/50 animate-tilt' :
                            plan.color === 'emerald' ? 'bg-emerald-50 border-emerald-100 text-emerald-600 shadow-emerald-100/50 animate-bounce-slow' : 
                            plan.color === 'purple' ? 'bg-purple-50 border-purple-100 text-purple-600 shadow-purple-100/50 animate-float' : 'bg-slate-50 border-slate-100 text-slate-500 shadow-slate-100/50'
                        ]">
                            <component :is="plan.icon" class="w-7 h-7" />
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-2 tracking-tight">{{ plan.name }}</h3>
                        <p class="text-[12px] font-bold text-slate-400 leading-snug line-clamp-2">{{ plan.description }}</p>
                    </div>

                    <!-- Price Section (Refined Spacing) -->
                    <div class="mb-7 p-4 bg-slate-50/50 rounded-3xl border border-slate-100/50 flex flex-wrap items-center justify-between gap-2 shrink-0">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-bold text-slate-900 tracking-tight">{{ plan.price }}</span>
                            <span class="text-slate-400 font-bold text-[11px]">{{ plan.period }}</span>
                        </div>
                    </div>

                    <!-- Features (2-Column Compact Grid) -->
                    <div class="mb-10 flex-grow">
                        <h4 class="text-[11px] font-bold text-slate-400 mb-3 ml-1">{{ __('key_benefits') }}</h4>
                        <ul class="grid grid-cols-1 gap-y-2.5 gap-x-2 h-full">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-3 group/feat">
                                <div class="mt-0.5 w-5 h-5 bg-white shadow-sm border border-slate-100 rounded-full flex items-center justify-center flex-shrink-0 transition-colors group-hover/feat:border-indigo-200">
                                    <Check class="w-3 h-3 text-emerald-500 stroke-[4]" />
                                </div>
                                <span class="text-xs font-bold text-slate-600 leading-tight">{{ feature }}</span>
                            </li>
                        </ul>
                    </div>

                    <button 
                        @click="(Number(plan.id) < Number(current_plan_id) || (Number(plan.id) === 4 && Number(current_plan_id) === 4) || Number(plan.id) === 1) ? null : checkout(plan.id)"
                        :disabled="Number(plan.id) < Number(current_plan_id) || (Number(plan.id) === 4 && Number(current_plan_id) === 4) || Number(plan.id) === 1"
                        :class="[
                            'w-full py-4.5 px-6 rounded-2xl font-bold text-[13px] flex items-center justify-center gap-3 transition-all duration-500 active:scale-95 disabled:cursor-not-allowed border-2',
                            Number(plan.id) === Number(current_plan_id) && Number(plan.id) !== 1 && Number(plan.id) !== 4
                                ? 'bg-indigo-50 text-indigo-600 border-indigo-200 hover:bg-indigo-100 shadow-lg shadow-indigo-100/50' 
                                : (Number(plan.id) > Number(current_plan_id) || !current_plan_id
                                    ? (plan.popular 
                                        ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-xl shadow-indigo-200 border-indigo-400 hover:shadow-indigo-300 hover:scale-[1.02]' 
                                        : 'bg-slate-900 text-white border-slate-700 hover:bg-slate-800 shadow-xl shadow-slate-200 hover:shadow-slate-300')
                                    : (Number(plan.id) === 1 || (Number(plan.id) === 4 && Number(current_plan_id) === 4)
                                        ? 'bg-white text-slate-400 border-slate-100 shadow-none opacity-60'
                                        : 'bg-emerald-50 text-emerald-600 border-emerald-100 shadow-none' // Unlocked State
                                      )
                                  )
                        ]"
                    >
                        <template v-if="Number(plan.id) === Number(current_plan_id)">
                            <template v-if="Number(plan.id) === 4">
                                {{ __('lifetime_active') }} <Zap class="w-4 h-4" />
                            </template>
                            <template v-else-if="Number(plan.id) === 1">
                                {{ __('current_free_plan') }} <CheckCircle2 class="w-4 h-4" />
                            </template>
                            <template v-else>
                                {{ __('extend_subscription') }} <component :is="plan.icon" class="w-4 h-4 animate-pulse" />
                            </template>
                        </template>
                        <template v-else-if="current_plan_id && Number(plan.id) < Number(current_plan_id)">
                            {{ __('unlocked_benefit') }} <ShieldCheck class="w-4 h-4 opacity-70" />
                        </template>
                        <template v-else>
                            {{ plan.buttonText }} <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </Layout>
</template>

<style scoped>
.perspective-2000 {
    perspective: 2000px;
}

@keyframes tilt {
    0%, 50%, 100% {
        transform: rotateX(0deg) rotateY(0deg) scale(1);
    }
    25% {
        transform: rotateX(10deg) rotateY(10deg) scale(1.05);
    }
    75% {
        transform: rotateX(-10deg) rotateY(-10deg) scale(1.05);
    }
}

@keyframes bounce-slow {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-10px) scale(1.05); }
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0); }
    25% { transform: translateY(-5px) rotate(2deg); }
    75% { transform: translateY(5px) rotate(-2deg); }
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-tilt {
    animation: tilt 6s infinite ease-in-out;
}

.animate-bounce-slow {
    animation: bounce-slow 4s infinite ease-in-out;
}

.animate-float {
    animation: float 5s infinite ease-in-out;
}

.animate-spin-slow {
    animation: spin-slow 12s infinite linear;
}

.glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 1px solid rgba(255, 255, 255, 0.4);
}

.pricing-card-hover {
    transform: translateY(-8px);
}

/* Custom Scrollbar for modern look */
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.1);
}
</style>
