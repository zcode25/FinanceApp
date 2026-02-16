<script setup>
import Layout from '../../Shared/Layout.vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, useForm, router, usePage, Deferred } from '@inertiajs/vue3';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";

const driverObj = ref(null);
const skipHTML = `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">Skip Tutorial</button>
</div>`;
import { Plus, X, Search, Filter, Pencil, Trash2, TrendingUp, TrendingDown, Scale, Activity, ChevronDown } from 'lucide-vue-next';
import CurrencyInput from '../../Shared/CurrencyInput.vue';
import CategoryCombobox from '../../Shared/CategoryCombobox.vue';
import SearchableSelect from '../../Shared/SearchableSelect.vue';
import Pagination from '../../Shared/Pagination.vue';
import debounce from 'lodash/debounce';
import PremiumUpsellModal from '@/Shared/PremiumUpsellModal.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const page = usePage();
const props = defineProps({
    transactions: Object,
    categories: Array,
    wallets: Array,
    filters: Object,
    currentExchangeRate: Number,
    summary: Object
});

// Robust data handling for deferred props
const summaryData = computed(() => {
    return props.summary || {
        total_income: 0,
        total_expense: 0,
        total_income_count: 0,
        total_expense_count: 0,
        net_balance: 0
    };
});

const showModal = ref(false);
const isEditing = ref(false);
const selectedTransactionId = ref(null);
const search = ref(props.filters.search || '');

// Transaction Detail Modal Logic
const showDetailModal = ref(false);
const selectedDetailTransaction = ref(null);

const openDetailModal = (transaction) => {
    selectedDetailTransaction.value = transaction;
    showDetailModal.value = true;
};

const showUpsellModal = ref(false);

const isPremium = computed(() => !!page.props.auth.user.is_premium);
const customCategoryCount = computed(() => props.categories.filter(c => c.user_id !== null).length);
const canCreateCategory = computed(() => isPremium.value || customCategoryCount.value < 3);

// Mobile Load More Logic
const mobileTransactions = ref(props.transactions.data);
const mobileNextPageUrl = ref(props.transactions.next_page_url);
const isLoadingMore = ref(false);

// Watch for prop updates (e.g. search, filter, desktop pagination) to reset mobile list
// Watcher removed - merged with optimistic watcher logic below


const loadMore = async () => {
    if (!mobileNextPageUrl.value || isLoadingMore.value) return;
    
    isLoadingMore.value = true;
    try {
        const response = await axios.get(mobileNextPageUrl.value, {
            headers: { 
                'X-Inertia': 'true', // Crucial to get JSON response
                'X-Inertia-Version': page.version, // Optional, but good practice
                'Accept': 'application/json'
            }
        });
        
        let newTransactionsData = [];
        let newNextPageUrl = null;

        if (response.data.props && response.data.props.transactions) {
             newTransactionsData = response.data.props.transactions.data;
             newNextPageUrl = response.data.props.transactions.next_page_url;
        } else if (response.data.transactions) { 
             newTransactionsData = response.data.transactions.data;
             newNextPageUrl = response.data.transactions.next_page_url;
        } else if (response.data.data) {
             newTransactionsData = response.data.data;
             newNextPageUrl = response.data.next_page_url;
        } else if (typeof response.data === 'string') {
             // Fallback: If server returned HTML, try to extract Inertia JSON from data-page attribute
             try {
                const match = response.data.match(/data-page="([^"]+)"/);
                if (match && match[1]) {
                    const jsonString = match[1].replace(/&quot;/g, '"').replace(/&amp;/g, '&');
                    const pageData = JSON.parse(jsonString);
                    if (pageData.props && pageData.props.transactions) {
                        newTransactionsData = pageData.props.transactions.data;
                        newNextPageUrl = pageData.props.transactions.next_page_url;
                    }
                }
             } catch (e) {
                console.error("Failed to parse Inertia HTML response", e);
             }
        } else {
             // Fallback: check if the response itself is an array
             newTransactionsData = Array.isArray(response.data) ? response.data : [];
        }
        
        if (Array.isArray(newTransactionsData) && newTransactionsData.length > 0) {
             // Filter out duplicates to avoid key collisions
             const existingIds = new Set(mobileTransactions.value.map(t => t.id));
             const uniqueNewTransactions = newTransactionsData.filter(t => !existingIds.has(t.id));
             
             if (uniqueNewTransactions.length > 0) {
                 mobileTransactions.value = [...mobileTransactions.value, ...uniqueNewTransactions];
             }
             
             mobileNextPageUrl.value = newNextPageUrl;
        } else {
            console.warn("Load More: No valid transaction array found in response", response.data);
            // If we got a valid response but no data, maybe we reached the end or format is wrong.
            // Disable further loading if we can't find a next URL or data.
            if (!newNextPageUrl) mobileNextPageUrl.value = null;
        }
    } catch (error) {
        console.error("Failed to load more transactions", error);
        // Optional: Show user feedback
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'Failed to load more transactions',
            showConfirmButton: false,
            timer: 3000
        });
    } finally {
        isLoadingMore.value = false;
    }
};

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
        end_date: props.filters.end_date || '',
        per_page: props.filters.per_page || 10
    });

    const isFiltered = computed(() => {
        return !!(search.value || 
               filters.value.wallet_id || 
               filters.value.type || 
               filters.value.start_date || 
               filters.value.end_date);
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
            end_date: '',
            per_page: 10
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
        form.clearErrors();
        form.date = new Date().toISOString().substr(0, 10);
        form.type = 'expense';
        showModal.value = true;
    };
    
    const openEditModal = (transaction) => {
        isEditing.value = true;
        selectedTransactionId.value = transaction.id;
        form.clearErrors();
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

    // Card Metrics Computations
    const currentDate = new Date();
    const currentMonthName = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
    const daysPassed = currentDate.getDate();

    const avgIncome = computed(() => summaryData.value.total_income > 0 ? summaryData.value.total_income / daysPassed : 0);
    const avgExpense = computed(() => summaryData.value.total_expense > 0 ? summaryData.value.total_expense / daysPassed : 0);
    const savingsRate = computed(() => {
        if (!summaryData.value.total_income || summaryData.value.total_income <= 0) return 0;
        const rate = ((summaryData.value.total_income - summaryData.value.total_expense) / summaryData.value.total_income) * 100;
        return Math.max(0, rate);
    });

    const incomeCount = computed(() => summaryData.value.total_income_count || 0);
    const expenseCount = computed(() => summaryData.value.total_expense_count || 0);
    
    // Optimistic Deletion Logic
    const desktopTransactions = ref(props.transactions.data);

    watch(() => props.transactions, (newVal) => {
        desktopTransactions.value = newVal.data;
        // Sync mobile transactions if resetting (not loading more)
        if (!isLoadingMore.value) {
           mobileTransactions.value = newVal.data;
        }
    }, { deep: true });

    const deleteTransaction = (transaction) => {
        Swal.fire({
            title: __('delete_transaction_title') || 'Delete Transaction?',
            text: __('delete_transaction_confirm') || 'Are you sure you want to delete this transaction?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: __('yes_delete') || 'Yes, delete it',
            cancelButtonText: __('cancel') || 'Cancel',
            customClass: {
                popup: '!rounded-[2rem] !p-10 !bg-white !shadow-2xl !border !border-slate-100 !font-sans !antialiased',
                title: '!text-xl !font-bold !text-slate-900 !pt-4 !pb-2 !px-0 !m-0 !leading-tight',
                htmlContainer: '!text-sm !font-semibold !text-slate-500 !leading-relaxed !pb-6 !px-0 !m-0',
                actions: '!flex !items-center !justify-center !gap-3 !mt-4 !w-full !px-0',
                confirmButton: '!inline-flex !items-center !justify-center !bg-rose-600 !text-white !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm hover:!shadow-rose-600/20 hover:!bg-rose-700 active:!scale-95 !border-none !outline-none !m-0 !cursor-pointer',
                cancelButton: '!inline-flex !items-center !justify-center !bg-slate-100 !text-slate-700 hover:!bg-slate-200 !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm !border-none !outline-none !m-0 !cursor-pointer active:!scale-95',
                icon: '!border-4 !border-rose-100 !text-rose-600 !scale-110 !mb-6 !mt-2'
            },
            buttonsStyling: false,
            backdrop: 'rgba(15, 23, 42, 0.4)'
        }).then((result) => {
            if (result.isConfirmed) {
                // 1. Optimistic UI Update
                const originalDesktop = [...desktopTransactions.value];
                const originalMobile = [...mobileTransactions.value];

                // Remove immediately from UI
                desktopTransactions.value = desktopTransactions.value.filter(t => t.id !== transaction.id);
                mobileTransactions.value = mobileTransactions.value.filter(t => t.id !== transaction.id);

                // 2. Background Request
                router.delete(`/transactions/${transaction.id}`, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        // Toast on actual success
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
                            title: __('deleted_title'),
                            background: '#ffffff',
                            color: '#1e293b',
                            customClass: {
                                popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                                title: '!text-sm !font-bold !text-slate-900',
                            }
                        });
                    },
                    onError: () => {
                        // 3. Rollback on failure
                        desktopTransactions.value = originalDesktop;
                        mobileTransactions.value = originalMobile;
                        
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: __('error_deleting'),
                            showConfirmButton: false,
                            timer: 3000
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
                    
                    // Toast for update
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
                        title: __('successfully_updated'),
                        background: '#ffffff',
                        color: '#1e293b',
                        customClass: {
                            popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                            title: '!text-sm !font-bold !text-slate-900',
                        }
                    })
                },
            });
        } else {
            form.post('/transactions', {
                onSuccess: () => {
                    form.reset();
                    showModal.value = false;
                    
                    // Toast for create
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
                        title: __('successfully_saved'),
                        background: '#ffffff',
                        color: '#1e293b',
                        customClass: {
                            popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                            title: '!text-sm !font-bold !text-slate-900',
                        }
                    })
                },
            });
        }
    };


    const checkTourTriggers = () => {
        const tourState = localStorage.getItem('tour_state');
        const tourCompleted = page.props.auth.user.has_completed_tour;
        const catchUpStates = ['welcome', 'wallet_setup', 'transaction_setup'];
        

        // Guard against duplicate triggers
        if (driverObj.value && document.querySelector('.driver-popover')) {
            return;
        }

        if (!tourState || (tourState && catchUpStates.includes(tourState))) {
            if (!tourState || tourState !== 'transaction_setup') {
                if (!tourState && tourCompleted) {
                    return;
                }
                localStorage.setItem('tour_state', 'transaction_setup');
            }

            // Force cleanup of any lingering tour elements
            const popover = document.querySelector('.driver-popover');
            const overlay = document.querySelector('.driver-overlay');
            if (popover) popover.remove();
            if (overlay) overlay.remove();

            if (props.transactions.total === 0) {
                setTimeout(() => {
                    initTransactionTour();
                }, 800);
            } else {
                setTimeout(() => {
                    initTransactionSuccessTour();
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

    // Watch for new transactions to trigger success tour immediately
    watch(() => props.transactions.total, (newTotal, oldTotal) => {
        const tourState = localStorage.getItem('tour_state');
        if (tourState === 'transaction_setup' && newTotal > 0 && (oldTotal === 0 || !oldTotal)) {
            checkTourTriggers();
        }
    });

    const initTransactionTour = () => {
        driverObj.value = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayOpacity: 0.85,
            steps: [
                { 
                    element: '#tour-transaction-add', 
                    popover: { 
                        title: __('tour_tx_add_title'), 
                        description: __('tour_tx_add_desc') + skipHTML, 
                        position: 'bottom' 
                    } 
                },
            ]
        });
        driverObj.value.drive();
    };

    const initTransactionSuccessTour = () => {
        const isMobile = window.innerWidth < 768;
        driverObj.value = driver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayOpacity: 0.85,
            onNextClick: () => {
                if (driverObj.value.isLastStep()) {
                    localStorage.setItem('tour_state', 'dashboard_explanation');
                    router.visit('/dashboard');
                    driverObj.value.destroy();
                } else {
                    driverObj.value.moveNext();
                }
            },
            steps: [
                { 
                    element: '#tour-transaction-list', 
                    popover: { 
                        title: __('tour_tx_list_title'), 
                        description: __('tour_tx_list_desc') + skipHTML, 
                        position: isMobile ? 'top' : 'bottom'
                    } 
                },
                { 
                    element: isMobile ? '#mobile-nav-home' : '#nav-dashboard', 
                    popover: { 
                        title: __('tour_tx_full_picture_title'), 
                        description: __('tour_tx_full_picture_desc') + skipHTML, 
                        position: isMobile ? 'top' : 'bottom'
                    } 
                },
            ]
        });
        driverObj.value.drive();
    };
    </script>
    
    <template>
        <Head :title="__('transactions')" />
        <Layout>
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-30">
                <div class="space-y-1">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('transactions') }}</h1>
                    <p class="text-sm md:text-base text-slate-500 font-medium">{{ __('manage_and_monitor') }}</p>
                </div>
                <button id="tour-transaction-add" @click="openAddModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 active:scale-95 transition-all flex items-center justify-center gap-2">
                    <Plus class="w-4 h-4" />
                    <span>{{ __('add_transaction') }}</span>
                </button>
            </header>
    
            <!-- Summary Row -->
            <div class="overflow-x-auto lg:overflow-visible no-scrollbar -mx-4 px-4 lg:mx-0 lg:px-0 flex lg:grid lg:grid-cols-3 flex-nowrap gap-6 mb-8 lg:mb-10 scroll-smooth py-4 lg:py-0 -my-4 lg:my-0">
                <!-- Total Income Card -->
                <div class="min-w-[85vw] lg:min-w-0 relative overflow-hidden rounded-[2rem] p-8 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <TrendingUp class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <TrendingUp class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('total_income') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatIDR(summaryData.total_income) }}</h2>
                            <div class="flex items-center gap-2 text-emerald-100 font-medium text-sm">
                                <span>{{ __('income_records') }}</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-xs font-bold backdrop-blur-sm border border-white/10">{{ incomeCount }} {{ __('items') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Expense Card -->
                <div class="min-w-[85vw] lg:min-w-0 relative overflow-hidden rounded-[2rem] p-8 bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <TrendingDown class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <TrendingDown class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('total_expense') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatIDR(summaryData.total_expense) }}</h2>
                            <div class="flex items-center gap-2 text-rose-100 font-medium text-sm">
                                <span>{{ __('expense_records') }}</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-xs font-bold backdrop-blur-sm border border-white/10">{{ expenseCount }} {{ __('items') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Balance Card -->
                <div class="min-w-[85vw] lg:min-w-0 relative overflow-hidden rounded-[2rem] p-8 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Scale class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <Scale class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('net_balance') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatIDR(summaryData.net_balance) }}</h2>
                            <div class="flex items-center gap-2 text-indigo-100 font-medium text-sm">
                                <span>{{ __('savings_efficiency') }}</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-xs font-bold backdrop-blur-sm border border-white/10">{{ savingsRate.toFixed(1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Toolbar: Search & Filters -->
            <div class="mb-8 lg:mb-8 flex items-center justify-between gap-6">
                <div class="relative flex-1 max-w-xl group">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                    <input 
                        v-model="search"
                        type="text" 
                        :placeholder="__('search_transactions')" 
                        class="w-full bg-white border-none rounded-2xl pl-12 pr-10 py-4 text-slate-900 placeholder-slate-400 shadow-sm focus:ring-2 focus:ring-indigo-600/10 transition-all font-semibold"
                    >
                </div>
                <button 
                    @click="showFilters = !showFilters"
                    class="hidden md:flex items-center gap-3 px-6 py-4 bg-white border border-slate-100 rounded-2xl text-slate-600 hover:bg-slate-50 transition-all font-bold text-sm shadow-sm"
                    :class="{ 'bg-slate-50 ring-2 ring-indigo-600/10': showFilters }"
                >
                    <Filter class="w-5 h-5 text-slate-400" />
                    <span>{{ __('filter') }}</span>
                </button>
            </div>
    
                <!-- Expandable Filter Panel -->
                <div v-if="showFilters" class="glass-card mb-8 lg:mb-8 p-8 bg-white border border-slate-200 rounded-3xl shadow-xl shadow-slate-200/40 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 animate-in slide-in-from-top-4 duration-300">
                    <!-- Wallet Filter -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-500 ml-1">{{ __('wallet_source') }}</label>
                        <div class="relative group">
                            <select v-model="filters.wallet_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm py-3.5 px-4 focus:ring-2 focus:ring-indigo-600/10 focus:border-indigo-600 appearance-none font-semibold transition-all">
                                <option value="">{{ __('all_wallets') }}</option>
                                <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">{{ wallet.name }}</option>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 group-hover:text-slate-600 transition-colors">
                                <Filter class="w-4 h-4" />
                            </div>
                        </div>
                    </div>
    
                    <!-- Type Filter -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-500 ml-1">{{ __('transaction_type') }}</label>
                        <div class="flex p-1.5 bg-slate-50 rounded-xl border border-slate-200 h-[50px]">
                            <button @click="filters.type = ''" class="flex-1 text-xs rounded-lg font-bold transition-all" :class="filters.type === '' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/50' : 'text-slate-500 hover:text-slate-900'">{{ __('all') }}</button>
                            <button @click="filters.type = 'income'" class="flex-1 text-xs rounded-lg font-bold transition-all" :class="filters.type === 'income' ? 'bg-emerald-500 text-white shadow-md' : 'text-slate-500 hover:text-slate-900'">{{ __('income') }}</button>
                            <button @click="filters.type = 'expense'" class="flex-1 text-xs rounded-lg font-bold transition-all" :class="filters.type === 'expense' ? 'bg-rose-500 text-white shadow-md' : 'text-slate-500 hover:text-slate-900'">{{ __('expense') }}</button>
                        </div>
                    </div>
    
                    <!-- Date Range -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-500 ml-1">{{ __('from_date') }}</label>
                        <input v-model="filters.start_date" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm py-3 px-4 focus:ring-2 focus:ring-indigo-600/10 focus:border-indigo-600 font-semibold transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-500 ml-1">{{ __('to_date') }}</label>
                        <input v-model="filters.end_date" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm py-3 px-4 focus:ring-2 focus:ring-indigo-600/10 focus:border-indigo-600 font-semibold transition-all">
                    </div>
                     
                     <div class="sm:col-span-2 lg:col-span-4 flex justify-end pt-2">
                        <button @click="resetFilters" class="text-xs font-bold text-slate-400 hover:text-indigo-600 transition-all flex items-center gap-2 px-4 py-2 hover:bg-indigo-50 rounded-xl border border-transparent hover:border-indigo-100">
                             <X class="w-3.5 h-3.5" />
                            <span>{{ __('reset_all_filters') }}</span>
                        </button>
                    </div>
                </div>
 
    
            <!-- Transaction List/Table -->
            <div id="tour-transaction-list" class="">
                <div v-if="desktopTransactions.length > 0">
                    <!-- Desktop Table & Unified Footer -->
                    <div class="hidden md:block bg-white border border-slate-100 rounded-3xl shadow-sm mb-12 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50/50 border-b border-slate-100">
                                    <tr class="text-xs font-bold text-slate-500 tracking-tight">
                                        <th class="px-6 py-4">{{ __('date') }}</th>
                                        <th class="px-6 py-4">{{ __('description') }}</th>
                                        <th class="px-6 py-4">{{ __('wallet') }}</th>
                                        <th class="px-6 py-4">{{ __('category') }}</th>
                                        <th class="px-6 py-4 text-right px-8">{{ __('amount') }}</th>
                                        <th class="px-6 py-4 text-right">{{ __('actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="tx in desktopTransactions" :key="tx.id" class="group hover:bg-slate-50/80 transition-colors">
                                        <!-- Date -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-semibold text-slate-700 whitespace-nowrap">
                                                {{ new Date(tx.date).toLocaleDateString($page.props.locale === 'id' ? 'id-ID' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                            </div>
                                        </td>
                                        
                                        <!-- Description -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-normal text-slate-900 group-hover:text-indigo-900 transition-colors">
                                                {{ tx.description || (tx.category ? tx.category.name : __('unknown')) }}
                                            </div>
                                        </td>

                                        <!-- Wallet -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full" :class="getTypeColor(tx.wallet?.type)"></div>
                                                <span class="text-sm font-semibold text-slate-600">{{ tx.wallet ? tx.wallet.name : __('unknown') }}</span>
                                            </div>
                                        </td>
                                        
                                        <!-- Category -->
                                        <td class="px-6 py-4">
                                            <span 
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold text-white border border-white/20"
                                                :class="tx.category?.color || 'bg-slate-500'"
                                            >
                                                {{ tx.category ? tx.category.name : 'Uncategorized' }}
                                            </span>
                                        </td>
                                        
                                        <!-- Amount -->
                                        <td class="px-6 py-4 text-right px-8">
                                            <div class="space-y-1">
                                                <span :class="['text-sm font-bold tabular-nums block', tx.type === 'expense' ? 'text-slate-900' : 'text-emerald-600']">
                                                    {{ tx.type === 'expense' ? '-' : '+' }} {{ new Intl.NumberFormat(tx.currency === 'USD' ? 'en-US' : 'id-ID', { style: 'currency', currency: tx.currency || 'IDR', maximumFractionDigits: 0 }).format(tx.amount) }}
                                                </span>
                                                <span v-if="tx.currency !== 'IDR' && tx.amount_in_base_currency" class="text-[10px] text-slate-400 font-bold block">
                                                    ≈ {{ formatIDR(tx.amount_in_base_currency) }}
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <!-- Actions -->
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="openEditModal(tx)" 
                                                        class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-100 rounded-lg transition-all">
                                                    <Pencil class="w-4 h-4" />
                                                </button>
                                                <button @click="deleteTransaction(tx)" 
                                                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-100 rounded-lg transition-all">
                                                    <Trash2 class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Table Footer (Desktop Only) -->
                        <div class="px-8 py-5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-500">{{ __('show') }}</span>
                                    <select 
                                        v-model="filters.per_page"
                                        class="appearance-none bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 py-2 px-4 pr-10 focus:ring-2 focus:ring-indigo-600/10 focus:border-indigo-600 transition-all outline-none cursor-pointer shadow-sm"
                                    >
                                        <option :value="10">10 {{ __('entries') }}</option>
                                        <option :value="25">25 {{ __('entries') }}</option>
                                        <option :value="50">50 {{ __('entries') }}</option>
                                        <option :value="100">100 {{ __('entries') }}</option>
                                    </select>
                                </div>
                                <span class="text-xs font-semibold text-slate-400">
                                    {{ __('showing') }} {{ transactions.from || 0 }} {{ __('to') }} {{ transactions.to || 0 }} {{ __('of_paging') }} {{ transactions.total }} {{ __('entries') }}
                                </span>
                            </div>
                            
                            <Pagination :links="transactions.links" />
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="md:hidden space-y-4 mb-4">
                        <div 
                            v-for="tx in mobileTransactions" 
                            :key="tx.id" 
                            @click="openDetailModal(tx)"
                            class="p-4 bg-white rounded-2xl border border-slate-100 shadow-sm transition-all animate-in fade-in slide-in-from-bottom-2 duration-300 cursor-pointer active:bg-slate-50"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col gap-1">
                                    <p class="text-[10px] font-bold text-slate-400 tracking-wider">
                                        {{ new Date(tx.date).getDate() }} {{ new Date(tx.date).toLocaleDateString(page.props.locale || 'id-ID', { month: 'short' }) }} {{ new Date(tx.date).getFullYear() }}
                                    </p>
                                    <div>
                                        <h4 class="text-[14px] font-bold text-slate-900 line-clamp-1 leading-tight mb-1.5">
                                            {{ tx.description || (tx.category ? tx.category.name : __('unknown')) }}
                                        </h4>
                                        <div class="flex items-center gap-2">
                                            <span 
                                                class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-bold text-white border border-white/20 whitespace-nowrap"
                                                :class="tx.category?.color || 'bg-slate-500'"
                                            >
                                                {{ tx.category ? tx.category.name : __('uncategorized') }}
                                            </span>
                                            <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1.5 whitespace-nowrap">
                                                <div class="w-1.5 h-1.5 rounded-full" :class="getTypeColor(tx.wallet?.type)"></div>
                                                {{ tx.wallet ? tx.wallet.name : __('unknown') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <span :class="['text-[14px] font-bold tabular-nums tracking-tight block', tx.type === 'expense' ? 'text-slate-900' : 'text-emerald-600']">
                                        {{ tx.type === 'expense' ? '-' : '+' }} {{ new Intl.NumberFormat(tx.currency === 'USD' ? 'en-US' : 'id-ID', { style: 'currency', currency: tx.currency || 'IDR', maximumFractionDigits: 0 }).format(tx.amount) }}
                                    </span>
                                    <span v-if="tx.currency !== 'IDR' && tx.amount_in_base_currency" class="text-[9px] text-slate-400 font-bold block mt-0.5">
                                        ≈ {{ formatIDR(tx.amount_in_base_currency) }}
                                    </span>
                                </div>
                             </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Bottom Spacer/Load More -->
                    <div class="pb-24 flex items-center justify-center">
                         <div class="md:hidden w-full px-4">
                            <button 
                                v-if="mobileNextPageUrl"
                                @click="loadMore"
                                :disabled="isLoadingMore"
                                class="w-full bg-white border border-slate-200 text-slate-600 font-bold text-sm py-4 px-6 rounded-2xl shadow-sm active:scale-95 transition-all flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <span v-if="isLoadingMore" class="w-4 h-4 border-2 border-slate-400 border-t-transparent rounded-full animate-spin"></span>
                                    <span>{{ isLoadingMore ? __('loading') : __('read_more') }}</span>
                                </div>
                                <ChevronDown v-if="!isLoadingMore" class="w-5 h-5 text-slate-400" />
                            </button>
                            <span v-else class="text-xs font-bold text-slate-400 py-2">
                                {{ __('no_more_transactions') }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State Card -->
                <div v-else class="bg-white border border-slate-100 rounded-[2rem] shadow-sm flex flex-col items-center justify-center py-20 text-center mb-32 md:mb-0">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                        <component :is="isFiltered ? Search : TrendingUp" class="w-8 h-8 text-slate-300" />
                    </div>
                    <h4 class="text-slate-900 font-bold text-base mb-1">{{ isFiltered ? __('no_results') : __('no_activity_detected') }}</h4>
                    <p class="text-slate-500 text-sm mb-4">
                        {{ isFiltered ? __('no_results_desc') : __('start_tracking_finances') }}
                    </p>
                    <button v-if="!isFiltered" @click="openAddModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-all active:scale-95 mx-auto">
                        <Plus class="w-4 h-4" />
                        <span>{{ __('add_first_transaction') }}</span>
                    </button>
                </div>
            </div>
        </Layout>

        <!-- Add/Edit Transaction Modal (Teleported to body) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto">
                <div @click="showModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                
                <div class="relative z-10 w-full max-w-2xl max-h-[92vh] flex flex-col bg-white rounded-t-[2rem] sm:rounded-3xl shadow-2xl overflow-hidden animate-in slide-in-from-bottom-10 sm:zoom-in-95 duration-300">
                    <!-- Header -->
                    <div class="p-6 md:p-8 border-b border-slate-200 flex items-center justify-between shrink-0">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">
                                {{ isEditing ? __('edit_transaction') : __('add_transaction') }}
                            </h2>
                            <p class="text-xs md:text-sm text-slate-500 font-medium">{{ isEditing ? __('edit_transaction_details') : __('fill_transaction_info') }}</p>
                        </div>
                        <button @click="showModal = false" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 transition-all">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
                    
                    <div class="p-6 md:p-8 overflow-y-auto flex-1">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Type Selection -->
                            <div class="flex p-1.5 bg-slate-100 rounded-2xl border border-slate-200">
                                <button type="button" @click="form.type = 'income'" :class="form.type === 'income' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'text-slate-500 hover:text-slate-900'" class="flex-1 py-3 rounded-xl transition-all font-bold text-sm flex items-center justify-center gap-2">
                                    <TrendingUp class="w-4 h-4" />
                                    {{ __('income') }}
                                </button>
                                <button type="button" @click="form.type = 'expense'" :class="form.type === 'expense' ? 'bg-rose-500 text-white shadow-lg shadow-rose-200' : 'text-slate-500 hover:text-slate-900'" class="flex-1 py-3 rounded-xl transition-all font-bold text-sm flex items-center justify-center gap-2">
                                    <TrendingDown class="w-4 h-4" />
                                    {{ __('expense') }}
                                </button>
                            </div>
                            <div v-if="form.errors.type" class="mt-1 text-[11px] font-bold text-rose-500 ml-1">
                                {{ form.errors.type }}
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('wallet') }}</label>
                                    <SearchableSelect 
                                        v-model="form.wallet_id" 
                                        :options="walletOptions" 
                                        :placeholder="__('select_wallet')" 
                                    />
                                    <div v-if="form.errors.wallet_id" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                                        {{ form.errors.wallet_id }}
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('date') }}</label>
                                    <input v-model="form.date" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm">
                                    <div v-if="form.errors.date" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                                        {{ form.errors.date }}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('amount') }}</label>
                                <CurrencyInput v-model="form.amount" :currency="selectedCurrency" :placeholder="selectedCurrency === 'USD' ? '$ 0' : 'Rp 0'" />
                                <div v-if="form.errors.amount" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                                    {{ form.errors.amount }}
                                </div>
                            </div>

                            <!-- Exchange Rate (only for non-IDR wallets) -->
                            <div v-if="showExchangeRate" class="p-5 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl border border-indigo-200 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-indigo-900">{{ __('exchange_rate') }} ({{ selectedCurrency }} to IDR)</label>
                                    <div class="flex items-center gap-1.5 px-3 py-1 bg-indigo-500 text-xs font-bold text-white rounded-full shadow-sm">
                                        <Activity class="w-3 h-3" />
                                        {{ __('auto') }}
                                    </div>
                                </div>
                                <input 
                                    v-model="form.exchange_rate" 
                                    type="number" 
                                    step="0.01"
                                    class="w-full bg-white border border-indigo-200 rounded-xl px-4 py-3 font-mono text-indigo-900 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold" 
                                    :placeholder="`1 ${selectedCurrency} = ... IDR`"
                                >
                                <div v-if="form.errors.exchange_rate" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                                    {{ form.errors.exchange_rate }}
                                </div>
                                <div v-if="conversionPreview" class="flex items-center gap-2 text-sm font-bold text-indigo-700">
                                    <span>{{ __('conversion') }}:</span>
                                    <span>{{ conversionPreview }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('category') }}</label>
                                <CategoryCombobox 
                                    v-model="form.category" 
                                    :categories="categories" 
                                    :type="form.type" 
                                    :placeholder="__('select_or_type_category')"
                                    :can-create="canCreateCategory"
                                    @limit-reached="showUpsellModal = true"
                                />
                                <div v-if="form.errors.category" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                                    {{ form.errors.category }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('description') }} <span class="text-slate-400 font-medium">({{ __('optional') }})</span></label>
                                <textarea v-model="form.description" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 min-h-[100px] text-sm font-medium text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none" :placeholder="__('details_placeholder')"></textarea>
                                <div v-if="form.errors.description" class="mt-2 text-[11px] font-bold text-rose-500 ml-1">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showModal = false" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-4 rounded-xl font-bold text-sm transition-all">
                                    {{ __('cancel') }}
                                </button>
                                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-4 rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl transition-all">
                                    {{ isEditing ? __('update_transaction') : __('save_transaction') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Transaction Detail Modal (Mobile) -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center p-0 sm:p-6">
                <div @click="showDetailModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                
                <div class="relative z-10 w-full max-w-full sm:max-w-sm bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl overflow-hidden animate-in slide-in-from-bottom-10 fade-in duration-300">
                    <div v-if="selectedDetailTransaction" class="p-6 space-y-6">
                        <!-- Header / Close -->
                        <div class="flex items-start justify-between">
                             <div class="space-y-1">
                                <h3 class="text-xs font-bold text-slate-400">{{ __('transaction_details') }}</h3>
                                <p class="text-xs font-semibold text-slate-500">
                                    {{ new Date(selectedDetailTransaction.date).toLocaleDateString($page.props.locale === 'id' ? 'id-ID' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                                </p>
                             </div>
                            <button @click="showDetailModal = false" class="p-2 -mr-2 -mt-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition-colors">
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Amount Display -->
                        <div class="text-center py-4">
                            <span :class="['text-2xl font-bold tracking-tight tabular-nums block', selectedDetailTransaction.type === 'expense' ? 'text-slate-900' : 'text-emerald-500']">
                                {{ selectedDetailTransaction.type === 'expense' ? '-' : '+' }} {{ new Intl.NumberFormat(selectedDetailTransaction.currency === 'USD' ? 'en-US' : 'id-ID', { style: 'currency', currency: selectedDetailTransaction.currency || 'IDR', maximumFractionDigits: 0 }).format(selectedDetailTransaction.amount) }}
                            </span>
                            <span v-if="selectedDetailTransaction.currency !== 'IDR' && selectedDetailTransaction.amount_in_base_currency" class="text-xs text-slate-400 font-bold mt-1 block">
                                ≈ {{ formatIDR(selectedDetailTransaction.amount_in_base_currency) }}
                            </span>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
                                <p class="text-[10px] font-bold text-slate-400">{{ __('wallet') }}</p>
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full" :class="getTypeColor(selectedDetailTransaction.wallet?.type)"></div>
                                    <p class="text-sm font-bold text-slate-700 truncate">{{ selectedDetailTransaction.wallet?.name }}</p>
                                </div>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
                                <p class="text-[10px] font-bold text-slate-400">{{ __('category') }}</p>
                                <span 
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold text-white mt-0.5"
                                    :class="selectedDetailTransaction.category?.color || 'bg-slate-500'"
                                >
                                    {{ selectedDetailTransaction.category ? selectedDetailTransaction.category.name : __('uncategorized') }}
                                </span>
                            </div>
                        </div>

                        <!-- Description -->
                         <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
                             <p class="text-[10px] font-bold text-slate-400">{{ __('description') }}</p>
                             <p class="text-sm font-medium text-slate-700 leading-relaxed break-words">
                                 {{ selectedDetailTransaction.description || __('no_description_provided') }}
                             </p>
                         </div>

                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-3 pt-2">
                             <button 
                                @click="openEditModal(selectedDetailTransaction); showDetailModal = false"
                                class="flex items-center justify-center gap-2 py-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl font-bold text-sm transition-colors"
                            >
                                <Pencil class="w-4 h-4" />
                                {{ __('edit') }}
                            </button>
                            <button 
                                @click="deleteTransaction(selectedDetailTransaction); showDetailModal = false"
                                class="flex items-center justify-center gap-2 py-3 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl font-bold text-sm transition-colors"
                            >
                                <Trash2 class="w-4 h-4" />
                                {{ __('delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Premium Upsell Modal -->
        <PremiumUpsellModal 
            :show="showUpsellModal" 
            :title="__('category_limit_reached')"
            :description="__('unlock_unlimited_categories')"
            @close="showUpsellModal = false" 
        />
    </template>
