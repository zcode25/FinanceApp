<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { 
    User, 
    Mail, 
    ShieldCheck, 
    ArrowLeft, 
    ArrowRight, 
    Sparkles,
    CheckCircle2,
    Crown,
    Rocket,
    Zap,
    Info
} from 'lucide-vue-next';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import { usePage } from '@inertiajs/vue3';
import { formatPrice } from '@/Utilities/plans';
import '@/../css/landing.css';

const page = usePage();
const __ = (key, replacements = {}) => {
    let translation = page.props.translations?.[key] || key;
    Object.keys(replacements).forEach(r => {
        translation = translation.replace(`:${r}`, replacements[r]);
    });
    return translation;
};
const props = defineProps({
    plan: Object,
    user: Object,
    existing_transaction: Object
});

const isProcessing = ref(false);
const promoCode = ref('');
const promoError = ref('');
const promoSuccess = ref('');
const isValidatingPromo = ref(false);
const basePrice = ref(props.plan.raw_price);
const discountAmount = ref(0);
const discountPercentage = ref(0);
const selectedMethod = ref('');
const pendingMessage = ref('');
const showConfirmModal = ref(false);

// Payment Persistence Logic
const lastSnapToken = ref(null);
const lastPaymentParams = ref(null);

const canResume = computed(() => {
    if (!lastSnapToken.value || !lastPaymentParams.value) return false;
    
    const currentParams = JSON.stringify({
        plan: props.plan.id,
        promo: promoCode.value,
        grand_total: grandTotal.value,
        payment_method: selectedMethod.value
    });
    
    return lastPaymentParams.value === currentParams;
});

// Reset promo state when user types to avoid confusion
watch(promoCode, () => {
    promoError.value = '';
    promoSuccess.value = '';
    discountAmount.value = 0;
    discountPercentage.value = 0;
});

const grandTotal = computed(() => {
    return Math.max(0, basePrice.value - discountAmount.value);
});

const formatCurrency = (value) => {
    return formatPrice(value);
};

const applyPromo = () => {
    if (!promoCode.value) return;

    isValidatingPromo.value = true;
    promoError.value = '';
    promoSuccess.value = '';
    discountAmount.value = 0;

    axios.post(route('subscription.promo.validate'), {
        promo_code: promoCode.value,
        plan_id: props.plan.id
    })
    .then(response => {
        if (response.data.valid) {
            discountAmount.value = response.data.discount_amount;
            discountPercentage.value = response.data.discount_percentage;
            promoSuccess.value = response.data.message;
            promoError.value = '';
        } else {
            promoError.value = response.data.message || 'Invalid promo code';
            promoSuccess.value = '';
            discountAmount.value = 0;
        }
    })
    .catch(error => {
        promoError.value = error.response?.data?.message || 'Error validating promo code';
    })
    .finally(() => {
        isValidatingPromo.value = false;
    });
};

// Locking logic: Once lastSnapToken exists, manual resets are disabled to prevent order spam
// No automatic resets on changes; choices are locked until transaction is complete or expires

onMounted(() => {
    // Dynamically load Midtrans Snap JS
    if (!window.snap) {
        const isProduction = page.props.midtrans_is_production;
        const scriptId = 'midtrans-snap-js';
        
        if (!document.getElementById(scriptId)) {
            const script = document.createElement('script');
            script.id = scriptId;
            script.src = isProduction 
                ? 'https://app.midtrans.com/snap/snap.js' 
                : 'https://app.sandbox.midtrans.com/snap/snap.js';
            script.setAttribute('data-client-key', page.props.midtrans_client_key);
            script.async = true;
            document.head.appendChild(script);
        }
    }

    // Initialize state from existing transaction if provided by backend
    if (props.existing_transaction) {
        lastSnapToken.value = props.existing_transaction.snap_token;
        promoCode.value = props.existing_transaction.promo_code || '';
        selectedMethod.value = props.existing_transaction.payment_method || '';
        discountAmount.value = props.existing_transaction.discount_amount || 0;
        
        // Re-calculate/validate to restore success messages and state
        if (props.existing_transaction.promo_code) {
            applyPromo();
        }

        // Reconstruct the exact params used to lock the UI
        lastPaymentParams.value = JSON.stringify({
            plan: props.plan.id,
            promo: props.existing_transaction.promo_code || '',
            grand_total: (props.existing_transaction.amount),
            payment_method: selectedMethod.value
        });

        pendingMessage.value = __('payment_delayed_notice');
    }
});

const paymentMethods = [
    { 
        id: 'bni', 
        name: 'BNI VA', 
        type: 'virtual_account', 
        logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f0/Bank_Negara_Indonesia_logo_%282004%29.svg/512px-Bank_Negara_Indonesia_logo_%282004%29.svg.png'
    },
    { 
        id: 'bri', 
        name: 'BRI VA', 
        type: 'virtual_account', 
        logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/BRI_2025_%28with_full_name%29.svg/512px-BRI_2025_%28with_full_name%29.svg.png'
    },
    { 
        id: 'mandiri', 
        name: 'Mandiri VA', 
        type: 'virtual_account', 
        logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/Bank_Mandiri_logo_2016.svg/512px-Bank_Mandiri_logo_2016.svg.png'
    },
    { 
        id: 'permata', 
        name: 'Permata VA', 
        type: 'virtual_account', 
        logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/ff/Permata_Bank_%282024%29.svg/512px-Permata_Bank_%282024%29.svg.png'
    },
    { 
        id: 'cimb', 
        name: 'CIMB VA', 
        type: 'virtual_account', 
        logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/CIMB_Niaga_logo.svg/512px-CIMB_Niaga_logo.svg.png'
    },
    { 
        id: 'gopay', 
        name: 'GoPay', 
        type: 'ewallet', 
        logo: 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Gopay_logo.svg/512px-Gopay_logo.svg.png'
    },
];

const handlePaymentClick = () => {
    if (canResume.value) {
        showSnapPopup(lastSnapToken.value);
        return;
    }
    showConfirmModal.value = true;
};

const confirmPayment = () => {
    showConfirmModal.value = false;
    initiatePayment();
};

const initiatePayment = async () => {
    if (!selectedMethod.value) {
        alert(__('select_payment_method_alert'));
        return;
    }

    // 1. Prepare Current Parameters for Change Detection
    const currentParams = JSON.stringify({
        plan: props.plan.id,
        promo: promoCode.value,
        grand_total: grandTotal.value,
        payment_method: selectedMethod.value
    });

    isProcessing.value = true;
    promoError.value = '';
    pendingMessage.value = '';
    
    try {
        const response = await axios.post(route('subscription.checkout'), {
            plan: props.plan.id,
            promo_code: promoSuccess.value ? promoCode.value : null,
            payment_method: selectedMethod.value
        });

            if (response.data.is_free) {
                // Direct activation success
                window.location.href = route('dashboard', { upgrade_success: 1 });
            } else if (response.data.snap_token) {
                // Save for potential resume
                lastSnapToken.value = response.data.snap_token;
                lastPaymentParams.value = currentParams;
                
                showSnapPopup(response.data.snap_token);
            }
    } catch (error) {
        promoError.value = error.response?.data?.message || __('payment_failed_error');
    } finally {
        isProcessing.value = false;
    }
};

const showSnapPopup = (token) => {
    window.snap.pay(token, {
        onSuccess: function(result) {
            window.location.href = route('dashboard', { upgrade_success: 1 });
        },
        onPending: function(result) {
            pendingMessage.value = __('payment_pending_instructions');
            promoError.value = '';
        },
        onError: function(result) {
            pendingMessage.value = '';
            promoError.value = __('payment_failed_alert');
            // Reset persistence on hard error
            lastSnapToken.value = null;
            lastPaymentParams.value = null;
        },
        onClose: function() {
            pendingMessage.value = __('payment_delayed_notice');
            promoError.value = '';
        }
    });
};
</script>

<template>
    <Head :title="__('confirm_upgrade')" />
    
    <div class="min-h-screen bg-white font-sans flex flex-col items-center justify-center p-4 pb-20 sm:p-6 sm:pb-24 lg:p-12 relative overflow-hidden">
        <!-- Background Decorations -->
        <div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-indigo-50/50 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-purple-50/40 rounded-full blur-[100px] pointer-events-none"></div>

        <!-- Focused Header -->
        <header class="w-full max-w-6xl flex items-center justify-between mb-6 md:mb-10 relative z-10">
            <div class="flex items-center gap-2 overflow-hidden">
                <div class="flex items-center justify-center">
                    <img src="/img/logo_vibefinance.png" class="h-6 md:h-7 w-auto object-contain" alt="VibeFinance Logo">
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-lg md:text-xl tracking-tight text-slate-900" style="font-family: Outfit, sans-serif;">
                        <span class="font-semibold">Vibe</span><span class="font-light text-indigo-600">Finance</span>
                    </span>
                    <span class="text-[9px] font-medium text-slate-400">Powered by terasweb.id</span>
                </div>
            </div>
            
            <Link 
                :href="route('dashboard')" 
                prefetch
                class="flex items-center gap-2 px-5 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-sm rounded-xl transition-all active:scale-95 border border-slate-100 shadow-sm whitespace-nowrap"
            >
                <ArrowLeft class="w-4 h-4" />
                {{ __('return_to_dashboard') }}
            </Link>
        </header>

        <div class="w-full max-w-6xl relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 md:gap-8 items-start">
                <!-- Left Column: User & Payment Details -->
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                        <div class="px-6 md:px-10 pt-8 pb-6 border-b border-slate-50 bg-white/50 backdrop-blur-md">
                            <h1 class="text-xl md:text-2xl font-bold text-slate-900 leading-tight mb-1">{{ __('secure_checkout') }}</h1>
                            <p class="text-xs md:text-sm text-slate-500 font-medium">{{ __('finalize_premium') }}</p>
                        </div>

                        <div class="p-6 md:p-10 space-y-8">
                            <!-- User Info Section -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-bold text-slate-400 pl-1">{{ __('billing_info') }}</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-5 transition-all hover:border-indigo-100 group">
                                        <div class="w-12 h-12 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 group-hover:text-indigo-600 group-hover:border-indigo-50 transition-colors shadow-sm">
                                            <User class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400">{{ __('account_name') }}</p>
                                            <p class="text-base font-bold text-slate-900">{{ user.name }}</p>
                                        </div>
                                    </div>
                                    <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-5 transition-all hover:border-indigo-100 group">
                                        <div class="w-12 h-12 rounded-2xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 group-hover:text-indigo-600 group-hover:border-indigo-50 transition-colors shadow-sm">
                                            <Mail class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400">{{ __('billing_email') }}</p>
                                            <p class="text-base font-bold text-slate-900">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method Section -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-bold text-slate-400 pl-1">{{ __('payment_method') }}</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                    <button 
                                        v-for="method in paymentMethods" 
                                        :key="method.id"
                                        @click="selectedMethod = method.id"
                                        :disabled="!!lastSnapToken"
                                        :class="[
                                            'p-6 rounded-[1.5rem] border text-left transition-all relative overflow-hidden group min-h-[130px] flex flex-col justify-between',
                                            selectedMethod === method.id 
                                                ? 'bg-white border-indigo-600 text-slate-900 ring-8 ring-indigo-50 shadow-xl shadow-indigo-100/50' 
                                                : 'bg-white border-slate-100 text-slate-900 hover:border-indigo-200 hover:bg-slate-50 shadow-sm',
                                            !!lastSnapToken ? 'opacity-50 cursor-not-allowed grayscale-[0.5]' : ''
                                        ]"
                                    >
                                        <div class="space-y-3">
                                            <div :class="['w-16 h-12 rounded-2xl flex items-center justify-center p-2.5 bg-white shadow-sm border border-slate-50 transition-all group-hover:scale-110', selectedMethod === method.id ? 'border-indigo-100 scale-105' : '']">
                                                <img :src="method.logo" :alt="method.name" class="w-full h-full object-contain" />
                                            </div>
                                            <div>
                                                <p :class="['text-[10px] font-bold mb-0.5', selectedMethod === method.id ? 'text-indigo-600' : 'text-slate-400']">{{ __(method.type) }}</p>
                                                <p class="text-sm font-bold leading-tight">{{ method.name }}</p>
                                            </div>
                                        </div>

                                        <!-- Selection Indicator -->
                                        <div v-if="selectedMethod === method.id" class="absolute top-4 right-4 animate-in zoom-in-50 duration-300">
                                            <div class="bg-indigo-600 rounded-full p-1 shadow-md">
                                                <CheckCircle2 class="w-4 h-4 text-white" />
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security & Trust -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-8 py-4 opacity-70">
                        <div class="flex items-center gap-4">
                            <ShieldCheck class="w-6 h-6 text-indigo-600" />
                            <div class="text-left">
                                <p class="text-[10px] font-bold text-slate-900">Bank-Grade</p>
                                <p class="text-xs font-bold text-slate-500">SSL Secure Transaction</p>
                            </div>
                        </div>
                        <div class="h-8 w-[1px] bg-slate-200 hidden sm:block"></div>
                        <div class="flex items-center gap-4">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/9/9d/Midtrans.png" alt="Midtrans" class="h-8 object-contain grayscale hover:grayscale-0 transition-all" />
                            <p class="text-[10px] font-bold text-slate-400">Official Payment Gateway</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-[2rem] md:rounded-[2.5rem] p-6 md:p-10 text-white shadow-2xl shadow-purple-200/40 relative overflow-hidden group">
                        <!-- Decorative mesh -->
                        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-white/10 rounded-full blur-[80px]"></div>
                        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-60 h-60 bg-white/10 rounded-full blur-[60px]"></div>

                        <div class="relative z-10">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 text-white rounded-full text-[10px] font-bold mb-6 backdrop-blur-md border border-white/10">
                                <Sparkles class="w-3.5 h-3.5 text-amber-300" /> {{ __('subscription_plan') }}
                            </div>

                            <div class="flex items-center gap-6 mb-8">
                                <div class="w-16 h-16 rounded-[1.5rem] bg-white/10 border border-white/20 flex items-center justify-center shadow-lg backdrop-blur-md">
                                    <Crown v-if="parseInt(plan.id) === 3" class="w-8 h-8 text-white" />
                                    <Zap v-else-if="parseInt(plan.id) === 4" class="w-8 h-8 text-white" />
                                    <Rocket v-else class="w-8 h-8 text-white" />
                                </div>
                                <div>
                                    <h2 class="text-xl md:text-2xl font-bold leading-none mb-1 text-white">{{ plan.name }}</h2>
                                    <p class="text-purple-100 font-bold text-xs leading-tight">{{ plan.description }}</p>
                                </div>
                            </div>

                            <div class="space-y-3 mb-8">
                                <div class="flex items-center justify-between py-3 border-b border-white/10">
                                    <span class="text-xs font-bold text-purple-100">{{ __('pricing_plan') }}</span>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-white">{{ plan.price }}</p>
                                        <p class="text-[9px] font-bold text-purple-200 leading-tight">{{ plan.period }}</p>
                                    </div>
                                </div>
                                
                                <!-- Features List -->
                                <div class="grid grid-cols-1 gap-2.5 py-1">
                                    <div class="flex items-center gap-2.5 text-[11px] font-bold text-purple-50">
                                        <div class="w-4.5 h-4.5 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                            <CheckCircle2 class="w-3 h-3 text-white" />
                                        </div>
                                        {{ __('instant_activation') }}
                                    </div>
                                    <div class="flex items-center gap-2.5 text-[11px] font-bold text-purple-50">
                                        <div class="w-4.5 h-4.5 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                                            <CheckCircle2 class="w-3 h-3 text-white" />
                                        </div>
                                        {{ __('unlimited_data_insights') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Promo Code Input (Inside Card) -->
                            <div class="mb-8 animate-in fade-in duration-500 delay-150">
                                <div class="flex gap-2">
                                    <div class="relative group flex-1">
                                        <input 
                                            v-model="promoCode"
                                            type="text"
                                            :disabled="isValidatingPromo || !!lastSnapToken"
                                            class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-[11px] font-bold placeholder-white/40 focus:ring-2 focus:ring-white focus:border-white transition-all text-white disabled:opacity-50 disabled:cursor-not-allowed"
                                            :placeholder="__('promo_code')"
                                            @keyup.enter="applyPromo"
                                        >
                                        <Sparkles class="absolute right-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white/20 group-focus-within:text-amber-300 transition-colors pointer-events-none" />
                                    </div>
                                    <button 
                                        @click="applyPromo"
                                        :disabled="!promoCode || isValidatingPromo || !!lastSnapToken"
                                        class="px-5 bg-white text-purple-600 font-bold text-[9px] rounded-xl hover:bg-purple-50 transition-all active:scale-95 disabled:opacity-50"
                                    >
                                        <span v-if="isValidatingPromo">...</span>
                                        <span v-else>{{ __('apply') }}</span>
                                    </button>
                                </div>
                                <p v-if="promoError" class="text-[10px] font-bold text-rose-300 mt-2 px-3 animate-in fade-in duration-300">{{ promoError }}</p>
                                <p v-if="promoSuccess" class="text-[10px] font-bold text-emerald-300 mt-2 px-3 animate-in fade-in duration-300">{{ promoSuccess }}</p>
                            </div>

                            <!-- Final Summary -->
                            <div class="p-6 bg-black/10 rounded-2xl border border-white/5 mb-8 backdrop-blur-sm shadow-inner">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-bold text-purple-100">{{ __('subtotal') }}</span>
                                    <span class="text-xs font-bold text-white">{{ plan.price }}</span>
                                </div>
                                
                                <!-- Dynamic Discount Line -->
                                <div v-if="discountAmount > 0" class="flex items-center justify-between mb-3 animate-in slide-in-from-top-2 duration-300 border-b border-white/5 pb-3">
                                    <span class="text-xs font-bold text-emerald-300 flex items-center gap-2">
                                        <Sparkles class="w-3 h-3" /> {{ __('promo_discount') }}
                                        <span v-if="discountPercentage" class="bg-emerald-500/20 px-1.5 py-0.5 rounded-md text-[9px] ml-1 border border-emerald-500/30">
                                            -{{ discountPercentage }}%
                                        </span>
                                    </span>
                                    <span class="text-xs font-bold text-emerald-400">-{{ formatCurrency(discountAmount) }}</span>
                                </div>

                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-xs font-bold text-purple-100">{{ __('taxes_fees') }}</span>
                                    <span class="text-xs font-bold text-emerald-400">{{ __('included') }}</span>
                                </div>
                                <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                                    <span class="text-sm font-bold text-white">{{ __('grand_total') }}</span>
                                    <span class="text-2xl font-bold text-white">{{ formatCurrency(grandTotal) }}</span>
                                </div>
                            </div>

                            <!-- Pending/Info Message specifically above button -->
                            <div v-if="pendingMessage" class="mb-5 p-4 bg-amber-400/10 border border-amber-400/20 rounded-[1.25rem] animate-in fade-in slide-in-from-top-2 duration-300">
                                <div class="flex flex-col gap-3">
                                    <div class="flex gap-3 text-left">
                                        <Info class="w-4 h-4 text-amber-300 shrink-0 mt-0.5" />
                                        <p class="text-[11px] font-bold text-amber-50 leading-relaxed">
                                            {{ pendingMessage }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button 
                                @click="handlePaymentClick"
                                :disabled="isProcessing || !selectedMethod"
                                class="w-full py-5 bg-white text-purple-600 rounded-[1.5rem] font-bold text-sm shadow-xl shadow-purple-900/20 hover:bg-slate-50 transition-all flex items-center justify-center gap-3 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed group/pay"
                            >
                                <template v-if="isProcessing">
                                    <div class="w-5 h-5 border-4 border-slate-200 border-t-purple-600 rounded-full animate-spin"></div>
                                    {{ __('processing') }}
                                </template>
                                <template v-else>
                                    <template v-if="grandTotal === 0">
                                        {{ __('claim_premium_now') }}
                                    </template>
                                    <template v-else>
                                        {{ canResume ? __('continue_payment') : __('complete_payment') }}
                                    </template>
                                    <ArrowRight class="w-5 h-5 group-hover/pay:translate-x-1 transition-transform" />
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md">
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                    appear
                >
                    <div class="bg-white rounded-[2.5rem] w-full max-w-md overflow-hidden shadow-2xl relative">
                        <!-- Decorative Header -->
                        <div class="h-32 bg-gradient-to-br from-purple-600 to-indigo-700 relative overflow-hidden flex items-center justify-center">
                            <div class="absolute inset-0 opacity-20">
                                <div class="absolute top-0 left-0 w-32 h-32 bg-white rounded-full blur-3xl -translate-x-12 -translate-y-12"></div>
                                <div class="absolute bottom-0 right-0 w-32 h-32 bg-white rounded-full blur-3xl translate-x-12 translate-y-12"></div>
                            </div>
                            <div class="w-16 h-16 bg-white/20 backdrop-blur-xl rounded-2xl flex items-center justify-center border border-white/20 shadow-xl">
                                <ShieldCheck class="w-8 h-8 text-white" />
                            </div>
                        </div>

                        <div class="p-8 md:p-10 text-center">
                            <h3 class="text-2xl font-bold text-slate-900 mb-3">{{ __('checkout_confirm_title') }}</h3>
                            <p class="text-sm font-bold text-slate-500 leading-relaxed mb-8">
                                {{ __('checkout_confirm_text') }}
                            </p>

                            <div class="flex flex-col gap-3">
                                <button 
                                    @click="confirmPayment"
                                    class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95"
                                >
                                    {{ __('yes_continue') }}
                                </button>
                                <button 
                                    @click="showConfirmModal = false"
                                    class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold text-sm hover:bg-slate-200 transition-all active:scale-95"
                                >
                                    {{ __('cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Teleport>
    </div>
</template>
