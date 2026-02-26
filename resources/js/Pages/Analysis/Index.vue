<script setup>
import Layout from '../../Shared/Layout.vue';
import { usePage, router, Head, Link, Deferred } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { TrendingUp, Flame, AlertTriangle, Calendar, Coffee, Award, Shield, ShieldAlert, Scissors, PiggyBank, Target, PieChart as PieChartIcon, Activity, ArrowUpRight, Lightbulb, Plus, Lock, Sparkles, Crown } from 'lucide-vue-next';
import VueApexCharts from "vue3-apexcharts";
import { driver } from "driver.js";
import "driver.js/dist/driver.css";

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;

const driverObj = ref(null);
const skipHTML = `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">${__('skip_tour')}</button>
</div>`;

    const props = defineProps({
        filters: Object,
        summary: Object,
        categorySpending: Array,
        cashFlowTrend: Object,
        smartInsights: Array,
        walletAllocation: Array,
        availableMonths: Array,
        financialTips: Array,
        is_premium: Boolean
    });

    // Centralized data access with robust defaults for deferred props
    const data = computed(() => {
        return {
            summary: props.summary || {
                savings_rate: 0,
                total_income: 0,
                total_expense: 0,
                net_savings: 0
            },
            categorySpending: props.categorySpending || [],
            cashFlowTrend: props.cashFlowTrend || {
                labels: [],
                income: [],
                expense: []
            },
            smartInsights: props.smartInsights || [],
            walletAllocation: props.walletAllocation || [],
            financialTips: props.financialTips || []
        };
    });
    
    // Filters state
    const getJakartaDate = () => new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit'
    }).format(new Date());

    const selectedMonth = ref(props.filters?.month || getJakartaDate().slice(0, 7));
    
    onMounted(() => {
        checkTourTriggers();
    });

    // Watch filters and reload
    watch(selectedMonth, (newMonth) => {
        router.get('/analysis', { month: newMonth }, {
            preserveState: true,
            preserveScroll: true,
            replace: true, // Use replace to avoid history stack buildup
            only: ['summary', 'categorySpending', 'filters', 'cashFlowTrend', 'smartInsights', 'walletAllocation', 'availableMonths', 'financialTips', 'selectedMonth']
        });
    });
    
    // Sync local state if props change (e.g. Back button)
    watch(() => props.filters.month, (newMonth) => {
        if (newMonth && newMonth !== selectedMonth.value) {
            selectedMonth.value = newMonth;
        }
    });
    
    // Chart Options
    const categoryChartOptions = computed(() => ({
        chart: {
            type: 'donut',
            background: 'transparent',
            foreColor: '#64748b'
        },
        labels: (data.value.categorySpending || []).map(item => item.category_name),
        colors: ['#4f46e5', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'],
        stroke: {
            show: true,
            width: 2,
            colors: ['#ffffff']
        },

        legend: {
            show: false
        },
        states: {
            hover: {
                filter: {
                    type: 'darken',
                    value: 0.9
                }
            },
            active: {
                allowMultipleDataPointsSelection: false,
                filter: {
                    type: 'darken',
                    value: 0.9
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        plotOptions: {
            pie: {
                offsetY: 0,
                donut: {
                    size: '80%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            color: '#64748b',
                            fontSize: '12px',
                            fontWeight: 600,
                            offsetY: -10
                        },
                        value: {
                            show: true,
                            color: '#1e293b',
                            fontSize: '28px',
                            fontWeight: 900,
                            offsetY: 10,
                            formatter: (val) => formatCurrency(val)
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            color: '#94a3b8',
                            fontSize: '12px',
                            fontWeight: 600,
                            formatter: (w) => {
                                const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                return formatCurrency(total);
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: (val) => formatCurrency(val)
            }
        }
    }));
    
    const commonChartOptions = computed(() => ({
        chart: {
            type: 'area', // Standardized to 'area'
            height: 220,
            toolbar: { show: false },
            zoom: { enabled: false },
            background: 'transparent',
            sparkline: { enabled: false }
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
            padding: { left: 20, right: 20, top: 10 }
        },
        xaxis: {
            type: 'category',
            categories: data.value.cashFlowTrend.labels,
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
                        return String(day).padStart(2, '0');
                    }
                    return '';
                }
            }
        },
        yaxis: {
            show: false,
            labels: {
                style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 500 },
                formatter: (val) => formatCompact(val)
            }
        },
        tooltip: {
            theme: 'light',
            x: { 
                show: true,
                formatter: (val) => {
                    const day = String(parseInt(val)).padStart(2, '0');
                    const monthLabel = props.availableMonths.find(m => m.value === selectedMonth.value)?.label || '';
                    return `${day} ${monthLabel}`;
                }
            },
            y: { formatter: (val) => formatCurrency(val) }
        },
        legend: { show: false }
    }));
    
    const incomeChartOptions = computed(() => ({
        ...commonChartOptions.value,
        colors: ['#10b981'],
    }));
    
    const expenseChartOptions = computed(() => ({
        ...commonChartOptions.value,
        colors: ['#f43f5e'],
    }));
    
    // Chart Series
    const incomeSeries = computed(() => [{ name: 'Pemasukan', data: data.value.cashFlowTrend.income }]);
    const expenseSeries = computed(() => [{ name: 'Pengeluaran', data: data.value.cashFlowTrend.expense }]);
    const categorySeries = computed(() => (data.value.categorySpending || []).map(item => parseFloat(item.total)));
    
    // Helpers
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };
    
    const formatCompact = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            notation: 'compact',
            maximumFractionDigits: 1
        }).format(amount);
    };
    


    const processedCategorySpending = computed(() => {
        const total = data.value.categorySpending.reduce((sum, item) => sum + parseFloat(item.total), 0);
        return (data.value.categorySpending || []).map(item => ({
            ...item,
            percentage: total > 0 ? ((parseFloat(item.total) / total) * 100).toFixed(1) : 0
        }));
    });
    
    const topCategory = computed(() => {
        if (data.value.categorySpending.length === 0) return __('status_no_data');
        return data.value.categorySpending[0].category_name;
    });
    
    const savingsStatus = computed(() => {
        const rate = data.value.summary.savings_rate;
        const income = data.value.summary.total_income;

        if (income === 0 && data.value.summary.total_expense === 0) return { label: __('status_no_data'), color: 'text-slate-400', bg: 'bg-slate-100' };
        if (income === 0 && data.value.summary.total_expense > 0) return { label: __('status_overspending'), color: 'text-rose-700', bg: 'bg-rose-50 border-rose-100' };

        if (rate >= 30) return { label: __('status_excellent'), color: 'text-emerald-700', bg: 'bg-emerald-50 border-emerald-100' };
        if (rate >= 10) return { label: __('status_good'), color: 'text-indigo-700', bg: 'bg-indigo-50 border-indigo-100' };
        if (rate > 0) return { label: __('status_saving'), color: 'text-amber-700', bg: 'bg-amber-50 border-amber-100' };
        return { label: __('status_overspending'), color: 'text-rose-700', bg: 'bg-rose-50 border-rose-100' };
    });
    
    const iconMap = {
        'TrendingUp': TrendingUp,
        'Flame': Flame,
        'AlertTriangle': AlertTriangle,
        'Calendar': Calendar,
        'Coffee': Coffee,
        'Award': Award,
        'Shield': Shield,
        'ShieldAlert': ShieldAlert,
        'Scissors': Scissors,
        'PiggyBank': PiggyBank
    };
    
    const getInsightStyle = (type) => {
        switch (type) {
            case 'critical': return 'bg-rose-50 text-rose-700 border-rose-100 shadow-sm';
            case 'warning': return 'bg-amber-50 text-amber-700 border-amber-100 shadow-sm';
            case 'success': return 'bg-emerald-50 text-emerald-700 border-emerald-100 shadow-sm';
            case 'info': return 'bg-indigo-50 text-indigo-700 border-indigo-100 shadow-sm';
            default: return 'bg-slate-50 text-slate-600 border-slate-100 shadow-sm';
        }
    };
    const efficiencyStatus = computed(() => {
        const rate = data.value.summary.savings_rate;
        const income = data.value.summary.total_income;

        if (income === 0 && data.value.summary.total_expense === 0) return { label: __('status_no_data'), color: 'text-slate-400' };
        
        if (rate >= 20) return { label: __('status_high'), color: 'text-emerald-600' };
        if (rate > 0) return { label: __('status_medium'), color: 'text-amber-600' };
        return { label: __('status_low'), color: 'text-rose-600' };
    });

    const hasData = computed(() => {
        return data.value.summary.total_income > 0 || data.value.summary.total_expense > 0;
    });

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
                    if (isMobile) {
                        localStorage.setItem('tour_state', 'hub_to_budget');
                        router.visit('/dashboard');
                    } else {
                        localStorage.setItem('tour_state', 'budget_setup');
                        router.visit('/budget');
                    }
                    driverObj.value.destroy();
                } else {
                    driverObj.value.moveNext();
                }
            },
        steps: [
                {
                    element: '#step-analysis-summary',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_analysis_summary_title')}</span>`,
                        description: __('tour_analysis_summary_desc') + skipHTML,
                        side: "bottom",
                    align: 'start'
                }
            },
                {
                    element: '#step-analysis-income',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_income_flow_title')}</span>`,
                        description: __('tour_income_flow_desc') + skipHTML,
                        side: "top",
                        align: 'start'
                    }
                },
                {
                    element: '#step-analysis-expense',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_expense_path_title')}</span>`,
                        description: __('tour_expense_path_desc') + skipHTML,
                        side: "top",
                        align: 'start'
                    }
                },
                {
                    element: '#step-analysis-mix',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_spending_mix_title')}</span>`,
                        description: __('tour_spending_mix_desc') + skipHTML,
                        side: "top",
                        align: 'start'
                    }
                },
                {
                    element: '#step-analysis-insights',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_smart_insights_title')}</span>`,
                        description: __('tour_smart_insights_desc') + skipHTML,
                        side: "top",
                    align: 'start'
                }
            },
                {
                    element: isMobile ? '#mobile-nav-home' : '#nav-budget',
                    popover: {
                        title: isMobile ? __('tour_return_hub_title') : __('tour_budget_control_title'),
                        description: (isMobile
                            ? __('tour_return_hub_desc_mobile')
                            : __('tour_budget_control_desc')) + skipHTML,
                        side: "bottom",
                    align: 'start'
                },
                onHighlighted: () => {
                    if (isMobile) {
                        localStorage.setItem('tour_state', 'hub_to_budget');
                    }
                }
            }
        ]
    });

        driverObj.value.drive();
    };

    const checkTourTriggers = () => {
        const tourState = localStorage.getItem('tour_state');
        const tourCompleted = page.props.auth.user.has_completed_tour;
        const catchUpStates = ['welcome', 'wallet_setup', 'transaction_setup', 'dashboard_explanation', 'hub_to_analysis', 'analysis_intro'];
        

        // Guard against duplicate triggers
        if (driverObj.value && document.querySelector('.driver-popover')) {
            return;
        }

        if (!tourState || (tourState && catchUpStates.includes(tourState))) {
            if (!tourState || tourState !== 'analysis_intro') {
                if (!tourState && tourCompleted) {
                    return;
                }
                localStorage.setItem('tour_state', 'analysis_intro');
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
    <Head :title="__('financial_analysis')" />
    <Layout>
        <header class="relative z-30 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1">
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('finance_analysis') }}</h1>
                <p class="text-sm md:text-base text-slate-500 font-medium">{{ __('analysis_desc') }}</p>
            </div>

            <!-- Month Filter -->
            <div class="flex items-center gap-4">
                <div class="w-full md:w-56 relative">
                    <select 
                        v-model="selectedMonth" 
                        class="appearance-none bg-white border border-slate-200 text-slate-900 rounded-xl px-6 py-3 pr-12 font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer hover:bg-slate-50 transition-all shadow-sm w-full"
                    >
                        <option v-for="month in availableMonths" :key="month.value" :value="month.value">
                            {{ month.label }}
                        </option>
                    </select>
                    <Calendar class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                </div>
            </div>
        </header>

        <!-- Summary Metrics -->
            <div id="step-analysis-summary" class="flex overflow-x-auto snap-x snap-mandatory md:grid md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 -mx-4 px-4 py-4 -my-4 md:mx-0 md:px-0 md:py-0 md:mt-0 md:mb-10 no-scrollbar md:overflow-visible">
                <!-- Savings Rate -->
                <div class="w-[88vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-6 md:p-8 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Target class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                    <Target class="w-5 h-5 md:w-6 md:h-6 text-white" />
                                </div>
                                <h3 class="font-bold text-base md:text-lg text-white/90 tracking-tight">{{ __('savings_rate') }}</h3>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-2xl md:text-3xl font-bold tracking-tight tabular-nums">{{ data.summary.savings_rate }}%</h2>
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-[10px] md:text-xs font-bold bg-white/20 backdrop-blur-sm border border-white/10 text-white">
                                    {{ savingsStatus.label }}
                                </div>
                            </div>
                    </div>
                </div>

                <!-- Net Savings -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-6 md:p-8 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <TrendingUp class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <TrendingUp class="w-5 h-5 md:w-6 md:h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-base md:text-lg text-white/90 tracking-tight">{{ __('net_savings') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-2xl md:text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(data.summary.net_savings).split(',')[0] }}</h2>
                            <p class="text-emerald-100 font-medium text-xs md:text-sm">{{ __('monthly_surplus') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Top Category -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-6 md:p-8 bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <PieChartIcon class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <PieChartIcon class="w-5 h-5 md:w-6 md:h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-base md:text-lg text-white/90 tracking-tight">{{ __('top_focus') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-xl md:text-2xl font-bold tracking-tight truncate">{{ topCategory }}</h2>
                            <p class="text-rose-100 font-medium text-xs md:text-sm">{{ __('highest_outflow') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Efficiency -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-6 md:p-8 bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Activity class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <Activity class="w-5 h-5 md:w-6 md:h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-base md:text-lg text-white/90 tracking-tight">{{ __('efficiency') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-2xl md:text-3xl font-bold tracking-tight tabular-nums">{{ efficiencyStatus.label }}</h2>
                            <p class="text-amber-100 font-medium text-xs md:text-sm">{{ __('spending_score') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Central: LINE CHARTS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8 mb-8">
                <!-- Income Chart -->
                <div id="step-analysis-income" class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight">{{ __('income_flow') }}</h2>
                            <p class="text-[10px] md:text-xs text-slate-500 font-medium mt-1">{{ __('daily_inflow_trend') }}</p>
                        </div>
                        <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600 shadow-sm border border-emerald-100">
                            <ArrowUpRight class="w-4 h-4 md:w-5 md:h-5" />
                        </div>
                    </div>
                    <Deferred data="cashFlowTrend">
                        <template #fallback>
                            <div class="h-[220px] md:h-[280px] w-full flex flex-col justify-end gap-3 pb-2">
                                <div class="flex items-end justify-between gap-2 h-full px-2">
                                    <div v-for="i in 12" :key="i" 
                                        class="w-full bg-slate-50 rounded-t-lg animate-pulse"
                                        :style="{ height: `${Math.floor(Math.random() * 40) + 20}%` }">
                                    </div>
                                </div>
                                <div class="h-px w-full bg-slate-100"></div>
                            </div>
                        </template>
                        <div class="h-[220px] md:h-[280px] w-full">
                            <VueApexCharts 
                                v-if="hasData"
                                type="area" 
                                height="100%" 
                                width="100%"
                                :options="incomeChartOptions" 
                                :series="incomeSeries" 
                            />
                            <!-- Empty State for Income Chart -->
                            <div v-else class="text-center px-4">
                                <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <TrendingUp class="w-7 h-7 text-slate-300" />
                                </div>
                                <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('no_analysis_yet') }}</h4>
                                <p class="text-slate-500 text-sm mb-4">{{ __('inc_data_appear') }}</p>
                                
                                <Link href="/transactions" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-all active:scale-95">
                                    <Plus class="w-4 h-4" />
                                    <span>{{ __('add_transaction') }}</span>
                                </Link>
                            </div>
                        </div>
                    </Deferred>
                </div>

                <!-- Expense Chart -->
                <div id="step-analysis-expense" class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-slate-200">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight">{{ __('expense_path') }}</h2>
                            <p class="text-[10px] md:text-xs text-slate-500 font-medium mt-1">{{ __('daily_outflow_trend') }}</p>
                        </div>
                        <div class="p-2 rounded-xl bg-rose-50 text-rose-600 shadow-sm border border-rose-100">
                            <ArrowUpRight class="w-4 h-4 md:w-5 md:h-5 rotate-90" />
                        </div>
                    </div>
                    <Deferred data="cashFlowTrend">
                        <template #fallback>
                            <div class="h-[220px] md:h-[280px] w-full flex flex-col justify-end gap-3 pb-2">
                                <div class="flex items-end justify-between gap-2 h-full px-2">
                                    <div v-for="i in 12" :key="i" 
                                        class="w-full bg-slate-50 rounded-t-lg animate-pulse"
                                        :style="{ height: `${Math.floor(Math.random() * 40) + 20}%` }">
                                    </div>
                                </div>
                                <div class="h-px w-full bg-slate-100"></div>
                            </div>
                        </template>
                        <div class="h-[220px] md:h-[280px] w-full">
                            <VueApexCharts 
                                v-if="hasData"
                                type="area" 
                                height="100%" 
                                width="100%"
                                :options="expenseChartOptions" 
                                :series="expenseSeries" 
                            />
                            <!-- Empty State for Expense Chart -->
                            <div v-else class="text-center px-4">
                                <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <ArrowUpRight class="w-7 h-7 text-slate-300 rotate-90" />
                                </div>
                                <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('no_data_available') }}</h4>
                                <p class="text-slate-500 text-sm mb-4">{{ __('cant_show_expense') }}</p>
                                
                                <Link href="/transactions" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-all active:scale-95">
                                    <Plus class="w-4 h-4" />
                                    <span>{{ __('add_transaction') }}</span>
                                </Link>
                            </div>
                        </div>
                    </Deferred>
                </div>
            </div>

            <!-- Spending Mix (Full Width) -->
            <div id="step-analysis-mix" class="bg-white rounded-[2rem] p-6 md:p-8 shadow-sm border border-slate-200 mb-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight">{{ __('spending_mix') }}</h2>
                        <p class="text-[10px] md:text-xs text-slate-500 font-medium mt-1">{{ __('cat_dist_breakdown') }}</p>
                    </div>
                    <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 shadow-sm border border-indigo-100">
                        <PieChartIcon class="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                </div>
                
                <Deferred data="categorySpending">
                    <template #fallback>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12 items-center py-8">
                            <div class="relative w-[280px] h-[280px] mx-auto animate-pulse flex items-center justify-center">
                                <div class="w-full h-full rounded-full border-[30px] border-slate-50"></div>
                                <div class="absolute w-[180px] h-[180px] rounded-full bg-slate-50/30"></div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="i in 6" :key="i" class="p-4 rounded-2xl border border-slate-50 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <div class="w-20 h-3 bg-slate-50 rounded animate-pulse"></div>
                                        <div class="w-16 h-3 bg-slate-50 rounded animate-pulse"></div>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-50 rounded-full animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div v-if="data.categorySpending.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12 items-center">
                        <!-- Chart Section (50%) -->
                        <div class="h-[340px] md:h-[380px] w-full relative flex items-center justify-center">
                            <VueApexCharts 
                                type="donut" 
                                height="100%" 
                                width="100%"
                                :options="categoryChartOptions" 
                                :series="categorySeries" 
                            />
                        </div>

                        <!-- Category Details Grid (50%) -->
                        <div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                                <div v-for="(item, index) in processedCategorySpending" :key="index" class="p-3 rounded-2xl border border-slate-100 hover:border-slate-200 hover:shadow-sm transition-all group bg-slate-50/50">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full shadow-sm" :style="{ backgroundColor: categoryChartOptions.colors[index % categoryChartOptions.colors.length] }"></span>
                                            <span class="text-xs font-bold text-slate-700">{{ item.category_name }}</span>
                                        </div>
                                        <span class="text-xs font-bold text-slate-900 tabular-nums">{{ formatCurrency(item.total).split(',')[0] }}</span>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="w-full bg-slate-200 rounded-full h-1 md:h-1.5 overflow-hidden mb-1">
                                        <div class="h-full rounded-full transition-all duration-500" 
                                            :style="{ 
                                                width: `${item.percentage}%`,
                                                backgroundColor: categoryChartOptions.colors[index % categoryChartOptions.colors.length]
                                            }">
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-400 font-medium text-right">{{ item.percentage }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State centered across full card -->
                    <div v-else class="py-12 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4">
                            <PieChartIcon class="w-8 h-8 text-slate-300" />
                        </div>
                        <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('no_spending_data') }}</h4>
                        <p class="text-sm text-slate-500 mb-6 max-w-xs mx-auto">{{ __('record_tx_see_breakdown') }}</p>
                        <Link href="/transactions" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-all active:scale-95">
                            <Plus class="w-4 h-4" />
                            <span>{{ __('add_transaction') }}</span>
                        </Link>
                    </div>
                </Deferred>
            </div>

            <!-- Insights & Tips (Split Grid) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8 mb-24 pb-8 md:mb-12 md:pb-0">
                <!-- Smart Insights -->
                <div id="step-analysis-insights" class="bg-white rounded-[2rem] shadow-sm border border-slate-200 h-full relative overflow-hidden flex flex-col">
                    <div class="p-6 md:p-8 pb-4 flex items-center justify-between">
                        <h2 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight">{{ __('smart_insights') }}</h2>
                        <div class="p-2 rounded-xl bg-violet-50 text-violet-600 shadow-sm border border-violet-100">
                            <Lightbulb class="w-4 h-4 md:w-5 md:h-5" />
                        </div>
                    </div>

                    <Deferred data="smartInsights">
                        <template #fallback>
                            <div class="p-6 md:p-8 pt-0 flex-grow space-y-4">
                                <div v-for="i in 3" :key="i" class="p-5 rounded-2xl border border-slate-50 flex gap-4 animate-pulse">
                                    <div class="w-5 h-5 rounded-lg bg-slate-50 shrink-0"></div>
                                    <div class="flex-grow space-y-2">
                                        <div class="w-32 h-3.5 bg-slate-50 rounded"></div>
                                        <div class="w-full h-12 bg-slate-50/50 rounded-lg"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-if="is_premium" class="p-6 md:p-8 pt-0 flex-grow">
                            <div v-if="data.smartInsights.length > 0" class="space-y-4">
                                <div 
                                    v-for="(insight, index) in data.smartInsights" 
                                    :key="index"
                                    :class="['p-4 md:p-5 rounded-2xl border flex gap-3 md:gap-4 transition-all', getInsightStyle(insight.type)]"
                                >
                                    <component :is="iconMap[insight.icon]" class="w-4 h-4 md:w-5 md:h-5 shrink-0 mt-1" />
                                    <div>
                                        <h4 class="text-[13px] md:text-sm font-bold mb-1">{{ insight.title }}</h4>
                                        <p class="text-[12px] md:text-sm opacity-90 leading-relaxed">{{ insight.message }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 flex flex-col items-center justify-center">
                                <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <Lightbulb class="w-7 h-7 text-slate-300" />
                                </div>
                                <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('analyzing_patterns') }}</h4>
                                <p class="text-slate-500 text-sm max-w-[200px] mx-auto">{{ __('smart_insights_appear') }}</p>
                            </div>
                        </div>

                        <!-- Premium Lock Overlay for Insights -->
                        <div v-else class="flex-grow flex flex-col items-center justify-center text-center p-8 bg-slate-50/50">
                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-100 mb-5 border border-indigo-50 animate-bounce-subtle">
                                <Crown class="w-8 h-8 text-amber-500" />
                            </div>
                            <h4 class="text-slate-900 font-bold text-lg mb-2">{{ __('unlock_smart_insights') }}</h4>
                            <p class="text-sm text-slate-500 mb-6 max-w-[280px]">{{ __('get_ai_analysis') }}</p>
                            <Link href="/subscription" class="px-6 py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95 flex items-center gap-2">
                                <Sparkles class="w-4 h-4" />
                                {{ __('upgrade_professional') }}
                            </Link>
                        </div>
                    </Deferred>
                </div>

                <!-- Financial Tips -->
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 h-full relative overflow-hidden flex flex-col">
                    <div class="p-6 md:p-8 pb-4 flex items-center justify-between">
                        <h2 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight">{{ __('pro_tips') }}</h2>
                        <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 shadow-sm border border-indigo-100">
                            <Award class="w-4 h-4 md:w-5 md:h-5" />
                        </div>
                    </div>
                    
                    <Deferred data="financialTips">
                        <template #fallback>
                            <div class="p-6 md:p-8 pt-0 flex-grow space-y-4">
                                <div v-for="i in 3" :key="i" class="p-5 rounded-2xl border border-slate-50 flex gap-4 animate-pulse">
                                    <div class="w-8 h-8 rounded-xl bg-slate-50 shrink-0"></div>
                                    <div class="flex-grow space-y-2">
                                        <div class="w-24 h-3 bg-slate-50 rounded"></div>
                                        <div class="w-full h-8 bg-slate-50/50 rounded"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-if="is_premium" class="p-6 md:p-8 pt-0 flex-grow">
                            <div v-if="data.financialTips.length > 0" class="space-y-4">
                                <div 
                                    v-for="(tip, index) in data.financialTips" 
                                    :key="index" 
                                    class="p-4 md:p-5 rounded-2xl border border-slate-100 bg-slate-50 flex gap-3 md:gap-4 transition-all hover:bg-white hover:shadow-sm"
                                >
                                    <div class="p-2 h-fit rounded-xl bg-white border border-slate-100 text-indigo-600 shrink-0 shadow-sm">
                                        <Target class="w-3.5 h-3.5 md:w-4 md:h-4" />
                                    </div>
                                    <div>
                                        <h4 class="text-[13px] md:text-sm font-bold mb-1 text-slate-900">{{ tip.title }}</h4>
                                        <p class="text-[12px] md:text-sm text-slate-600 leading-relaxed">{{ tip.message }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 flex flex-col items-center justify-center">
                                <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <Award class="w-7 h-7 text-slate-300" />
                                </div>
                                <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('growth_tips') }}</h4>
                                <p class="text-slate-500 text-sm max-w-[200px] mx-auto">{{ __('tips_generated') }}</p>
                            </div>
                        </div>

                        <!-- Premium Lock Overlay for Pro Tips -->
                        <div v-else class="flex-grow flex flex-col items-center justify-center text-center p-8 bg-slate-50/50">
                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-100 mb-5 border border-indigo-50 animate-bounce-subtle">
                                <Crown class="w-8 h-8 text-amber-500" />
                            </div>
                            <h4 class="text-slate-900 font-bold text-lg mb-2">{{ __('master_finances') }}</h4>
                            <p class="text-sm text-slate-500 mb-6 max-w-[280px]">{{ __('unlock_advice') }}</p>
                            <Link href="/subscription" class="px-6 py-3 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95 flex items-center gap-2">
                                <Sparkles class="w-4 h-4" />
                                {{ __('upgrade_professional') }}
                            </Link>
                        </div>
                    </Deferred>
                </div>
            </div>
        </Layout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
