<script setup>
import Layout from '../../Shared/Layout.vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { TrendingUp, Flame, AlertTriangle, Calendar, Coffee, Award, Shield, ShieldAlert, Scissors, PiggyBank, Target, PieChart as PieChartIcon, Activity, ArrowUpRight, Lightbulb } from 'lucide-vue-next';
import SearchableSelect from '../../Shared/SearchableSelect.vue';
import VueApexCharts from "vue3-apexcharts";
    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;

    const props = defineProps({
        filters: Object,
        summary: Object,
        categorySpending: Array,
        cashFlowTrend: Object,
        smartInsights: Array,
        walletAllocation: Array,
        availableMonths: Array,
        financialTips: Array
    });
    
    // Filters state
    const selectedMonth = ref(props.filters?.month || new Date().toISOString().slice(0, 7));
    
    // Watch filters and reload
    watch(selectedMonth, (newMonth) => {
        router.get('/analysis', { month: newMonth }, {
            preserveState: true,
            preserveScroll: true,
            replace: true, // Use replace to avoid history stack buildup
            only: ['summary', 'categorySpending', 'filters', 'cashFlowTrend', 'smartInsights', 'walletAllocation', 'availableMonths', 'financialTips']
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
            foreColor: '#94a3b8'
        },
        labels: props.categorySpending.map(item => item.category_name),
        colors: ['#6366f1', '#10b981', '#f43f5e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'],
        stroke: {
            show: false
        },
        legend: {
            position: 'bottom',
            labels: {
                colors: '#94a3b8'
            }
        },
        dataLabels: {
            enabled: false
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        name: { color: '#94a3b8' },
                        value: { 
                            color: '#ffffff', 
                            fontSize: '24px', 
                            fontWeight: 'bold',
                            formatter: (val) => formatCurrency(val)
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            color: '#94a3b8',
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
            type: 'line',
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
        grid: {
            borderColor: 'rgba(255, 255, 255, 0.05)',
            strokeDashArray: 4,
            padding: { left: 10, right: 10 }
        },
        xaxis: {
            categories: props.cashFlowTrend.labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: { colors: 'rgba(156, 163, 175, 0.7)', fontSize: '10px', fontWeight: 600 },
                formatter: (val) => {
                    const day = parseInt(val);
                    return (day === 1 || day % 5 === 0) ? val : '';
                }
            }
        },
        yaxis: {
            labels: {
                style: { colors: 'rgba(156, 163, 175, 0.7)', fontSize: '10px' },
                formatter: (val) => formatCompact(val)
            }
        },
        tooltip: {
            theme: 'dark',
            x: { 
                show: true,
                formatter: (val) => {
                    const monthLabel = props.availableMonths.find(m => m.value === selectedMonth.value)?.label || selectedMonth.value;
                    return `${val} ${monthLabel}`; 
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
    const incomeSeries = computed(() => [{ name: 'Income', data: props.cashFlowTrend.income }]);
    const expenseSeries = computed(() => [{ name: 'Expense', data: props.cashFlowTrend.expense }]);
    const categorySeries = computed(() => props.categorySpending.map(item => parseFloat(item.total)));
    
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
    
    const topCategory = computed(() => {
        if (props.categorySpending.length === 0) return __('status_no_data');
        return props.categorySpending[0].category_name;
    });
    
    const savingsStatus = computed(() => {
        const rate = props.summary.savings_rate;
        const income = props.summary.total_income;

        if (income === 0 && props.summary.total_expense === 0) return { label: __('status_no_data'), color: 'text-gray-400', bg: 'bg-gray-500/10' };
        if (income === 0 && props.summary.total_expense > 0) return { label: __('status_overspending'), color: 'text-rose-400', bg: 'bg-rose-500/10' };

        if (rate >= 30) return { label: __('status_excellent'), color: 'text-emerald-400', bg: 'bg-emerald-500/10' };
        if (rate >= 10) return { label: __('status_good'), color: 'text-indigo-400', bg: 'bg-indigo-500/10' };
        if (rate > 0) return { label: __('status_saving'), color: 'text-amber-400', bg: 'bg-amber-500/10' };
        return { label: __('status_overspending'), color: 'text-rose-400', bg: 'bg-rose-500/10' };
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
            case 'critical': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
            case 'warning': return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
            case 'success': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
            case 'info': return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
            default: return 'bg-gray-500/10 text-gray-400 border-gray-500/20';
        }
    };
    const efficiencyStatus = computed(() => {
        const rate = props.summary.savings_rate;
        const income = props.summary.total_income;

        if (income === 0 && props.summary.total_expense === 0) return { label: __('status_no_data'), color: 'text-gray-400' };
        
        if (rate >= 20) return { label: __('status_high'), color: 'text-emerald-400' };
        if (rate > 0) return { label: __('status_medium'), color: 'text-amber-400' };
        return { label: __('status_low'), color: 'text-rose-400' };
    });
    </script>
    
    <template>
        <Head :title="__('financial_analysis')" />
        <Layout>
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ __('financial_analysis') }}</h1>
                    <p class="text-gray-400">{{ __('financial_analysis_desc') }}</p>
                </div>
    
                <!-- Month Filter -->
                <div class="flex items-center gap-3 bg-gray-900/50 p-1.5 rounded-xl border border-white/5 relative z-50">
                    <div class="flex items-center gap-2 px-3">
                        <Calendar class="w-4 h-4 text-indigo-400" />
                        <span class="text-sm font-medium text-gray-400">{{ __('month_label') }}</span>
                    </div>
                    <div class="w-[200px]">
                        <SearchableSelect 
                            v-model="selectedMonth" 
                            :options="availableMonths" 
                            :placeholder="__('select_month')" 
                        />
                    </div>
                </div>
            </header>
    
            <!-- Summary Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="glass-card p-6 border-white/5 relative group overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400">
                                <Target class="w-5 h-5" />
                            </div>
                            <span class="text-sm font-medium text-gray-400">{{ __('savings_rate') }}</span>
                        </div>
                        <h3 class="text-3xl font-extrabold text-white mb-2">{{ summary.savings_rate }}%</h3>
                        <span :class="['text-xs font-bold px-2 py-1 rounded-full uppercase tracking-wider', savingsStatus.bg, savingsStatus.color]">
                            {{ savingsStatus.label }}
                        </span>
                    </div>
                </div>
    
                <div class="glass-card p-6 border-white/5 relative group overflow-hidden">
                     <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                                <TrendingUp class="w-5 h-5" />
                            </div>
                            <span class="text-sm font-medium text-gray-400">{{ __('total_savings') }}</span>
                        </div>
                        <h3 class="text-3xl font-extrabold text-emerald-400 mb-1">{{ formatCurrency(summary.net_savings) }}</h3>
                        <p class="text-xs text-gray-500">{{ __('selected_period') }}</p>
                    </div>
                </div>
    
                <div class="glass-card p-6 border-white/5 relative group overflow-hidden">
                     <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl group-hover:bg-rose-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 rounded-lg bg-rose-500/10 text-rose-400">
                                <PieChartIcon class="w-5 h-5" />
                            </div>
                            <span class="text-sm font-medium text-gray-400">{{ __('top_category') }}</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-white mb-1 uppercase tracking-tight">{{ topCategory }}</h3>
                        <p class="text-xs text-gray-500">{{ __('highest_spending_item') }}</p>
                    </div>
                </div>
    
                <div class="glass-card p-6 border-white/5 relative group overflow-hidden">
                     <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-2 rounded-lg bg-amber-500/10 text-amber-400">
                                <Activity class="w-5 h-5" />
                            </div>
                            <span class="text-sm font-medium text-gray-400">{{ __('efficiency') }}</span>
                        </div>
                        <h3 :class="['text-3xl font-extrabold mb-1', efficiencyStatus.color]">{{ efficiencyStatus.label }}</h3>
                        <p class="text-xs text-gray-500">{{ __('spending_score') }}</p>
                    </div>
                </div>
            </div>
    
            <!-- Charts Central: LINE CHARTS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Income Chart -->
                <div class="glass-card p-8 border-emerald-500/10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-400">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">{{ __('income_trend') }}</h2>
                            <p class="text-sm text-gray-400">{{ __('total_inflow') }} {{ selectedMonth }}</p>
                        </div>
                    </div>
                    <div class="h-[220px] w-full">
                        <VueApexCharts 
                            type="line" 
                            height="100%" 
                            :options="incomeChartOptions" 
                            :series="incomeSeries" 
                        />
                    </div>
                </div>
    
                <!-- Expense Chart -->
                <div class="glass-card p-8 border-rose-500/10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-400">
                            <TrendingUp class="w-6 h-6 rotate-180" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">{{ __('expense_trend') }}</h2>
                            <p class="text-sm text-gray-400">{{ __('total_outflow') }} {{ selectedMonth }}</p>
                        </div>
                    </div>
                    <div class="h-[220px] w-full">
                        <VueApexCharts 
                            type="line" 
                            height="100%" 
                            :options="expenseChartOptions" 
                            :series="expenseSeries" 
                        />
                    </div>
                </div>
            </div>
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                <!-- Spending Breakdown (Left Column) -->
                <div class="glass-card p-8 border-white/5 h-full">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400">
                                    <PieChartIcon class="w-5 h-5" />
                                </div>
                                <h2 class="text-xl font-bold text-white">{{ __('spending_mix') }}</h2>
                            </div>
                            <p class="text-sm text-gray-400">{{ __('category_distribution') }}</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-8">
                        <div class="h-[300px] w-full">
                            <VueApexCharts 
                                v-if="categorySpending.length > 0"
                                type="donut" 
                                height="100%" 
                                :options="categoryChartOptions" 
                                :series="categorySeries" 
                            />
                            <div v-else class="h-full flex flex-col items-center justify-center text-center space-y-4 opacity-50">
                                <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center">
                                    <PieChartIcon class="w-8 h-8 text-gray-500" />
                                </div>
                                <p class="text-sm text-gray-500">{{ __('no_expense_data') }}</p>
                            </div>
                        </div>
    
                        <!-- Legend / List -->
                        <div class="max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="(item, index) in categorySpending" :key="index" class="flex items-center justify-between group cursor-default p-3 rounded-lg hover:bg-white/5 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: categoryChartOptions.colors[index % categoryChartOptions.colors.length] }"></div>
                                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">{{ item.category_name }}</span>
                                </div>
                                <span class="text-sm font-bold text-white">{{ formatCurrency(item.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Insights & Tips (Right Column) -->
                <div class="space-y-8">
                     <!-- Smart Insights -->
                     <div class="glass-card p-6 border-white/5">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 rounded-xl bg-indigo-500/20 text-indigo-400">
                                <Activity class="w-5 h-5" />
                            </div>
                            <h2 class="text-lg font-bold text-white">{{ __('smart_insights') }}</h2>
                        </div>
    
                        <div v-if="smartInsights.length > 0" class="space-y-4">
                            <div 
                                v-for="(insight, index) in smartInsights" 
                                :key="index"
                                :class="['p-4 rounded-xl border flex items-start gap-4 transition-all hover:scale-[1.02]', getInsightStyle(insight.type).replace('bg-', 'bg-opacity-50 ')]"
                                class="bg-gray-900/40"
                            >
                                <div :class="['p-2 rounded-lg shrink-0', getInsightStyle(insight.type)]">
                                    <component :is="iconMap[insight.icon]" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white mb-1">{{ insight.title }}</h4>
                                    <p class="text-xs text-gray-400 leading-relaxed">{{ insight.message }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500 text-sm">
                            <Target class="w-8 h-8 mx-auto mb-2 opacity-30" />
                            <p>{{ __('no_insights') }}</p>
                        </div>
                     </div>
    
                     <!-- Financial Tips (Dynamic) -->
                     <div class="glass-card p-8 border-white/5 bg-gray-900/40">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400">
                                <Lightbulb class="w-5 h-5" />
                            </div>
                            <h2 class="text-xl font-bold text-white">{{ __('financial_tips') }}</h2>
                        </div>
                        <div v-if="financialTips.length > 0" class="space-y-4">
                            <div 
                                v-for="(tip, index) in financialTips" 
                                :key="index" 
                                :class="['p-4 rounded-xl border flex items-start gap-4 transition-all hover:scale-[1.02] bg-gray-900/40', getInsightStyle(tip.type).replace('bg-', 'bg-opacity-50 ')]"
                            >
                                <div class="p-2 rounded-lg bg-gray-800/50 shrink-0">
                                    <component 
                                        :is="iconMap[tip.icon] || Activity" 
                                        :class="[
                                            'w-5 h-5 shrink-0 transition-transform group-hover:scale-110', 
                                            tip.type === 'critical' ? 'text-rose-400' : 
                                            tip.type === 'warning' ? 'text-amber-400' : 
                                            tip.type === 'success' ? 'text-emerald-400' : 'text-blue-400'
                                        ]" 
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-white mb-1">{{ tip.title }}</h4>
                                    <p class="text-xs text-gray-400 leading-relaxed">{{ tip.message }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-10 text-gray-500 text-xs flex flex-col items-center gap-3">
                            <div class="p-3 rounded-full bg-white/5">
                                <Award class="w-6 h-6 text-gray-600" />
                            </div>
                            <p>{{ __('no_tips') }}</p>
                        </div>
                     </div>
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
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
