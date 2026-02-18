<script setup>
import { Head, Link, router, usePage, Deferred } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import confetti from 'canvas-confetti';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";
import { 
    LayoutDashboard,
    Wallet, 
    TrendingUp, 
    TrendingDown, 
    PieChart, 
    Plus, 
    ArrowUpRight, 
    ArrowDownRight,
    ArrowRight,
    Clock,
    Target,
    Zap,
    ShieldCheck,
    AlertCircle,
    Calendar,
    Globe,
    Banknote,
    CreditCard,
    ShoppingBag,
    Car,
    Home,
    MoreHorizontal,
    Settings,
    Tag,
    FileText,
    Crown,
    Rocket,
    Sparkles,
    CheckCircle2,
    PieChart as AnalysisIcon
} from 'lucide-vue-next';
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    summary: Object,
    available_months: Array,
    subscription: Object,
    deferred_charts: Object,
    deferred_breakdown: Object,
    deferred_transactions: Array
});

// Shorthand for easy access with robust defaults to prevent initialization crashes
const data = computed(() => {
    const summaryDefaults = { 
        selected_month: '', 
        selected_month_label: '', 
        net_worth: 0, 
        monthly_income: 0, 
        monthly_expense: 0 
    };
    const subscriptionDefaults = { 
        is_premium: false, 
        plan_id: 1, 
        plan_name: 'Starter', 
        days_remaining: 0 
    };

    return {
        summary: props.summary || summaryDefaults,
        available_months: props.available_months || [],
        subscription: props.subscription || subscriptionDefaults,
        charts: props.deferred_charts || { labels: [], income: [], expense: [] },
        wallets: props.deferred_breakdown?.wallets || [],
        categories: props.deferred_breakdown?.categories || [],
        recent_transactions: props.deferred_transactions || []
    };
});

const page = usePage();
const __ = (key, replacements = {}) => {
    let translation = page.props.translations?.[key] || key;
    Object.keys(replacements).forEach(r => {
        translation = translation.replace(`:${r}`, replacements[r]);
    });
    return translation;
};

const selectedMonth = ref(props.summary?.selected_month || '');
const driverObj = ref(null);

const skipHTML = `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">${__('skip_tutorial')}</button>
</div>`;

// Dynamic greeting based on time
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour >= 5 && hour < 12) return __('good_morning');
    if (hour >= 12 && hour < 18) return __('good_afternoon');
    return __('good_evening');
});

// Chart visibility toggles
const showIncome = ref(true);
const showExpense = ref(true);

const toggleIncome = () => {
    // Prevent hiding both lines
    if (!showIncome.value || showExpense.value) {
        showIncome.value = !showIncome.value;
    }
};

const toggleExpense = () => {
    // Prevent hiding both lines
    if (!showExpense.value || showIncome.value) {
        showExpense.value = !showExpense.value;
    }
};

watch(selectedMonth, (newMonth) => {
    router.get('/dashboard', { month: newMonth }, { 
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['summary', 'available_months', 'deferred_charts', 'deferred_breakdown', 'deferred_transactions', 'subscription']
    });
});

// Sync local state if props change (e.g. Back button)
watch(() => props.summary?.selected_month, (newMonth) => {
    if (newMonth && newMonth !== selectedMonth.value) {
        selectedMonth.value = newMonth;
    }
});

const formatCurrency = (value) => {
    const val = Number(value);
    if (isNaN(val)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(val);
};

const commonChartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false },
        zoom: { enabled: false },
        background: 'transparent',
    },
    dataLabels: { enabled: false },
    stroke: { 
        show: true, 
        curve: 'smooth', 
        width: 3 
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.15,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    grid: {
        borderColor: 'rgba(15, 23, 42, 0.05)',
        strokeDashArray: 4,
        padding: { left: 20, right: 20, top: 0, bottom: 0 }
    },
    xaxis: {
        type: 'category',
        categories: data.value.charts.labels,
        axisBorder: { show: true, color: '#cbd5e1' },
        axisTicks: { show: false },
        tooltip: { enabled: false }, // Menghilangkan kotak abu-abu "06" di bawah
        labels: {
            style: { colors: '#94a3b8', fontSize: '12px', fontWeight: 500 },
            formatter: (val, timestamp, opts) => {
                const day = parseInt(val);
                // Menampilkan tanggal ganjil (01, 03, 05...)
                if (day % 2 !== 0) {
                    if (day === 1 && opts && opts.categoryIndex > 0) return '';
                    return day < 10 ? `0${day}` : `${day}`;
                }
                return '';
            }
        }
    },
    yaxis: {
        show: false
    },
    tooltip: {
        theme: 'light',
        x: { 
            show: true,
            formatter: (val) => {
                const day = String(parseInt(val)).padStart(2, '0');
                return `${day} ${data.value.summary.selected_month_label}`;
            }
        },
        y: { formatter: (val) => formatCurrency(val) }
    },
    legend: { show: false }
}));

const statisticsSeries = computed(() => {
    const series = [];
    if (showIncome.value) {
        series.push({ name: __('total_income'), data: data.value.charts.income, color: '#10b981' });
    }
    if (showExpense.value) {
        series.push({ name: __('total_expense'), data: data.value.charts.expense, color: '#f59e0b' });
    }
    return series;
});

// PIE CHART OPTIONS with hover effects
const pieChartOptions = computed(() => ({
    chart: {
        type: 'pie',
        background: 'transparent',
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800,
            animateGradually: {
                enabled: true,
                delay: 150
            },
            dynamicAnimation: {
                enabled: true,
                speed: 350
            }
        }
    },
    labels: (data.value.categories || []).slice(0, 3).map(item => item.category),
    colors: ['#10b981', '#f43f5e', '#f59e0b'],
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        fontSize: '13px',
        fontWeight: 600,
        labels: {
            colors: '#64748b'
        },
        markers: {
            width: 10,
            height: 10,
            radius: 10
        },
        itemMargin: {
            horizontal: 12,
            vertical: 8
        }
    },
    dataLabels: {
        enabled: true,
        formatter: function (val) {
            return Math.round(val) + "%"
        },
        style: {
            fontSize: '14px',
            fontWeight: 700,
            colors: ['#fff']
        },
        dropShadow: {
            enabled: true,
            top: 1,
            left: 1,
            blur: 1,
            opacity: 0.45
        }
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['#fff']
    },
    plotOptions: {
        pie: {
            expandOnClick: true,
            donut: {
                size: '0%'
            },
            customScale: 1,
            offsetX: 0,
            offsetY: 0,
            dataLabels: {
                offset: 0,
                minAngleToShowLabel: 10
            }
        }
    },
    states: {
        hover: {
            filter: {
                type: 'darken',
                value: 0.1
            }
        },
        active: {
            allowMultipleDataPointsSelection: false,
            filter: {
                type: 'darken',
                value: 0.2
            }
        }
    },
    tooltip: {
        enabled: true,
        theme: 'dark',
        y: {
            formatter: (val) => formatCurrency(data.value.categories.slice(0, 3).find((cat, idx) => {
                const total = data.value.categories.reduce((a, b) => a + parseFloat(b.total), 0);
                return Math.round((parseFloat(cat.total) / total) * 100) === Math.round(val);
            })?.total || 0)
        }
    }
}));

const categorySeries = computed(() => {
    const total = data.value.categories.reduce((a, b) => a + parseFloat(b.total), 0);
    return (data.value.categories || []).slice(0, 3).map(item => Math.round((parseFloat(item.total) / total) * 100));
});

const getTypeColor = (type) => {
    switch (type) {
        case 'cash':
            return {
                bg: 'bg-emerald-50 text-emerald-600',
                border: 'border-emerald-100'
            };
        case 'ewallet':
            return {
                bg: 'bg-purple-50 text-purple-600',
                border: 'border-purple-100'
            };
        case 'bank':
            return {
                bg: 'bg-orange-50 text-orange-600',
                border: 'border-orange-100'
            };
        default:
            return {
                bg: 'bg-indigo-50 text-indigo-600',
                border: 'border-indigo-100'
            };
    }
};

const getDotColor = (type) => {
    switch (type) {
        case 'cash': return 'bg-emerald-500';
        case 'ewallet': return 'bg-purple-500';
        case 'bank': return 'bg-orange-500';
        default: return 'bg-indigo-500';
    }
};
const savingsRate = computed(() => {
    const income = data.value.summary.monthly_income || 0;
    const expense = data.value.summary.monthly_expense || 0;
    
    if (income === 0 && expense === 0) return 0;
    // If income is 0 but there are expenses, savings rate is effectively -100% (or we can just show 0 to be safe)
    if (income === 0) return 0;

    const rate = ((income - expense) / income) * 100;
    return Math.round(rate);
});

// Check if user has any transactions
const hasTransactions = computed(() => {
    return data.value.recent_transactions && data.value.recent_transactions.length > 0;
});

// Success Celebration Logic
const showSuccessModal = ref(false);

const fireConfetti = () => {
    const duration = 3 * 1000;
    const animationEnd = Date.now() + duration;
    const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

    const randomInRange = (min, max) => Math.random() * (max - min) + min;

    const interval = setInterval(function() {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        const particleCount = 50 * (timeLeft / duration);
        // since particles fall down, start a bit higher than random
        confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } });
        confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } });
    }, 250);
};

    // Product Tour Master Logic
    const startTour = (phase = 'welcome') => {
        const steps = [];
        const isMobile = window.innerWidth < 768;

        if (phase === 'welcome') {
            steps.push({
                popover: {
                    title: `<span class="text-xl font-bold">${__('tour_welcome_title')}</span>`,
                    description: __('tour_welcome_desc') + skipHTML,
                    side: "bottom",
                    align: 'start'
                }
            });
            steps.push({
                element: '#step-metrics',
                popover: {
                    title: `<span class="text-lg font-bold">${__('tour_metrics_title')}</span>`,
                    description: __('tour_metrics_desc') + skipHTML,
                    side: "bottom",
                    align: 'start'
                }
            });
            steps.push({
                element: isMobile ? '#mobile-hub-wallets' : '#nav-wallets',
                popover: {
                    title: `<span class="text-lg font-bold">${__('tour_wallets_title')}</span>`,
                    description: __('tour_wallets_desc') + skipHTML,
                    side: isMobile ? "top" : "bottom",
                    align: 'start'
                }
            });
        } else if (phase === 'dashboard_explanation') {
            steps.push({
                element: '#step-metrics',
                popover: {
                    title: `<span class="text-lg font-bold">${__('tour_pulse_title')}</span>`,
                    description: __('tour_pulse_desc') + skipHTML,
                    side: "bottom",
                    align: 'start'
                }
            });
            if (!isMobile) {
                steps.push({
                    element: '#tour-charts',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_charts_title')}</span>`,
                        description: __('tour_charts_desc') + skipHTML,
                        side: "top",
                        align: 'start'
                    }
                });
                steps.push({
                    element: '#tour-breakdown',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_breakdown_title')}</span>`,
                        description: __('tour_breakdown_desc') + skipHTML,
                        side: "top",
                        align: 'start'
                    }
                });
                steps.push({
                    element: '#step-activity',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_activity_title')}</span>`,
                        description: __('tour_activity_desc') + skipHTML,
                        side: "top",
                        align: 'start'
                    }
                });
            }
            steps.push({
                element: isMobile ? '#mobile-hub-analysis' : '#nav-analysis',
                popover: {
                    title: `<span class="text-lg font-bold">${__('tour_analysis_title')}</span>`,
                    description: __('tour_analysis_desc') + skipHTML,
                    side: isMobile ? "top" : "bottom",
                    align: 'start'
                }
            });
        } else if (phase.startsWith('hub_to_')) {
            const isMobile = window.innerWidth < 768;
            const target = phase.replace('hub_to_', '');
            
            let element = '';
            let title = '';
            let description = '';
            
            if (target === 'wallets') {
                element = isMobile ? '#mobile-hub-wallets' : '#nav-wallets';
                title = __('tour_wallets_title');
                description = __('tour_wallets_desc') + skipHTML;
            } else if (target === 'analysis') {
                element = isMobile ? '#mobile-hub-analysis' : '#nav-analysis';
                title = __('tour_analysis_title');
                description = __('tour_analysis_desc') + skipHTML;
            } else if (target === 'transactions') {
                element = isMobile ? '#mobile-hub-transactions' : '#nav-transactions';
                title = __('tour_transactions_title');
                description = __('tour_transactions_desc') + skipHTML;
            } else if (target === 'budget') {
                element = isMobile ? '#mobile-hub-budget' : '#nav-budget';
                title = __('tour_budget_title');
                description = __('tour_budget_desc') + skipHTML;
            } else if (target === 'goals') {
                element = isMobile ? '#mobile-hub-goals' : '#nav-goals';
                title = __('tour_goals_title');
                description = __('tour_goals_desc') + skipHTML;
            } else if (target === 'categories') {
                element = isMobile ? '#mobile-hub-categories' : '#nav-categories';
                title = __('tour_categories_title');
                description = __('tour_categories_desc') + skipHTML;
            } else if (target === 'tracker') {
                element = isMobile ? '#mobile-hub-tracker' : '#nav-tracker';
                title = __('tour_tracker_title');
                description = __('tour_tracker_desc') + skipHTML;
            } else if (target === 'reports') {
                element = isMobile ? '#mobile-hub-reports' : '#nav-reports';
                title = __('tour_reports_title');
                description = __('tour_reports_desc') + skipHTML;
            }

            steps.push({
                element: element,
                popover: {
                    title: `<span class="text-lg font-bold">${title}</span>`,
                    description: description,
                    side: isMobile ? "top" : "bottom",
                    align: 'start'
                }
            });
        } else if (phase === 'final_congrats') {
            steps.push({
                popover: {
                    title: `<span class="text-xl font-bold">${__('mission_accomplished')}</span>`,
                    description: __('mission_accomplished_desc'),
                    side: "bottom",
                    align: 'start'
                }
            });
        }

        driverObj.value = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayOpacity: 0.85,
            stagePadding: 5,
            onNextClick: () => {
                if (driverObj.value.isLastStep()) {
                    if (phase === 'welcome') {
                        localStorage.setItem('tour_state', 'wallet_setup');
                        router.visit('/wallets');
                    } else if (phase === 'dashboard_explanation') {
                        if (isMobile) {
                            localStorage.setItem('tour_state', 'analysis_intro');
                            router.visit('/analysis');
                        } else {
                            localStorage.setItem('tour_state', 'analysis_intro');
                            router.visit('/analysis');
                        }
                    } else if (phase.startsWith('hub_to_')) {
                        const target = phase.replace('hub_to_', '');
                        const states = {
                            'wallets': 'wallet_setup',
                            'analysis': 'analysis_intro',
                            'transactions': 'transaction_setup',
                            'budget': 'budget_setup',
                            'goals': 'goals_setup',
                            'categories': 'categories_setup',
                            'tracker': 'tracker_intro',
                            'reports': 'reports_intro'
                        };
                        const routes = {
                            'wallets': '/wallets',
                            'analysis': '/analysis',
                            'transactions': '/transactions',
                            'budget': '/budget',
                            'goals': '/goals',
                            'categories': '/categories',
                            'tracker': '/tracker',
                            'reports': '/reports'
                        };
                        localStorage.setItem('tour_state', states[target]);
                        router.visit(routes[target]);
                    } else if (phase === 'final_congrats') {
                        localStorage.removeItem('tour_state');
                        router.post('/user/complete-tour', {}, {
                            onFinish: () => {
                                confetti({
                                    particleCount: 150,
                                    spread: 70,
                                    origin: { y: 0.6 },
                                    colors: ['#4f46e5', '#10b981', '#f59e0b']
                                });
                            }
                        });
                    }
                    driverObj.value.destroy();
                } else {
                    driverObj.value.moveNext();
                }
            },
            steps: steps
        });

        driverObj.value.drive();
    };

    const checkTourTriggers = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const tourState = localStorage.getItem('tour_state');
        const tourCompleted = page.props.auth.user.has_completed_tour;
        const forceRestart = urlParams.get('restart_tour') === 'true';



        // Guard against duplicate triggers (unless forceRestart)
        if (!forceRestart && driverObj.value && document.querySelector('.driver-popover')) {

            return;
        }

        if (forceRestart) {

            
            // 1. Reset Database status via API
            router.post('/user/reset-tour', {}, {
                preserveScroll: true,
                onSuccess: () => {

                    // 2. Clear LocalStorage
                    localStorage.removeItem('tour_state');
                    // 3. Remove URL parameter
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, '', newUrl);
                    // 4. Start the tour
                    setTimeout(() => startTour('welcome'), 800);
                }
            });
            return;
        }

        if (!tourCompleted || tourState) {

            // Force cleanup of any lingering tour elements
            const popover = document.querySelector('.driver-popover');
            const overlay = document.querySelector('.driver-overlay');
            if (popover) popover.remove();
            if (overlay) overlay.remove();

            if (!tourState || tourState === 'welcome') {

                if (!tourState) {
                    localStorage.setItem('tour_state', 'welcome');
                }
                setTimeout(() => startTour('welcome'), 800);
            } else if (tourState === 'transaction_setup') {

                localStorage.setItem('tour_state', 'dashboard_explanation');
                setTimeout(() => startTour('dashboard_explanation'), 800);
            } else if (tourState === 'dashboard_explanation') {

                setTimeout(() => startTour('dashboard_explanation'), 800);
            } else if (tourState === 'wallet_setup') {

                setTimeout(() => startTour('hub_to_wallets'), 800);
            } else if (tourState === 'analysis_intro') {

                setTimeout(() => startTour('hub_to_analysis'), 800);
            } else if (tourState === 'budget_setup') {

                setTimeout(() => startTour('hub_to_budget'), 800);
            } else if (tourState === 'goals_setup') {

                setTimeout(() => startTour('hub_to_goals'), 800);
            } else if (tourState === 'categories_setup') {

                setTimeout(() => startTour('hub_to_categories'), 800);
            } else if (tourState === 'tracker_intro') {

                setTimeout(() => startTour('hub_to_tracker'), 800);
            } else if (tourState?.startsWith('hub_to_')) {

            } else if (tourState === 'reports_intro' || tourState === 'final_congrats') {

                localStorage.setItem('tour_state', 'final_congrats');
                setTimeout(() => startTour('final_congrats'), 800);
            } else {

            }
        } else {

        }
    };

    onMounted(() => {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Success Modal Check
        if (urlParams.get('upgrade_success') === '1') {
            showSuccessModal.value = true;
            const newUrl = window.location.pathname;
            window.history.replaceState({}, '', newUrl);
            fireConfetti();
            // Tour will be triggered when success modal is closed
        } else {
            checkTourTriggers();
        }
        window.addEventListener('skip-tour', () => {
            localStorage.removeItem('tour_state');
            if (driverObj.value) {
                driverObj.value.destroy();
            }
            router.post('/user/complete-tour', {}, { 
                preserveScroll: true, 
                preserveState: true 
            });
        });
    });

    onUnmounted(() => {
        if (driverObj.value) {
            driverObj.value.destroy();
        }
    });

    // Handle Inertia navigation triggers
    watch(() => page.url, () => {

        checkTourTriggers();
    });

    const closeSuccessModal = () => {
        showSuccessModal.value = false;
        // Start tour after a short delay for smooth transition
        setTimeout(() => {
            checkTourTriggers();
        }, 500);
    };

const marketingInsight = computed(() => {
    const sub = data.value.subscription;
        if (!sub) return null;

        // Using a similar logic to Pricing.vue for consistency
        if (!sub.is_premium) {
            return {
                title: __('insight_marketing_pro_title'),
                text: __('insight_marketing_pro_text'),
                cta: __('cta_go_pro'),
                link: '/subscription',
                theme: 'indigo',
                icon: Rocket
            };
        }

        switch (sub.plan_name) {
            case 'Professional':
                return {
                    title: __('insight_marketing_master_title'),
                    text: __('insight_marketing_master_text'),
                    cta: __('cta_upgrade'),
                    link: '/subscription',
                    theme: 'emerald',
                    icon: Crown
                };
            case 'Master':
                return {
                    title: __('insight_marketing_lifetime_title'),
                    text: __('insight_marketing_lifetime_text'),
                    cta: __('cta_buy_lifetime'),
                    link: '/subscription',
                    theme: 'purple',
                    icon: Zap
                };
            case 'Lifetime':
                return {
                    title: __('insight_marketing_founder_title'),
                    text: __('insight_marketing_founder_text'),
                    cta: __('cta_view_perks'),
                    link: '/subscription',
                    theme: 'emerald',
                    icon: Sparkles
                };
            default:
                return {
                    title: __('insight_marketing_default_title'),
                    text: __('insight_marketing_default_text'),
                    cta: __('cta_manage_plan'),
                    link: '/subscription',
                    theme: 'indigo',
                    icon: ShieldCheck
                };
        }
    });
</script>

<template>
    <Head :title="__('dashboard')" />

    <Layout>
        <div class="max-w-[1600px] mx-auto space-y-8 pb-24">
           <!-- SIMPLIFIED HEADER -->
            <header id="step-dashboard-welcome" class="mb-4 lg:mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-30">
                <div class="space-y-1">
                    <h1 class="text-2xl md:text-4xl font-bold text-slate-900 leading-tight">
                        {{ greeting }}, {{ $page.props.auth?.user?.name || __('user') }}
                    </h1>
                    <p class="text-sm md:text-base font-normal text-slate-400">{{ __('financial_overview_for') }} <span class="font-semibold text-slate-900">{{ data.summary.selected_month_label }}</span></p>
                </div>
                
                <!-- Month/Year Dropdown -->
                <div class="flex items-center gap-4">
                    <div class="w-full md:w-56 relative">
                        <select 
                            v-model="selectedMonth" 
                            class="appearance-none bg-white border border-slate-200 text-slate-900 rounded-xl px-6 py-3 pr-12 font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer hover:bg-slate-50 transition-all shadow-sm w-full"
                        >
                            <option v-for="month in data.available_months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>
                        <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                    </div>
                </div>
            </header>

            <!-- SUBSCRIPTION INTELLIGENCE CARD (Dashboard Context) -->
            <div v-if="data.subscription && data.subscription.plan_id !== 4" class="relative group mb-8">
                <!-- Subtle Glow Backdrop -->
                <div class="absolute -inset-4 bg-gradient-to-r from-indigo-500/5 to-purple-500/5 rounded-3xl blur-3xl opacity-50"></div>
                
                <div class="relative bg-white border border-slate-100/60 rounded-3xl p-5 lg:p-6 shadow-sm overflow-hidden flex flex-col lg:flex-row lg:items-center gap-6 backdrop-blur-3xl hover:shadow-md transition-all duration-300">
                    <!-- 1. Status Section -->
                    <div class="flex items-center gap-4 lg:border-r lg:border-slate-100 lg:pr-8">
                        <div 
                            :class="[
                                data.subscription.plan_id === 2 ? 'bg-indigo-600 shadow-indigo-100' :
                                data.subscription.plan_id === 3 ? 'bg-emerald-600 shadow-emerald-100' :
                                data.subscription.plan_id === 4 ? 'bg-purple-600 shadow-purple-100' : 'bg-slate-600',
                                'w-12 h-12 rounded-xl flex items-center justify-center shadow-lg shrink-0 transition-colors duration-500'
                            ]"
                        >
                            <Rocket v-if="data.subscription.plan_id === 2" class="w-6 h-6 text-white" />
                            <Crown v-else-if="data.subscription.plan_id === 3" class="w-6 h-6 text-white" />
                            <Zap v-else-if="data.subscription.plan_id === 4" class="w-6 h-6 text-white" />
                            <ShieldCheck v-else class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-lg font-bold text-slate-900 leading-none">{{ data.subscription.plan_name }}</h3>
                                <span v-if="data.subscription.is_premium" 
                                    :class="[
                                        data.subscription.plan_id === 2 ? 'bg-indigo-50 text-indigo-600 border-indigo-100' :
                                        data.subscription.plan_id === 3 ? 'bg-emerald-50 text-emerald-600 border-emerald-100' :
                                        data.subscription.plan_id === 4 ? 'bg-purple-50 text-purple-600 border-purple-100' : 'bg-slate-50 text-slate-600 border-slate-100',
                                        'px-2 py-0.5 text-[9px] font-bold rounded-full border transition-colors duration-500'
                                    ]"
                                >
                                    {{ data.subscription.plan_id === 2 ? 'Pro' : data.subscription.plan_id === 3 ? 'Master' : 'Lifetime' }}
                                </span>
                            </div>
                            <p class="text-[11px] font-bold text-slate-400">
                                {{ data.subscription.is_premium ? __('premium_access_active') : __('limited_free_access') }}
                            </p>
                        </div>
                    </div>

                    <!-- 2. Countdown Section -->
                    <div class="grid grid-cols-2 gap-6 flex-1 lg:pl-2">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5">{{ __('time_remaining') }}</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-bold text-slate-900">
                                    {{ data.subscription.days_remaining > 3650 ? '∞' : data.subscription.days_remaining }}
                                </span>
                                <span class="text-[10px] font-bold text-slate-400">
                                    {{ data.subscription.days_remaining > 3650 ? '' : __('days') }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5">{{ __('expiry_date') }}</p>
                            <div 
                                :class="[
                                    data.subscription.plan_id === 2 ? 'text-indigo-600' :
                                    data.subscription.plan_id === 3 ? 'text-emerald-600' :
                                    data.subscription.plan_id === 4 ? 'text-purple-600' : 'text-slate-600',
                                    'text-sm font-bold'
                                ]"
                            >
                                {{ data.subscription.subscription_until || __('forever') }}
                            </div>
                        </div>
                    </div>

                    <!-- 3. Smart Insight Section (Condensed) -->
                    <div 
                        :class="[
                            marketingInsight.theme === 'indigo' ? 'bg-indigo-50/50 border-indigo-100/50' :
                            marketingInsight.theme === 'emerald' ? 'bg-emerald-50/50 border-emerald-100/50' :
                            marketingInsight.theme === 'purple' ? 'bg-purple-50/50 border-purple-100/50' : 'bg-slate-50/50 border-slate-100/50',
                            'relative border rounded-2xl p-4 flex items-center justify-between gap-4 min-w-[300px] lg:min-w-[350px] transition-colors duration-500'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div 
                                :class="[
                                    marketingInsight.theme === 'indigo' ? 'bg-indigo-100 text-indigo-600' :
                                    marketingInsight.theme === 'emerald' ? 'bg-emerald-100 text-emerald-600' :
                                    marketingInsight.theme === 'purple' ? 'bg-purple-100 text-purple-600' : 'bg-slate-100 text-slate-600',
                                    'w-8 h-8 rounded-lg flex items-center justify-center shrink-0'
                                ]"
                            >
                                <component :is="marketingInsight.icon" class="w-4 h-4" />
                            </div>
                            <div class="space-y-0.5">
                                <h4 
                                    :class="[
                                        marketingInsight.theme === 'indigo' ? 'text-indigo-900' :
                                        marketingInsight.theme === 'emerald' ? 'text-emerald-900' :
                                        marketingInsight.theme === 'purple' ? 'text-purple-900' : 'text-slate-900',
                                        'text-[11px] font-bold tracking-tight'
                                    ]"
                                >
                                    {{ marketingInsight.title }}
                                </h4>
                                <p class="text-[11px] font-medium text-slate-500 leading-tight line-clamp-1">{{ marketingInsight.text }}</p>
                            </div>
                        </div>
                        <Link 
                            v-if="data.subscription.plan_id !== 4"
                            :href="marketingInsight.link"
                            :class="[
                                marketingInsight.theme === 'indigo' ? 'bg-indigo-600 hover:bg-indigo-700' :
                                marketingInsight.theme === 'emerald' ? 'bg-emerald-600 hover:bg-emerald-700' :
                                marketingInsight.theme === 'purple' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-slate-600 hover:bg-slate-700',
                                'shrink-0 px-3 py-1.5 text-white rounded-lg text-[10px] font-bold transition-colors shadow-sm whitespace-nowrap'
                            ]"
                        >
                            {{ marketingInsight.cta }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ENHANCED FINANCIAL METRICS - CAROUSEL ON MOBILE (No Scrollbar) -->
            <!-- Always show metrics (values will be 0) -->
            <div id="step-metrics" class="overflow-x-auto lg:overflow-visible no-scrollbar -mx-4 px-4 lg:mx-0 lg:px-0 flex lg:block flex-nowrap scroll-smooth py-4 lg:py-0 -my-4 lg:my-0 mb-6 lg:mb-10">
                <div class="flex lg:grid lg:grid-cols-4 gap-6 min-w-max lg:min-w-0 no-scrollbar">
                    <!-- Net Worth -->
                    <div class="w-[90vw] lg:w-auto relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-200">
                        <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                            <Wallet class="w-24 h-24 text-white" />
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                    <Wallet class="w-6 h-6 text-white" />
                                </div>
                                <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('total_net_worth') }}</h3>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(data.summary.net_worth).split(',')[0] }}</h2>
                                <p class="text-indigo-100 font-medium text-sm">{{ __('across_all_wallets') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Income -->
                    <div class="w-[90vw] lg:w-auto relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-200">
                        <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                            <TrendingUp class="w-24 h-24 text-white" />
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                    <TrendingUp class="w-6 h-6 text-white" />
                                </div>
                                <h3 class="font-bold text-lg text-white/90 tracking-tight">Monthly Income</h3>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(data.summary.monthly_income).split(',')[0] }}</h2>
                                <p class="text-emerald-100 font-medium text-sm">{{ data.summary.selected_month_label }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Expense -->
                    <div class="w-[90vw] lg:w-auto relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-200">
                        <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                            <TrendingDown class="w-24 h-24 text-white" />
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                    <TrendingDown class="w-6 h-6 text-white" />
                                </div>
                                <h3 class="font-bold text-lg text-white/90 tracking-tight">Monthly Expense</h3>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(data.summary.monthly_expense).split(',')[0] }}</h2>
                                <p class="text-rose-100 font-medium text-sm">{{ data.summary.selected_month_label }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Savings Rate -->
                    <div id="step-savings-rate" class="w-[90vw] lg:w-auto relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg shadow-purple-200">
                        <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                            <Target class="w-24 h-24 text-white" />
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                    <Target class="w-6 h-6 text-white" />
                                </div>
                                <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('savings_rate') }}</h3>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ savingsRate }}%</h2>
                                <p class="text-purple-100 font-medium text-sm">{{ __('of_total_income') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MOBILE NAVIGATION HUB (APP LAUNCHER STYLE) -->
            <div class="lg:hidden mt-6 pb-10">
                <div class="mb-6 ml-1">
                    <h2 class="text-xl font-bold text-slate-900">{{ __('main_hub') }}</h2>
                    <p class="text-xs font-semibold text-slate-400">{{ __('explore_modules_desc') }}</p>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-4 gap-y-8">
                    <Link href="/transactions" id="mobile-hub-transactions" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <Banknote class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('transactions') }}</span>
                    </Link>
                    <Link href="/wallets" id="mobile-hub-wallets" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                            <Wallet class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('wallets') }}</span>
                    </Link>
                    <Link href="/analysis" id="mobile-hub-analysis" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-all">
                            <AnalysisIcon class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('analysis') }}</span>
                    </Link>
                    <Link href="/budget" id="mobile-hub-budget" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition-all">
                            <Zap class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('budget') }}</span>
                    </Link>
                    <Link href="/goals" id="mobile-hub-goals" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-all">
                            <Target class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('goals') }}</span>
                    </Link>
                    <Link href="/tracker" id="mobile-hub-tracker" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('tracker') }}</span>
                    </Link>
                    <Link href="/categories" id="mobile-hub-categories" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-slate-600 group-hover:bg-slate-700 group-hover:text-white transition-all">
                            <Tag class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('categories') }}</span>
                    </Link>
                    <Link href="/reports" id="mobile-hub-reports" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition-all">
                            <FileText class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('reports') }}</span>
                    </Link>
                    <Link href="/settings" class="flex flex-col items-center gap-3 active:scale-95 transition-all group">
                        <div class="p-4 rounded-[1.5rem] bg-white border border-slate-100 shadow-sm text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <Settings class="w-6 h-6" />
                        </div>
                        <span class="text-[12px] font-bold text-slate-600 tracking-tight">{{ __('settings') }}</span>
                    </Link>
                </div>
            </div>

            <!-- STATISTICS & EXPENSE BREAKDOWN (Desktop Only) -->
            <div class="hidden lg:grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Statistics Chart (2/3) -->
                <Deferred data="deferred_charts">
                    <template #fallback>
                        <div class="lg:col-span-2 h-[498px] bg-white rounded-[2rem] border border-slate-100 p-8 space-y-8 animate-pulse shadow-sm">
                            <div class="flex items-center justify-between">
                                <div class="space-y-2">
                                    <div class="h-6 w-48 bg-slate-100 rounded-md"></div>
                                    <div class="h-4 w-32 bg-slate-50 rounded-md"></div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="h-8 w-24 bg-slate-100 rounded-xl"></div>
                                    <div class="h-8 w-24 bg-slate-100 rounded-xl"></div>
                                </div>
                            </div>
                            <div class="flex-1 w-full bg-slate-50/50 rounded-2xl"></div>
                        </div>
                    </template>
                    <div id="tour-charts" class="lg:col-span-2 bg-white border border-slate-100 rounded-[2rem] p-8 space-y-8 shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <h2 class="text-xl font-bold text-slate-900">{{ __('income_vs_expense') }}</h2>
                                <p class="text-sm font-medium text-slate-400">{{ __('yearly_analytics_desc') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button @click="toggleIncome" :class="[
                                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
                                        showIncome ? 'bg-emerald-500 text-white shadow-md shadow-emerald-200 hover:bg-emerald-600' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'
                                    ]">
                                    <div :class="['w-2 h-2 rounded-full', showIncome ? 'bg-white' : 'bg-slate-400']"></div>
                                    <span>{{ __('income') }}</span>
                                </button>
                                <button @click="toggleExpense" :class="[
                                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all',
                                        showExpense ? 'bg-orange-500 text-white shadow-md shadow-orange-200 hover:bg-orange-600' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'
                                    ]">
                                    <div :class="['w-2 h-2 rounded-full', showExpense ? 'bg-white' : 'bg-slate-400']"></div>
                                    <span>{{ __('expense') }}</span>
                                </button>
                            </div>
                        </div>
                        <div class="h-[350px] w-full">
                            <VueApexCharts v-if="hasTransactions" type="area" height="100%" width="100%" :options="commonChartOptions" :series="statisticsSeries" />
                            <div v-else class="text-center px-8 py-16">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <TrendingUp class="w-8 h-8 text-slate-300" />
                                </div>
                                <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('no_analysis_yet') }}</h4>
                                <p class="text-slate-500 text-sm mb-4">{{ __('no_analysis_desc') }}</p>
                                <Link href="/transactions" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-all active:scale-95">
                                    <Plus class="w-4 h-4" />
                                    <span>{{ __('add_transaction') }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </Deferred>

                <!-- Expense Breakdown PIE CHART (1/3) -->
                <Deferred data="deferred_breakdown">
                    <template #fallback>
                        <div class="h-[498px] bg-white rounded-[2rem] border border-slate-100 p-8 flex flex-col animate-pulse shadow-sm">
                            <div class="space-y-2 mb-8">
                                <div class="h-6 w-32 bg-slate-100 rounded-md"></div>
                                <div class="h-4 w-48 bg-slate-50 rounded-md"></div>
                            </div>
                            <div class="flex-1 flex items-center justify-center">
                                <div class="w-48 h-48 rounded-full border-[20px] border-slate-50"></div>
                            </div>
                            <div class="flex justify-center gap-4 mt-8">
                                <div class="h-4 w-16 bg-slate-100 rounded-full"></div>
                                <div class="h-4 w-16 bg-slate-100 rounded-full"></div>
                            </div>
                        </div>
                    </template>
                    <div id="tour-breakdown" class="bg-white border border-slate-100 rounded-[2rem] p-8 flex flex-col shadow-sm hover:shadow-md transition-all duration-300 h-full">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-xl font-bold text-slate-900">{{ __('top_expenses') }}</h2>
                        </div>
                        <p class="text-sm font-medium text-slate-400 mb-6">{{ __('expense_breakdown') }}</p>
                        <div class="flex justify-center items-center flex-1">
                            <div v-if="hasTransactions" class="relative w-full max-w-[320px]">
                                <VueApexCharts type="pie" height="320" :options="pieChartOptions" :series="categorySeries" />
                            </div>
                            <div v-else class="text-center px-8 py-16">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <PieChart class="w-8 h-8 text-slate-300" />
                                </div>
                                <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('no_results') }}</h4>
                                <p class="text-slate-500 text-sm mb-4">{{ __('no_data_available_desc') }}</p>
                                <Link href="/transactions" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-all active:scale-95">
                                    <Plus class="w-4 h-4" />
                                    <span>{{ __('add_transaction') }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </Deferred>
            </div>

            <!-- RECENT TRANSACTIONS (Desktop Only) -->
            <Deferred data="deferred_transactions">
                <template #fallback>
                    <div class="hidden lg:block bg-white border border-slate-100 rounded-[2rem] p-8 space-y-6 animate-pulse shadow-sm min-h-[400px]">
                        <div class="flex items-center justify-between mb-4">
                            <div class="space-y-2">
                                <div class="h-8 w-64 bg-slate-100 rounded-md"></div>
                                <div class="h-4 w-48 bg-slate-50 rounded-md"></div>
                            </div>
                            <div class="h-10 w-32 bg-slate-100 rounded-xl"></div>
                        </div>
                        <div class="space-y-4">
                            <div v-for="i in 5" :key="i" class="h-16 w-full bg-slate-50/50 rounded-2xl"></div>
                        </div>
                    </div>
                </template>
                <div id="step-activity" class="hidden lg:block bg-white border border-slate-100 rounded-[2rem] overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between">
                        <div class="space-y-1">
                            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('recent_transactions') }}</h2>
                            <p class="text-sm font-semibold text-slate-400">{{ __('latest_financial_activities') }}</p>
                        </div>
                        <Link href="/transactions" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-all shadow-sm hover:shadow-indigo-600/20 active:scale-95">
                            {{ __('view_all') }} <ArrowRight class="w-4 h-4" />
                        </Link>
                    </div>

                    <div class="overflow-x-auto pb-6">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50/50 border-b border-slate-100">
                                <tr class="text-xs font-bold text-slate-500 tracking-tight">
                                    <th class="px-8 py-4 pl-10">{{ __('date') }}</th>
                                    <th class="px-8 py-4">{{ __('description') }}</th>
                                    <th class="px-8 py-4">{{ __('wallet') }}</th>
                                    <th class="px-8 py-4">{{ __('category') }}</th>
                                    <th class="px-8 py-4 text-right pr-10">{{ __('amount') }}</th>
                                </tr>
                            </thead>
                            <!-- Empty State Table Body -->
                            <tbody v-if="!hasTransactions" class="divide-y divide-slate-50">
                                <tr>
                                    <td colspan="5" class="px-8 py-16 text-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                            <Clock class="w-8 h-8 text-slate-300" />
                                        </div>
                                        <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('no_transactions') }}</h4>
                                        <p class="text-slate-500 text-sm mb-4">{{ __('no_activity_desc') }}</p>

                                        <Link href="/transactions" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-all active:scale-95">
                                            <Plus class="w-4 h-4" />
                                            <span>{{ __('add_transaction') }}</span>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else class="divide-y divide-slate-50">
                                <tr v-for="tx in data.recent_transactions.slice(0, 5)" :key="tx.id" class="group hover:bg-slate-50/80 transition-colors">
                                    <td class="px-8 py-4 pl-10">
                                        <div class="text-sm font-semibold text-slate-700 whitespace-nowrap">
                                            {{ new Date(tx.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <div class="text-sm font-normal text-slate-900 group-hover:text-indigo-900 transition-colors">
                                            {{ tx.description }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full" :class="getDotColor(typeof tx.wallet === 'object' ? tx.wallet?.type : 'cash')"></div>
                                            <span class="text-sm font-semibold text-slate-600">{{ typeof tx.wallet === 'object' ? tx.wallet?.name : (tx.wallet || 'Cash') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold text-white border border-white/20 shadow-sm" :class="tx.category?.color || 'bg-slate-500'">
                                            {{ tx.category ? tx.category.name : 'Uncategorized' }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-4 text-right pr-10">
                                        <span :class="['text-sm font-bold tabular-nums block', tx.type === 'expense' ? 'text-slate-900' : 'text-emerald-600']">
                                            {{ tx.type === 'expense' ? '-' : '+' }}{{ formatCurrency(tx.amount).split(',')[0] }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </Deferred>
        </div>

        <!-- Success Upgrade Celebration Modal -->
        <Teleport to="body">
            <div v-if="showSuccessModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 font-sans">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xl animate-in fade-in duration-500" @click="closeSuccessModal"></div>
                
                <!-- Modal Content -->
                <div class="relative bg-white rounded-[3rem] shadow-2xl shadow-indigo-500/30 border-4 border-white/50 w-full max-w-md overflow-hidden animate-in zoom-in-95 slide-in-from-bottom-10 duration-700">
                    
                    <!-- Confetti Canvas (handled by JS but container here just in case needed for positioning) -->
                    
                    <!-- Premium Background Mesh -->
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50 opacity-100"></div>
                    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-[80px]"></div>
                    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-60 h-60 bg-purple-500/10 rounded-full blur-[60px]"></div>

                    <div class="relative pt-12 pb-10 px-8 text-center">
                        
                        <!-- Floating Icon -->
                        <div class="mb-8 relative inline-block">
                            <div class="absolute inset-0 bg-amber-400 blur-2xl opacity-20 animate-pulse"></div>
                            <div class="w-24 h-24 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-[2rem] flex items-center justify-center shadow-xl shadow-indigo-500/30 rotate-3 transform transition-transform hover:rotate-6 duration-300">
                                <Crown class="w-12 h-12 text-white drop-shadow-md" />
                            </div>
                            <!-- Floating Elements -->
                            <div class="absolute -top-6 -right-6 animate-bounce delay-100">
                                <div class="w-12 h-12 bg-white rounded-2xl shadow-lg flex items-center justify-center rotate-12">
                                    <Sparkles class="w-6 h-6 text-amber-500" />
                                </div>
                            </div>
                            <div class="absolute -bottom-4 -left-6 animate-bounce delay-700">
                                <div class="w-10 h-10 bg-white rounded-2xl shadow-lg flex items-center justify-center -rotate-12">
                                    <Zap class="w-5 h-5 text-indigo-500" />
                                </div>
                            </div>
                        </div>

                        <h2 class="text-3xl font-bold text-slate-900 mb-2 tracking-tight leading-tight">
                            {{ __('you_are_pro_now') }}
                        </h2>
                        <p class="text-slate-500 font-medium mb-8 text-sm leading-relaxed px-4">
                            {{ __('pro_upgrade_welcome', { name: $page.props.auth.user.name }) }}
                        </p>

                        <!-- Feature List -->
                        <div class="space-y-3 mb-8 text-left bg-white/60 backdrop-blur-sm rounded-3xl p-5 border border-white/50 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                                </div>
                                <span class="text-sm font-bold text-slate-700">{{ __('unlimited_wallets_budgets') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                    <CheckCircle2 class="w-3.5 h-3.5 text-indigo-600" />
                                </div>
                                <span class="text-sm font-bold text-slate-700">{{ __('advanced_ai_insights') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                                    <CheckCircle2 class="w-3.5 h-3.5 text-purple-600" />
                                </div>
                                <span class="text-sm font-bold text-slate-700">{{ __('priority_support') }}</span>
                            </div>
                        </div>

                        <button 
                            @click="closeSuccessModal"
                            class="w-full py-4 bg-slate-900 text-white rounded-[1.5rem] font-bold text-sm shadow-xl shadow-slate-900/20 hover:bg-slate-800 transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2 group relative overflow-hidden"
                        >
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                            {{ __('lets_get_started') }}
                            <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </Layout>
</template>
