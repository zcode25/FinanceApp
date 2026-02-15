<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { route } from 'ziggy-js';
import { ArrowRight, CheckCircle2, Zap, Wallet, BarChart3, Target, Check, Rocket, Crown, Sparkles, ShieldCheck, Globe, TrendingUp, TrendingDown, CreditCard, Power, Pencil, GripVertical, Lightbulb, Flame, Calendar, ChartPie, Trash2, ChartColumn, Pen, EllipsisVertical, CircleCheck, Lock, Github, Twitter, Instagram } from 'lucide-vue-next';
import draggable from 'vuedraggable';
import LandingLanguageSwitcher from '@/Shared/LandingLanguageSwitcher.vue';
import { __ } from '@/Plugins/i18n';

const props = defineProps({
    plans: Array,
});

const formatPrice = (price) => {
    if (price === 0) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
};

const page = usePage();
const currentLocale = computed(() => page.props.locale || 'en');

const switchLanguage = (lang) => {
    router.post(route('locale.update'), {
        locale: lang
    }, {
        preserveScroll: true,
        onSuccess: () => {
            window.location.reload();
        }
    });
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Mockup Wallets for Draggable Interactive List
const mockupWallets = ref([
    { 
        id: 1,
        name: 'Bank Utama', 
        balance: 'Rp 15.000.000', 
        accountNumber: '987654321',
        iconColor: 'bg-indigo-100 text-indigo-600 border-indigo-200',
        cardTheme: 'bg-gradient-to-br from-white to-indigo-50 border-2 border-indigo-200 shadow-xl shadow-indigo-100/50',
        currency: 'IDR'
    },
    { 
        id: 2,
        name: 'Dompet Digital', 
        balance: 'Rp 2.450.000', 
        accountNumber: '08123456789',
        iconColor: 'bg-orange-100 text-orange-600 border-orange-200',
        cardTheme: 'bg-gradient-to-br from-white to-orange-50 border-2 border-orange-200 shadow-xl shadow-orange-100/50',
        currency: 'IDR'
    },
    { 
        id: 3,
        name: 'Uang Tunai', 
        balance: 'Rp 500.000', 
        accountNumber: 'Kas Tunai',
        iconColor: 'bg-emerald-100 text-emerald-600 border-emerald-200',
        cardTheme: 'bg-gradient-to-br from-white to-emerald-50 border-2 border-emerald-200 shadow-xl shadow-emerald-100/50',
        currency: 'IDR'
    }
]);

const plansData = [
    {
        id: 1,
        name: 'Starter',
        period: '/forever',
        description: __('starter_plan_desc'),
        icon: CheckCircle2,
        color: 'slate',
        features: [
            __('feature_3_wallets'),
            __('feature_3_categories'),
            __('feature_3_months'),
            __('feature_dashboard'),
            __('feature_standard_budgeting')
        ],
        buttonText: 'get_started'
    },
    {
        id: 2,
        name: 'Professional',
        period: '/month',
        description: __('pro_plan_desc'),
        icon: Rocket,
        color: 'indigo',
        features: [
            __('feature_unlimited_wallets'),
            __('feature_ai_recs'),
            __('feature_full_history'),
            __('feature_exports'),
            __('feature_ledger')
        ],
        buttonText: 'get_professional'
    },
    {
        id: 3,
        name: 'Master',
        period: '/year',
        description: __('master_plan_desc'),
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
        buttonText: 'get_master'
    },
    {
        id: 4,
        name: 'Lifetime',
        period: 'once',
        description: __('lifetime_plan_desc'),
        icon: Zap,
        color: 'purple',
        features: [
            __('feature_master_included'),
            __('feature_lifetime_updates'),
            __('feature_founder_status'),
            __('feature_early_access'),
            __('feature_one_time')
        ],
        buttonText: 'buy_lifetime'
    }
];

const testimonialsData = [
    {
        name: __('testimonial_1_name'),
        role: __('testimonial_1_role'),
        quote: __('testimonial_1_quote'),
        avatar: '/images/testimonials/testimonial_avatar_1_1771023953600.png',
        color: 'indigo'
    },
    {
        name: __('testimonial_2_name'),
        role: __('testimonial_2_role'),
        quote: __('testimonial_2_quote'),
        avatar: '/images/testimonials/testimonial_avatar_2_1771023970669.png',
        color: 'emerald'
    },
    {
        name: __('testimonial_3_name'),
        role: __('testimonial_3_role'),
        quote: __('testimonial_3_quote'),
        avatar: '/images/testimonials/testimonial_avatar_3_1771023989829.png',
        color: 'slate'
    },
    {
        name: __('testimonial_4_name'),
        role: __('testimonial_4_role'),
        quote: __('testimonial_4_quote'),
        avatar: '/images/testimonials/testimonial_avatar_4_1771024015993.png',
        color: 'purple'
    }
];

const plans = computed(() => {
    return plansData.map(planData => {
        const dbPlan = props.plans?.find(p => parseInt(p.id) === parseInt(planData.id));
        return {
            ...planData,
            name: dbPlan?.name ?? planData.name,
            price: formatPrice(dbPlan?.price ?? 0),
            rawPrice: dbPlan?.price ?? 0,
            buttonText: __(planData.buttonText)
        };
    });
});

const features = [
    {
        title: 'Smart Wallets',
        description: 'Manage all your bank accounts, digital wallets, and physical cash in one unified place.',
        icon: Wallet,
        color: 'text-indigo-600',
        bg: 'bg-indigo-50'
    },
    {
        title: 'Deep Analytics',
        description: 'Understand every penny with sophisticated charts and AI-driven spending insights.',
        icon: BarChart3,
        color: 'text-purple-600',
        bg: 'bg-purple-50'
    },
    {
        title: 'Financial Goals',
        description: 'Set ambitious targets and track your progress with smart milestones and projections.',
        icon: Target,
        color: 'text-emerald-600',
        bg: 'bg-emerald-50'
    }
];

// Mockup Data for Dynamic Line Chart
const incomeData = [40, 60, 45, 80, 55, 90, 70, 85, 50, 75, 65, 85];
const expenseData = [30, 40, 35, 60, 45, 70, 55, 65, 40, 55, 50, 60];

// Helper to generate smooth SVG path from data points
const smoothPath = (data) => {
    if (!data.length) return "";
    
    const width = 1000;
    const height = 300;
    const padding = 20;
    const innerHeight = height - padding * 2;
    const stepX = width / (data.length - 1);
    
    const points = data.map((val, i) => ({
        x: i * stepX,
        y: height - (val / 100) * innerHeight - padding
    }));

    return points.reduce((path, point, i, a) => {
        if (i === 0) return `M ${point.x},${point.y}`;
        
        const prev = a[i - 1];
        const cpsX = prev.x + (point.x - prev.x) / 2;
        
        return `${path} C ${cpsX},${prev.y} ${cpsX},${point.y} ${point.x},${point.y}`;
    }, "");
};

const incomePath = computed(() => smoothPath(incomeData));
const expensePath = computed(() => smoothPath(expenseData));

const scrollToPricing = () => {
    const el = document.getElementById('pricing');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};

const trustPillars = [
    { 
        title: __('trust_pilar_1_title'), 
        desc: __('trust_pilar_1_desc'), 
        icon: ShieldCheck,
        color: 'text-indigo-600',
        bg: 'bg-indigo-50'
    },
    { 
        title: __('trust_pilar_2_title'), 
        desc: __('trust_pilar_2_desc'), 
        icon: Pencil,
        color: 'text-emerald-600',
        bg: 'bg-emerald-50'
    },
    { 
        title: __('trust_pilar_3_title'), 
        desc: __('trust_pilar_3_desc'), 
        icon: Lock,
        color: 'text-purple-600',
        bg: 'bg-purple-50'
    },
    { 
        title: __('trust_pilar_4_title'), 
        desc: __('trust_pilar_4_desc'), 
        icon: Zap,
        color: 'text-amber-600',
        bg: 'bg-amber-50'
    }
];

const navigateToCheckout = (planId) => {
    router.visit(route('register', { plan: planId }));
};

// Scroll Reveal Logic
const observer = ref(null);

onMounted(() => {
    const options = {
        root: null,
        threshold: 0.1,
        rootMargin: '0px'
    };

    observer.value = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                // Optional: Stop observing after reveal
                // observer.value.unobserve(entry.target);
            }
        });
    }, options);

    document.querySelectorAll('.scroll-reveal').forEach(el => {
        observer.value.observe(el);
    });
});

onUnmounted(() => {
    if (observer.value) {
        observer.value.disconnect();
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 font-sans selection:bg-indigo-100 selection:text-indigo-900 scroll-smooth">
        <Head :title="__('welcome_back') + ' - VibeFinance'" />

        <!-- Navigation -->
        <nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-xl border-b border-slate-100 px-4 md:px-6 py-3 md:py-4 transition-all duration-300">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2 group cursor-pointer">
                    <div class="flex items-center justify-center transition-all group-hover:scale-110 duration-300">
                        <img src="/img/logo_vibefinance.png" class="h-6 md:h-7 w-auto object-contain" alt="VibeFinance Logo">
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-lg md:text-xl tracking-tight text-slate-900" style="font-family:'Outfit', sans-serif;">
                            <span class="font-semibold">Vibe</span><span class="font-light text-indigo-600">Finance</span>
                        </span>
                        <span class="text-[9px] font-medium text-slate-400">Powered by terasweb.id</span>
                    </div>
                </Link>
                <div class="flex items-center gap-3 sm:gap-6">
                    <LandingLanguageSwitcher />
                    <button @click="scrollToPricing" class="text-sm font-bold text-slate-600 hover:text-indigo-600 transition-colors hidden md:block">{{ __('pricing') }}</button>
                    <!-- Login Button (Visible on all screens) -->
                    <Link :href="route('login')" class="text-xs md:text-sm font-bold text-slate-600 hover:text-indigo-600 transition-colors">{{ __('login') }}</Link>
                    <Link :href="route('register')" class="px-4 py-2 md:px-5 md:py-2.5 bg-indigo-600 text-white rounded-xl text-xs md:text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all hover:scale-[1.02] active:scale-95">
                        {{ __('start_for_free') }}
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <header class="relative pt-28 md:pt-40 pb-16 md:pb-20 overflow-hidden">
            <!-- Ambient Background Texture -->
            <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
                <!-- Subtle Blueprint Grid -->
                <div class="absolute inset-0 opacity-[0.08]" 
                     style="background-image: linear-gradient(#4f46e5 1px, transparent 1px), linear-gradient(90deg, #4f46e5 1px, transparent 1px); background-size: 60px 60px;">
                </div>
                
                <!-- Moving Data Particles -->
                <div v-for="i in 15" :key="i" 
                     class="absolute w-2 h-2 bg-indigo-500/50 rounded-full animate-float"
                     :style="{
                        left: (Math.random() * 100) + '%',
                        top: (Math.random() * 100) + '%',
                        animationDelay: (Math.random() * 5) + 's',
                        animationDuration: (8 + Math.random() * 6) + 's'
                     }">
                </div>

                <!-- Abstract Glow Orbs (Enhanced) -->
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-screen h-screen">
                    <div class="absolute top-20 left-1/4 w-[300px] md:w-[600px] h-[300px] md:h-[600px] bg-indigo-100 rounded-full blur-[80px] md:blur-[120px] opacity-20 animate-pulse"></div>
                    <div class="absolute bottom-40 right-1/4 w-[300px] md:w-[600px] h-[300px] md:h-[600px] bg-purple-100 rounded-full blur-[80px] md:blur-[120px] opacity-20 animate-pulse" style="animation-delay: 2s;"></div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 md:px-6 relative z-10 text-center">
                <div class="relative inline-block z-10 w-full mb-6 md:mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2 bg-indigo-50 text-indigo-600 rounded-full text-[10px] md:text-xs font-bold mb-6 md:mb-8 relative z-20">
                        <Zap class="w-3 md:w-4 h-3 md:h-4" /> {{ __('next_gen_finance') }}
                    </div>

                    <!-- Floating Icons (Minimalist) -->
                    <!-- Kiri Atas: Target -->
                    <div class="hidden xl:flex absolute left-0 top-0 w-12 h-12 bg-white/40 backdrop-blur-xl border border-white/40 rounded-2xl shadow-2xl items-center justify-center animate-float z-30 text-emerald-500 hover:scale-110 transition-transform" style="animation-delay: 0.5s; animation-duration: 5s;">
                        <Target class="w-6 h-6" />
                    </div>

                    <!-- Kanan Atas: Check -->
                    <div class="hidden xl:flex absolute right-4 top-6 w-14 h-14 bg-white/40 backdrop-blur-xl border border-white/40 rounded-2xl shadow-2xl items-center justify-center animate-float z-30 text-indigo-600 hover:scale-110 transition-transform" style="animation-delay: 1.2s; animation-duration: 4s;">
                        <CheckCircle2 class="w-7 h-7" />
                    </div>

                    <!-- Kiri Bawah: Chart -->
                    <div class="hidden xl:flex absolute left-8 bottom-4 w-14 h-14 bg-white/40 backdrop-blur-xl border border-white/40 rounded-2xl shadow-2xl items-center justify-center animate-float z-30 text-purple-600 hover:scale-110 transition-transform" style="animation-delay: 2.1s; animation-duration: 6s;">
                        <BarChart3 class="w-7 h-7" />
                    </div>

                    <!-- Kanan Bawah: Wallet -->
                    <div class="hidden xl:flex absolute right-12 bottom-0 w-12 h-12 bg-white/40 backdrop-blur-xl border border-white/40 rounded-2xl shadow-2xl items-center justify-center animate-float z-30 text-amber-500 hover:scale-110 transition-transform" style="animation-delay: 1.5s; animation-duration: 5.5s;">
                        <Wallet class="w-6 h-6" />
                    </div>

                    <!-- AI Sparkles -->
                    <div class="hidden xl:flex absolute left-1/4 -top-12 animate-pulse z-30 text-indigo-400">
                        <Sparkles class="w-5 h-5" />
                    </div>

                    <h1 class="text-4xl sm:text-5xl md:text-7xl font-bold text-slate-900 leading-[1.1] scroll-reveal animate-fade-up relative z-10" style="font-family: 'Inter', sans-serif;">
                        {{ __('landing_hero_title_1') }} <br />
                        <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 scroll-reveal animate-fade-up" style="animation-delay: 0.2s">{{ __('landing_hero_title_2') }}</span>
                    </h1>
                </div>
                
                <p class="text-base md:text-lg text-slate-500 max-w-2xl mx-auto mb-12 leading-relaxed font-medium scroll-reveal animate-fade-up" style="animation-delay: 0.4s">
                    {{ __('landing_hero_subtitle') }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 scroll-reveal animate-fade-up" style="animation-delay: 0.6s">
                    <Link :href="route('register')" class="w-full sm:w-auto px-6 py-3.5 bg-slate-900 text-white rounded-2xl text-base font-bold shadow-2xl shadow-slate-200 hover:bg-slate-800 transition-all hover:scale-105 flex items-center justify-center gap-2 relative overflow-hidden group/btn">
                        <span class="relative z-10 flex items-center gap-2">
                            {{ __('start_for_free') }} <ArrowRight class="w-5 h-5" />
                        </span>
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover/btn:animate-shine"></div>
                    </Link>
                    <button @click="scrollToPricing" class="w-full sm:w-auto px-6 py-3.5 bg-white text-slate-900 border border-slate-100 rounded-2xl text-base font-bold shadow-xl shadow-slate-100 hover:border-indigo-100 hover:text-indigo-600 transition-all flex items-center justify-center gap-2">
                        {{ __('view_pricing') }}
                    </button>
                </div>

                <!-- Pixel-Perfect Dashboard Mockup -->
                <div class="mt-16 md:mt-24 relative px-2 md:px-4 group max-w-[1400px] mx-auto z-20 -mb-32 md:-mb-64 scroll-reveal animate-mockup-lift">
                    <div class="absolute inset-x-0 -bottom-10 h-64 bg-gradient-to-t from-slate-50 via-transparent to-transparent z-30 pointer-events-none"></div>
                    
                    <!-- Floating Badge -->
                    <div class="absolute -top-5 md:-top-6 left-1/2 -translate-x-1/2 z-40 bg-white px-5 py-2 md:px-8 md:py-3 rounded-full shadow-2xl border border-indigo-50 flex items-center gap-2 md:gap-3 animate-bounce hover:animate-none cursor-default">
                        <div class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-[9px] md:text-[10px] font-bold text-slate-600"> {{ __('live_preview') }}</span>
                    </div>

                    <div class="bg-white rounded-[2rem] md:rounded-[4rem] border-[4px] md:border-[16px] border-white shadow-[0_30px_60px_rgba(0,0,0,0.1)] md:shadow-[0_60px_120px_rgba(0,0,0,0.15)] overflow-hidden scale-[0.98] group-hover:scale-100 transition-all duration-1000 bg-slate-50 relative">
                        <!-- Scrollable Container -->
                        <div class="h-[400px] md:h-[700px] overflow-y-auto no-scrollbar scroll-smooth">
                            <div class="flex-1 w-full mx-auto px-4 py-8 md:px-12 md:py-16 relative z-10 text-left">
                                <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-50 rounded-full blur-[100px] pointer-events-none opacity-50 animate-float"></div>
                                
                                <!-- Header Mockup -->
                                <header class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-30">
                                    <div class="space-y-1">
                                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 leading-tight">{{ __('good_evening') }}, Alex</h1>
                                        <p class="text-xs md:text-sm font-medium text-slate-400">{{ __('financial_overview_for') }} <span class="font-bold text-slate-900">Februari 2026</span></p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="w-full md:w-56 relative bg-white border border-slate-200 rounded-xl px-4 py-3 flex items-center justify-between shadow-sm cursor-default">
                                            <span class="text-xs font-bold text-slate-700">Februari 2026</span>
                                            <Globe class="w-4 h-4 text-slate-400" />
                                        </div>
                                    </div>
                                </header>

                                <!-- Metrics Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-10">
                                    <!-- Net Worth -->
                                    <div class="relative overflow-hidden rounded-[2rem] p-6 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-xl shadow-indigo-100 group/card transition-transform hover:scale-[1.02]">
                                        <div class="absolute right-0 top-0 p-6 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                                            <Wallet class="w-24 h-24 text-white" />
                                        </div>
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-3 mb-6">
                                                <div class="p-2.5 bg-white/20 backdrop-blur-md rounded-xl border border-white/20">
                                                    <Wallet class="w-5 h-5 text-white" />
                                                </div>
                                                <h3 class="font-bold text-sm text-white/90">{{ __('total_net_worth') }}</h3>
                                            </div>
                                            <div class="space-y-1">
                                                <h2 class="text-2xl font-bold tabular-nums">Rp 54.336.440</h2>
                                                <p class="text-[10px] text-indigo-100 font-medium">{{ __('across_all_wallets') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Monthly Income -->
                                    <div class="relative overflow-hidden rounded-[2rem] p-6 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-xl shadow-emerald-100 group/card transition-transform hover:scale-[1.02]">
                                        <div class="absolute right-0 top-0 p-6 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                                            <TrendingUp class="w-24 h-24 text-white" />
                                        </div>
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-3 mb-6">
                                                <div class="p-2.5 bg-white/20 backdrop-blur-md rounded-xl border border-white/20">
                                                    <TrendingUp class="w-5 h-5 text-white" />
                                                </div>
                                                <h3 class="font-bold text-sm text-white/90">{{ __('monthly_income') }}</h3>
                                            </div>
                                            <div class="space-y-1">
                                                <h2 class="text-2xl font-bold tabular-nums">Rp 25.070.000</h2>
                                                <p class="text-[10px] text-emerald-100 font-medium">Februari 2026</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Monthly Expense -->
                                    <div class="relative overflow-hidden rounded-[2rem] p-6 bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-xl shadow-rose-100 group/card transition-transform hover:scale-[1.02]">
                                        <div class="absolute right-0 top-0 p-6 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                                            <TrendingDown class="w-24 h-24 text-white" />
                                        </div>
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-3 mb-6">
                                                <div class="p-2.5 bg-white/20 backdrop-blur-md rounded-xl border border-white/20">
                                                    <TrendingDown class="w-5 h-5 text-white" />
                                                </div>
                                                <h3 class="font-bold text-sm text-white/90">{{ __('monthly_expense') }}</h3>
                                            </div>
                                            <div class="space-y-1">
                                                <h2 class="text-2xl font-bold tabular-nums">Rp 8.240.500</h2>
                                                <p class="text-[10px] text-rose-100 font-medium">Februari 2026</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Savings Rate -->
                                    <div class="relative overflow-hidden rounded-[2rem] p-6 bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-xl shadow-purple-100 group/card transition-transform hover:scale-[1.02]">
                                        <div class="absolute right-0 top-0 p-6 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                                            <Target class="w-24 h-24 text-white" />
                                        </div>
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-3 mb-6">
                                                <div class="p-2.5 bg-white/20 backdrop-blur-md rounded-xl border border-white/20">
                                                    <Target class="w-5 h-5 text-white" />
                                                </div>
                                                <h3 class="font-bold text-sm text-white/90">{{ __('savings_rate') }}</h3>
                                            </div>
                                            <div class="space-y-1">
                                                <h2 class="text-2xl font-bold tabular-nums">67%</h2>
                                                <p class="text-[10px] text-purple-100 font-medium">{{ __('of_total_income') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts Row -->
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                                    <!-- Line Chart Mockup -->
                                <div class="lg:col-span-2 bg-white border border-slate-100 rounded-[2.5rem] p-8 shadow-sm">
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
                                        <div class="space-y-0.5">
                                            <h2 class="text-lg font-bold text-slate-900">{{ __('income_vs_expense') }}</h2>
                                            <p class="text-[10px] font-bold text-slate-400">{{ __('monthly_analytics_desc') }}</p>
                                        </div>
                                        <div class="flex items-center gap-3 w-full sm:w-auto">
                                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[9px] font-bold bg-emerald-500 text-white border border-emerald-400 shadow-md shadow-emerald-100 flex-1 sm:flex-none justify-center">
                                                <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                                <span>{{ __('income') }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[9px] font-bold bg-amber-500 text-white border border-amber-400 shadow-md shadow-amber-100 flex-1 sm:flex-none justify-center">
                                                <div class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                                <span>{{ __('expense') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- High-Fidelity Dynamic Line Chart Wrapper -->
                                    <div class="h-56 w-full relative group/chart mt-4">
                                        <svg viewBox="0 0 1000 300" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                                            <!-- Grid Lines (Horizontal Dotted) -->
                                            <g stroke="rgba(15, 23, 42, 0.05)" stroke-width="1.5" stroke-dasharray="4">
                                                <line v-for="i in [0, 1, 2, 3]" :key="i" x1="0" :y1="i * 70" x2="1000" :y2="i * 70" />
                                            </g>
                                            
                                            <!-- Bottom Border -->
                                            <line x1="0" y1="282" x2="1000" y2="282" stroke="#cbd5e1" stroke-width="1" />

                                            <!-- Dynamic Line Paths (Lines Only, No Background) -->
                                            <!-- Income Line -->
                                            <path :d="incomePath" 
                                                  fill="none" stroke="#10b981" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />

                                            <!-- Expense Line -->
                                            <path :d="expensePath" 
                                                  fill="none" stroke="#f59e0b" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>

                                        <!-- X-Axis Labels Overlay -->
                                        <div class="absolute bottom-[-10px] inset-x-0 flex justify-between px-2">
                                            <span v-for="d in ['01', '03', '05', '07', '09', '11', '13', '15', '17', '19', '21', '23', '25', '27']" :key="d" class="text-[8px] font-bold text-slate-400">{{ d }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pie Chart Mockup -->
                                <div class="bg-white border border-slate-100 rounded-[2.5rem] p-8 flex flex-col shadow-sm">
                                    <h2 class="text-lg font-bold text-slate-900 mb-0.5">{{ __('top_expenses') }}</h2>
                                    <p class="text-[10px] font-bold text-slate-400 mb-8">{{ __('expense_breakdown') }}</p>
                                    
                                    <div class="flex justify-center mb-8">
                                        <div class="relative w-36 h-36">
                                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                                <!-- Food & Drink (60%) -->
                                                <circle cx="50" cy="50" r="25" fill="none" stroke="#10b981" stroke-width="50" 
                                                        stroke-dasharray="94.25 157.08" />
                                                
                                                <!-- Shopping (25%) -->
                                                <circle cx="50" cy="50" r="25" fill="none" stroke="#f43f5e" stroke-width="50" 
                                                        stroke-dasharray="39.27 157.08" stroke-dashoffset="-94.25" />
                                                
                                                <!-- Others (15%) -->
                                                <circle cx="50" cy="50" r="25" fill="none" stroke="#f59e0b" stroke-width="50" 
                                                        stroke-dasharray="23.56 157.08" stroke-dashoffset="-133.52" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Refined Legend (2 top, 1 bottom, Centered) -->
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="flex items-center justify-center gap-6">
                                            <div class="flex items-center gap-2">
                                                <div class="bg-emerald-500 w-2 h-2 rounded-full shadow-sm"></div>
                                                <span class="text-[10px] font-bold text-slate-800">{{ __('food_drink') }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="bg-rose-500 w-2 h-2 rounded-full shadow-sm"></div>
                                                <span class="text-[10px] font-bold text-slate-800">{{ __('shopping') }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="bg-orange-500 w-2 h-2 rounded-full shadow-sm"></div>
                                            <span class="text-[10px] font-bold text-slate-800">{{ __('others') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                <!-- Transaction Table Mockup -->
                                <div class="bg-white border border-slate-100 rounded-[2.5rem] overflow-hidden shadow-sm hidden md:block">
                                    <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <h2 class="text-lg font-bold text-slate-900">{{ __('recent_transactions') }}</h2>
                                            <p class="text-[10px] font-bold text-slate-400">{{ __('latest_financial_activities') }}</p>
                                        </div>
                                        <div class="px-4 py-2 bg-indigo-600 text-white text-[10px] font-bold rounded-xl shadow-lg shadow-indigo-100">
                                            {{ __('view_all') }}
                                        </div>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left">
                                            <thead class="bg-slate-50/50 border-b border-slate-100">
                                                <tr class="text-[9px] font-bold text-slate-400">
                                                    <th class="px-8 py-4">{{ __('date') }}</th>
                                                    <th class="px-8 py-4">{{ __('description') }}</th>
                                                    <th class="px-8 py-4">{{ __('wallet') }}</th>
                                                    <th class="px-8 py-4">{{ __('category') }}</th>
                                                    <th class="px-8 py-4 text-right">{{ __('amount') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-50">
                                                <tr v-for="(row, ir) in [
                                                    {date: '7 Feb 2026', desc: __('service_motor'), wallet: __('main_bank_bni'), walletCol: 'bg-orange-500', cat: __('groceries'), catCol: 'bg-indigo-500', amt: '-Rp 120.000', positive: false},
                                                    {date: '6 Feb 2026', desc: __('buy_soy_sauce'), wallet: __('main_bank_bni'), walletCol: 'bg-orange-500', cat: __('shopping'), catCol: 'bg-pink-500', amt: '-Rp 120.000', positive: false},
                                                    {date: '6 Feb 2026', desc: __('test_entry'), wallet: __('wallet_dana'), walletCol: 'bg-indigo-500', cat: __('freelance'), catCol: 'bg-teal-500', amt: '+Rp 30.000', positive: true},
                                                    {date: '2 Feb 2026', desc: __('snack_cooperative'), wallet: __('main_bank_bni'), walletCol: 'bg-orange-500', cat: __('food_drink'), catCol: 'bg-orange-500', amt: '-Rp 27.500', positive: false},
                                                    {date: '1 Feb 2026', desc: __('extra_food_money'), wallet: __('main_bank_bni'), walletCol: 'bg-orange-500', cat: __('transfer'), catCol: 'bg-fuchsia-500', amt: '+Rp 20.000', positive: true}
                                                ]" :key="ir" class="hover:bg-slate-50/80 transition-colors">
                                                    <td class="px-8 py-5 text-[11px] font-bold text-slate-400 whitespace-nowrap">{{ row.date }}</td>
                                                    <td class="px-8 py-5 text-[11px] font-bold text-slate-800">{{ row.desc }}</td>
                                                    <td class="px-8 py-5">
                                                        <div class="flex items-center gap-2">
                                                            <div :class="[row.walletCol, 'w-1.5 h-1.5 rounded-full']"></div>
                                                            <span class="text-[11px] font-bold text-slate-600">{{ row.wallet }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-8 py-5">
                                                        <span :class="[row.catCol, 'px-3 py-1 rounded-full text-[9px] font-bold text-white border border-white/20']">
                                                            {{ row.cat }}
                                                        </span>
                                                    </td>
                                                    <td class="px-8 py-5 text-right">
                                                        <span :class="['text-[11px] font-bold tabular-nums', row.positive ? 'text-emerald-500' : 'text-slate-900']">{{ row.amt }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </header>

        <!-- Value Pillars (Structured Glass Design) -->
        <section class="py-16 bg-white relative z-20 overflow-hidden scroll-reveal animate-fade-up" 
                 style="box-shadow: 0 20px 60px -15px rgba(0,0,0,0.05); animation-delay: 0.1s;">
            
            <!-- Deep-Color Neon Borders (Refined Intensity) -->
            <div class="absolute top-0 left-0 w-full flex justify-center overflow-hidden">
                <div class="w-2/3 h-[4px] bg-gradient-to-r from-transparent via-indigo-600 via-indigo-400 via-indigo-600 to-transparent opacity-100 blur-[1px] animate-neon-pulse shadow-[0_0_30px_rgba(79,70,229,1),0_0_70px_rgba(79,70,229,0.6)]"></div>
            </div>
            <div class="absolute bottom-0 left-0 w-full flex justify-center overflow-hidden">
                <div class="w-2/3 h-[4px] bg-gradient-to-r from-transparent via-purple-600 via-purple-400 via-purple-600 to-transparent opacity-100 blur-[1px] animate-neon-pulse shadow-[0_0_30px_rgba(168,85,247,1),0_0_70px_rgba(168,85,247,0.6)]" style="animation-delay: 1.5s"></div>
            </div>

            <!-- Subtle Decorative Orbs -->
            <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-indigo-50/30 rounded-full blur-[120px] -z-10 animate-pulse"></div>
            <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-emerald-50/20 rounded-full blur-[120px] -z-10 animate-pulse" style="animation-delay: 2s"></div>

            <div class="max-w-7xl mx-auto px-6 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="(pillar, index) in trustPillars" :key="index" 
                         class="group relative p-8 rounded-[2.5rem] bg-white border border-slate-100/50 shadow-sm transition-all duration-500 hover:shadow-xl hover:shadow-indigo-500/5 hover:-translate-y-2 hover:border-indigo-100 flex flex-col items-center text-center scroll-reveal animate-fade-up"
                         :style="{ animationDelay: (0.1 + (index * 0.1)) + 's' }">
                        
                        <!-- Hover Spotlight Effect -->
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/0 via-transparent to-indigo-50/0 opacity-0 group-hover:opacity-100 transition-opacity duration-700 rounded-[2.5rem]"></div>

                        <!-- Glass Icon Container -->
                        <div :class="['relative z-10 w-16 h-16 rounded-2xl flex items-center justify-center mb-6 transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 border border-slate-100 bg-gradient-to-br shadow-inner', pillar.bg]">
                            <component :is="pillar.icon" :class="['w-8 h-8', pillar.color]" />
                        </div>
                        
                        <div class="relative z-10">
                            <h3 class="text-lg font-bold text-slate-900 mb-2">{{ pillar.title }}</h3>
                            <p class="text-sm text-slate-500 leading-relaxed max-w-[240px] font-medium">{{ pillar.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Showcases -->
        <section class="py-20 bg-slate-50 relative overflow-hidden">
            <!-- Ambient Background Texture (Synced with Hero) -->
            <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
                <!-- Subtle Blueprint Grid -->
                <div class="absolute inset-0 opacity-[0.08]" 
                     style="background-image: linear-gradient(#4f46e5 1px, transparent 1px), linear-gradient(90deg, #4f46e5 1px, transparent 1px); background-size: 60px 60px;">
                </div>
                
                <!-- Moving Data Particles -->
                <div v-for="i in 15" :key="'feat-particle-' + i" 
                     class="absolute w-2 h-2 bg-indigo-500/50 rounded-full animate-float"
                     :style="{
                        left: (Math.random() * 100) + '%',
                        top: (Math.random() * 100) + '%',
                        animationDelay: (Math.random() * 5) + 's',
                        animationDuration: (8 + Math.random() * 6) + 's'
                     }">
                </div>

                <!-- Abstract Glow Orbs -->
                <div class="absolute inset-0">
                    <div class="absolute top-1/4 -left-20 w-[600px] h-[600px] bg-indigo-100 rounded-full blur-[140px] opacity-30 animate-pulse"></div>
                    <div class="absolute bottom-1/4 -right-20 w-[600px] h-[600px] bg-purple-100 rounded-full blur-[140px] opacity-30 animate-pulse" style="animation-delay: 2s;"></div>
                </div>
            </div>
            
            <div class="max-w-7xl mx-auto px-6 text-center relative z-10">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold mb-6 scroll-reveal animate-fade-up">
                    <Zap class="w-4 h-4" /> {{ __('sophisticated_tools') }}
                </div>
                <h2 class="text-2xl md:text-5xl font-bold text-slate-900 mb-6 scroll-reveal animate-fade-up" style="animation-delay: 0.1s;">{{ __('features_desc_title_simplified') }}</h2>
                <p class="text-base md:text-lg text-slate-500 max-w-2xl mx-auto font-medium mb-16 md:mb-24 leading-relaxed scroll-reveal animate-fade-up" style="animation-delay: 0.2s;">
                    {{ __('features_desc') }}
                </p>
                
                <div class="space-y-20">
                    <!-- Feature 1: Smart Wallets (Integrated Card) -->
                    <div class="scroll-reveal animate-fade-up">
                        <div class="bg-white/70 backdrop-blur-2xl px-6 py-10 md:py-14 md:px-16 rounded-[2.5rem] md:rounded-[4rem] border border-white shadow-2xl shadow-indigo-100/50 relative group/card overflow-hidden text-left">
                            <!-- Background Accent -->
                            <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-50/50 rounded-full blur-[100px] opacity-60"></div>
                            
                            <div class="relative z-20 flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                                <!-- Wallet Mockup (Inside Card - Left) -->
                                <div class="flex-1 w-full relative">
                                    <div class="relative py-6 flex justify-center lg:justify-center">
                                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[110%] h-[110%] bg-indigo-100/30 rounded-[4rem] blur-[80px] opacity-60 rotate-6 scale-90"></div>
                                        
                                        <draggable 
                                            v-model="mockupWallets" 
                                            item-key="id"
                                            class="relative flex flex-col gap-0 items-center lg:items-center w-full max-w-[420px]"
                                            ghost-class="opacity-0"
                                            drag-class="grabbing-state"
                                            :animation="400"
                                        >
                                            <template #item="{element: w, index}">
                                                <div 
                                                    :class="['group relative w-full rounded-[2.5rem] p-8 overflow-hidden transition-all duration-700 flex flex-col shadow-2xl transition-all duration-300 hover:scale-[1.02] hover:shadow-xl cursor-grab active:cursor-grabbing', w.cardTheme || 'bg-white border border-slate-100 hover:border-indigo-200']"
                                                    :style="{
                                                        marginTop: index === 0 ? '0' : '-100px',
                                                        zIndex: index + 10,
                                                        transform: `translateX(${index * -20}px) rotate(${index * -1.5}deg)`
                                                    }"
                                                >
                                                    <!-- Content -->
                                                    <div class="relative z-10 flex flex-col justify-between h-full gap-4 md:gap-0">
                                                        <!-- Top Row: Icons & Actions -->
                                                        <div class="flex justify-between items-start">
                                                            <div class="flex items-center gap-3 md:gap-4">
                                                                <div :class="['p-2.5 md:p-3 rounded-2xl shadow-sm border backdrop-blur-md', w.iconColor]">
                                                                    <CreditCard class="w-5 h-5 md:w-6 md:h-6" />
                                                                </div>
                                                                <div class="text-left">
                                                                    <h3 class="font-semibold text-base md:text-xl leading-none tracking-tight text-slate-900 mt-1">{{ w.name }}</h3>
                                                                </div>
                                                            </div>
                                                            <!-- Actions Row -->
                                                            <div class="flex items-center gap-1.5 md:gap-2 mr-0">
                                                                <!-- Drag Handle -->
                                                                <div class="p-2 md:p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 group-hover:text-indigo-600 transition-colors">
                                                                    <GripVertical class="w-3.5 h-3.5 md:w-4 md:h-4" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Middle: Balance -->
                                                        <div class="mt-4 md:mt-6 text-left">
                                                            <h2 class="text-xl md:text-[30px] font-bold tracking-tight tabular-nums text-slate-900">{{ w.balance }}</h2>
                                                        </div>

                                                        <!-- Bottom: Footer -->
                                                        <div class="flex justify-between items-end mt-4 pt-4 border-t border-slate-50">
                                                            <div class="flex flex-col text-left">
                                                                <span class="text-[10px] font-bold text-slate-400 mb-0.5">{{ __('feature_label_account_id') }}</span>
                                                                <span class="text-xs md:text-sm font-bold text-slate-600 truncate max-w-[120px] md:max-w-[150px]">{{ w.accountNumber }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[10px] md:text-xs font-bold text-slate-400 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100 uppercase">{{ w.currency }}</span>
                                                                <!-- Status Pill -->
                                                                <div class="flex items-center gap-2 px-2 md:px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">
                                                                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                                                                    <span class="text-[10px] font-bold uppercase tracking-tight">{{ __('active') }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </draggable>
                                    </div>
                                </div>

                                <!-- Card Content (Inside Card - Right) -->
                                <div class="flex-1 text-left relative z-20">
                                    <div class="w-14 h-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-indigo-100/50">
                                        <Wallet class="w-7 h-7" />
                                    </div>
                                    <h3 class="text-xl md:text-3xl font-bold text-slate-900 mb-6 leading-tight">{{ __('feature_wallets_title') }}</h3>
                                    <p class="text-sm md:text-base text-slate-500 leading-relaxed font-medium mb-8">
                                        {{ __('feature_wallets_desc') }}
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div v-for="i in [__('feature_wallets_li_1'), __('feature_wallets_li_2'), __('feature_wallets_li_3')]" :key="i" 
                                             class="flex items-center gap-4 p-4 rounded-2xl bg-white/50 border border-slate-100 transition-all hover:border-indigo-200 hover:bg-white group/li">
                                            <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0 border border-emerald-100 group-hover/li:scale-110 transition-transform">
                                                <Check class="w-5 h-5 text-emerald-600" />
                                            </div>
                                            <span class="font-bold text-slate-700 leading-tight text-sm md:text-base">{{ i }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 2: Deep Analytics (Integrated Card) -->
                    <div class="scroll-reveal animate-fade-up">
                        <div class="bg-white/70 backdrop-blur-2xl px-6 py-10 md:py-14 md:px-16 rounded-[2.5rem] md:rounded-[4rem] border border-white shadow-2xl shadow-purple-100/50 relative group/card overflow-hidden text-left">
                            <!-- Background Accent -->
                            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-purple-50/50 rounded-full blur-[100px] opacity-60"></div>
                            
                            <div class="relative z-20 flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                                <!-- Analytics Mockups (Inside Card - Left) -->
                                <div class="flex-1 w-full relative">
                                    <div class="grid grid-cols-1 gap-6 relative py-12 place-items-center">
                                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[110%] h-[110%] bg-purple-100/30 rounded-[4rem] blur-[80px] opacity-60 rotate-12 scale-90"></div>
                                        
                                        <!-- Spending Mix Card -->
                                        <div class="relative bg-white border border-slate-200 rounded-[2rem] p-6 md:p-8 shadow-sm transition-all duration-700 hover:shadow-xl hover:scale-[1.02] z-20 w-full max-w-[500px]">
                                            <div class="flex items-center justify-between mb-8">
                                                <div class="text-left">
                                                    <h4 class="font-bold text-lg md:text-xl text-slate-900 tracking-tight leading-none mb-1">{{ __('spending_mix') }}</h4>
                                                    <p class="text-[10px] md:text-xs text-slate-500 font-medium">{{ __('category_distribution') }}</p>
                                                </div>
                                                <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 shadow-sm border border-indigo-100">
                                                    <ChartPie class="w-4 h-4 md:w-5 md:h-5" />
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-10 items-center">
                                                <!-- Chart Section -->
                                                <div class="relative flex items-center justify-center">
                                                    <div class="relative w-40 h-40 md:w-48 md:h-48">
                                                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                                            <circle cx="50" cy="50" r="40" stroke="#f8fafc" stroke-width="8" fill="none" />
                                                            <!-- Main segment (Food & Drink 76.2%) -->
                                                            <circle cx="50" cy="50" r="40" stroke="#4f46e5" stroke-width="8" fill="none" stroke-dasharray="191.5 251.2" />
                                                            <!-- Second segment (Shopping 12%) -->
                                                            <circle cx="50" cy="50" r="40" stroke="#10b981" stroke-width="8" fill="none" stroke-dasharray="30.1 251.2" stroke-dashoffset="-191.5" />
                                                            <!-- Third segment (Groceries 12%) -->
                                                            <circle cx="50" cy="50" r="40" stroke="#f43f5e" stroke-width="8" fill="none" stroke-dasharray="30.1 251.2" stroke-dashoffset="-221.6" />
                                                        </svg>
                                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                                            <span class="text-xs font-bold text-slate-400 uppercase mb-1">Total</span>
                                                            <span class="text-xl md:text-2xl font-bold text-slate-900 leading-none">Rp 1.0M</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Category Details Grid -->
                                                <div class="flex flex-col gap-3">
                                                    <div v-for="(cat, idx) in [
                                                        {name: __('food_drink'), color: '#4f46e5', amount: 'Rp 766.500', percent: '76.2%'},
                                                        {name: __('shopping'), color: '#10b981', amount: 'Rp 120.000', percent: '11.9%'},
                                                        {name: __('groceries'), color: '#f43f5e', amount: 'Rp 120.000', percent: '11.9%'}
                                                    ]" :key="idx" class="p-3 rounded-2xl border border-slate-100 hover:border-slate-200 hover:shadow-sm transition-all group bg-slate-50/50">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <div class="flex items-center gap-2">
                                                                <span class="w-2 h-2 rounded-full shadow-sm" :style="{ backgroundColor: cat.color }"></span>
                                                                <span class="text-[11px] font-bold text-slate-700">{{ cat.name }}</span>
                                                            </div>
                                                            <span class="text-[11px] font-bold text-slate-900 tabular-nums">{{ cat.amount }}</span>
                                                        </div>
                                                        <!-- Progress Bar -->
                                                        <div class="w-full bg-slate-200 rounded-full h-1 overflow-hidden mb-1">
                                                            <div class="h-full rounded-full transition-all duration-1000" :style="{ width: cat.percent, backgroundColor: cat.color }"></div>
                                                        </div>
                                                        <p class="text-[10px] text-slate-400 font-medium text-right">{{ cat.percent }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Smart Insights Card (Restored Overlapping) -->
                                        <div class="relative bg-white border border-slate-100 rounded-[2.5rem] p-8 shadow-2xl transition-all duration-700 hover:shadow-2xl hover:scale-[1.05] z-30 lg:-mt-16 lg:ml-20">
                                            <div class="flex items-center gap-4 mb-6">
                                                <div class="p-3 rounded-2xl bg-violet-50 text-violet-600 border border-violet-100 shadow-sm">
                                                    <Lightbulb class="w-6 h-6" />
                                                </div>
                                                <div class="text-left">
                                                    <h4 class="font-bold text-lg text-slate-900 leading-none">{{ __('smart_insights') }}</h4>
                                                    <p class="text-[9px] font-bold text-slate-400 mt-1">{{ __('feature_label_ai_analysis') }}</p>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 gap-4">
                                                <div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100 flex gap-4 group/insight transition-all hover:bg-white hover:border-violet-100">
                                                    <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center shrink-0 group-hover/insight:scale-110 transition-transform">
                                                        <Flame class="w-5 h-5 text-orange-500" />
                                                    </div>
                                                    <div class="text-left">
                                                        <h5 class="text-sm font-bold text-slate-900 mb-1">{{ __('insight_burn_rate_title') }}</h5>
                                                        <p class="text-[11px] text-slate-500 leading-relaxed font-medium">{{ __('insight_burn_rate_message', {amount: 'Rp 2M'}) }}</p>
                                                    </div>
                                                </div>
                                                <div class="p-5 bg-indigo-50/50 rounded-2xl border border-indigo-100 flex gap-4 group/insight transition-all hover:bg-white hover:border-indigo-200">
                                                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center shrink-0 group-hover/insight:scale-110 transition-transform">
                                                        <Calendar class="w-5 h-5 text-indigo-600" />
                                                    </div>
                                                    <div class="text-left">
                                                        <h5 class="text-sm font-bold text-indigo-900 mb-1">{{ __('insight_weekend_title') }}</h5>
                                                        <p class="text-[11px] text-indigo-600/80 leading-relaxed font-medium">{{ __('insight_weekend_message', {percentage: '85'}) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Content (Inside Card - Right) -->
                                <div class="flex-1 text-left relative z-20">
                                    <div class="w-14 h-14 bg-purple-600 text-white rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-purple-100/50">
                                        <BarChart3 class="w-7 h-7" />
                                    </div>
                                    <h3 class="text-xl md:text-3xl font-bold text-slate-900 mb-6 leading-tight">{{ __('feature_analytics_title') }}</h3>
                                    <p class="text-sm md:text-base text-slate-500 leading-relaxed font-medium mb-8">
                                        {{ __('feature_analytics_desc') }}
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div v-for="i in [__('feature_analytics_li_1'), __('feature_analytics_li_2'), __('feature_analytics_li_3')]" :key="i" 
                                             class="flex items-center gap-4 p-4 rounded-2xl bg-white/50 border border-slate-100 transition-all hover:border-purple-200 hover:bg-white group/li">
                                            <div class="w-9 h-9 bg-purple-50 rounded-xl flex items-center justify-center shrink-0 border border-purple-100 group-hover/li:scale-110 transition-transform">
                                                <Check class="w-5 h-5 text-purple-600" />
                                            </div>
                                            <span class="font-bold text-slate-700 leading-tight text-sm md:text-base">{{ i }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 3: Financial Goals (Integrated Card) -->
                    <div class="scroll-reveal animate-fade-up">
                        <div class="bg-white/70 backdrop-blur-2xl px-6 py-10 md:py-14 md:px-16 rounded-[2.5rem] md:rounded-[4rem] border border-white shadow-2xl shadow-emerald-100/50 relative group/card overflow-hidden text-left">
                            <!-- Background Accent -->
                            <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-50/50 rounded-full blur-[100px] opacity-60"></div>
                            
                            <div class="relative z-20 flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                                <!-- Goals Mockup (Inside Card - Left) -->
                                <div class="flex-1 w-full relative">
                                    <div class="relative py-6 w-full flex justify-center lg:justify-center">
                                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[110%] h-[110%] bg-emerald-100/30 rounded-[4rem] blur-[80px] opacity-60 rotate-[-15deg] scale-90"></div>
                                        <div class="relative w-full max-w-[420px] space-y-6 transition-all duration-700 hover:scale-[1.02]">
                                            <!-- Smart Budgeting Card (Restored) -->
                                            <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-xl transition-all duration-500 hover:border-orange-200 group/budget cursor-default">
                                                <div class="flex flex-col gap-6">
                                                    <div class="flex items-center gap-5">
                                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-lg bg-orange-500 group-hover/budget:scale-110 transition-transform">
                                                            <Target class="w-7 h-7 text-white" />
                                                        </div>
                                                        <div class="text-left">
                                                            <h3 class="text-lg font-bold text-slate-900 leading-tight mb-1">{{ __('food_drink') }}</h3>
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-block w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                                                                <span class="text-[11px] font-bold text-orange-600 tracking-wider">{{ __('status_warning') }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-3">
                                                        <div class="flex justify-between items-end">
                                                            <span class="text-[11px] font-bold text-slate-400 tracking-tight">Rp 766.500 <span class="text-slate-300 mx-1">/</span> Rp 800.000</span>
                                                            <span class="text-sm font-bold text-slate-900">96%</span>
                                                        </div>
                                                        <div class="w-full h-3 bg-slate-50 rounded-full overflow-hidden border border-slate-100 shadow-inner">
                                                            <div class="h-full bg-orange-500 rounded-full" style="width: 96%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Savings Goal Card (Restored) -->
                                            <div class="bg-white border border-slate-100 rounded-[2rem] p-6 shadow-xl transition-all duration-500 hover:border-emerald-200 group/goal cursor-default">
                                                <div class="flex items-start justify-between mb-8">
                                                    <div class="flex gap-4 md:gap-5">
                                                        <!-- Goal Icon -->
                                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-200 shrink-0 group-hover/goal:scale-110 transition-transform">
                                                            <ShieldCheck class="w-7 h-7" />
                                                        </div>
                                                        <div class="space-y-1">
                                                            <h3 class="text-lg font-bold text-slate-900 leading-none group-hover/goal:text-indigo-600 transition-colors">{{ __('emergency_fund', {default: 'Dana Darurat'}) }}</h3>
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="px-2 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold capitalize tracking-wider">{{ __('emergency_fund', {default: 'Dana Darurat'}) }}</span>
                                                                <span class="text-xs text-slate-400 flex items-center gap-1 font-medium">
                                                                    <Calendar class="w-3.5 h-3.5" /> 25 Des 2026
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="space-y-4">
                                                    <div class="flex justify-between items-end">
                                                        <div class="space-y-1">
                                                            <p class="text-xs font-bold text-slate-400">{{ __('progress') }}</p>
                                                            <div class="flex items-baseline gap-1.5">
                                                                <span class="text-xl font-bold text-slate-800 tracking-tight leading-none">Rp 15.816.390</span>
                                                                <span class="text-[10px] font-bold text-slate-400">/ Rp 23.000.000</span>
                                                            </div>
                                                        </div>
                                                        <span class="text-sm font-bold text-slate-900 leading-none">69%</span>
                                                    </div>
                                                    <div class="h-3.5 bg-slate-100 rounded-full overflow-hidden shadow-inner border border-slate-200/50">
                                                        <div class="h-full bg-indigo-500 rounded-full relative overflow-hidden transition-all duration-1000 ease-out" style="width: 69%">
                                                            <div class="absolute inset-0 bg-white/20 animate-shimmer scale-x-150 -skew-x-12"></div>
                                                        </div>
                                                    </div>
                                                    <div class="pt-4 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4">
                                                        <div class="flex items-center justify-between w-full md:w-auto md:gap-4">
                                                            <div class="flex -space-x-2">
                                                                <div class="w-7 h-7 rounded-full border-2 border-white flex items-center justify-center text-[8px] font-bold text-white bg-orange-500 ring-1 ring-slate-100" title="Dana Darurat SSMMF">D</div>
                                                                <div class="w-7 h-7 rounded-full border-2 border-white flex items-center justify-center text-[8px] font-bold text-white bg-indigo-500 ring-1 ring-slate-100" title="Bank Jago Dana Darurat">B</div>
                                                            </div>
                                                            <span class="text-[10px] font-bold text-slate-400">{{ __('connected_wallets') }}</span>
                                                        </div>
                                                        <div class="w-full md:w-auto px-4 py-2 bg-indigo-50/50 text-indigo-700 rounded-xl text-[10px] font-bold flex items-center justify-center gap-2 border border-indigo-100/50">
                                                            <Zap class="w-3.5 h-3.5" /> Butuh Rp 718.361 / {{ __('month', {default: 'bulan'}) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Content (Inside Card - Right) -->
                                <div class="flex-1 text-left relative z-20">
                                    <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-emerald-100/50">
                                        <Target class="w-7 h-7" />
                                    </div>
                                    <h3 class="text-xl md:text-3xl font-bold text-slate-900 mb-6 leading-tight">{{ __('feature_goals_title') }}</h3>
                                    <p class="text-sm md:text-base text-slate-500 leading-relaxed font-medium mb-8">
                                        {{ __('feature_goals_desc') }}
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div v-for="i in [__('feature_goals_li_1'), __('feature_goals_li_2'), __('feature_goals_li_3')]" :key="i" 
                                             class="flex items-center gap-4 p-4 rounded-2xl bg-white/50 border border-slate-100 transition-all hover:border-emerald-200 hover:bg-white group/li">
                                            <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0 border border-emerald-100 group-hover/li:scale-110 transition-transform">
                                                <Check class="w-5 h-5 text-emerald-600" />
                                            </div>
                                            <span class="font-bold text-slate-700 leading-tight text-sm md:text-base">{{ i }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-16 md:py-20 bg-white">
            <div class="max-w-[1500px] mx-auto px-4 md:px-6">
                <div class="text-center mb-12 md:mb-16">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold mb-4 scroll-reveal animate-fade-up">
                        <Sparkles class="w-4 h-4" /> {{ __('pricing_plans') }}
                    </div>
                    <h2 class="text-2xl md:text-5xl font-bold text-slate-900 mb-6 scroll-reveal animate-fade-up" style="animation-delay: 0.1s;">{{ __('transparent_pricing_title') }}</h2>
                    <p class="text-base md:text-lg text-slate-500 max-w-2xl mx-auto font-medium scroll-reveal animate-fade-up" style="animation-delay: 0.2s;">
                        {{ __('transparent_pricing_desc') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8 items-stretch">
                    <div 
                        v-for="(plan, index) in plans" 
                        :key="plan.id"
                        class="scroll-reveal animate-fade-up"
                        :style="{ animationDelay: (0.1 + (index * 0.1)) + 's' }"
                    >
                    <div 
                        :class="[
                            'relative flex flex-col p-6 lg:p-8 rounded-3xl lg:rounded-[2.5rem] transition-all duration-700 h-full backdrop-blur-2xl glass-card border border-white/40 overflow-hidden group hover:scale-[1.02] shadow-xl shadow-slate-200/50',
                            plan.color === 'slate' ? 'bg-slate-50/10' :
                            plan.color === 'indigo' ? 'bg-indigo-50/10' :
                            plan.color === 'emerald' ? 'bg-emerald-50/10' : 
                            'bg-purple-50/10',
                            plan.id === 3 ? 'ring-4 ring-emerald-500/10 scale-[1.02] shadow-[0_20px_50px_-12px_rgba(16,185,129,0.3)] z-10 bg-white/80' : ''
                        ]"
                    >
                        <!-- Background Decor -->
                        <div :class="[
                            'absolute -top-24 -right-24 w-48 h-48 rounded-full opacity-5 pointer-events-none group-hover:scale-150 transition-transform duration-1000',
                            plan.color === 'slate' ? 'bg-slate-500' :
                            plan.color === 'indigo' ? 'bg-indigo-500' :
                            plan.color === 'emerald' ? 'bg-emerald-500' : 'bg-purple-500'
                        ]"></div>

                        <!-- Best Value Badge (Master only) -->
                        <div v-if="plan.popular" class="absolute top-4 right-6 bg-emerald-600 text-white text-[10px] font-bold px-4 py-1.5 rounded-full shadow-lg">
                            {{ __('best_value') }}
                        </div>

                        <!-- Card Header -->
                        <div class="mb-8 relative shrink-0 text-left">
                            <div :class="[
                                'w-14 h-14 rounded-2xl flex items-center justify-center mb-6 shadow-inner border transition-all duration-500',
                                plan.color === 'slate' ? 'bg-slate-50 border-slate-100 text-slate-500 shadow-slate-100/50' :
                                plan.color === 'indigo' ? 'bg-indigo-50 border-indigo-100 text-indigo-600 shadow-indigo-100/50 animate-tilt' :
                                plan.color === 'emerald' ? 'bg-emerald-50 border-emerald-100 text-emerald-600 shadow-emerald-100/50 animate-bounce-slow' :
                                'bg-purple-50 border-purple-100 text-purple-600 shadow-purple-100/50 animate-float'
                            ]">
                                <component :is="plan.icon == CheckCircle2 ? CircleCheck : plan.icon" class="w-7 h-7" />
                            </div>
                            <h3 class="text-2xl font-bold text-slate-900 mb-2 tracking-tight">{{ plan.name }}</h3>
                            <p class="text-[12px] font-bold text-slate-400 leading-snug line-clamp-2">{{ plan.description }}</p>
                        </div>

                        <!-- Price Section -->
                        <div class="mb-7 p-4 bg-slate-50/50 rounded-3xl border border-slate-100/50 flex flex-wrap items-center justify-between gap-2 shrink-0">
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-2xl font-bold text-slate-900 tracking-tight">{{ plan.price }}</span>
                                <span class="text-slate-400 font-bold text-[11px]">{{ plan.period }}</span>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="mb-10 flex-grow text-left">
                            <h4 class="text-[11px] font-bold text-slate-400 mb-3 ml-1 text-left">{{ __('key_benefits') }}</h4>
                            <ul class="grid grid-cols-1 gap-y-2.5 gap-x-2 h-full">
                                <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-3 group/feat">
                                    <div class="mt-0.5 w-5 h-5 bg-white shadow-sm border border-slate-100 rounded-full flex items-center justify-center flex-shrink-0 transition-colors group-hover/feat:border-indigo-200">
                                        <Check class="w-3 h-3 text-emerald-500 stroke-[4]" />
                                    </div>
                                    <span class="text-xs font-bold text-slate-600 leading-tight">{{ feature }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Action Button -->
                        <button
                            @click="navigateToCheckout(plan.id)"
                            :class="[
                                'w-full py-4.5 px-6 rounded-2xl font-bold text-[13px] flex items-center justify-center gap-3 transition-all duration-500 active:scale-95',
                                plan.color === 'slate' ? 'bg-slate-900 text-white hover:bg-slate-800' :
                                plan.color === 'indigo' ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-100' :
                                plan.color === 'emerald' ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-100' :
                                'bg-purple-600 text-white hover:bg-purple-700 shadow-lg shadow-purple-100'
                            ]"
                        >
                            {{ plan.buttonText }} 
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section (Hidden until real users are available) -->
        <section v-if="false" class="py-24 relative overflow-hidden">
            <!-- Background Decorations -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full -z-10 pointer-events-none">
                <div class="absolute top-1/4 left-10 w-96 h-96 bg-indigo-500/5 rounded-full blur-[120px] animate-pulse"></div>
                <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-emerald-500/5 rounded-full blur-[120px] animate-pulse" style="animation-delay: 2s"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="mb-16">
                    <h2 class="text-3xl md:text-5xl font-bold text-slate-900 mb-6 tracking-tight">
                        {{ __('testimonials_title') }}
                    </h2>
                    <p class="text-lg text-slate-500 max-w-2xl mx-auto font-medium">
                        {{ __('testimonials_subtitle') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                    <div v-for="testimonial in testimonialsData" :key="testimonial.name"
                         class="glass-card group relative p-8 rounded-[32px] border border-white/40 shadow-xl transition-all duration-700 hover:scale-[1.02] hover:-translate-y-1 flex flex-col h-full items-start">
                        
                        <!-- Quote Icon -->
                        <div class="absolute -top-4 left-8 w-10 h-10 rounded-2xl flex items-center justify-center shadow-lg border-2 border-white transition-all duration-500 group-hover:scale-110"
                             :class="[
                                testimonial.color === 'indigo' ? 'bg-indigo-600 text-white' :
                                testimonial.color === 'emerald' ? 'bg-emerald-600 text-white' :
                                testimonial.color === 'purple' ? 'bg-purple-600 text-white' : 'bg-slate-900 text-white'
                             ]">
                            <span class="text-2xl font-serif">“</span>
                        </div>

                        <!-- Content -->
                        <div class="mt-4 text-left flex flex-col h-full">
                            <p class="text-[13px] font-bold text-slate-600 leading-relaxed mb-8 italic flex-grow">
                                "{{ testimonial.quote }}"
                            </p>

                            <!-- User Info -->
                            <div class="flex items-center gap-4 mt-auto w-full">
                                <div class="relative flex-shrink-0">
                                    <div class="absolute -inset-1 rounded-full blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                                         :class="[
                                            testimonial.color === 'indigo' ? 'bg-indigo-500' :
                                            testimonial.color === 'emerald' ? 'bg-emerald-500' :
                                            testimonial.color === 'purple' ? 'bg-purple-500' : 'bg-slate-400'
                                         ]"></div>
                                    <img :src="testimonial.avatar" :alt="testimonial.name" 
                                         class="relative w-12 h-12 rounded-full border-2 border-white object-cover">
                                </div>
                                <div class="overflow-hidden">
                                    <h4 class="text-xs font-bold text-slate-900 leading-tight truncate">{{ testimonial.name }}</h4>
                                    <p class="text-[10px] font-bold text-slate-400 truncate">{{ testimonial.role }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Animated Circles -->
                        <div class="absolute -bottom-4 -right-4 w-24 h-24 rounded-full -z-10 blur-2xl transition-all duration-700 opacity-20 group-hover:scale-150 group-hover:opacity-30"
                             :class="[
                                testimonial.color === 'indigo' ? 'bg-indigo-500' :
                                testimonial.color === 'emerald' ? 'bg-emerald-500' :
                                testimonial.color === 'purple' ? 'bg-purple-500' : 'bg-slate-400'
                             ]"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-32 px-6">
            <div class="max-w-5xl mx-auto relative group">
                <div class="absolute inset-0 bg-indigo-600 rounded-[2.5rem] md:rounded-[3rem] blur-[80px] opacity-10 group-hover:opacity-20 transition-opacity"></div>
                <div class="relative bg-slate-900 rounded-[2.5rem] md:rounded-[3rem] p-12 md:p-20 text-center text-white overflow-hidden">
                    <div class="absolute -top-20 -right-20 w-80 h-80 bg-indigo-500 rounded-full blur-[100px] opacity-20"></div>
                    <div class="relative z-10 scroll-reveal animate-fade-up">
                        <h2 class="text-2xl md:text-5xl font-bold mb-8 leading-tight">{{ __('ready_to_master') }}</h2>
                        <p class="text-base md:text-lg text-slate-400 mb-12 max-w-2xl mx-auto font-medium">
                            {{ __('cta_desc') }}
                        </p>
                        <Link :href="route('register')" class="inline-flex items-center gap-2 px-6 py-3.5 md:px-8 md:py-4 bg-white text-slate-900 rounded-2xl text-base md:text-lg font-bold hover:bg-slate-50 transition-all hover:scale-105">
                            {{ __('get_free_account') }} <ArrowRight class="w-5 h-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="pt-16 md:pt-24 pb-8 md:pb-12 bg-slate-950 text-slate-400 border-t border-slate-800/50 overflow-hidden relative">
            <div class="max-w-7xl mx-auto px-6 relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-10 md:gap-12 mb-12 md:mb-20">
                    <!-- Brand Column -->
                    <div class="space-y-6">
                        <Link href="/" class="flex items-center gap-3 group/logo cursor-pointer" @click="scrollToTop">
                            <div class="flex items-center justify-center transition-all hover:scale-110">
                                <img src="/img/logo_vibefinance.png" class="h-8 w-auto object-contain brightness-110" alt="VibeFinance Logo">
                            </div>
                            <div class="flex flex-col leading-tight">
                                <span class="text-2xl tracking-tight text-white" style="font-family:'Outfit', sans-serif;">
                                    <span class="font-semibold text-white">Vibe</span><span class="font-light text-indigo-400">Finance</span>
                                </span>
                                <span class="text-[10px] font-medium text-slate-500">Powered by terasweb.id</span>
                            </div>
                        </Link>
                        <p class="text-slate-400 max-w-sm leading-relaxed font-medium text-sm md:text-base">
                            {{ __('footer_tagline') }}
                        </p>
                    </div>

                    <!-- Legal & Contact Column -->
                    <div class="space-y-6 w-full md:w-auto">
                        <ul class="flex flex-col md:flex-row md:flex-wrap gap-4 md:gap-x-8 md:gap-y-4">
                            <li><a href="mailto:info@terasweb.id" class="hover:text-white transition-colors flex items-center gap-2 group/link w-full md:w-auto p-2 md:p-0 rounded-lg md:rounded-none bg-slate-900/50 md:bg-transparent border border-slate-800 md:border-none"><div class="w-1.5 h-1.5 rounded-full bg-indigo-500 md:scale-0 group-hover/link:scale-100 transition-transform"></div> info@terasweb.id</a></li>
                            <li><Link :href="route('privacy.policy')" class="hover:text-white transition-colors flex items-center gap-2 group/link w-full md:w-auto p-2 md:p-0 rounded-lg md:rounded-none bg-slate-900/50 md:bg-transparent border border-slate-800 md:border-none"><div class="w-1.5 h-1.5 rounded-full bg-indigo-500 md:scale-0 group-hover/link:scale-100 transition-transform"></div> {{ __('privacy_policy') }}</Link></li>
                            <li><Link :href="route('terms.service')" class="hover:text-white transition-colors flex items-center gap-2 group/link w-full md:w-auto p-2 md:p-0 rounded-lg md:rounded-none bg-slate-900/50 md:bg-transparent border border-slate-800 md:border-none"><div class="w-1.5 h-1.5 rounded-full bg-indigo-500 md:scale-0 group-hover/link:scale-100 transition-transform"></div> {{ __('terms_of_service') }}</Link></li>
                            <li><Link :href="route('cookies.policy')" class="hover:text-white transition-colors flex items-center gap-2 group/link w-full md:w-auto p-2 md:p-0 rounded-lg md:rounded-none bg-slate-900/50 md:bg-transparent border border-slate-800 md:border-none"><div class="w-1.5 h-1.5 rounded-full bg-indigo-500 md:scale-0 group-hover/link:scale-100 transition-transform"></div> {{ __('cookies_policy') }}</Link></li>
                        </ul>
                    </div>
                </div>
                <!-- Bottom Footer -->
                <div class="pt-8 border-t border-slate-900 flex flex-col-reverse md:flex-row justify-between items-center gap-6 text-center md:text-left">
                    <p class="text-sm font-bold text-slate-600 italic">
                        &copy; 2026 VibeFinance powered by terasweb.id
                    </p>
                    <div class="flex items-center gap-4 text-sm font-bold text-slate-500 bg-slate-900/50 px-4 py-2 rounded-full border border-slate-800/50">
                        <button @click="switchLanguage('en')" :class="['transition-colors hover:text-white', currentLocale === 'en' ? 'text-white' : 'text-slate-500']">EN</button>
                        <span class="text-slate-700">|</span>
                        <button @click="switchLanguage('id')" :class="['transition-colors hover:text-white', currentLocale === 'id' ? 'text-white' : 'text-slate-500']">ID</button>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes pulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.1); }
}
@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-20px) scale(1.05); }
}
@keyframes fade-up {
    0% { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}
@keyframes mockup-lift {
    0% { opacity: 0; transform: translateY(100px) scale(0.95); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes shine {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Scroll Reveal Base */
.scroll-reveal {
    opacity: 0;
    will-change: transform, opacity;
}

.scroll-reveal.is-visible.animate-fade-up {
    animation: fade-up 1s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.scroll-reveal.is-visible.animate-mockup-lift {
    animation: mockup-lift 1.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.animate-neon-pulse {
    animation: neon-pulse 3s ease-in-out infinite;
}
@keyframes neon-pulse {
    0%, 100% { opacity: 0.8; transform: scaleX(1); filter: brightness(1) blur(1px); }
    50% { opacity: 1; transform: scaleX(1.1); filter: brightness(1.8) blur(3px); }
}
.animate-shine {
    animation: shine 0.8s ease-in-out;
}
.animate-pulse {
    animation: pulse 8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.grabbing-state {
    opacity: 0.9;
    transform: scale(1.05) rotate(1deg);
    box-shadow: 0 40px 80px rgba(255,165,0,0.15);
    z-index: 100;
}
.sortable-ghost {
    opacity: 0.1 !important;
}
.custom-scrollbar::-webkit-scrollbar {
    display: none;
}
.custom-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0); }
    50% { transform: translateY(-15px) rotate(2deg); }
}
@keyframes tilt {
    0%, 100% { transform: rotate(0); }
    25% { transform: rotate(1deg); }
    75% { transform: rotate(-1deg); }
}
.animate-bounce-slow { animation: bounce-slow 4s ease-in-out infinite; }
.animate-float { animation: float 6s ease-in-out infinite; }
.animate-tilt { animation: tilt 3s ease-in-out infinite; }
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}
.animate-shimmer {
    animation: shimmer 2s infinite;
}
</style>
