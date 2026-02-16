<script setup>
import { computed, ref } from 'vue';
import { Wallet, TrendingUp, TrendingDown, Target, Globe, ChartPie, Activity, ShieldCheck, Calendar, Zap } from 'lucide-vue-next';
import { __ } from '@/Plugins/i18n';

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
</script>

<template>
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

                                <!-- Dynamic Line Paths -->
                                <path :d="incomePath" fill="none" stroke="#10b981" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                                <path :d="expensePath" fill="none" stroke="#f59e0b" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
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
                                    <circle cx="50" cy="50" r="25" fill="none" stroke="#10b981" stroke-width="50" stroke-dasharray="94.25 157.08" />
                                    <circle cx="50" cy="50" r="25" fill="none" stroke="#f43f5e" stroke-width="50" stroke-dasharray="39.27 157.08" stroke-dashoffset="-94.25" />
                                    <circle cx="50" cy="50" r="25" fill="none" stroke="#f59e0b" stroke-width="50" stroke-dasharray="23.56 157.08" stroke-dashoffset="-133.52" />
                                </svg>
                            </div>
                        </div>

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
</template>
