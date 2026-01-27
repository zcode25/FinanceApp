
<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
import SearchableSelect from '@/Shared/SearchableSelect.vue';
import { ref, watch, computed } from 'vue';
import { 
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
    Globe
} from 'lucide-vue-next';
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    data: Object
});

const selectedMonth = ref(props.data.summary.selected_month);

watch(selectedMonth, (newMonth) => {
    router.get('/', { month: newMonth }, { 
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
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
        categories: props.data.charts.labels,
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
            formatter: (val) => (val / 1000000).toFixed(1) + 'M'
        }
    },
    tooltip: {
        theme: 'dark',
        x: { 
            show: true,
            formatter: (val) => `${val} ${props.data.summary.selected_month_label}`
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

const incomeSeries = computed(() => [{ name: 'Inflow', data: props.data.charts.income }]);
const expenseSeries = computed(() => [{ name: 'Outflow', data: props.data.charts.expense }]);

const getTypeColor = (type) => {
    switch (type) {
        case 'cash':
            return {
                bg: 'bg-emerald-500/10',
                text: 'text-emerald-400',
                solid: 'bg-emerald-500',
                border: 'border-emerald-500/20'
            };
        case 'ewallet':
            return {
                bg: 'bg-purple-500/10',
                text: 'text-purple-400',
                solid: 'bg-purple-500',
                border: 'border-purple-500/20'
            };
        case 'bank':
            return {
                bg: 'bg-orange-500/10',
                text: 'text-orange-400',
                solid: 'bg-orange-500',
                border: 'border-orange-500/20'
            };
        default:
            return {
                bg: 'bg-indigo-500/10',
                text: 'text-indigo-400',
                solid: 'bg-indigo-500',
                border: 'border-indigo-500/20'
            };
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <Layout>
        <div class="space-y-10">
            <!-- TOP HEADER: Period Control & Search -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-8 pt-2 relative z-[60]">
                <div class="space-y-2">
                    <h1 class="text-4xl font-bold text-white tracking-tight flex items-center gap-4">
                        {{ __('dashboard') }}
                    </h1>
                    <div class="flex items-center gap-3 text-gray-400">
                        <Calendar class="w-4 h-4" />
                        <p class="text-sm font-medium tracking-wide">
                            {{ __('performance_log') }}: <span class="text-white font-bold">{{ data.summary.selected_month_label }}</span>
                        </p>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-4 bg-white/5 p-2 rounded-2xl border border-white/5 backdrop-blur-md">
                    <div class="w-[200px]">
                         <SearchableSelect 
                            v-model="selectedMonth" 
                            :options="data.available_months"
                            :placeholder="__('select_month')"
                        />
                    </div>

                    <Link 
                        href="/transactions" 
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-black transition-all flex items-center gap-3 shadow-xl shadow-indigo-600/30 group"
                    >
                        <Plus class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" />
                        <span>{{ __('add_transaction') }}</span>
                    </Link>
                </div>
            </div>

            <!-- SECTION 1: GLOBAL METRICS (Strategic) -->
            <div class="space-y-4 relative z-0">
                <div class="flex items-center gap-2 ml-1">
                    <Globe class="w-4 h-4 text-indigo-400" />
                    <h2 class="text-xs font-black text-gray-500 uppercase tracking-[0.3em]">{{ __('strategic_assets_overview') }}</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <div class="xl:col-span-2 glass-card p-8 border-indigo-500/20 relative overflow-hidden group shadow-2xl shadow-indigo-500/5">
                        <div class="absolute -right-10 -top-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl group-hover:bg-indigo-500/15 transition-all"></div>
                        <div class="relative z-10 flex flex-col h-full justify-between">
                            <div>
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-3">{{ __('global_net_worth') }}</p>
                                <h3 class="text-4xl md:text-6xl font-bold text-white tracking-tighter">{{ formatCurrency(data.summary.net_worth) }}</h3>
                            </div>
                            <div class="flex items-center gap-12 mt-8 pt-8 border-t border-white/5">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">{{ __('lifetime_savings') }}</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-3xl font-black text-emerald-400">{{ data.summary.lifetime_savings_rate }}%</span>
                                        <div class="p-1 rounded bg-emerald-500/10"><TrendingUp class="w-4 h-4 text-emerald-500" /></div>
                                    </div>
                                </div>
                                <div class="hidden sm:block w-px h-12 bg-white/5"></div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">{{ __('cluster_count') }}</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-3xl font-black text-indigo-400">{{ data.wallets.length }}</span>
                                        <div class="p-1 rounded bg-indigo-500/10"><Wallet class="w-4 h-4 text-indigo-500" /></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Wallet Clusters -->
                    <div class="xl:col-span-2 glass-card p-6 border-white/5 flex flex-col">
                        <div class="flex items-center justify-between mb-6">
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em]">{{ __('asset_allocation') }}</p>
                            <Link href="/wallets" class="text-[10px] font-black text-indigo-400 hover:text-indigo-300 transition-colors uppercase tracking-widest flex items-center gap-1">
                                {{ __('manage_clusters') }} <ArrowRight class="w-3 h-3" />
                            </Link>
                        </div>
                        <div v-if="data.wallets.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                            <div v-for="wallet in data.wallets.slice(0, 4)" :key="wallet.name" class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:border-white/10 transition-all group">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-tighter">{{ wallet.name }}</span>
                                    <span class="text-[8px] px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-400 font-bold">{{ wallet.currency }}</span>
                                </div>
                                <div class="text-sm font-black text-white">{{ formatCurrency(wallet.balance) }}</div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-8 text-center bg-white/5 rounded-2xl border border-dashed border-white/10 flex-1 group hover:border-indigo-500/30 transition-all">
                            <Wallet class="w-8 h-8 text-gray-600 mb-3 group-hover:text-indigo-400 transition-colors" />
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('no_assets_tracked') }}</p>
                            <p class="text-[9px] text-gray-500 mb-4 px-4 leading-relaxed">{{ __('no_assets_desc') }}</p>
                            <Link href="/wallets" class="px-4 py-2 bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                                <Plus class="w-3 h-3" /> {{ __('create_wallet') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: OPERATIONAL METRICS (Selected Month) -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 ml-1">
                    <Zap class="w-4 h-4 text-emerald-400" />
                    <h2 class="text-xs font-black text-gray-500 uppercase tracking-[0.3em]">{{ data.summary.selected_month_label }} {{ __('performance_context') }}</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-3 gap-6">
                    <div class="glass-card p-6 border-emerald-500/20 group hover:bg-emerald-500/[0.03] transition-colors">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-400"><TrendingUp class="w-6 h-6" /></div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ __('revenue_inflow') }}</span>
                        </div>
                        <h4 class="text-3xl font-black text-white mb-1">{{ formatCurrency(data.summary.monthly_income) }}</h4>
                        <p class="text-[10px] text-emerald-400/50 font-bold uppercase tracking-widest">{{ __('confirmed_deposits') }}</p>
                    </div>
                    
                    <div class="glass-card p-6 border-rose-500/20 group hover:bg-rose-500/[0.03] transition-colors">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-400"><TrendingDown class="w-6 h-6" /></div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ __('capital_outflow') }}</span>
                        </div>
                        <h4 class="text-3xl font-black text-white mb-1">{{ formatCurrency(data.summary.monthly_expense) }}</h4>
                        <p class="text-[10px] text-rose-400/50 font-bold uppercase tracking-widest">{{ __('processed_settlements') }}</p>
                    </div>

                    <div :class="['glass-card p-6 border-white/5 flex flex-col justify-between transition-all group hover:scale-[1.02]', (data.summary.monthly_net_savings > 0) ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-rose-500/5 border-rose-500/20']">
                        <div class="flex items-center justify-between mb-4">
                            <div :class="['p-3 rounded-2xl shadow-lg', (data.summary.monthly_net_savings > 0) ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white']">
                                <Zap class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">{{ (data.summary.monthly_net_savings > 0) ? __('operational_profit') : __('budget_deficit') }}</span>
                        </div>
                        <div>
                            <h4 :class="['text-3xl font-black tracking-tighter', (data.summary.monthly_net_savings > 0) ? 'text-emerald-400' : 'text-rose-400']">
                                {{ (data.summary.monthly_net_savings > 0) ? '+' : '' }}{{ formatCurrency(data.summary.monthly_net_savings) }}
                            </h4>
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1">{{ __('monthly_delta_balance') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: ANALYTICS HUB (Dual Charts) -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <!-- Dual Chart: Income -->
                <div class="glass-card p-8 border-emerald-500/10 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                             <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-400"><ArrowUpRight class="w-6 h-6" /></div>
                             <div>
                                 <h3 class="text-lg font-black text-white tracking-tight uppercase">{{ __('inflow_performance') }}</h3>
                                 <p class="text-xs text-gray-400">12-month revenue velocity</p>
                             </div>
                        </div>
                        <div class="text-right">
                             <p class="text-[10px] font-bold text-gray-500 uppercase">{{ __('avg_monthly') }}</p>
                             <p class="text-sm font-black text-emerald-400">{{ formatCurrency(data.charts.income.reduce((a,b) => a+b, 0) / 12) }}</p>
                        </div>
                    </div>
                    <VueApexCharts type="line" height="220" :options="incomeChartOptions" :series="incomeSeries" />
                </div>

                <!-- Dual Chart: Expense -->
                <div class="glass-card p-8 border-rose-500/10 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                             <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-400"><ArrowDownRight class="w-6 h-6" /></div>
                             <div>
                                 <h3 class="text-lg font-black text-white tracking-tight uppercase">{{ __('outflow_intensity') }}</h3>
                                 <p class="text-xs text-gray-400">12-month burning pattern</p>
                             </div>
                        </div>
                        <div class="text-right">
                             <p class="text-[10px] font-bold text-gray-500 uppercase">{{ __('avg_monthly') }}</p>
                             <p class="text-sm font-black text-rose-400">{{ formatCurrency(data.charts.expense.reduce((a,b) => a+b, 0) / 12) }}</p>
                        </div>
                    </div>
                    <VueApexCharts type="line" height="220" :options="expenseChartOptions" :series="expenseSeries" />
                </div>
            </div>

            <!-- SECTION 4: LOWER INTEL (Recent Activity & Top Burners) -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                <div class="xl:col-span-8 glass-card border-white/5 overflow-hidden flex flex-col">
                    <div class="p-5 md:p-8 flex items-center justify-between border-b border-white/5">
                        <Link href="/transactions" class="flex items-center gap-4 group cursor-pointer">
                            <div class="p-3 rounded-2xl bg-white/5 text-gray-400 group-hover:text-indigo-400 group-hover:bg-indigo-500/10 transition-all"><Clock class="w-6 h-6" /></div>
                            <div>
                                <h3 class="text-lg font-black text-white tracking-tight uppercase group-hover:text-indigo-400 transition-colors">{{ __('activity_stream') }}</h3>
                                <p class="text-xs text-gray-400">{{ __('chronological_log') }} <span class="text-indigo-500/50 group-hover:text-indigo-400 ml-1 transition-colors text-[10px] uppercase font-bold tracking-widest">{{ __('view_all') }} &rarr;</span></p>
                            </div>
                        </Link>
                    </div>
                    <div v-if="data.recent_transactions.length > 0">
                        <!-- Desktop View -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-white/5 text-[9px] font-black text-gray-500 uppercase tracking-[0.2em]">
                                    <tr>
                                        <th class="px-8 py-4">{{ __('timestamp') }}</th>
                                        <th class="px-8 py-4">{{ __('narrative') }}</th>
                                        <th class="px-8 py-4 text-center">{{ __('category') }}</th>
                                        <th class="px-8 py-4 text-right">{{ __('magnitude') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr v-for="tx in data.recent_transactions" :key="tx.id" class="hover:bg-white/5 transition-colors group">
                                        <td class="px-8 py-5 text-xs text-gray-500 font-bold">{{ new Date(tx.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) }}</td>
                                        <td class="px-8 py-5 text-xs font-bold text-white group-hover:text-indigo-400 transition-colors">{{ tx.description }}</td>
                                        <td class="px-8 py-5 text-center">
                                        <span class="text-[9px] font-black px-2 py-1 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase tracking-tighter">{{ tx.category }}</span>
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <div :class="['text-sm font-black tracking-tight', tx.type === 'expense' ? 'text-rose-400' : 'text-emerald-400']">
                                                {{ tx.type === 'expense' ? '-' : '+' }}{{ formatCurrency(tx.amount) }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View -->
                        <div class="md:hidden space-y-3 p-5">
                            <div v-for="tx in data.recent_transactions" :key="tx.id" class="p-4 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between group active:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center shrink-0', tx.type === 'expense' ? 'bg-rose-500/10 text-rose-500' : 'bg-emerald-500/10 text-emerald-500']">
                                        <component :is="tx.type === 'expense' ? TrendingDown : TrendingUp" class="w-5 h-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-white text-sm truncate pr-2">{{ tx.description }}</h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider shrink-0">{{ new Date(tx.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) }}</span>
                                            <span :class="['text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-tighter truncate', tx.category_color ? tx.category_color + '/10 ' + tx.category_color.replace('bg-', 'text-') : 'bg-white/5 text-gray-400']">{{ tx.category }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div :class="['text-sm font-black tracking-tight whitespace-nowrap pl-2', tx.type === 'expense' ? 'text-rose-400' : 'text-emerald-400']">
                                    {{ tx.type === 'expense' ? '-' : '+' }}{{ formatCurrency(tx.amount) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-16 text-center group">
                        <div class="p-4 rounded-full bg-white/5 mb-4 group-hover:bg-indigo-500/10 transition-colors">
                            <Clock class="w-8 h-8 text-gray-600 group-hover:text-indigo-400 transition-colors" />
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('no_activity') }}</p>
                        <p class="text-[9px] text-gray-500 mb-4 bg-transparent">{{ __('no_activity_desc') }}</p>
                        <Link href="/transactions" class="px-4 py-2 bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                            <Plus class="w-3 h-3" /> {{ __('add_transaction') }}
                        </Link>
                    </div>
                </div>

                <div class="xl:col-span-4 glass-card p-5 md:p-8 border-white/5 flex flex-col">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-400"><PieChart class="w-6 h-6" /></div>
                        <div>
                             <h3 class="text-lg font-black text-white tracking-tight uppercase">{{ __('top_burners') }}</h3>
                             <p class="text-xs text-gray-400">{{ data.summary.selected_month_label }}</p>
                        </div>
                    </div>
                    <div class="space-y-4 flex-1">
                        <div v-for="cat in data.categories" :key="cat.category" class="flex items-center justify-between p-4 rounded-2xl bg-white/5 border border-white/5 hover:border-indigo-500/30 transition-all cursor-default">
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-gray-200 uppercase tracking-widest">{{ cat.category }}</span>
                                <span class="text-[10px] text-gray-500 font-bold">{{ Math.round((cat.total / (data.summary.monthly_expense || 1)) * 100) }}% contribution</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-black text-rose-400">{{ formatCurrency(cat.total) }}</span>
                            </div>
                        </div>
                        <div v-if="data.categories.length === 0" class="flex flex-col items-center justify-center py-12 text-center bg-white/5 rounded-3xl border border-dashed border-white/10 h-full group hover:border-rose-500/30 transition-all">
                            <AlertCircle class="w-8 h-8 text-gray-600 mb-3 group-hover:text-rose-400 transition-colors" />
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('no_expense_data') }}</p>
                            <p class="text-[9px] text-gray-500 mb-4 px-2 leading-relaxed">Start recording expenses to identify your top spending categories.</p>
                            <Link href="/transactions" class="px-4 py-2 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                                <Plus class="w-3 h-3" /> {{ __('add_expense') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>

<style scoped>
.glass-card {
    background: rgba(30, 41, 59, 0.4);
    backdrop-filter: blur(24px);
    border-radius: 32px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
