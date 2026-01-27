<script setup>
import Layout from '../../Shared/Layout.vue';
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { Plus, X, Search, Filter, Pencil, Trash2, TrendingUp, TrendingDown, Scale } from 'lucide-vue-next';
import CurrencyInput from '../../Shared/CurrencyInput.vue';
import CategoryCombobox from '../../Shared/CategoryCombobox.vue';
import SearchableSelect from '../../Shared/SearchableSelect.vue';
import Pagination from '../../Shared/Pagination.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    transactions: Object,
    categories: Array,
    wallets: Array,
    filters: Object,
    currentExchangeRate: Number,
    summary: Object
});

const showModal = ref(false);
const isEditing = ref(false);
const selectedTransactionId = ref(null);
const search = ref(props.filters.search || '');

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;

    // Search Logic
    watch(search, debounce((value) => {
        router.get('/transactions', { 
            search: value,
            wallet_id: filters.value.wallet_id,
            type: filters.value.type,
            start_date: filters.value.start_date,
            end_date: filters.value.end_date
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }, 300));
    
    // Filter Logic
    const showFilters = ref(false);
    const filters = ref({
        wallet_id: props.filters.wallet_id || '',
        type: props.filters.type || '',
        start_date: props.filters.start_date || '',
        end_date: props.filters.end_date || ''
    });
    
    // Watch filters to trigger reload
    watch(filters, debounce((value) => {
        router.get('/transactions', { 
            search: search.value,
            ...value
        }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        });
    }, 300), { deep: true });
    
    const resetFilters = () => {
        filters.value = {
            wallet_id: '',
            type: '',
            start_date: '',
            end_date: ''
        };
    };
    
    const form = useForm({
        amount: '',
        category: '',
        type: 'expense',
        date: new Date().toISOString().substr(0, 10),
        description: '',
        wallet_id: '',
        exchange_rate: ''
    });
    
    const openAddModal = () => {
        isEditing.value = false;
        selectedTransactionId.value = null;
        form.reset();
        form.date = new Date().toISOString().substr(0, 10);
        form.type = 'expense';
        showModal.value = true;
    };
    
    const openEditModal = (transaction) => {
        isEditing.value = true;
        selectedTransactionId.value = transaction.id;
        form.amount = transaction.amount;
        // Handle object or string (backwards compatibility or if not eager loaded)
        form.category = transaction.category && typeof transaction.category === 'object' 
            ? transaction.category.name 
            : transaction.category;
        form.type = transaction.type;
        form.date = transaction.date;
        form.description = transaction.description || '';
        form.wallet_id = transaction.wallet_id;
        showModal.value = true;
    };
    
    const walletOptions = computed(() => {
        return props.wallets.map(wallet => ({
            value: wallet.id,
            label: wallet.name
        }));
    });
    
    const selectedCurrency = computed(() => {
        if (!form.wallet_id) return 'IDR';
        const wallet = props.wallets.find(w => w.id === form.wallet_id);
        return wallet ? wallet.currency : 'IDR';
    });
    
    const showExchangeRate = computed(() => {
        return selectedCurrency.value !== 'IDR';
    });
    
    const conversionPreview = computed(() => {
        if (!showExchangeRate.value || !form.amount) return '';
        const rate = form.exchange_rate || props.currentExchangeRate || 0;
        const idrAmount = form.amount * rate;
        return `≈ ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(idrAmount)}`;
    });
    
    // Watch wallet change to auto-populate exchange rate
    watch(() => form.wallet_id, (newWalletId) => {
        if (newWalletId) {
            const wallet = props.wallets.find(w => w.id === newWalletId);
            if (wallet && wallet.currency !== 'IDR') {
                form.exchange_rate = props.currentExchangeRate || '';
            } else {
                form.exchange_rate = '';
            }
        }
    });
    
    const getTypeColor = (type) => {
        switch (type) {
            case 'cash':
                return 'bg-emerald-500';
            case 'ewallet':
                return 'bg-purple-500';
            case 'bank':
                return 'bg-orange-500';
            default:
                return 'bg-indigo-500';
        }
    };
    
    const deleteTransaction = (transaction) => {
        Swal.fire({
            title: __('delete_confirm_title'),
            text: __('delete_confirm_text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#374151',
            confirmButtonText: __('yes_delete'),
            cancelButtonText: __('cancel'),
            background: '#1f2937',
            color: '#ffffff'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(`/transactions/${transaction.id}`, {
                    onSuccess: () => {
                        Swal.fire({
                            title: __('deleted_title'),
                            text: __('deleted_text'),
                            icon: 'success',
                            background: '#1f2937',
                            color: '#ffffff',
                            confirmButtonColor: '#6366f1'
                        });
                    }
                });
            }
        });
    };
    
    const formatIDR = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };
    
    const submit = () => {
        if (isEditing.value) {
            form.put(`/transactions/${selectedTransactionId.value}`, {
                onSuccess: () => {
                    form.reset();
                    showModal.value = false;
                },
            });
        } else {
            form.post('/transactions', {
                onSuccess: () => {
                    form.reset();
                    showModal.value = false;
                },
            });
        }
    };
    </script>
    
    <template>
        <Head :title="__('transactions')" />
        <Layout>
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                     <h1 class="text-3xl font-bold text-white">{{ __('transactions') }}</h1>
                     <p class="text-gray-400">{{ __('manage_transactions_desc') }}</p>
                </div>
                <button @click="openAddModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold transition-all shadow-lg shadow-indigo-500/20 w-fit">
                    <Plus class="w-5 h-5" />
                    <span>{{ __('add_transaction') }}</span>
                </button>
            </header>
    
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Income -->
                <div class="glass-card p-6 border-emerald-500/20 shadow-emerald-500/5 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl -mr-8 -mt-8 transition-all group-hover:bg-emerald-500/20"></div>
                    <div class="flex items-start justify-between relative z-10">
                        <div>
                            <p class="text-sm font-medium text-gray-400 mb-1">{{ __('total_income') }}</p>
                            <h3 class="text-2xl font-bold text-emerald-400">{{ formatIDR(summary.total_income) }}</h3>
                        </div>
                        <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                    </div>
                </div>
    
                <!-- Total Expense -->
                <div class="glass-card p-6 border-rose-500/20 shadow-rose-500/5 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl -mr-8 -mt-8 transition-all group-hover:bg-rose-500/20"></div>
                    <div class="flex items-start justify-between relative z-10">
                        <div>
                            <p class="text-sm font-medium text-gray-400 mb-1">{{ __('total_expense') }}</p>
                            <h3 class="text-2xl font-bold text-rose-500">{{ formatIDR(summary.total_expense) }}</h3>
                        </div>
                        <div class="p-3 rounded-xl bg-rose-500/10 text-rose-500">
                            <TrendingDown class="w-6 h-6" />
                        </div>
                    </div>
                </div>
    
                <!-- Net Balance -->
                <div class="glass-card p-6 border-indigo-500/20 shadow-indigo-500/5 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl -mr-8 -mt-8 transition-all group-hover:bg-indigo-500/20"></div>
                    <div class="flex items-start justify-between relative z-10">
                        <div>
                            <p class="text-sm font-medium text-gray-400 mb-1">{{ __('net_balance') }}</p>
                            <h3 class="text-2xl font-bold text-white shadow-indigo-500/20">{{ formatIDR(summary.net_balance) }}</h3>
                        </div>
                        <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-400">
                            <Scale class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Filters & Search -->
            <div class="mb-6 space-y-4">
                <div class="flex gap-4">
                    <div class="relative flex-1 max-w-md">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" />
                        <input 
                            v-model="search"
                            type="text" 
                            :placeholder="__('search_transactions')" 
                            class="w-full bg-gray-900/50 border border-white/5 rounded-xl pl-10 pr-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all font-medium"
                        >
                    </div>
                    <button 
                        @click="showFilters = !showFilters"
                        class="px-4 py-3 bg-gray-900/50 border border-white/5 rounded-xl text-gray-400 hover:text-white hover:bg-white/5 transition-all flex items-center gap-2"
                        :class="{ 'bg-indigo-500/10 text-indigo-400 border-indigo-500/50': showFilters }"
                    >
                        <Filter class="w-5 h-5" />
                        <span class="hidden sm:inline">{{ __('filters') }}</span>
                    </button>
                </div>
    
                <!-- Expandable Filter Panel -->
                <div v-if="showFilters" class="glass-card p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-in slide-in-from-top-2 duration-200">
                    <!-- Wallet Filter -->
                    <div>
                        <label class="block text-xs text-gray-400 mb-1 ml-1">{{ __('wallet') }}</label>
                        <select v-model="filters.wallet_id" class="w-full bg-gray-800 border-white/10 rounded-lg text-white text-sm py-2 px-3 focus:ring-1 focus:ring-indigo-500 appearance-none">
                            <option value="">{{ __('all_wallets') }}</option>
                            <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">{{ wallet.name }}</option>
                        </select>
                    </div>
    
                    <!-- Type Filter -->
                    <div>
                        <label class="block text-xs text-gray-400 mb-1 ml-1">{{ __('type') }}</label>
                        <div class="flex p-1 bg-gray-800 rounded-lg">
                            <button @click="filters.type = ''" class="flex-1 py-1.5 text-xs rounded font-medium transition-colors" :class="filters.type === '' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white'">{{ __('all') }}</button>
                            <button @click="filters.type = 'income'" class="flex-1 py-1.5 text-xs rounded font-medium transition-colors" :class="filters.type === 'income' ? 'bg-emerald-500/20 text-emerald-400' : 'text-gray-400 hover:text-white'">{{ __('income') }}</button>
                            <button @click="filters.type = 'expense'" class="flex-1 py-1.5 text-xs rounded font-medium transition-colors" :class="filters.type === 'expense' ? 'bg-rose-500/20 text-rose-400' : 'text-gray-400 hover:text-white'">{{ __('expense') }}</button>
                        </div>
                    </div>
    
                    <!-- Date Range -->
                    <div>
                        <label class="block text-xs text-gray-400 mb-1 ml-1">{{ __('start_date') }}</label>
                        <input v-model="filters.start_date" type="date" class="w-full bg-gray-800 border-white/10 rounded-lg text-white text-sm py-2 px-3 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1 ml-1">{{ __('end_date') }}</label>
                        <input v-model="filters.end_date" type="date" class="w-full bg-gray-800 border-white/10 rounded-lg text-white text-sm py-2 px-3 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    
                     <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                        <button @click="resetFilters" class="text-xs text-gray-400 hover:text-white underline">
                            {{ __('reset_filters') }}
                        </button>
                    </div>
                </div>
            </div>
    
            <!-- Refined Transaction Ledger (3-Column Card Layout) -->
            <div class="mt-8">
                <div v-if="transactions.data.length > 0">
                    <!-- Transactions List with Spacing -->
                    <div class="space-y-6 mb-10">
                        <div v-for="tx in transactions.data" :key="tx.id" 
                             class="group relative flex flex-col md:flex-row md:items-center gap-4 p-4 bg-gray-800/20 hover:bg-gray-800/40 border border-white/5 rounded-2xl transition-all duration-300 hover:scale-[1.005] hover:shadow-2xl hover:shadow-indigo-500/5">
                            
                            <!-- COLUMN 1: Date & Icon (Identity) -->
                            <div class="flex items-center gap-4 md:w-48 shrink-0">
                                <!-- Modern Full Date Block -->
                                <div class="flex flex-col items-center justify-center min-w-[76px] h-14 rounded-2xl bg-white/5 border border-white/10 group-hover:border-indigo-500/30 transition-colors shrink-0 px-2">
                                    <span class="text-[9px] uppercase font-black tracking-widest text-gray-500 leading-none mb-1 opacity-70">{{ new Date(tx.date).getFullYear() }}</span>
                                    <span class="text-sm font-black text-white leading-none tabular-nums whitespace-nowrap">
                                        {{ new Date(tx.date).getDate() }} {{ new Date(tx.date).toLocaleDateString('id-ID', { month: 'short' }) }}
                                    </span>
                                </div>
                                
                                <!-- Category Icon with Wallet Indicator -->
                                <div class="relative shrink-0">
                                    <div class="p-3 rounded-2xl shadow-inner transition-transform duration-500 group-hover:scale-110" 
                                         :class="tx.type === 'income' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'">
                                        <TrendingUp v-if="tx.type === 'income'" class="w-5 h-5" />
                                        <TrendingDown v-else class="w-5 h-5" />
                                    </div>
                                    <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-gray-900 shadow-lg group-hover:scale-125 transition-transform duration-300" 
                                         :class="tx.wallet ? getTypeColor(tx.wallet.type) : 'bg-gray-700'"
                                         :title="tx.wallet ? tx.wallet.name : ''"></div>
                                </div>
                            </div>
    
                            <!-- COLUMN 2: Narrative (Center - Flex Fill) -->
                            <div class="flex-1 min-w-0 flex flex-col justify-center">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="font-bold text-lg text-white truncate group-hover:text-indigo-300 transition-colors tracking-tight">
                                        {{ tx.description || (tx.category ? tx.category.name : 'Unknown') }}
                                    </h4>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        {{ tx.category ? tx.category.name : 'Unknown' }}
                                    </span>
                                    <span class="text-xs text-gray-500 font-medium tracking-tight">
                                        {{ tx.wallet ? tx.wallet.name : __('unknown_wallet') }}
                                    </span>
                                </div>
                            </div>
    
                            <!-- COLUMN 3: Financials & Actions (Right) -->
                            <div class="flex items-center justify-between md:justify-end gap-6 md:w-64 shrink-0 border-t md:border-t-0 border-white/5 pt-3 md:pt-0 mt-3 md:mt-0">
                                <div class="flex flex-col items-end">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl font-black tracking-tight flex items-baseline gap-1" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">
                                            <span class="text-xs opacity-50">{{ tx.type === 'income' ? '+' : '-' }}</span>
                                            {{ new Intl.NumberFormat(tx.currency === 'USD' ? 'en-US' : 'id-ID', { style: 'currency', currency: tx.currency || 'IDR', maximumFractionDigits: 0 }).format(tx.amount) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span v-if="tx.currency === 'USD' && tx.amount_in_base_currency" class="text-[10px] text-gray-500 font-bold tracking-tight">
                                            ≈ {{ formatIDR(tx.amount_in_base_currency) }}
                                        </span>
                                        <!-- <span class="text-[10px] text-gray-600 font-medium tabular-nums uppercase">{{ new Date(tx.created_at || tx.date).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span> -->
                                    </div>
                                </div>
    
                                <!-- Actions -->
                                <div class="flex items-center gap-1">
                                    <button @click="openEditModal(tx)" 
                                            class="p-2.5 text-gray-500 hover:text-white hover:bg-white/10 rounded-2xl transition-all">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="deleteTransaction(tx)" 
                                            class="p-2.5 text-gray-500 hover:text-rose-500 hover:bg-rose-500/10 rounded-2xl transition-all">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
    
                    <!-- Pagination -->
                    <div class="p-6 border-t border-white/5">
                        <Pagination :links="transactions.links" />
                    </div>
                </div>
    
                 <div v-else class="flex flex-col items-center justify-center py-24 text-center glass-card border-dashed border-white/10 group hover:border-indigo-500/30 transition-all">
                     <div class="p-5 rounded-full bg-white/5 mb-6 group-hover:bg-indigo-500/10 transition-colors">
                         <component :is="search ? Search : TrendingUp" class="w-12 h-12 text-gray-600 group-hover:text-indigo-400 transition-colors" />
                     </div>
                     <h3 class="text-lg font-black text-gray-400 uppercase tracking-widest mb-2">{{ search ? __('no_results') : __('no_activity') }}</h3>
                     <p class="text-sm text-gray-500 mb-8 max-w-md mx-auto leading-relaxed">
                         {{ search ? __('no_results_desc') : __('no_activity_desc') }}
                     </p>
                     <button v-if="!search" @click="openAddModal" class="px-6 py-3 bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white rounded-xl text-sm font-bold transition-all flex items-center gap-3 shadow-lg hover:shadow-indigo-500/30">
                         <Plus class="w-5 h-5" />
                         <span>{{ __('record_transaction') }}</span>
                     </button>
                     <button v-else @click="search = ''; resetFilters()" class="px-6 py-3 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-xl text-sm font-bold transition-all flex items-center gap-3">
                         <X class="w-5 h-5" />
                         <span>{{ __('clear_search') }}</span>
                     </button>
                 </div>
            </div>

        <!-- Add/Edit Transaction Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="showModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
            
            <div class="glass-card w-full max-w-lg relative z-10 animate-in fade-in zoom-in-95 duration-200">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        {{ isEditing ? __('edit_transaction') : __('add_transaction') }}
                    </h2>
                    <button @click="showModal = false" class="text-gray-400 hover:text-white transition-colors">
                        <X class="w-6 h-6" />
                    </button>
                </div>
                
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Type Selection -->
                         <div class="grid grid-cols-2 gap-4 p-1 bg-gray-800/50 rounded-xl border border-white/5">
                            <button type="button" @click="form.type = 'income'" :class="form.type === 'income' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'text-gray-400 hover:text-white'" class="py-2.5 rounded-lg transition-all font-bold text-sm">
                                {{ __('income') }}
                            </button>
                            <button type="button" @click="form.type = 'expense'" :class="form.type === 'expense' ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/20' : 'text-gray-400 hover:text-white'" class="py-2.5 rounded-lg transition-all font-bold text-sm">
                                {{ __('expense') }}
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">{{ __('wallet') }}</label>
                            <SearchableSelect 
                                v-model="form.wallet_id" 
                                :options="walletOptions" 
                                :placeholder="__('select_wallet')" 
                            />
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">{{ __('amount') }}</label>
                            <CurrencyInput v-model="form.amount" :currency="selectedCurrency" :placeholder="selectedCurrency === 'USD' ? '$ 0' : 'Rp 0'" />
                        </div>

                        <!-- Exchange Rate (only for non-IDR wallets) -->
                        <div v-if="showExchangeRate" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm text-gray-400">{{ __('exchange_rate') }} ({{ selectedCurrency }} to IDR)</label>
                                <span class="text-xs text-indigo-400">{{ __('auto_updated') }}</span>
                            </div>
                            <input 
                                v-model="form.exchange_rate" 
                                type="number" 
                                step="0.01"
                                class="w-full input-premium px-4 py-3 font-mono" 
                                :placeholder="`1 ${selectedCurrency} = ... IDR`"
                            >
                            <div v-if="conversionPreview" class="flex items-center gap-2 text-sm">
                                <span class="text-gray-500">{{ __('conversion') }}:</span>
                                <span class="text-emerald-400 font-semibold">{{ conversionPreview }}</span>
                            </div>
                        </div>

                        <div>
                             <label class="block text-sm text-gray-400 mb-1">{{ __('category') }}</label>
                             <CategoryCombobox 
                                v-model="form.category" 
                                :categories="categories" 
                                :type="form.type" 
                                :placeholder="__('select_or_type_category')"
                            />
                        </div>

                        <div>
                             <label class="block text-sm text-gray-400 mb-1">{{ __('description') }}</label>
                            <textarea v-model="form.description" class="w-full input-premium px-4 py-3 min-h-[80px]" :placeholder="__('details_placeholder')"></textarea>
                        </div>

                         <div>
                             <label class="block text-sm text-gray-400 mb-1">{{ __('date') }}</label>
                            <input v-model="form.date" type="date" class="w-full input-premium px-4 py-3">
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-500/20 transition-all">
                                {{ isEditing ? __('update_transaction') : __('save_transaction') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Layout>
</template>
