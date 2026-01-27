<script setup>
import Layout from '../../Shared/Layout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    FileText, 
    Download, 
    Calendar, 
    Wallet as WalletIcon,
    Banknote,
    CreditCard,
    TrendingUp,
    TrendingDown,
    ArrowRight
} from 'lucide-vue-next';
import SearchableSelect from '@/Shared/SearchableSelect.vue';

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;

    const props = defineProps({
        reports: Array,
        totals: Object,
        filters: Object,
        availableMonths: Array
    });
    
    const selectedMonth = ref(props.filters.month);
    
    // availableMonths passed as prop
    
    const localizedAvailableMonths = computed(() => {
        return props.availableMonths.map(monthStr => {
            const [year, month] = monthStr.split('-');
            const date = new Date(year, month - 1);
            return {
                value: monthStr,
                label: new Intl.DateTimeFormat(page.props.locale || 'en', { month: 'long', year: 'numeric' }).format(date)
            };
        });
    });

    watch(selectedMonth, (newMonth) => {
        router.get('/reports', { month: newMonth }, {
            preserveState: true,
            preserveScroll: true,
            replace: true
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
        window.open(`/reports/export/pdf?month=${selectedMonth.value}`, '_blank');
    };
    
    const downloadExcel = () => {
        window.location.href = `/reports/export/excel?month=${selectedMonth.value}`;
    };
    
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
        <Head :title="__('e_statement')" />
        <Layout>
            <div class="mb-8 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                        <FileText class="w-8 h-8 text-indigo-400" />
                        {{ __('e_statement') }}
                    </h1>
                    <p class="text-gray-400 mt-1">{{ __('e_statement_desc') }}</p>
                </div>
    
                <div class="flex flex-col md:flex-row items-center gap-4 w-full xl:w-auto">
                    <!-- Month Picker -->
                    <div class="w-full md:w-48">
                        <SearchableSelect 
                            v-model="selectedMonth" 
                            :options="localizedAvailableMonths" 
                            :placeholder="__('select_month')"
                            class="w-full"
                        />
                    </div>
    
                    <!-- Export Actions -->
                    <div class="flex w-full md:w-auto gap-2">
                        <button 
                            @click="downloadPdf"
                            class="flex-1 md:flex-none justify-center flex items-center gap-2 px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl text-sm font-medium transition-all shadow-lg shadow-indigo-500/20 whitespace-nowrap"
                        >
                            <Download class="w-4 h-4" />
                            {{ __('export_pdf') }}
                        </button>
                        <button 
                            @click="downloadExcel"
                            class="flex-1 md:flex-none justify-center flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-medium transition-all shadow-lg shadow-emerald-500/20 whitespace-nowrap"
                        >
                            <Download class="w-4 h-4" />
                            {{ __('export_excel') }}
                        </button>
                    </div>
                </div>
            </div>
    
            <!-- Wallet Reports Loop -->
            <div v-if="reports.length > 0">
                <div v-for="report in reports" :key="report.wallet.id" class="mb-12 animate-fade-in-up">
                    <!-- Wallet Header & Summary -->
                    <div class="flex flex-col xl:flex-row gap-6 mb-6">
                        <!-- Wallet Identity Card -->
                        <div class="glass-card p-4 md:p-6 w-full xl:w-1/3 flex flex-col justify-center relative overflow-hidden group shadow-2xl" :class="getTypeColor(report.wallet.type).border">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full blur-3xl group-hover:bg-white/10 transition-all duration-500"></div>
                            <div class="relative z-10">
                                <div class="flex items-center gap-4 mb-3">
                                     <div :class="[getTypeColor(report.wallet.type).bg, getTypeColor(report.wallet.type).text, 'p-3 rounded-xl ring-1 ring-white/10 group-hover:scale-110 transition-transform duration-300']">
                                        <Banknote v-if="report.wallet.type === 'cash'" class="w-6 h-6" />
                                        <CreditCard v-if="report.wallet.type === 'bank'" class="w-6 h-6" />
                                        <WalletIcon v-if="report.wallet.type === 'ewallet'" class="w-6 h-6" />
                                        <WalletIcon v-if="!['cash', 'bank', 'ewallet'].includes(report.wallet.type)" class="w-6 h-6" />
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-xl font-bold text-white tracking-tight truncate">{{ report.wallet.name }}</h2>
                                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">{{ report.wallet.currency }} {{ __('wallet') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
    
                        <!-- Summary Stats Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full xl:w-2/3">
                            <div class="bg-slate-800/40 border border-white/5 p-4 rounded-2xl hover:bg-slate-800/60 transition-colors">
                                <span class="text-xs text-slate-400 block mb-1 uppercase tracking-wider font-semibold">{{ __('opening_balance') }}</span>
                                <span class="text-lg font-bold text-white truncate">{{ formatCurrency(report.summary.opening_balance, report.wallet.currency) }}</span>
                            </div>
                             <div class="bg-emerald-500/5 border border-emerald-500/10 p-4 rounded-2xl hover:bg-emerald-500/10 transition-colors">
                                <span class="text-xs text-emerald-400 block mb-1 uppercase tracking-wider font-semibold">{{ __('income') }}</span>
                                <span class="text-lg font-bold text-emerald-400 font-bold truncate">+{{ formatCurrency(report.summary.income, report.wallet.currency) }}</span>
                            </div>
                             <div class="bg-rose-500/5 border border-rose-500/10 p-4 rounded-2xl hover:bg-rose-500/10 transition-colors">
                                <span class="text-xs text-rose-400 block mb-1 uppercase tracking-wider font-semibold">{{ __('expense') }}</span>
                                <span class="text-lg font-bold text-rose-400 font-bold truncate">-{{ formatCurrency(report.summary.expense, report.wallet.currency) }}</span>
                            </div>
                             <div class="bg-indigo-500/10 border border-indigo-500/20 p-4 rounded-2xl relative overflow-hidden hover:bg-indigo-500/20 transition-colors">
                                <div class="absolute inset-0 bg-indigo-500/5"></div>
                                <span class="text-xs text-indigo-300 block mb-1 relative z-10 uppercase tracking-wider font-semibold">{{ __('closing_balance') }}</span>
                                <span class="text-lg font-bold text-indigo-300 relative z-10 truncate">{{ formatCurrency(report.summary.closing_balance, report.wallet.currency) }}</span>
                            </div>
                        </div>
                    </div>
    
                    <!-- Transaction Table (Desktop) -->
                    <div class="glass-card overflow-hidden ring-1 ring-white/5 shadow-xl w-full hidden md:block">
                        <div class="p-5 border-b border-white/5 bg-gray-900/30 flex items-center gap-2">
                            <TrendingUp class="w-4 h-4 text-gray-400" />
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider">{{ __('account_mutation') }}</h3>
                        </div>
                         <div class="w-full overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left min-w-[800px]">
                                <thead>
                                    <tr class="text-gray-400 text-xs uppercase tracking-wider border-b border-white/5 bg-white/[0.02]">
                                        <th class="p-4 font-semibold" style="width: 15%">{{ __('date') }}</th>
                                        <th class="p-4 font-semibold" style="width: 25%">{{ __('description') }}</th>
                                        <th class="p-4 font-semibold" style="width: 15%">{{ __('category') }}</th>
                                        <th class="p-4 font-semibold text-center" style="width: 10%">{{ __('type') }}</th>
                                        <th class="p-4 font-semibold text-right" style="width: 15%">{{ __('amount') }}</th>
                                        <th class="p-4 font-semibold text-right" style="width: 20%">{{ __('balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 text-sm">
                                     <!-- Initial Balance Row -->
                                    <tr class="bg-indigo-500/5 hover:bg-indigo-500/10 transition-colors">
                                        <td class="p-4 text-xs font-bold text-indigo-300 uppercase tracking-wide">{{ __('opening') }}</td>
                                        <td colspan="4" class="p-4 text-xs text-gray-500 italic">{{ __('brought_forward') }}</td>
                                        <td class="p-4 text-right font-bold text-white">{{ formatCurrency(report.summary.opening_balance, report.wallet.currency) }}</td>
                                    </tr>
    
                                    <!-- Transactions -->
                                    <tr v-for="(tx, index) in report.transactions" :key="tx.id" class="group hover:bg-white/5 transition-colors even:bg-white/[0.02]">
                                        <td class="p-4 text-gray-400 whitespace-nowrap text-xs">{{ new Date(tx.date).toLocaleDateString(page.props.locale || 'en', { day: 'numeric', month: 'short', year: 'numeric' }) }}</td>
                                        <td class="p-4 text-white font-medium group-hover:text-indigo-300 transition-colors truncate max-w-[200px]">{{ tx.description }}</td>
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] uppercase font-bold tracking-wide bg-white/5 text-gray-400 border border-white/5 group-hover:border-white/10 transition-all">{{ tx.category }}</span>
                                        </td>
                                         <td class="p-4 text-center">
                                            <div class="flex justify-center">
                                                <div :class="['w-6 h-6 rounded-full flex items-center justify-center', tx.type === 'income' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400']">
                                                    <component :is="tx.type === 'income' ? TrendingUp : TrendingDown" class="w-3 h-3" />
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 text-right font-medium" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">
                                            {{ tx.type === 'income' ? '+' : '-' }} {{ formatCurrency(tx.amount, report.wallet.currency) }}
                                        </td>
                                        <td class="p-4 text-right font-bold text-gray-300 group-hover:text-white transition-colors">
                                            {{ formatCurrency(tx.running_balance, report.wallet.currency) }}
                                        </td>
                                    </tr>
                                    <tr v-if="report.transactions.length === 0">
                                        <td colspan="6" class="py-12 text-center text-gray-500 group relative overflow-hidden">
                                            <div class="flex flex-col items-center justify-center gap-3 relative z-10">
                                                <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-indigo-500/10 transition-all">
                                                    <FileText class="w-5 h-5 opacity-30 group-hover:opacity-100 group-hover:text-indigo-400 transition-all" />
                                                </div>
                                                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('no_activities') }}</h3>
                                                <p class="text-[10px] text-gray-500 mb-2">{{ __('no_activity_desc_report') }}</p>
                                            </div>
                                        </td>
                                    </tr>
    
                                    <!-- Closing Balance Row -->
                                    <tr class="bg-indigo-500/5 border-t border-indigo-500/20 hover:bg-indigo-500/10 transition-colors">
                                        <td class="p-4 text-xs font-bold text-indigo-300 uppercase tracking-wide">{{ __('closing') }}</td>
                                        <td colspan="4" class="p-4 text-xs text-gray-500 italic">{{ __('ending_balance_period') }}</td>
                                        <td class="p-4 text-right font-bold text-white text-base">{{ formatCurrency(report.summary.closing_balance, report.wallet.currency) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Transaction Card View (Mobile) -->
                    <div class="md:hidden space-y-4">
                        <!-- Header -->
                         <div class="flex items-center justify-between mb-2 px-1">
                            <div class="flex items-center gap-2">
                                <TrendingUp class="w-4 h-4 text-gray-400" />
                                <h3 class="text-sm font-bold text-white uppercase tracking-wider">{{ __('account_mutation') }}</h3>
                            </div>
                        </div>

                        <!-- Opening Balance Card -->
                        <div class="glass-card p-4 border-l-4 border-l-indigo-500 flex justify-between items-center bg-indigo-500/5">
                            <div>
                                <p class="text-[10px] text-indigo-300 uppercase font-bold tracking-wider mb-1">{{ __('opening_balance') }}</p>
                                <p class="text-xs text-gray-400 italic">{{ __('brought_forward') }}</p>
                            </div>
                            <p class="text-white font-bold">{{ formatCurrency(report.summary.opening_balance, report.wallet.currency) }}</p>
                        </div>

                        <!-- Transactions List -->
                         <div class="space-y-3">
                            <div v-for="tx in report.transactions" :key="tx.id" class="glass-card p-4 flex flex-col gap-3">
                                <div class="flex justify-between items-start gap-3">
                                    <div class="flex items-start gap-3">
                                        <!-- Date Box -->
                                        <div class="flex flex-col items-center justify-center min-w-[50px] h-12 rounded-xl bg-white/5 border border-white/10 shrink-0">
                                            <span class="text-[9px] font-black text-gray-500 uppercase leading-none mb-0.5">{{ new Date(tx.date).toLocaleDateString(page.props.locale || 'en', { month: 'short' }) }}</span>
                                            <span class="text-lg font-black text-white leading-none">{{ new Date(tx.date).getDate() }}</span>
                                        </div>
                                        
                                        <div>
                                            <h4 class="text-sm font-bold text-white line-clamp-1 break-all">{{ tx.description }}</h4>
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-white/5 text-gray-400 border border-white/5">
                                                {{ tx.category }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right shrink-0">
                                        <p class="font-bold text-sm whitespace-nowrap" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">
                                            {{ tx.type === 'income' ? '+' : '-' }} {{ formatCurrency(tx.amount, report.wallet.currency) }}
                                        </p>
                                        <div class="flex items-center justify-end gap-1 mt-1">
                                            <span class="text-[9px] text-gray-500 uppercase">{{ __('bal') }}</span>
                                            <p class="text-xs font-medium text-gray-400">{{ formatCurrency(tx.running_balance, report.wallet.currency) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State Mobile -->
                             <div v-if="report.transactions.length === 0" class="glass-card p-8 text-center flex flex-col items-center justify-center">
                                <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center mb-3">
                                    <FileText class="w-5 h-5 opacity-30 text-gray-400" />
                                </div>
                                <p class="text-xs text-gray-500">{{ __('no_activities') }}</p>
                             </div>
                         </div>

                        <!-- Closing Balance Card -->
                        <div class="glass-card p-4 border-l-4 border-l-indigo-500 flex justify-between items-center bg-indigo-500/5">
                            <div>
                                <p class="text-[10px] text-indigo-300 uppercase font-bold tracking-wider mb-1">{{ __('closing_balance') }}</p>
                                <p class="text-xs text-gray-400 italic">{{ __('ending_balance_period') }}</p>
                            </div>
                            <p class="text-white font-bold">{{ formatCurrency(report.summary.closing_balance, report.wallet.currency) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-24 text-center glass-card border-dashed border-white/10 group hover:border-indigo-500/30 transition-all mb-10">
                <div class="p-5 rounded-full bg-white/5 mb-6 group-hover:bg-indigo-500/10 transition-colors">
                    <FileText class="w-12 h-12 text-gray-600 group-hover:text-indigo-400 transition-colors" />
                </div>
                <h3 class="text-lg font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('no_reports_generated') }}</h3>
                <p class="text-sm text-gray-500 mb-8 max-w-md mx-auto leading-relaxed">{{ __('no_reports_desc') }}</p>
                <div @click="router.visit('/wallets')" class="px-6 py-3 bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white rounded-xl text-sm font-bold transition-all flex items-center gap-3 shadow-lg hover:shadow-indigo-500/30 cursor-pointer">
                    <WalletIcon class="w-5 h-5" />
                    <span>{{ __('create_wallet_tracking') }}</span>
                </div>
            </div>
        </Layout>
    </template>

<style scoped>
.glass-card {
    background: rgba(30, 41, 59, 0.4);
    backdrop-filter: blur(24px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}
</style>
