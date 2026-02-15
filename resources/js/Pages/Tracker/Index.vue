<script setup>
import Layout from '../../Shared/Layout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";

const driverObj = ref(null);
const skipHTML = `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">Skip Tutorial</button>
</div>`;
import { 
    Wallet, 
    Calendar,
    TrendingUp,
    TrendingDown,
    Zap,
    X,
    ArrowRight,
    Plus,
    Crown,
    Sparkles,
    Lock
} from 'lucide-vue-next';
import PremiumUpsellModal from '@/Shared/PremiumUpsellModal.vue';
import { formatCurrency } from '../../Utilities/formatCurrency';
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
    periods: Array,
    matrix: Array,
    totals: Object,
    filters: Object,
    is_premium: Boolean
});

const showUpsellModal = ref(false);
const upsellTitle = ref('Premium Feature');
const upsellDescription = ref('Unlock this feature with the Professional Plan.');

const triggerUpsell = (title, description) => {
    upsellTitle.value = title;
    upsellDescription.value = description;
    showUpsellModal.value = true;
};

const range = ref(props.filters.range || '6m');

watch(range, (value) => {
    router.get(route('tracker'), { range: value }, {
        preserveState: true,
        preserveScroll: true,
        only: ['periods', 'matrix', 'totals', 'filters'],
    });
});

const getChangeColor = (current, previous) => {
    if (previous === undefined || previous === null) return 'text-slate-500';
    if (current > previous) return 'text-emerald-600 font-bold';
    if (current < previous) return 'text-rose-600 font-bold';
    return 'text-slate-500';
};

const getChangeIcon = (current, previous) => {
    if (previous === undefined || previous === null) return '';
    if (current > previous) return '↑';
    if (current < previous) return '↓';
    return '-';
};

const totalNetWorth = computed(() => {
    if (props.periods.length === 0) return 0;
    const latestIndex = props.periods.length - 1;
    const key = props.periods[latestIndex].key;
    return props.totals[key] || 0;
});

const firstNetWorth = computed(() => {
    if (props.periods.length === 0) return 0;
    const key = props.periods[0].key;
    return props.totals[key] || 0;
});

const periodChange = computed(() => totalNetWorth.value - firstNetWorth.value);

const avgMonthlyGrowth = computed(() => {
    if (props.periods.length <= 1) return 0;
    return periodChange.value / (props.periods.length - 1);
});

// Mobile Logic
const showHistoryModal = ref(false);
const selectedWalletForHistory = ref(null);

const openHistoryMobile = (wallet) => {
    if (window.innerWidth < 768) {
        selectedWalletForHistory.value = wallet;
        showHistoryModal.value = true;
    }
};

const getLatestBalance = (balances) => {
    if (!props.periods.length) return 0;
    const key = props.periods[props.periods.length - 1].key;
    return balances[key] || 0;
};

const getLatestChangeStat = (balances) => {
    if (props.periods.length < 2) return { value: 0, percent: 0 };
    const curKey = props.periods[props.periods.length - 1].key;
    const prevKey = props.periods[props.periods.length - 2].key;
    
    const current = balances[curKey] || 0;
    const previous = balances[prevKey] || 0;
    
    const diff = current - previous;
    const pct = previous !== 0 ? (diff / Math.abs(previous)) * 100 : 0;
    
    return { value: diff, percent: pct };
};

const startTour = () => {
    const isMobile = window.innerWidth < 768;
    const steps = [];

    steps.push({
        element: '#step-tracker-range',
        popover: {
            title: `<span class="text-lg font-bold">${__('tour_tracker_range_title')}</span>`,
            description: __('tour_tracker_range_desc') + skipHTML,
            side: "bottom",
            align: 'start'
        }
    });

    steps.push({
        element: '#step-tracker-total',
        popover: {
            title: `<span class="text-lg font-bold">${__('tour_tracker_total_title')}</span>`,
            description: __('tour_tracker_total_desc') + skipHTML,
            side: "bottom",
            align: 'start'
        }
    });

    steps.push({
        element: isMobile ? '#step-tracker-mobile-list' : '#step-tracker-matrix',
        popover: {
            title: `<span class="text-lg font-bold">${__('tour_tracker_matrix_title')}</span>`,
            description: isMobile 
                ? __('tour_tracker_mobile_list_desc') + skipHTML
                : __('tour_tracker_matrix_desc') + skipHTML,
            side: "bottom",
            align: 'start'
        }
    });

    steps.push({
        element: isMobile ? '#mobile-nav-home' : '#nav-reports',
        popover: {
            title: isMobile ? __('tour_return_hub_title') : `<span class="text-lg font-bold">${__('tour_return_reports_title')}</span>`,
            description: isMobile 
                ? __('tour_return_hub_desc') + skipHTML
                : __('tour_return_reports_desc') + skipHTML,
            side: "bottom",
            align: 'start'
        }
    });

    driverObj.value = driver({
        showProgress: true,
        animate: true,
        allowClose: true,
        overlayOpacity: 0.85,
        stagePadding: 10,
        onNextClick: () => {
            if (driverObj.value.isLastStep()) {
                if (isMobile) {
                    localStorage.setItem('tour_state', 'hub_to_reports');
                    router.visit('/dashboard');
                } else {
                    localStorage.setItem('tour_state', 'reports_intro');
                    router.visit('/reports');
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
    const tourState = localStorage.getItem('tour_state');
    const tourCompleted = page.props.auth.user.has_completed_tour;
    const catchUpStates = [
        'welcome', 'wallet_setup', 'transaction_setup', 'dashboard_explanation', 
        'analysis_intro', 'budget_setup', 'goals_setup', 'categories_setup', 'hub_to_tracker', 'tracker_intro'
    ];
    

    // Guard against duplicate triggers
    if (driverObj.value && document.querySelector('.driver-popover')) {
        return;
    }

    if (!tourState || (tourState && catchUpStates.includes(tourState))) {
        if (!tourState || tourState !== 'tracker_intro') {
            if (!tourState && tourCompleted) {
                return;
            }
            localStorage.setItem('tour_state', 'tracker_intro');
        }

        // Force cleanup
        const popover = document.querySelector('.driver-popover');
        const overlay = document.querySelector('.driver-overlay');
        if (popover) popover.remove();
        if (overlay) overlay.remove();

        setTimeout(startTour, 800);
    } else {
    }
};

onMounted(() => {
    checkTourTriggers();
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

</script>

<template>
    <Head :title="__('tracker_title')" />

    <Layout>
        <!-- SIMPLIFIED HEADER -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-30">
            <div class="space-y-1">
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('tracker_title') }}</h1>
                <p class="text-base text-slate-500 font-medium">{{ __('tracker_desc') }}</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div id="step-tracker-range" class="w-full md:w-56 relative">
                    <select 
                        v-model="range"
                        class="appearance-none bg-white border border-slate-200 text-slate-900 rounded-xl px-6 py-3 pr-12 font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer hover:bg-slate-50 transition-all shadow-sm w-full"
                    >
                        <option value="3m">{{ __('time_range_3m') }}</option>
                        <option value="6m">{{ __('time_range_6m') }}{{ !is_premium ? ' (Pro)' : '' }}</option>
                        <option value="1y">{{ __('time_range_1y') }}{{ !is_premium ? ' (Pro)' : '' }}</option>
                        <option value="all">{{ __('time_range_all') }}{{ !is_premium ? ' (Pro)' : '' }}</option>
                    </select>
                    <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                </div>
            </div>
        </header>

        <div class="max-w-[1600px] space-y-8">
            <template v-if="matrix.length > 0">
                <!-- SUMMARY GRID -->
                <div class="flex overflow-x-auto snap-x snap-mandatory md:grid md:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-12 -mx-4 px-4 md:mx-0 md:px-0 no-scrollbar md:overflow-visible">
                    <!-- Current Net Worth -->
                    <div id="step-tracker-total" class="relative overflow-hidden rounded-[2rem] p-8 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-200 min-w-[90vw] md:min-w-0 snap-center">
                        <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                            <Wallet class="w-32 h-32 text-white" />
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                    <Wallet class="w-6 h-6 text-white" />
                                </div>
                                <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('current_net_worth') }}</h3>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(totalNetWorth, 'IDR').split(',')[0] }}</h2>
                                <p class="text-indigo-100 font-medium text-sm">{{ __('aggregated_balance') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Period Progress -->
                    <div 
                        class="relative overflow-hidden rounded-[2rem] p-8 text-white shadow-lg transition-all duration-300 min-w-[90vw] md:min-w-0 snap-center"
                        :class="periodChange >= 0 ? 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-200' : 'bg-gradient-to-br from-rose-500 to-pink-600 shadow-rose-200'"
                    >
                        <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                            <component :is="periodChange >= 0 ? TrendingUp : TrendingDown" class="w-32 h-32 text-white" />
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                    <component :is="periodChange >= 0 ? TrendingUp : TrendingDown" class="w-6 h-6 text-white" />
                                </div>
                                <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('period_progress') }}</h3>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-3xl font-bold tracking-tight tabular-nums">
                                    {{ periodChange >= 0 ? '+' : '' }}{{ formatCurrency(periodChange, 'IDR').split(',')[0] }}
                                </h2>
                                <p class="text-white/80 font-medium text-sm">{{ __('period_change_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Avg Monthly Growth -->
                    <div class="relative overflow-hidden rounded-[2rem] p-8 bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg shadow-purple-200 min-w-[90vw] md:min-w-0 snap-center">
                        <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                            <Zap class="w-32 h-32 text-white" />
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                    <Zap class="w-6 h-6 text-white" />
                                </div>
                                <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('avg_monthly_growth') }}</h3>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-3xl font-bold tracking-tight tabular-nums">
                                    {{ avgMonthlyGrowth >= 0 ? '+' : '' }}{{ formatCurrency(avgMonthlyGrowth, 'IDR').split(',')[0] }}
                                </h2>
                                <p class="text-purple-100 font-medium text-sm">{{ __('monthly_performance') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Matrix Table (Desktop) -->
                <div id="step-tracker-matrix" class="hidden md:flex bg-white overflow-hidden shadow-sm rounded-2xl border border-slate-100 flex-col mb-12 relative">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-bold text-lg text-slate-800">{{ __('historical_matrix') }}</h3>
                    </div>

                    <!-- Premium Lock Overlay for Historical Matrix -->
                    <div v-if="!is_premium && range !== '3m'" class="absolute inset-0 z-40 flex flex-col items-center justify-center text-center p-8 bg-white/40 backdrop-blur-[12px] rounded-2xl">
                        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-100 mb-5 border border-indigo-50 animate-bounce-subtle">
                            <Crown class="w-8 h-8 text-amber-500" />
                        </div>
                        <h4 class="text-slate-900 font-black text-xl mb-2 tracking-tight">{{ __('unlock_history_title') }}</h4>
                        <p class="text-sm text-slate-600 mb-8 max-w-[340px] font-medium leading-relaxed">{{ __('unlock_history_desc') }}</p>
                        <button @click="triggerUpsell(__('unlock_history_title'), __('unlock_history_desc'))" class="px-8 py-4 bg-emerald-600 text-white rounded-2xl text-sm font-black shadow-xl shadow-emerald-200 hover:bg-emerald-700 transition-all active:scale-95 flex items-center gap-2 group">
                            <Sparkles class="w-4 h-4 group-hover:rotate-12 transition-transform" />
                            {{ __('unlock_history_btn') }}
                        </button>
                    </div>
                    
                    <div class="relative overflow-x-auto" :class="{ 'blur-[8px] select-none pointer-events-none': !is_premium && range !== '3m' }">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50/50 text-slate-500">
                                <tr>
                                    <!-- Sticky Column Header -->
                                    <th scope="col" class="sticky left-0 z-20 bg-slate-50 px-6 py-4 font-bold border-b border-r border-slate-200 min-w-[200px] shadow-[4px_0_24px_-2px_rgba(0,0,0,0.05)]">
                                        {{ __('asset_wallet') }}
                                    </th>
                                    <!-- Month Headers -->
                                    <th 
                                        v-for="period in periods" 
                                        :key="period.key"
                                        scope="col" 
                                        class="px-8 py-4 font-semibold border-b border-slate-100 text-right min-w-[160px]"
                                    >
                                        {{ period.label }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <!-- Wallet Rows -->
                                <tr 
                                    v-for="(row, rowIndex) in matrix" 
                                    :key="row.id" 
                                    class="group transition-all duration-200"
                                    :class="rowIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50/30'"
                                >
                                    <!-- Sticky Wallet Name -->
                                    <td 
                                        class="sticky left-0 z-10 px-6 py-5 font-bold text-slate-700 border-r border-slate-100 transition-colors duration-200"
                                        :class="rowIndex % 2 === 0 ? 'bg-white group-hover:bg-slate-50' : 'bg-slate-50 group-hover:bg-slate-100'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="w-2 h-2 rounded-full" :class="row.type === 'bank' ? 'bg-orange-400' : (row.type === 'ewallet' ? 'bg-purple-400' : 'bg-emerald-400')"></div>
                                            <div>
                                                <span class="block text-sm font-bold leading-tight">{{ row.name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Monthly Balances -->
                                    <td 
                                        v-for="(period, index) in periods" 
                                        :key="period.key"
                                        class="px-8 py-5 text-right tabular-nums transition-colors duration-200"
                                        :class="rowIndex % 2 === 0 ? 'group-hover:bg-slate-50/50' : 'group-hover:bg-slate-100/50'"
                                    >
                                        <div class="flex flex-col items-end gap-1">
                                            <span class="text-sm font-semibold tracking-tight text-slate-900">
                                                {{ formatCurrency(row.balances[period.key], row.currency || 'IDR').split(',')[0] }}
                                            </span>
                                            <span 
                                                v-if="index > 0 && row.balances[periods[index-1].key] !== 0" 
                                                class="text-[10px] font-medium"
                                                :class="getChangeColor(row.balances[period.key], row.balances[periods[index-1].key])"
                                            >
                                                {{ row.balances[period.key] > row.balances[periods[index-1].key] ? '▲' : (row.balances[period.key] < row.balances[periods[index-1].key] ? '▼' : '') }}
                                                {{ Math.abs(((row.balances[period.key] - row.balances[periods[index-1].key]) / Math.abs(row.balances[periods[index-1].key]) * 100)).toFixed(1) }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Total Row -->
                                <tr class="bg-indigo-50/30 font-bold border-t-2 border-indigo-100">
                                    <td class="sticky left-0 z-10 bg-indigo-50 px-6 py-4 text-slate-900 border-r border-indigo-100/50">
                                        <span class="text-sm font-bold ml-7">{{ __('total_net_worth') }}</span>
                                    </td>
                                    <td 
                                        v-for="(period, index) in periods" 
                                        :key="period.key"
                                        class="px-8 py-4 text-right text-slate-900 text-sm tabular-nums"
                                    >
                                        {{ formatCurrency(totals[period.key], 'IDR').split(',')[0] }}
                                    </td>
                                </tr>

                                <!-- Net Change Row -->
                                <tr class="bg-slate-50/50 border-t border-slate-100">
                                    <td class="sticky left-0 z-10 bg-slate-50 px-6 py-4 text-slate-900 font-medium border-r border-slate-100">
                                        <span class="text-sm font-semibold ml-7">{{ __('monthly_change') }}</span>
                                    </td>
                                    <td 
                                        v-for="(period, index) in periods" 
                                        :key="period.key"
                                        class="px-8 py-4 text-right tabular-nums font-bold text-sm text-slate-900"
                                    >
                                        <template v-if="index > 0">
                                            {{ totals[period.key] - totals[periods[index-1].key] >= 0 ? '+' : '' }}{{ formatCurrency(totals[period.key] - totals[periods[index-1].key], 'IDR').split(',')[0] }}
                                        </template>
                                        <template v-else>-</template>
                                    </td>
                                </tr>

                                <!-- Growth % Row -->
                                <tr class="bg-slate-50/50 border-t border-slate-100">
                                    <td class="sticky left-0 z-10 bg-slate-50 px-6 py-4 text-slate-900 font-medium border-r border-slate-100">
                                        <span class="text-sm font-semibold ml-7">{{ __('growth_pct') }}</span>
                                    </td>
                                    <td 
                                        v-for="(period, index) in periods" 
                                        :key="period.key"
                                        class="px-8 py-4 text-right tabular-nums font-bold text-sm"
                                        :class="index > 0 ? (totals[period.key] - totals[periods[index-1].key] >= 0 ? 'text-emerald-500' : 'text-rose-500') : 'text-slate-400'"
                                    >
                                        <template v-if="index > 0 && totals[periods[index-1].key] !== 0">
                                            {{ totals[period.key] - totals[periods[index-1].key] >= 0 ? '▲' : '▼' }}
                                            {{ Math.abs(((totals[period.key] - totals[periods[index-1].key]) / Math.abs(totals[periods[index-1].key]) * 100)).toFixed(1) }}%
                                        </template>
                                        <template v-else>-</template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile Wallet List -->
                <div class="md:hidden space-y-4 mb-24 relative">
                    <!-- Premium Lock Overlay for Mobile List -->
                    <div v-if="!is_premium && range !== '3m'" class="absolute inset-0 z-40 flex flex-col items-center justify-center text-center p-6 bg-white/60 backdrop-blur-md rounded-[2rem] border-2 border-dashed border-indigo-100 min-h-[400px]">
                        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-100 mb-4 border border-indigo-50 animate-bounce-subtle">
                            <Crown class="w-7 h-7 text-amber-500" />
                        </div>
                        <h4 class="text-slate-900 font-black text-lg mb-1">{{ __('professional_history') }}</h4>
                        <p class="text-xs text-slate-600 mb-6 max-w-[240px] font-medium leading-relaxed">{{ __('professional_history_desc') }}</p>
                        <button @click="triggerUpsell(__('professional_history'), __('professional_history_desc'))" class="px-6 py-3.5 bg-emerald-600 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-200 active:scale-95 transition-all flex items-center gap-2">
                            <Sparkles class="w-4 h-4" />
                            {{ __('upgrade_now') }}
                        </button>
                    </div>

                    <div id="step-tracker-mobile-list" :class="{ 'blur-md opacity-50 pointer-events-none select-none': !is_premium && range !== '3m' }">
                        <div 
                            v-for="row in matrix" 
                            :key="row.id" 
                            @click="openHistoryMobile(row)"
                            class="bg-white rounded-[1.5rem] p-5 shadow-sm border border-slate-200 active:scale-[0.98] transition-all cursor-pointer flex items-center justify-between group mb-4"
                        >
                        <div class="flex items-center gap-3">
                            <div class="w-2.5 h-2.5 rounded-full shrink-0" :class="row.type === 'bank' ? 'bg-orange-400' : (row.type === 'ewallet' ? 'bg-purple-400' : 'bg-emerald-400')"></div>
                            <div>
                                <h3 class="text-[14px] font-bold text-slate-900 leading-tight">{{ row.name }}</h3>
                                <p class="text-xs font-semibold text-slate-400 mt-0.5 capitalize">{{ row.type }}</p>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <p class="text-[14px] font-bold text-slate-900 tabular-nums">{{ formatCurrency(getLatestBalance(row.balances), row.currency).split(',')[0] }}</p>
                            <div class="flex items-center justify-end gap-1 mt-0.5">
                                <span 
                                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-md flex items-center gap-0.5"
                                    :class="getLatestChangeStat(row.balances).value >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                                >
                                    {{ getLatestChangeStat(row.balances).value >= 0 ? '▲' : '▼' }} 
                                    {{ Math.abs(getLatestChangeStat(row.balances).percent).toFixed(1) }}%
                                </span>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </template><div v-else class="bg-white border border-slate-100 rounded-[2rem] p-12 md:p-20 text-center space-y-6 shadow-sm animate-in fade-in zoom-in duration-500">
                <div class="w-16 h-16 bg-slate-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                    <Zap class="w-8 h-8 text-slate-300" />
                </div>
                <div class="space-y-1">
                    <h2 class="text-base font-bold text-slate-900">{{ __('no_tracking_data') }}</h2>
                    <p class="text-slate-500 max-w-sm mx-auto font-medium text-sm">{{ __('no_tracking_desc') }}</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 justify-center pt-2">
                    <Link 
                        href="/transactions"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 active:scale-95 transition-all flex items-center justify-center gap-2"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ __('add_transactions') }}</span>
                    </Link>
                    <Link 
                        href="/wallets"
                        class="bg-white hover:bg-slate-50 text-slate-600 px-8 py-3.5 rounded-2xl font-bold text-sm border border-slate-200 shadow-sm active:scale-95 transition-all flex items-center justify-center gap-2"
                    >
                        <Wallet class="w-4 h-4" />
                        <span>{{ __('manage_wallets') }}</span>
                    </Link>
                </div>
            </div>

            <!-- History Details Bottom Sheet (Mobile) -->
            <Teleport to="body">
                <div v-if="showHistoryModal" class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center p-0 sm:p-4">
                    <div @click="showHistoryModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                    
                    <div class="relative z-10 w-full max-w-lg bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl animate-in slide-in-from-bottom duration-300 overflow-hidden flex flex-col max-h-[85vh]">
                        <div v-if="selectedWalletForHistory">
                            <!-- Header -->
                            <div class="p-6 md:p-8 border-b border-slate-100 flex items-center justify-between bg-white shrink-0 sticky top-0 z-20">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 flex items-center justify-center shrink-0">
                                        <div class="w-3 h-3 rounded-full" :class="selectedWalletForHistory.type === 'bank' ? 'bg-orange-400' : (selectedWalletForHistory.type === 'ewallet' ? 'bg-purple-400' : 'bg-emerald-400')"></div>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-slate-900 leading-tight">
                                            {{ selectedWalletForHistory.name }}
                                        </h2>
                                        <p class="text-sm text-slate-500 font-medium capitalize">{{ __('history_modal_title', { type: selectedWalletForHistory.type }) }}</p>
                                    </div>
                                </div>
                                <button @click="showHistoryModal = false" class="p-2 bg-slate-50 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 transition-all border border-slate-100">
                                    <X class="w-6 h-6" />
                                </button>
                            </div>
                            
                            <div class="overflow-y-auto p-0 md:p-0 custom-scrollbar">
                                <table class="w-full text-left text-sm whitespace-nowrap">
                                    <thead class="bg-slate-50 text-slate-500 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-6 py-3 font-bold border-b border-slate-200">{{ __('period') }}</th>
                                            <th class="px-6 py-3 font-bold border-b border-slate-200 text-right">{{ __('balance') }}</th>
                                            <th class="px-6 py-3 font-bold border-b border-slate-200 text-right">{{ __('change') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <!-- Iterate periods in reverse for history (newest first) -->
                                        <tr v-for="(period, index) in [...periods].reverse()" :key="period.key">
                                            <td class="px-6 py-4 font-bold text-slate-700">
                                                {{ period.label }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold text-slate-900 tabular-nums">
                                                {{ formatCurrency(selectedWalletForHistory.balances[period.key], selectedWalletForHistory.currency).split(',')[0] }}
                                            </td>
                                            <td class="px-6 py-4 text-right tabular-nums">
                                                <div 
                                                    v-if="index < periods.length - 1" 
                                                    class="flex items-center justify-end gap-1"
                                                    :class="getChangeColor(selectedWalletForHistory.balances[period.key], selectedWalletForHistory.balances[[...periods].reverse()[index+1].key])"
                                                >
                                                     {{ getChangeIcon(selectedWalletForHistory.balances[period.key], selectedWalletForHistory.balances[[...periods].reverse()[index+1].key]) }}
                                                     <!-- Calculate % change vs previous period (which is next in reversed array) -->
                                                     <span class="text-xs">
                                                        {{ 
                                                            selectedWalletForHistory.balances[[...periods].reverse()[index+1].key] !== 0 
                                                            ? Math.abs(((selectedWalletForHistory.balances[period.key] - selectedWalletForHistory.balances[[...periods].reverse()[index+1].key]) / Math.abs(selectedWalletForHistory.balances[[...periods].reverse()[index+1].key]) * 100)).toFixed(1) + '%'
                                                            : '-' 
                                                        }}
                                                     </span>
                                                </div>
                                                <span v-else class="text-slate-400 text-xs">-</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>

        <!-- Premium Upsell Modal -->
        <PremiumUpsellModal 
            :show="showUpsellModal" 
            @close="showUpsellModal = false"
            :title="upsellTitle"
            :description="upsellDescription"
        />
    </Layout>
</template>
