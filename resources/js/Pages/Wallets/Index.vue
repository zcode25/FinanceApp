<script setup>
import Layout from '../../Shared/Layout.vue';
import { ref, computed } from 'vue';
import { Head, usePage, useForm, router } from '@inertiajs/vue3';
import { Plus, Banknote, CreditCard, Wallet as WalletIcon, Power, Pencil, X } from 'lucide-vue-next';
import SearchableSelect from '../../Shared/SearchableSelect.vue';
import CurrencyInput from '../../Shared/CurrencyInput.vue';

    const page = usePage();
    const __ = (key, replacements = {}) => {
        let translation = page.props.translations?.[key] || key;
        Object.keys(replacements).forEach(r => {
            translation = translation.replace(`:${r}`, replacements[r]);
        });
        return translation;
    };

    const props = defineProps({
        wallets: Array,
        currentExchangeRate: Number
    });
    
    const showModal = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    
    const form = useForm({
        name: '',
        type: 'cash',
        currency: 'IDR',
        balance: '',
        account_number: '',
        bank_name: '',
        is_active: true
    });
    
    const typeOptions = computed(() => [
        { label: __('cash'), value: 'cash' },
        { label: __('bank_account'), value: 'bank' },
        { label: __('ewallet'), value: 'ewallet' }
    ]);
    
    const currencyOptions = [
        { label: 'IDR (Rupiah)', value: 'IDR' },
        { label: 'USD (Dollar)', value: 'USD' }
    ];
    
    const getIDREquivalent = (wallet) => {
        if (wallet.currency === 'USD' && props.currentExchangeRate) {
            const idrAmount = wallet.balance * props.currentExchangeRate;
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(idrAmount);
        }
        return null;
    };
    
    const openCreateModal = () => {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.currency = 'IDR'; // Default
        form.type = 'cash'; // Default
        showModal.value = true;
    };
    
    const openEditModal = (wallet) => {
        isEditing.value = true;
        editingId.value = wallet.id;
        form.name = wallet.name;
        form.type = wallet.type;
        form.currency = wallet.currency;
        form.balance = wallet.balance;
        form.account_number = wallet.account_number;
        form.bank_name = wallet.bank_name;
        form.is_active = !!wallet.is_active;
        showModal.value = true;
    };
    
    const submit = () => {
        if (isEditing.value) {
            form.put(`/wallets/${editingId.value}`, {
                onSuccess: () => {
                    form.reset();
                    showModal.value = false;
                    isEditing.value = false;
                },
            });
        } else {
            form.post('/wallets', {
                onSuccess: () => {
                    form.reset();
                    showModal.value = false;
                },
            });
        }
    };
    
    const exchangeRate = ref(16250); // Fallback default
    const isRateLoaded = ref(false);
    
    const fetchExchangeRate = async () => {
        try {
            const response = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
            const data = await response.json();
            if (data.rates && data.rates.IDR) {
                exchangeRate.value = data.rates.IDR;
                isRateLoaded.value = true;
            }
        } catch (error) {
            console.error('Failed to fetch exchange rate:', error);
            // Keep default fallback
        }
    };
    
    fetchExchangeRate();
    
    const formatCurrency = (amount, currency) => {
        return new Intl.NumberFormat(currency === 'IDR' ? 'id-ID' : 'en-US', { 
            style: 'currency', 
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    };
    
    const getEstimatedIDR = (usdAmount) => {
        if (!props.currentExchangeRate) return __('rate_unavailable');
        return formatCurrency(usdAmount * props.currentExchangeRate, 'IDR');
    };
    
    const toggleStatus = (wallet) => {
        Swal.fire({
            title: wallet.is_active ? __('deactivate_wallet') : __('activate_wallet'),
            text: __('toggle_wallet_text', { status: wallet.is_active ? __('deactivate') : __('activate') }),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6366f1', // Indigo 500
            cancelButtonColor: '#9ca3af', // Gray 400
            confirmButtonText: __('yes_do_it'),
            cancelButtonText: __('cancel'),
            background: '#1f2937', // Gray 800
            color: '#ffffff'
        }).then((result) => {
            if (result.isConfirmed) {
                router.patch(`/wallets/${wallet.id}/toggle`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire({
                            title: __('success'),
                            text: __('wallet_status_updated'),
                            icon: 'success',
                            confirmButtonColor: '#6366f1',
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    }
                });
            }
        });
    };
    
    const totalBalanceIDR = computed(() => {
        return props.wallets.reduce((total, wallet) => {
            let amount = parseFloat(wallet.balance) || 0;
            if (wallet.currency === 'USD' && props.currentExchangeRate) {
                amount = amount * props.currentExchangeRate;
            }
            return total + amount;
        }, 0);
    });
    
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
        <Head :title="__('wallets')" />
        <Layout>
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ __('wallets') }}</h1>
                    <p class="text-gray-400">{{ __('manage_wallets_desc') }}</p>
                </div>
                <button @click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold transition-all shadow-lg shadow-indigo-500/20 w-fit">
                    <Plus class="w-5 h-5" />
                    <span>{{ __('add_new_wallet') }}</span>
                </button>
            </header>
    
            <!-- Total Balance Card -->
            <div class="glass-card p-6 md:p-8 mb-10 relative overflow-hidden group border-indigo-500/30 shadow-indigo-500/10">
                    <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl -mr-20 -mt-20 transition-all group-hover:bg-indigo-500/20"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex flex-col md:flex-row items-center gap-4 text-center md:text-left">
                        <div class="p-4 rounded-xl bg-indigo-500/20 text-indigo-400">
                                <Banknote class="w-8 h-8" />
                        </div>
                        <div>
                            <span class="text-xs md:text-sm font-bold uppercase tracking-wider text-gray-400 block mb-1">{{ __('total_net_worth') }}</span>
                            <h3 class="text-3xl md:text-4xl font-bold text-white">{{ formatCurrency(totalBalanceIDR, 'IDR') }}</h3>
                        </div>
                    </div>
                    <div class="text-center md:text-right">
                        <p class="text-xs md:text-sm text-indigo-300">{{ __('aggregated_balance') }}</p>
                    </div>
                    </div>
            </div>
    
            <div v-if="wallets.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <div v-for="wallet in wallets" :key="wallet.id" class="glass-card p-6 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300" :class="[getTypeColor(wallet.type).border, {'opacity-60 grayscale': !wallet.is_active}]">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-white/5 rounded-full blur-2xl -mr-16 -mt-16 transition-all group-hover:bg-white/10"></div>
                    
                    <div class="flex justify-between items-start mb-4">
                        <div :class="[getTypeColor(wallet.type).bg, getTypeColor(wallet.type).text, 'p-3 rounded-xl shadow-lg']">
                            <Banknote v-if="wallet.type === 'cash'" class="w-6 h-6" />
                            <CreditCard v-if="wallet.type === 'bank'" class="w-6 h-6" />
                            <WalletIcon v-if="wallet.type === 'ewallet'" class="w-6 h-6" />
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Status Badge -->
                            <span v-if="!wallet.is_active" class="text-xs font-bold uppercase tracking-wider text-rose-500 bg-rose-500/10 px-2 py-1 rounded">{{ __('inactive') }}</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 bg-gray-800/50 px-2 py-1 rounded">{{ wallet.currency }}</span>
                            
                            <!-- Toggle Status Button -->
                            <button 
                                @click="toggleStatus(wallet)" 
                                class="p-1 transition-colors z-20 relative rounded-full hover:bg-white/10"
                                :class="wallet.is_active ? 'text-gray-400 hover:text-rose-400' : 'text-gray-500 hover:text-emerald-400'"
                                title="Toggle Status"
                            >
                                <Power class="w-4 h-4" />
                            </button>
    
                            <!-- Edit Button -->
                            <button @click="openEditModal(wallet)" class="p-1 text-gray-400 hover:text-white transition-colors z-20 relative">
                                <Pencil class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    
                    <div class="relative z-10">
                        <h3 class="text-lg font-medium text-gray-300 mb-1">{{ wallet.name }}</h3>
                        
                        <div v-if="wallet.account_number" class="text-sm text-gray-400 mb-3 flex items-center gap-2">
                            <span class="font-mono tracking-wider opacity-80">{{ wallet.account_number }}</span>
                        </div>
    
                        <p class="text-2xl font-bold text-white">{{ formatCurrency(wallet.balance, wallet.currency) }}</p>
                        <p v-if="wallet.currency === 'USD'" class="text-sm text-emerald-400 mt-1 font-medium bg-emerald-500/10 inline-block px-2 py-1 rounded">
                            {{ __('est_prefix') }} {{ getEstimatedIDR(wallet.balance) }}
                        </p>
                    </div>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-24 text-center glass-card border-dashed border-white/10 group hover:border-indigo-500/30 transition-all mb-10">
                <div class="p-5 rounded-full bg-white/5 mb-6 group-hover:bg-indigo-500/10 transition-colors">
                    <WalletIcon class="w-12 h-12 text-gray-600 group-hover:text-indigo-400 transition-colors" />
                </div>
                <h3 class="text-lg font-black text-gray-400 uppercase tracking-widest mb-2">{{ __('no_wallets_tracked') }}</h3>
                <p class="text-sm text-gray-500 mb-8 max-w-md mx-auto leading-relaxed">{{ __('no_assets_desc') }}</p>
                <button @click="openCreateModal" class="w-full md:w-auto px-6 py-3 bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-3 shadow-lg hover:shadow-indigo-500/30">
                    <Plus class="w-5 h-5" />
                    <span>{{ __('create_first_wallet') }}</span>
                </button>
            </div>
    
            <!-- Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div @click="showModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
    
                <!-- Modal Content -->
                <div class="glass-card w-full max-w-2xl relative z-10 animate-in fade-in zoom-in-95 duration-200 overflow-y-auto max-h-[90vh]">
                    <div class="p-6 border-b border-white/5 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <component :is="isEditing ? Pencil : Plus" class="w-5 h-5 text-indigo-400" /> 
                            {{ isEditing ? __('edit_wallet') : __('add_new_wallet') }}
                        </h2>
                        <button @click="showModal = false" class="text-gray-400 hover:text-white transition-colors">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
                
                <div class="p-6">
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-400 mb-1">{{ __('wallet_name') }}</label>
                            <input v-model="form.name" type="text" class="w-full input-premium px-4 py-3" :placeholder="__('wallet_name_placeholder')" required>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">{{ __('type') }}</label>
                             <SearchableSelect 
                                v-model="form.type" 
                                :options="typeOptions" 
                                :placeholder="__('select_type')" 
                            />
                        </div>
                        
                        <div v-if="!isEditing">
                            <label class="block text-sm text-gray-400 mb-1">{{ __('currency') }}</label>
                            <SearchableSelect 
                                v-model="form.currency" 
                                :options="currencyOptions" 
                                :placeholder="__('select_currency')" 
                            />
                        </div>

                        <div v-if="form.type !== 'cash'" class="md:col-span-1">
                            <label class="block text-sm text-gray-400 mb-1">{{ __('bank_name') }}</label>
                            <input v-model="form.bank_name" type="text" class="w-full input-premium px-4 py-3" :placeholder="__('bank_name_placeholder')">
                        </div>

                        <div v-if="form.type !== 'cash'" class="md:col-span-1">
                            <label class="block text-sm text-gray-400 mb-1">{{ __('account_number') }}</label>
                            <input v-model="form.account_number" type="text" class="w-full input-premium px-4 py-3" :placeholder="__('account_number_placeholder')">
                        </div>

                        <div v-if="!isEditing" class="md:col-span-2">
                            <label class="block text-sm text-gray-400 mb-1">{{ __('initial_balance') }}</label>
                            <CurrencyInput 
                                v-model="form.balance" 
                                :currency="form.currency" 
                                :placeholder="form.currency === 'IDR' ? 'Rp 100.000' : '$ 10'"
                            />
                        </div>

                        <div class="md:col-span-2 pt-4">
                            <button type="submit" class="w-full py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-500/20 transition-all">
                                {{ isEditing ? __('update_wallet') : __('create_wallet') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </Layout>
</template>
