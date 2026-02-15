<script setup>
import Layout from '../../Shared/Layout.vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, usePage, useForm, router } from '@inertiajs/vue3';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";
import { Plus, Banknote, CreditCard, Wallet as WalletIcon, Power, Pencil, X, Scale, GripVertical } from 'lucide-vue-next';
import SearchableSelect from '../../Shared/SearchableSelect.vue';
import CurrencyInput from '../../Shared/CurrencyInput.vue';
import Swal from 'sweetalert2';
import PremiumUpsellModal from '@/Shared/PremiumUpsellModal.vue';
import draggable from 'vuedraggable';
import { route } from 'ziggy-js';

const driverObj = ref(null);
const skipHTML = `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">Skip Tutorial</button>
</div>`;
import UpgradeModal from '../../Shared/UpgradeModal.vue';

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
    
    const localWallets = ref([...props.wallets]);

    watch(() => props.wallets, (newWallets) => {
        localWallets.value = [...newWallets];
    }, { deep: true });

    const onDragEnd = () => {
        const orderMap = localWallets.value.map((wallet, index) => ({
            id: wallet.id,
            sort_order: index
        }));
        


        router.post(route('wallets.reorder'), { wallets: orderMap }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
                Toast.fire({
                    icon: 'success',
                    title: __('order_updated'),
                    background: '#ffffff',
                    color: '#1e293b',
                    customClass: {
                        popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                        title: '!text-sm !font-bold !text-slate-900',
                    }
                });
            },
            onError: (errors) => {
                console.error('Reorder Failed:', errors);
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'error',
                    title: __('failed_update_order'),
                    text: Object.values(errors).flat().join(', '),
                    background: '#ffffff',
                    color: '#be123c', // rose-700
                    customClass: {
                        popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-rose-100',
                        title: '!text-sm !font-bold !text-rose-900',
                    }
                });
            }
        });
    };
    
    const showModal = ref(false);
    const showUpgradeModal = ref(false);
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
        // Proactive Limit Check for Starter Plan
        if (!page.props.auth.user.is_premium && props.wallets.length >= 3) {
            showUpgradeModal.value = true;
            return;
        }

        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.currency = 'IDR'; // Default
        form.type = 'cash'; // Default
        showModal.value = true;
    };
    
    const openEditModal = (wallet) => {
        // If coming from detail modal, close it first
        if (showDetailModal.value) {
            showDetailModal.value = false;
        }
        
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

    // Detail Modal Logic
    const showDetailModal = ref(false);
    const selectedDetailWallet = ref(null);

    const openDetailModal = (wallet) => {
        // Only open on mobile/tablet (check screen width or just rely on CSS hiding desktop actions)
        // Actually, let's allow it everywhere but it's primarily for mobile
        selectedDetailWallet.value = wallet;
        showDetailModal.value = true;
    };
    
    const submit = () => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        if (isEditing.value) {
            form.put(`/wallets/${editingId.value}`, {
                onSuccess: () => {
                    form.reset();
                    showModal.value = false;
                    isEditing.value = false;
                    
                    Toast.fire({
                        icon: 'success',
                        title: __('successfully_updated'),
                        background: '#ffffff',
                        color: '#1e293b',
                        customClass: {
                            popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                            title: '!text-sm !font-bold !text-slate-900',
                        }
                    });
                },
            });
        } else {
            form.post('/wallets', {
                onSuccess: () => {
                    form.reset();
                    showModal.value = false;
                    
                    Toast.fire({
                        icon: 'success',
                        title: __('successfully_saved'),
                        background: '#ffffff',
                        color: '#1e293b',
                        customClass: {
                            popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                            title: '!text-sm !font-bold !text-slate-900',
                        }
                    });
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
            text: wallet.is_active ? __('deactivate_confirm_text') : __('activate_confirm_text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: wallet.is_active ? __('yes_deactivate') : __('yes_activate'),
            cancelButtonText: __('cancel'),
            customClass: {
                popup: '!rounded-[2rem] !p-10 !bg-white !shadow-2xl !border !border-slate-100 !font-sans !antialiased',
                title: '!text-xl !font-bold !text-slate-900 !pt-4 !pb-2 !px-0 !m-0 !leading-tight',
                htmlContainer: '!text-sm !font-semibold !text-slate-500 !leading-relaxed !pb-6 !px-0 !m-0',
                actions: '!flex !items-center !justify-center !gap-3 !mt-4 !w-full !px-0',
                confirmButton: wallet.is_active 
                    ? '!inline-flex !items-center !justify-center !bg-rose-600 !text-white !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm hover:!shadow-rose-600/20 hover:!bg-rose-700 active:!scale-95 !border-none !outline-none !m-0 !cursor-pointer'
                    : '!inline-flex !items-center !justify-center !bg-emerald-600 !text-white !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm hover:!shadow-emerald-600/20 hover:!bg-emerald-700 active:!scale-95 !border-none !outline-none !m-0 !cursor-pointer',
                cancelButton: '!inline-flex !items-center !justify-center !bg-slate-100 !text-slate-700 hover:!bg-slate-200 !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm !border-none !outline-none !m-0 !cursor-pointer active:!scale-95',
                icon: wallet.is_active 
                    ? '!border-4 !border-rose-100 !text-rose-600 !scale-110 !mb-6 !mt-2'
                    : '!border-4 !border-emerald-100 !text-emerald-600 !scale-110 !mb-6 !mt-2'
            },
            buttonsStyling: false,
            backdrop: 'rgba(15, 23, 42, 0.4)'
        }).then((result) => {
            if (result.isConfirmed) {
                router.patch(`/wallets/${wallet.id}/toggle`, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });
                        showDetailModal.value = false;
                        Toast.fire({
                            icon: 'success',
                            title: __('wallet_status_updated'),
                            background: '#ffffff',
                            color: '#1e293b',
                            customClass: {
                                popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                                title: '!text-sm !font-bold !text-slate-900',
                            }
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

    const bankCount = computed(() => props.wallets.filter(w => w.type === 'bank' && w.is_active).length);
    const ewalletCount = computed(() => props.wallets.filter(w => w.type === 'ewallet' && w.is_active).length);
    
    const getTypeColor = (type) => {
        switch (type) {
            case 'cash':
                return {
                    blob: 'bg-emerald-500',
                    icon_bg: 'bg-emerald-50 text-emerald-600 border-emerald-100',
                    pill: 'bg-emerald-50 text-emerald-700 border-emerald-100',
                    border_hover: 'hover:border-emerald-200'
                };
            case 'ewallet':
                return {
                    blob: 'bg-violet-500',
                    icon_bg: 'bg-violet-50 text-violet-600 border-violet-100',
                    pill: 'bg-violet-50 text-violet-700 border-violet-100',
                    border_hover: 'hover:border-violet-200'
                };
            case 'bank':
                return {
                    blob: 'bg-orange-500',
                    icon_bg: 'bg-orange-50 text-orange-600 border-orange-100',
                    pill: 'bg-orange-50 text-orange-700 border-orange-100',
                    border_hover: 'hover:border-orange-200'
                };
            default:
                return {
                    blob: 'bg-slate-500',
                    icon_bg: 'bg-slate-50 text-slate-600 border-slate-100',
                    pill: 'bg-slate-50 text-slate-700 border-slate-100',
                    border_hover: 'hover:border-slate-200'
                };
        }
    };


    const checkTourTriggers = () => {
        const tourState = localStorage.getItem('tour_state');
        const tourCompleted = page.props.auth.user.has_completed_tour;
        

        // Guard against duplicate triggers
        if (driverObj.value && document.querySelector('.driver-popover')) {
            return;
        }

        if (!tourState || tourState === 'welcome' || tourState === 'wallet_setup') {
            if (!tourState || tourState === 'welcome') {
                if (!tourState && tourCompleted) {
                    return;
                }
                localStorage.setItem('tour_state', 'wallet_setup');
            }

            // Force cleanup of any lingering tour elements
            const popover = document.querySelector('.driver-popover');
            const overlay = document.querySelector('.driver-overlay');
            if (popover) popover.remove();
            if (overlay) overlay.remove();

            if (props.wallets.length === 0) {
                setTimeout(() => {
                    initWalletTour();
                }, 800);
            } else {
                setTimeout(() => {
                    initWalletSuccessTour();
                }, 800);
            }
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

    // Watch for new wallets to trigger the success tour immediately
    watch(() => props.wallets, (newWallets, oldWallets) => {
        const tourState = localStorage.getItem('tour_state');
        if (tourState === 'wallet_setup' && newWallets.length > 0 && (!oldWallets || oldWallets.length === 0)) {
            checkTourTriggers();
        }
    }, { deep: true });

    const initWalletTour = () => {
        driverObj.value = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayOpacity: 0.85,
            steps: [
                { 
                    element: '#tour-wallet-add', 
                    popover: { 
                        title: __('tour_wallet_first_title'), 
                        description: __('tour_wallet_first_desc') + skipHTML, 
                        position: 'bottom' 
                    } 
                },
            ]
        });
        driverObj.value.drive();
    };

    const initWalletSuccessTour = () => {
        const isMobile = window.innerWidth < 768;
        driverObj.value = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayOpacity: 0.85,
            onNextClick: () => {
                if (driverObj.value.isLastStep()) {
                    if (isMobile) {
                        localStorage.setItem('tour_state', 'hub_to_transactions');
                        router.visit('/dashboard');
                    } else {
                        localStorage.setItem('tour_state', 'transaction_setup');
                        router.visit('/transactions');
                    }
                    driverObj.value.destroy();
                } else {
                    driverObj.value.moveNext();
                }
            },
            steps: [
                { 
                    element: '#tour-wallet-list', 
                    popover: { 
                        title: __('tour_wallet_success_title'), 
                        description: __('tour_wallet_success_desc') + skipHTML, 
                        position: isMobile ? 'top' : 'bottom'
                    } 
                },
                { 
                    element: isMobile ? '#mobile-nav-home' : '#nav-transactions', 
                    popover: { 
                        title: isMobile ? __('tour_return_hub') : __('tour_record_activity'), 
                        description: isMobile 
                            ? __('tour_return_hub_desc') + skipHTML
                            : __('tour_record_activity_desc') + skipHTML, 
                        position: isMobile ? 'top' : 'bottom'
                    } 
                },
            ]
        });
        driverObj.value.drive();
    };
    </script>
    
    <template>
        <Head :title="__('wallets')" />
        <Layout>
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-30">
                <div class="space-y-1">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('wallets') }}</h1>
                    <p class="text-sm md:text-base text-slate-500 font-medium">{{ __('manage_wallets_desc') }}</p>
                </div>
                <button id="tour-wallet-add" @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 active:scale-95 transition-all flex items-center justify-center gap-2">
                    <Plus class="w-4 h-4" />
                    <span>{{ __('add_new_wallet') }}</span>
                </button>
            </header>
    

            <!-- Information Cards Grid -->
            <!-- Information Cards Grid -->
            <div class="flex overflow-x-auto snap-x snap-mandatory md:grid md:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-12 pb-4 md:pb-0 -mx-4 px-4 md:mx-0 md:px-0 scrollbar-hide md:overflow-visible">
                <!-- Total Net Worth Card -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Scale class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <Scale class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('total_net_worth') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(totalBalanceIDR, 'IDR').split(',')[0] }}</h2>
                            <div class="flex items-center gap-2 text-indigo-100 font-medium text-sm">
                                <span>{{ __('across_accounts') }}:</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-xs font-bold backdrop-blur-sm border border-white/10">{{ wallets.length }} {{ __('accounts') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Linked Banks Card -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <CreditCard class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <CreditCard class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('linked_banks') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ bankCount }}</h2>
                            <div class="flex items-center gap-2 text-emerald-100 font-medium text-sm">
                                <span>{{ __('active_connections') }}:</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-xs font-bold backdrop-blur-sm border border-white/10">{{ __('active') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Digital Wallets Card -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <WalletIcon class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <WalletIcon class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('digital_wallets') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ ewalletCount }}</h2>
                            <div class="flex items-center gap-2 text-rose-100 font-medium text-sm">
                                <span>{{ __('active_connections') }}:</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-xs font-bold backdrop-blur-sm border border-white/10">{{ __('active') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <draggable 
            id="tour-wallet-list"
            v-if="wallets.length > 0" 
            v-model="localWallets" 
            item-key="id" 
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-16 pb-12"
            ghost-class="opacity-50"
            handle=".drag-handle"
            :animation="200"
            @end="onDragEnd"
        >
            <template #item="{ element: wallet }">
                <div 
                    @click="openDetailModal(wallet)"
                    class="group relative min-h-auto md:min-h-[240px] h-auto rounded-[1.5rem] md:rounded-[2rem] p-5 md:p-8 overflow-hidden transition-all duration-300 hover:scale-[1.02] hover:shadow-xl bg-white border border-slate-100 flex flex-col cursor-pointer active:scale-[0.98]" 
                    :class="getTypeColor(wallet.type).border_hover"
                >
                    <!-- Content -->
                    <div class="relative z-10 flex flex-col justify-between h-full gap-4 md:gap-0">
                        <!-- Top Row: Icons & Actions -->
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3 md:gap-4">
                                <div class="p-2.5 md:p-3 rounded-2xl shadow-sm border" :class="getTypeColor(wallet.type).icon_bg">
                                    <Banknote v-if="wallet.type === 'cash'" class="w-5 h-5 md:w-6 md:h-6" />
                                    <CreditCard v-if="wallet.type === 'bank'" class="w-5 h-5 md:w-6 md:h-6" />
                                    <WalletIcon v-if="wallet.type === 'ewallet'" class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-base md:text-xl leading-none tracking-tight text-slate-900 mt-1">{{ wallet.name }}</h3>
                                </div>
                            </div>
                            
                            <!-- Drag Handle Only (on mobile) -->
                            <div class="flex items-center gap-1.5 md:gap-2 mr-0">
                                <!-- Desktop Actions (Visible only on md+) -->
                                <div class="hidden md:flex items-center gap-2">
                                    <button 
                                        @click.stop="toggleStatus(wallet)" 
                                        class="p-2.5 rounded-xl bg-slate-50 hover:bg-white hover:shadow-md border border-slate-100 transition-all text-slate-400 hover:text-rose-600"
                                        :title="__('toggle_status')"
                                    >
                                        <Power class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click.stop="openEditModal(wallet)" 
                                        class="p-2.5 rounded-xl bg-slate-50 hover:bg-white hover:shadow-md border border-slate-100 transition-all text-slate-400 hover:text-indigo-600"
                                        :title="__('edit_wallet')"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                </div>

                                <div class="p-2 md:p-2.5 rounded-xl bg-slate-50 hover:bg-white hover:shadow-md border border-slate-100 transition-all text-slate-400 hover:text-slate-600 cursor-move drag-handle" :title="__('drag_to_reorder')" @click.stop>
                                    <GripVertical class="w-3.5 h-3.5 md:w-4 md:h-4" />
                                </div>
                            </div>
                        </div>

                        <!-- Middle: Balance -->
                        <div class="mt-2 md:mt-6">
                             <h2 class="text-xl md:text-[30px] font-bold tracking-tight tabular-nums text-slate-900">{{ formatCurrency(wallet.balance, wallet.currency).split(',')[0] }}</h2>
                             <p v-if="wallet.currency === 'USD'" class="text-xs font-medium text-slate-500 mt-1 inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-slate-50 border border-slate-100">
                                 <span>≈ {{ getEstimatedIDR(wallet.balance).split(',')[0] }}</span>
                             </p>
                        </div>

                        <!-- Bottom: Footer -->
                        <div class="flex justify-between items-end mt-auto pt-3 md:pt-4 border-t border-slate-50">
                             <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 mb-0.5">{{ __('account_number') }}</span>
                                <span class="text-xs md:text-sm font-bold text-slate-600 truncate max-w-[120px] md:max-w-[150px]">
                                    {{ wallet.account_number || '-' }}
                                </span>
                             </div>
                             
                             <div class="flex items-center gap-2">
                                 <span class="text-[10px] md:text-xs font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100">{{ wallet.currency }}</span>
                                 <!-- Status Pill -->
                                 <span v-if="!wallet.is_active" class="px-2 md:px-3 py-1 rounded-full bg-rose-50 text-rose-600 text-[10px] font-bold shadow-sm border border-rose-100">
                                    {{ __('inactive') }}
                                 </span>
                                 <span v-else class="px-2 md:px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-bold shadow-sm border border-emerald-100">
                                    {{ __('active') }}
                                 </span>
                             </div>
                        </div>
                    </div>
                </div>
            </template>
        </draggable>
            <div v-else class="bg-white border border-slate-100 rounded-[2rem] shadow-sm flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                    <WalletIcon class="w-8 h-8 text-slate-300" />
                </div>
                <h4 class="text-slate-900 font-bold text-base mb-1">{{ __('no_wallets_tracked') }}</h4>
                <p class="text-slate-500 text-sm mb-8 max-w-sm mx-auto">
                    {{ __('no_wallets_desc') }}
                </p>
                <button @click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-all active:scale-95 mx-auto">
                    <Plus class="w-4 h-4" />
                    <span>{{ __('create_first_wallet') }}</span>
                </button>
            </div>
    
            <!-- Modal (Standardized) -->
            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto">
                    <!-- Backdrop -->
                    <div @click="showModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        
                    <!-- Modal Content -->
                    <div class="relative z-10 w-full max-w-2xl max-h-[92vh] flex flex-col bg-white rounded-t-[2rem] sm:rounded-3xl shadow-2xl overflow-hidden animate-in slide-in-from-bottom-10 sm:zoom-in-95 duration-300">
                        <div class="p-6 md:p-8 border-b border-slate-200 flex items-center justify-between shrink-0">
                            <div>
                                <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">
                                    {{ isEditing ? __('update_wallet') : __('new_wallet') }}
                                </h2>
                                <p class="text-xs md:text-sm text-slate-500 font-medium">{{ isEditing ? __('update_wallet_desc') : __('new_wallet_desc') }}</p>
                            </div>
                            <button @click="showModal = false" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 transition-all">
                                <X class="w-6 h-6" />
                            </button>
                        </div>
                    
                    <div class="p-6 md:p-8 overflow-y-auto flex-1">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('name') }}</label>
                                <input v-model="form.name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm" :placeholder="__('wallet_placeholder_name')" required>
                            </div>
    
                            <div class="grid grid-cols-1 gap-6" :class="{ 'md:grid-cols-2': !isEditing }">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('type') }}</label>
                                     <SearchableSelect 
                                        v-model="form.type" 
                                        :options="typeOptions" 
                                        :placeholder="__('select_type')" 
                                    />
                                </div>
                                
                                <div v-if="!isEditing">
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('currency') }}</label>
                                    <SearchableSelect 
                                        v-model="form.currency" 
                                        :options="currencyOptions" 
                                        :placeholder="__('select_currency')" 
                                    />
                                </div>
                            </div>
    
                            <div v-if="form.type !== 'cash'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('bank_name') }}</label>
                                    <input v-model="form.bank_name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm" :placeholder="__('wallet_placeholder_bank')">
                                </div>
    
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('account_number') }}</label>
                                    <input v-model="form.account_number" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm" :placeholder="__('wallet_placeholder_account')">
                                </div>
                            </div>
    
                            <div v-if="!isEditing">
                                 <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('current_balance') }}</label>
                                <CurrencyInput 
                                    v-model="form.balance" 
                                    :currency="form.currency" 
                                    placeholder="0"
                                />
                            </div>
    
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showModal = false" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-4 rounded-xl font-bold text-sm transition-all">
                                    {{ __('cancel') }}
                                </button>
                                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-4 rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl transition-all">
                                    {{ isEditing ? __('save_changes') : __('initialize_wallet') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                </div>
            </Teleport>

            <!-- Wallet Detail Modal -->
            <Teleport to="body">
                <div v-if="showDetailModal" class="fixed inset-0 z-[100] flex items-end md:items-center justify-center sm:p-4">
                    <div @click="showDetailModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                    
                    <div class="relative z-10 w-full max-w-lg bg-white rounded-t-[2rem] md:rounded-[2rem] shadow-2xl overflow-hidden animate-in slide-in-from-bottom-10 fade-in duration-300">
                        <!-- Header -->
                        <div class="p-6 md:p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <div class="flex items-center gap-4">
                                <div class="p-3 rounded-2xl shadow-sm border bg-white" :class="getTypeColor(selectedDetailWallet?.type).icon_bg">
                                    <Banknote v-if="selectedDetailWallet?.type === 'cash'" class="w-6 h-6" />
                                    <CreditCard v-if="selectedDetailWallet?.type === 'bank'" class="w-6 h-6" />
                                    <WalletIcon v-if="selectedDetailWallet?.type === 'ewallet'" class="w-6 h-6" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-slate-900 leading-tight">{{ selectedDetailWallet?.name }}</h2>
                                    <p class="text-sm font-medium text-slate-500 capitalize">{{ __(selectedDetailWallet?.type) }}</p>
                                </div>
                            </div>
                            <button @click="showDetailModal = false" class="p-2 hover:bg-slate-100 rounded-full text-slate-400 hover:text-slate-900 transition-all">
                                <X class="w-6 h-6" />
                            </button>
                        </div>
                        
                        <div class="p-6 md:p-8 space-y-8">
                            <!-- Balance Section -->
                            <div class="text-center space-y-2">
                                <p class="text-sm font-bold text-slate-400">{{ __('current_balance') }}</p>
                                <h3 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight tabular-nums">
                                    {{ formatCurrency(selectedDetailWallet?.balance, selectedDetailWallet?.currency).split(',')[0] }}
                                </h3>
                                <div v-if="selectedDetailWallet?.currency === 'USD'" class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-full text-xs font-bold text-slate-600">
                                    <span>≈ {{ getEstimatedIDR(selectedDetailWallet?.balance) }}</span>
                                </div>
                            </div>

                            <!-- Details Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                                    <p class="text-xs font-bold text-slate-400">{{ __('currency') }}</p>
                                    <p class="font-bold text-slate-700">{{ selectedDetailWallet?.currency }}</p>
                                </div>
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                                    <p class="text-xs font-bold text-slate-400">{{ __('status') }}</p>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full" :class="selectedDetailWallet?.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                                        <p class="font-bold" :class="selectedDetailWallet?.is_active ? 'text-emerald-700' : 'text-rose-700'">
                                            {{ selectedDetailWallet?.is_active ? __('active') : __('inactive') }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="selectedDetailWallet?.bank_name" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1 col-span-2">
                                    <p class="text-xs font-bold text-slate-400">{{ __('bank_name') }}</p>
                                    <p class="font-bold text-slate-700">{{ selectedDetailWallet?.bank_name }}</p>
                                </div>
                                <div v-if="selectedDetailWallet?.account_number" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1 col-span-2">
                                    <p class="text-xs font-bold text-slate-400">{{ __('account_number') }}</p>
                                    <p class="font-bold text-slate-700 font-mono tracking-wide">{{ selectedDetailWallet?.account_number }}</p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button 
                                    @click="openEditModal(selectedDetailWallet)" 
                                    class="flex items-center justify-center gap-2 px-4 py-3.5 rounded-xl font-bold text-sm transition-all border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 active:scale-95"
                                >
                                    <Pencil class="w-4 h-4" />
                                    {{ __('edit_wallet') }}
                                </button>
                                <button 
                                    @click="toggleStatus(selectedDetailWallet)" 
                                    class="flex items-center justify-center gap-2 px-4 py-3.5 rounded-xl font-bold text-sm transition-all text-white shadow-lg active:scale-95"
                                    :class="selectedDetailWallet?.is_active ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-200' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-200'"
                                >
                                    <Power class="w-4 h-4" />
                                    {{ selectedDetailWallet?.is_active ? __('deactivate') : __('activate') }}
                                </button>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Upgrade Modal -->
            <UpgradeModal 
                :show="showUpgradeModal" 
                @close="showUpgradeModal = false"
                :title="__('wallet_limit_reached')"
                :description="__('wallet_limit_desc')"
            />

    </Layout>
</template>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.scrollbar-hide {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
