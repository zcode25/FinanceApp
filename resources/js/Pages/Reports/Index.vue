<script setup>
import Layout from '../../Shared/Layout.vue';
import { Head, router, usePage, Deferred } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";

const driverObj = ref(null);
const skipHTML = `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">Skip Tutorial</button>
</div>`;
import { 
    FileText, 
    Download, 
    Calendar, 
    Wallet as WalletIcon,
    Banknote,
    CreditCard,
    TrendingUp,
    TrendingDown,
    ArrowRight,
    ChevronDown,
    ChevronUp,
    Crown,
    Sparkles,
    Lock
} from 'lucide-vue-next';
import SearchableSelect from '@/Shared/SearchableSelect.vue';
import PremiumUpsellModal from '@/Shared/PremiumUpsellModal.vue';

    const page = usePage();
    const __ = (key, replacements = {}) => {
        let translation = page.props.translations?.[key] || key;
        Object.keys(replacements).forEach(r => {
            translation = translation.replace(`:${r}`, replacements[r]);
        });
        return translation;
    };

    const props = defineProps({
        reports_data: Object, // Deferred: { reports, totals }
        filters: Object,
        availableMonths: Array,
        is_premium: Boolean
    });

    // Centralized data access with robust defaults for deferred props
    const data = computed(() => {
        return {
            reports: props.reports_data?.reports || [],
            totals: props.reports_data?.totals || {
                total_income: 0,
                total_expense: 0,
                total_net: 0
            }
        };
    });

    const showUpsellModal = ref(false);
    const upsellTitle = ref('Premium Feature');
    const upsellDescription = ref('Unlock this feature with the Professional Plan.');

    const triggerUpsell = (title, description) => {
        upsellTitle.value = title;
        upsellDescription.value = description;
        showUpsellModal.value = true;
    };
    
    const selectedMonth = ref(props.filters.month);
    
    // availableMonths passed as prop
    
    const localizedAvailableMonths = computed(() => {
        const threeMonthsAgo = new Date();
        threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);
        threeMonthsAgo.setDate(1);

        return (props.availableMonths || []).map(monthStr => {
            const [year, month] = monthStr.split('-');
            const date = new Date(year, month - 1);
            const isRestricted = !props.is_premium && date < threeMonthsAgo;

            return {
                value: monthStr,
                label: new Intl.DateTimeFormat(page.props.locale || 'en', { month: 'long', year: 'numeric' }).format(date) + (isRestricted ? ' (Pro)' : ''),
                restricted: isRestricted
            };
        });
    });

    const expandedReports = ref(new Set());

    const toggleReport = (walletId) => {
        if (expandedReports.value.has(walletId)) {
            expandedReports.value.delete(walletId);
        } else {
            expandedReports.value.add(walletId);
        }
    };

    const isExpanded = (walletId) => expandedReports.value.has(walletId);

    watch(selectedMonth, (newMonth) => {
        const monthData = localizedAvailableMonths.value.find(m => m.value === newMonth);
        if (monthData?.restricted) {
            triggerUpsell(__('upsell_historical_statement_title'), __('upsell_historical_statement_desc'));
            // Reset to current filter to avoid navigation to restricted data
            selectedMonth.value = props.filters.month;
            return;
        }

        router.get('/reports', { month: newMonth }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['reports_data', 'filters', 'availableMonths']
        });
    });
    
    const formatCurrency = (amount, currency = 'IDR') => {
        return new Intl.NumberFormat(currency === 'IDR' ? 'id-ID' : 'en-US', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: currency === 'IDR' ? 0 : 2,
            maximumFractionDigits: currency === 'IDR' ? 0 : 2
        }).format(amount);
    };
    
    const downloadPdf = () => {
        if (!props.is_premium) {
            triggerUpsell(__('upsell_professional_exports_title'), __('upsell_professional_exports_desc'));
            return;
        }
        window.open(`/reports/export/pdf?month=${selectedMonth.value}`, '_blank');
    };
    
    const downloadExcel = () => {
        if (!props.is_premium) {
            triggerUpsell(__('upsell_data_interoperability_title'), __('upsell_data_interoperability_desc'));
            return;
        }
        window.location.href = `/reports/export/excel?month=${selectedMonth.value}`;
    };
    
    const getTypeColor = (type) => {
        switch (type) {
            case 'cash':
                return {
                    bg: 'bg-emerald-50',
                    text: 'text-emerald-600',
                    solid: 'bg-emerald-500',
                    border: 'border-emerald-100'
                };
            case 'ewallet':
                return {
                    bg: 'bg-purple-50',
                    text: 'text-purple-600',
                    solid: 'bg-purple-500',
                    border: 'border-purple-100'
                };
            case 'bank':
                return {
                    bg: 'bg-orange-50',
                    text: 'text-orange-600',
                    solid: 'bg-orange-500',
                    border: 'border-orange-100'
                };
            default:
                return {
                    bg: 'bg-indigo-50',
                    text: 'text-indigo-600',
                    solid: 'bg-indigo-500',
                    border: 'border-indigo-100'
                };
        }
    };

    const startTour = () => {
        const isMobile = window.innerWidth < 768;
        driverObj.value = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayOpacity: 0.85,
            stagePadding: 10,
            onNextClick: () => {
                if (driverObj.value.isLastStep()) {
                    localStorage.setItem('tour_state', 'final_congrats');
                    router.visit('/dashboard');
                    driverObj.value.destroy();
                } else {
                    driverObj.value.moveNext();
                }
            },
            steps: [
                {
                    element: '#step-report-filters',
                    popover: {
                        title: `<span class="text-xl font-bold">${__('tour_report_filters_title')}</span>`,
                        description: __('tour_report_filters_desc') + skipHTML,
                        side: "bottom",
                        align: 'start'
                    }
                },
                {
                    element: '#step-report-export',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_report_export_title')}</span>`,
                        description: __('tour_report_export_desc') + skipHTML,
                        side: "top",
                        align: 'start'
                    }
                },
                {
                    element: '#step-reports-card',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_reports_card_title')}</span>`,
                        description: __('tour_reports_card_desc') + skipHTML,
                        side: "top",
                        align: 'start'
                    }
                },
                {
                    element: isMobile ? '#mobile-nav-home' : '#nav-dashboard',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_victory_lap_title')}</span>`,
                        description: __('tour_victory_lap_desc') + skipHTML,
                        side: "bottom",
                        align: 'start'
                    }
                }
            ]
        });

        driverObj.value.drive();
    };

    const checkTourTriggers = () => {
        const tourState = localStorage.getItem('tour_state');
        const tourCompleted = page.props.auth.user.has_completed_tour;
        const catchUpStates = [
            'welcome', 'wallet_setup', 'transaction_setup', 'dashboard_explanation', 
            'analysis_intro', 'budget_setup', 'goals_setup', 'categories_setup', 'tracker_intro', 'hub_to_reports', 'reports_intro'
        ];
        

        // Guard against duplicate triggers
        if (driverObj.value && document.querySelector('.driver-popover')) {
            return;
        }

        if (!tourState || (tourState && catchUpStates.includes(tourState))) {
            if (!tourState || tourState !== 'reports_intro') {
                if (!tourState && tourCompleted) {
                    return;
                }
                localStorage.setItem('tour_state', 'reports_intro');
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
        <Head :title="__('e_statement')" />
        <Layout>
            <header class="relative z-30 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('e_statement') }}</h1>
                    <p class="text-sm md:text-base text-slate-500 font-medium">{{ __('e_statement_desc') }}</p>
                </div>
    
                <div class="flex flex-col md:flex-row items-center gap-4">
                    <!-- Month Picker -->
                    <div id="step-report-filters" class="w-full md:w-56 relative">
                        <select 
                            v-model="selectedMonth" 
                            class="appearance-none bg-white border border-slate-200 text-slate-900 rounded-xl px-6 py-3 pr-12 font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer hover:bg-slate-50 transition-all shadow-sm w-full"
                        >
                            <option v-for="month in localizedAvailableMonths" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>
                        <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                    </div>
    
                    <!-- Export Actions -->
                    <div id="step-report-export" class="flex items-center gap-3 w-full md:w-auto">
                        <button 
                            @click="downloadPdf"
                            class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-sm shadow-sm hover:shadow-md hover:shadow-rose-200 transition-all active:scale-95 group"
                        >
                            <Download class="w-4 h-4 text-white" />
                            <span>PDF</span>
                        </button>
                        <button 
                            @click="downloadExcel"
                            class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-sm hover:shadow-md hover:shadow-emerald-200 transition-all active:scale-95 group"
                        >
                            <Download class="w-4 h-4 text-white" />
                            <span>Excel</span>
                        </button>
                    </div>
                </div>
            </header>
    
            <!-- Wallet Reports Loop -->
            <Deferred data="reports_data">
                <template #fallback>
                    <div class="space-y-8 pb-24 md:pb-0">
                        <div v-for="i in 2" :key="i" class="glass-card overflow-hidden animate-pulse">
                            <div class="p-6 md:p-8 space-y-8">
                                <div class="flex items-center gap-5">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-100"></div>
                                    <div class="space-y-2">
                                        <div class="h-6 w-32 bg-slate-100 rounded"></div>
                                        <div class="h-4 w-20 bg-slate-50 rounded"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                                    <div v-for="j in 4" :key="j" class="h-20 bg-slate-50 rounded-2xl"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-if="data.reports.length > 0" class="pb-24 md:pb-0">
                    <div 
                        v-for="(report, index) in data.reports" 
                        :key="report.wallet.id" 
                        class="mb-8"
                        :id="index === 0 ? 'step-reports-card' : ''"
                    >
                    <div class="glass-card overflow-hidden">
                        <!-- Wallet Header & Stats Container -->
                        <div class="p-6 md:p-8 space-y-8">
                            <!-- Wallet Header -->
                            <div class="flex items-center gap-5">
                                <div :class="[getTypeColor(report.wallet.type).bg, getTypeColor(report.wallet.type).text, 'w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center border shadow-sm', getTypeColor(report.wallet.type).border]">
                                    <component :is="report.wallet.type === 'cash' ? Banknote : (report.wallet.type === 'bank' ? CreditCard : WalletIcon)" class="w-6 h-6 md:w-7 md:h-7" />
                                </div>
                                <div>
                                    <h2 class="text-[14px] md:text-2xl font-bold text-slate-900 tracking-tight">{{ report.wallet.name }}</h2>
                                    <p class="text-[10px] md:text-sm font-medium text-slate-500 tracking-wider">{{ __('wallet_currency_label', { currency: report.wallet.currency }) }}</p>
                                </div>
                            </div>
        
                            <!-- Quick Stats Carousel (Mobile) / Grid (Desktop) -->
                            <div class="flex md:grid md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 overflow-x-auto md:overflow-x-visible pb-4 md:pb-0 hide-scrollbar -mx-6 px-6 md:mx-0 md:px-0">
                                <!-- Opening Balance -->
                                <div class="min-w-[240px] md:min-w-0 p-5 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col justify-center transition-all hover:bg-slate-100/50">
                                    <span class="text-xs font-bold text-slate-400 mb-1">{{ __('opening') }}</span>
                                    <span class="text-lg md:text-xl font-bold text-slate-900 tabular-nums truncate" :title="formatCurrency(report.summary.opening_balance, report.wallet.currency)">{{ formatCurrency(report.summary.opening_balance, report.wallet.currency).split(',')[0] }}</span>
                                </div>
                                <!-- Income -->
                                <div class="min-w-[240px] md:min-w-0 p-5 rounded-2xl bg-emerald-50 border border-emerald-100/50 flex flex-col justify-center transition-all hover:bg-emerald-100/30">
                                    <span class="text-xs font-bold text-emerald-600 mb-1">{{ __('income') }}</span>
                                    <span class="text-lg md:text-xl font-bold text-emerald-600 tabular-nums truncate" :title="formatCurrency(report.summary.income, report.wallet.currency)">+{{ formatCurrency(report.summary.income, report.wallet.currency).split(',')[0] }}</span>
                                </div>
                                <!-- Expense -->
                                <div class="min-w-[240px] md:min-w-0 p-5 rounded-2xl bg-rose-50 border border-rose-100/50 flex flex-col justify-center transition-all hover:bg-rose-100/30">
                                    <span class="text-xs font-bold text-rose-600 mb-1">{{ __('expense') }}</span>
                                    <span class="text-lg md:text-xl font-bold text-rose-600 tabular-nums truncate" :title="formatCurrency(report.summary.expense, report.wallet.currency)">-{{ formatCurrency(report.summary.expense, report.wallet.currency).split(',')[0] }}</span>
                                </div>
                                <!-- Closing -->
                                <div class="min-w-[240px] md:min-w-0 p-5 rounded-2xl bg-indigo-50 border border-indigo-100/50 flex flex-col justify-center transition-all hover:bg-indigo-100/30">
                                    <span class="text-xs font-bold text-indigo-600 mb-1">{{ __('closing') }}</span>
                                    <span class="text-lg md:text-xl font-bold text-indigo-700 tabular-nums truncate" :title="formatCurrency(report.summary.closing_balance, report.wallet.currency)">{{ formatCurrency(report.summary.closing_balance, report.wallet.currency).split(',')[0] }}</span>
                                </div>
                            </div>
                        </div>
    
                        <!-- Transaction Ledger (Collapsible) -->
                        <div class="border-t border-slate-100 bg-slate-50/30">
                            <div 
                                @click="toggleReport(report.wallet.id)"
                                class="px-6 py-4 md:px-8 md:py-6 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition-colors group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white border border-slate-200 rounded-lg text-slate-500 group-hover:text-indigo-600 group-hover:border-indigo-100 transition-colors">
                                        <TrendingUp class="w-4 h-4" />
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">{{ __('account_mutation') }}</h3>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold text-slate-400 hidden md:inline-block">{{ selectedMonth }}</span>
                                    <button class="p-1 text-slate-400 group-hover:text-indigo-600 transition-colors bg-white rounded-md border border-slate-200 group-hover:border-indigo-100">
                                        <component :is="isExpanded(report.wallet.id) ? ChevronUp : ChevronDown" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            
                            <div v-show="isExpanded(report.wallet.id)" class="border-t border-slate-100 bg-white animate-in slide-in-from-top-1 duration-200 relative" :class="{ 'min-h-[500px] flex flex-col': !is_premium }">
                                 <!-- Premium Lock Overlay for Account Mutation -->
                                 <div v-if="!is_premium" class="absolute inset-0 z-40 flex flex-col items-center justify-center text-center px-8 bg-white/70 backdrop-blur-[12px]">
                                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-100 mb-5 border border-indigo-50 animate-bounce-subtle">
                                        <Crown class="w-8 h-8 text-amber-500" />
                                    </div>
                                    <h4 class="text-slate-900 font-black text-xl mb-2 tracking-tight">{{ __('professional_mutation_ledger') }}</h4>
                                    <p class="text-sm text-slate-600 mb-8 max-w-[340px] font-medium leading-relaxed">{{ __('professional_mutation_desc') }}</p>
                                    <button @click="triggerUpsell(__('upsell_transaction_ledger_title'), __('upsell_transaction_ledger_desc'))" class="px-8 py-4 bg-emerald-600 text-white rounded-2xl text-sm font-black shadow-xl shadow-emerald-200 hover:bg-emerald-700 transition-all active:scale-95 flex items-center gap-2 group">
                                        <Sparkles class="w-4 h-4 group-hover:rotate-12 transition-transform" />
                                        {{ __('unlock_history_btn') }}
                                    </button>
                                </div>

                                 <div class="w-full overflow-x-auto" :class="{ 'blur-[8px] select-none pointer-events-none': !is_premium }">
                                    <table class="w-full text-left min-w-[900px] hidden md:table">
                                        <thead>
                                            <tr class="text-slate-500 text-xs font-semibold border-b border-slate-50 bg-slate-50/50">
                                                <th class="px-8 py-4">{{ __('date') }}</th>
                                                <th class="px-8 py-4">{{ __('description') }}</th>
                                                <th class="px-8 py-4">{{ __('category') }}</th>
                                                <th class="px-8 py-4 text-right">{{ __('amount') }}</th>
                                                <th class="px-8 py-4 text-right">{{ __('balance') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50">
                                             <!-- Initial Balance Row -->
                                            <tr class="bg-slate-50/30 hover:bg-slate-50 transition-colors">
                                                <td class="px-8 py-5">
                                                    <span class="text-xs font-bold text-slate-600 px-2.5 py-1 bg-slate-100 rounded-lg border border-slate-200">{{ __('opening') }}</span>
                                                </td>
                                                <td colspan="3" class="px-8 py-5 text-sm font-medium text-slate-500 italic">{{ __('brought_forward') }}</td>
                                                <td class="px-8 py-5 text-right font-bold text-slate-900 tabular-nums">{{ formatCurrency(report.summary.opening_balance, report.wallet.currency) }}</td>
                                            </tr>
            
                                            <!-- Transactions -->
                                            <tr v-for="tx in report.transactions" :key="tx.id" class="group hover:bg-slate-50/40 transition-all">
                                                <td class="px-8 py-5">
                                                    <div class="text-sm font-semibold text-slate-700 whitespace-nowrap">
                                                        {{ new Date(tx.date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                                    </div>
                                                </td>
                                                <td class="px-8 py-5">
                                                    <div class="text-sm font-normal text-slate-900 group-hover:text-indigo-900 transition-colors">
                                                        {{ tx.description }}
                                                    </div>
                                                </td>
                                                <td class="px-8 py-6">
                                                <span 
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold text-white border border-white/20"
                                                    :class="tx.category.color"
                                                >
                                                    {{ tx.category.name }}
                                                </span>
                                            </td>
                                                <td class="px-8 py-5 text-right font-bold tabular-nums" :class="tx.type === 'income' ? 'text-emerald-600' : 'text-slate-900'">
                                                    <span class="text-xs opacity-50 mr-1">{{ tx.type === 'income' ? '+' : '-' }}</span>{{ formatCurrency(tx.amount, report.wallet.currency).split(',')[0] }}
                                                </td>
                                                <td class="px-8 py-5 text-right font-bold text-slate-500 group-hover:text-slate-900 transition-colors tabular-nums">
                                                    {{ formatCurrency(tx.running_balance, report.wallet.currency).split(',')[0] }}
                                                </td>
                                            </tr>
            
                                            <!-- Closing Balance Row -->
                                            <tr class="bg-indigo-50/20 text-slate-900 border-t border-indigo-100">
                                                <td class="px-8 py-6">
                                                    <span class="text-xs font-bold text-indigo-700 px-2.5 py-1 bg-indigo-50 rounded-lg border border-indigo-100">{{ __('closing') }}</span>
                                                </td>
                                                <td colspan="3" class="px-8 py-6 text-sm font-bold text-indigo-900/60 italic">{{ __('ending_balance_period') }}</td>
                                                <td class="px-8 py-6 text-right font-bold text-xl tabular-nums text-indigo-700">{{ formatCurrency(report.summary.closing_balance, report.wallet.currency).split(',')[0] }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
    
                                    <!-- Mobile View Inside Collapsible -->
                                    <div class="md:hidden p-5 space-y-4 bg-slate-50/30">
                                         <!-- Initial Balance Mobile -->
                                        <div class="flex justify-between items-center p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
                                            <div>
                                                <p class="text-[10px] text-slate-400 font-bold mb-0.5">{{ __('opening') }}</p>
                                                <p class="text-[10px] text-slate-400 font-medium italic">{{ __('brought_forward') }}</p>
                                            </div>
                                            <p class="text-slate-900 font-bold text-[14px] tabular-nums">{{ formatCurrency(report.summary.opening_balance, report.wallet.currency).split(',')[0] }}</p>
                                        </div>
    
                                        <div v-for="tx in report.transactions" :key="tx.id" class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm transition-all active:bg-slate-50">
                                            <div class="flex justify-between items-start">
                                                <div class="flex flex-col gap-1">
                                                    <p class="text-[10px] font-bold text-slate-400 tracking-wider">
                                                        {{ new Date(tx.date).getDate() }} {{ new Date(tx.date).toLocaleDateString(page.props.locale || 'id-ID', { month: 'short' }) }} {{ new Date(tx.date).getFullYear() }}
                                                    </p>
                                                    <div>
                                                        <h4 class="text-[14px] font-bold text-slate-900 line-clamp-1 leading-tight mb-1.5">{{ tx.description }}</h4>
                                                        <span 
                                                            class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold text-white border border-white/20"
                                                            :class="tx.category.color"
                                                        >
                                                            {{ tx.category.name }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                     <p class="font-bold text-[14px] tabular-nums tracking-tight" :class="tx.type === 'income' ? 'text-emerald-600' : 'text-slate-900'">
                                                        {{ tx.type === 'income' ? '+' : '-' }} {{ formatCurrency(tx.amount, report.wallet.currency).split(',')[0] }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Closing Balance Mobile -->
                                        <div class="flex justify-between items-center p-4 bg-indigo-50 rounded-2xl border border-indigo-100 shadow-sm">
                                            <div>
                                                <p class="text-[10px] text-indigo-600 font-bold mb-0.5">{{ __('closing') }}</p>
                                                <p class="text-[10px] text-indigo-400 font-medium italic">{{ __('ending_balance_period') }}</p>
                                            </div>
                                            <p class="text-indigo-700 font-bold text-[14px] tabular-nums">{{ formatCurrency(report.summary.closing_balance, report.wallet.currency).split(',')[0] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-else class="bg-white border border-slate-100 rounded-[2rem] p-12 md:p-20 text-center space-y-6 shadow-sm animate-in fade-in zoom-in duration-500">
                    <div class="w-16 h-16 bg-slate-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                        <FileText class="w-8 h-8 text-slate-300" />
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-base font-bold text-slate-900">{{ __('no_statement_generated') }}</h2>
                        <p class="text-slate-500 max-w-sm mx-auto font-medium text-sm">{{ __('no_statement_desc') }}</p>
                    </div>
                    <button 
                        @click="router.visit('/wallets')"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 active:scale-95 transition-all flex items-center justify-center gap-2 mx-auto"
                    >
                        <WalletIcon class="w-4 h-4" />
                        <span>{{ __('manage_wallets') }}</span>
                    </button>
                </div>
            </Deferred>

            <!-- Premium Upsell Modal -->
            <PremiumUpsellModal 
                :show="showUpsellModal" 
                @close="showUpsellModal = false"
                :title="upsellTitle"
                :description="upsellDescription"
            />
        </Layout>
    </template>
    
<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
