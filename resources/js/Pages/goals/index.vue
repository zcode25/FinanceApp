<script setup>
import Layout from '../../Shared/Layout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";
import { 
    Target, 
    TrendingUp, 
    TrendingDown, 
    Zap, 
    ShieldCheck, 
    Wallet as WalletIcon, 
    Calendar,
    Plus,
    MoreVertical,
    Edit2,
    Trash2,
    ChevronRight,
    AlertCircle,
    CheckCircle2,
    X,
    Info
} from 'lucide-vue-next';
import { formatCurrency } from '../../Utilities/formatCurrency';
import CurrencyInput from '../../Shared/CurrencyInput.vue';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';
import UpgradeModal from '../../Shared/UpgradeModal.vue';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const driverObj = ref(null);
const skipHTML = computed(() => `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">${__('skip_tutorial')}</button>
</div>`);

const props = defineProps({
    goals: Array,
    wallets: Array,
    currentExchangeRate: Number,
});

// Optimistic UI State
const localGoals = ref([...props.goals]);

watch(() => props.goals, (newVal) => {
    localGoals.value = [...newVal];
}, { deep: true });


const showModal = ref(false);
const showUpgradeModal = ref(false);
const showDetailsModal = ref(false);
const isEditing = ref(false);
const editingGoal = ref(null);
const selectedGoalForDetails = ref(null);

const form = useForm({
    name: '',
    target_amount: '',
    type: 'saving',
    target_date: '',
    start_date: new Date().toISOString().split('T')[0],
    notes: '',
    currency: 'IDR',
    wallet_ids: [],
});

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

const openAddModal = () => {
    // Proactive Limit Check for Starter Plan
    if (!page.props.auth.user.is_premium && props.goals.length >= 1) {
        showUpgradeModal.value = true;
        return;
    }

    isEditing.value = false;
    editingGoal.value = null;
    form.clearErrors();
    form.name = '';
    form.target_amount = '';
    form.type = 'saving';
    form.target_date = '';
    form.start_date = new Date().toISOString().split('T')[0];
    form.notes = '';
    form.currency = 'IDR';
    form.wallet_ids = [];
    showModal.value = true;
};

const openEditModal = (goal) => {
    isEditing.value = true;
    editingGoal.value = goal;
    form.name = goal.name;
    form.target_amount = goal.target_amount;
    form.type = goal.type;
    form.target_date = goal.target_date ? new Date(goal.target_date).toISOString().split('T')[0] : '';
    form.start_date = goal.start_date ? new Date(goal.start_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0];
    form.notes = goal.notes;
    form.currency = goal.currency;
    form.wallet_ids = goal.wallets.map(w => w.id);
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    isEditing.value = false;
    editingGoal.value = null;
    form.clearErrors();
    form.name = '';
    form.target_amount = '';
    form.type = 'saving';
    form.target_date = '';
    form.start_date = new Date().toISOString().split('T')[0];
    form.notes = '';
    form.currency = 'IDR';
    form.wallet_ids = [];
};

const openDetails = (goal) => {
    selectedGoalForDetails.value = goal;
    showDetailsModal.value = true;
};

const submitForm = () => {
    if (isEditing.value && editingGoal.value) {
        // Optimistic Update
        const originalGoals = JSON.parse(JSON.stringify(localGoals.value));
        const index = localGoals.value.findIndex(g => g?.id === editingGoal.value?.id);
        
        if (index !== -1) {
            localGoals.value[index] = {
                ...localGoals.value[index],
                name: form.name,
                target_amount: form.target_amount,
                type: form.type,
                target_date: form.target_date,
                start_date: form.start_date,
                notes: form.notes,
                currency: form.currency,
                wallets: props.wallets.filter(w => form.wallet_ids.includes(w.id)) // Optimistic wallet link
            };
        }
        
        const goalId = editingGoal.value.id;
        showModal.value = false; // Just hide, don't clear form yet

        form.put(route('goals.update', goalId), {
            onSuccess: () => {
                closeModal(); // Now clear form
                Toast.fire({
                    icon: 'success',
                    title: __('goal_updated'),
                    background: '#ffffff',
                    color: '#1e293b',
                    customClass: {
                        popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                        title: '!text-sm !font-bold !text-slate-900',
                    }
                });
            },
            onError: () => {
                // Rollback
                localGoals.value = originalGoals;
                showModal.value = true; // Re-open modal
                Toast.fire({ icon: 'error', title: 'Failed to update goal' });
            }
        });
    } else {
        form.post(route('goals.store'), {
            onSuccess: () => {
                closeModal();
                Toast.fire({
                    icon: 'success',
                    title: __('goal_created'),
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

const deleteGoal = (goal) => {
    Swal.fire({
        title: __('delete_goal_title'),
        text: __('delete_goal_text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: __('yes_delete'),
        cancelButtonText: __('cancel'),
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
            // Optimistic Delete
            const originalGoals = [...localGoals.value];
            localGoals.value = localGoals.value.filter(g => g.id !== goal.id);

            router.delete(route('goals.destroy', goal.id), {
                onSuccess: () => {
                    Toast.fire({
                        icon: 'success',
                        title: __('goal_deleted'),
                        background: '#ffffff',
                        color: '#1e293b',
                        customClass: {
                            popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                            title: '!text-sm !font-bold !text-slate-900',
                        }
                    });
                },
                onError: () => {
                    // Rollback
                    localGoals.value = originalGoals;
                    Toast.fire({ icon: 'error', title: 'Failed to delete goal' });
                }
            });
        }
    });
};

const totalTarget = computed(() => {
    return localGoals.value.reduce((sum, goal) => {
        let amount = parseFloat(goal.target_amount);
        if (goal.currency === 'USD' && props.currentExchangeRate) {
            amount = amount * props.currentExchangeRate;
        }
        return sum + amount;
    }, 0);
});
const totalSaved = computed(() => {
    return localGoals.value.reduce((sum, goal) => {
        // Calculate saved amount for this goal based on linked wallets w/ conversion
        const goalSavedIDR = goal.wallets.reduce((wSum, wallet) => {
             let wBalance = parseFloat(wallet.balance);
             if (wallet.currency === 'USD' && props.currentExchangeRate) {
                 return wSum + (wBalance * props.currentExchangeRate);
             }
             return wSum + wBalance;
        }, 0);
        
        return sum + goalSavedIDR;
    }, 0);
});
const overallProgress = computed(() => totalTarget.value > 0 ? (totalSaved.value / totalTarget.value) * 100 : 0);

const getGoalIcon = (type) => {
    switch (type) {
        case 'emergency': return ShieldCheck;
        case 'retirement': return Zap;
        case 'saving': return TrendingUp;
        default: return Target;
    }
};

const getGoalTypeLabel = (type) => {
    const map = {
        'emergency': 'emergency_fund',
        'retirement': 'retirement',
        'saving': 'general_saving',
        'custom': 'custom'
    };
    return map[type] || type;
};

const getGoalColor = (type) => {
    switch (type) {
        case 'emergency': return 'from-rose-500 to-pink-600 shadow-rose-200';
        case 'retirement': return 'from-purple-500 to-violet-600 shadow-purple-200';
        case 'saving': return 'from-emerald-500 to-teal-600 shadow-emerald-200';
        default: return 'from-indigo-500 to-indigo-600 shadow-indigo-200';
    }
};

const getProgressColor = (progress) => {
    if (progress >= 100) return 'bg-emerald-500';
    if (progress >= 50) return 'bg-indigo-500';
    return 'bg-amber-500';
};

const getWalletColor = (type) => {
    switch (type) {
        case 'cash': return 'bg-emerald-500';
        case 'ewallet': return 'bg-violet-500';
        case 'bank': return 'bg-orange-500';
        default: return 'bg-slate-500';
    }
};

const calculateMonthlySavings = (goal) => {
    if (!goal.target_date) return null;
    const today = new Date();
    const target = new Date(goal.target_date);
    const monthsDiff = (target.getFullYear() - today.getFullYear()) * 12 + (target.getMonth() - today.getMonth());
    
    if (monthsDiff <= 0) return null;
    
    const remaining = goal.target_amount - getGoalSavedAmount(goal);
    return remaining > 0 ? remaining / monthsDiff : 0;
};

const getGoalSavedAmount = (goal) => {
    return goal.wallets.reduce((wSum, wallet) => {
             let wBalance = parseFloat(wallet.balance);
             if (wallet.currency === 'USD' && props.currentExchangeRate) {
                 return wSum + (wBalance * props.currentExchangeRate);
             }
             return wSum + wBalance;
        }, 0);
};

const openDetailsMobile = (goal) => {
    if (window.innerWidth < 768) {
        selectedGoalForDetails.value = goal;
        showDetailsModal.value = true;
    }
};

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
                    localStorage.setItem('tour_state', 'hub_to_categories');
                    router.visit('/dashboard');
                } else {
                    localStorage.setItem('tour_state', 'categories_setup');
                    router.visit('/categories');
                }
                driverObj.value.destroy();
            } else {
                driverObj.value.moveNext();
            }
        },
        steps: [
            {
                element: '#step-goal-summary',
                popover: {
                    title: `<span class="text-xl font-bold">${__('tour_goal_summary_title')}</span>`,
                    description: __('tour_goal_summary_desc') + skipHTML.value,
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: '#step-add-goal',
                popover: {
                    title: `<span class="text-xl font-bold">${__('tour_add_goal_title')}</span>`,
                    description: __('tour_add_goal_desc') + skipHTML.value,
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: '#tour-goals-section',
                popover: {
                    title: `<span class="text-xl font-bold">${__('tour_goals_section_title')}</span>`,
                    description: __('tour_goals_section_desc') + skipHTML.value,
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: isMobile ? '#mobile-nav-home' : '#nav-categories',
                popover: {
                    title: isMobile ? __('tour_return_hub') : `<span class="text-xl font-bold">${__('tour_return_categories_title')}</span>`,
                    description: (isMobile 
                        ? __('tour_return_hub_desc')
                        : __('tour_return_categories_desc')) + skipHTML.value,
                    side: "bottom",
                    align: 'start'
                }
            }
        ]
    });

    driverObj.value.drive();
};

const checkTourTriggers = () => {
    const tourState = localStorage.getItem('tour_state');
    const tourCompleted = page.props.auth.user.has_completed_tour;
    const catchUpStates = [
        'welcome', 'wallet_setup', 'transaction_setup', 'dashboard_explanation', 
        'analysis_intro', 'budget_setup', 'hub_to_goals', 'goals_setup'
    ];
    

    // Guard against duplicate triggers
    if (driverObj.value && document.querySelector('.driver-popover')) {
        return;
    }

    if (!tourState || (tourState && catchUpStates.includes(tourState))) {
        if (!tourState || tourState !== 'goals_setup') {
            if (!tourState && tourCompleted) {
                return;
            }
            localStorage.setItem('tour_state', 'goals_setup');
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
    <Head :title="__('goals_title')" />

    <Layout>
        <!-- HEADER -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-30">
            <div class="space-y-1">
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('goals_title') }}</h1>
                <p class="text-sm md:text-base text-slate-500 font-medium">{{ __('goals_desc') }}</p>
            </div>
            
            <button 
                id="step-add-goal"
                @click="openAddModal"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 active:scale-95 transition-all flex items-center justify-center gap-2 group"
            >
                <Plus class="w-4 h-4 group-hover:rotate-90 transition-transform duration-300" />
                <span>{{ __('add_goal') }}</span>
            </button>
        </header>

            <!-- SUMMARY GRID -->
            <div id="step-goal-summary" class="flex overflow-x-auto snap-x snap-mandatory md:grid md:grid-cols-3 gap-4 md:gap-6 mb-8 -mx-4 px-4 md:mx-0 md:px-0 no-scrollbar md:overflow-visible">
                <!-- Total Target -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-slate-800 to-slate-900 text-white shadow-lg shadow-slate-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Target class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <Target class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('total_targets') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(totalTarget, 'IDR').split(',')[0] }}</h2>
                            <p class="text-slate-400 font-medium text-sm">{{ __('combined_goals') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Saved -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <WalletIcon class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <WalletIcon class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('total_saved') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(totalSaved, 'IDR').split(',')[0] }}</h2>
                            <p class="text-indigo-100 font-medium text-sm">{{ __('allocated_goals') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Overall Progress -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Zap class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <Zap class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-lg text-white/90 tracking-tight">{{ __('overall_health') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-3xl font-bold tracking-tight tabular-nums">{{ Math.round(overallProgress) }}%</h2>
                            <p class="text-emerald-100 font-medium text-sm">{{ __('avg_completion') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GOAL LIST -->
            <!-- GOAL LIST SECTION -->
            <div id="tour-goals-section">
                <div v-if="localGoals.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8 mb-24">
                <div v-for="goal in localGoals" :key="goal.id" @click="openDetailsMobile(goal)" class="group bg-white border border-slate-100 rounded-[1.5rem] md:rounded-[2.5rem] p-6 md:p-8 hover:shadow-xl hover:shadow-slate-100 transition-all duration-500 relative overflow-hidden cursor-pointer md:cursor-default active:scale-[0.98] active:md:scale-100">

                    <div class="flex items-start justify-between relative z-10">
                        <div class="flex gap-4 md:gap-6">
                            <!-- Goal Icon -->
                            <div 
                                class="w-14 h-14 md:w-16 md:h-16 rounded-2xl md:rounded-3xl flex items-center justify-center bg-gradient-to-br text-white shadow-lg shrink-0"
                                :class="getGoalColor(goal.type)"
                            >
                                <component :is="getGoalIcon(goal.type)" class="w-6 h-6 md:w-8 md:h-8" />
                            </div>

                            <div class="space-y-1 md:space-y-2">
                                <h3 class="text-lg md:text-xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-1">{{ goal.name }}</h3>
                                <div class="flex items-center gap-2 md:gap-3 flex-wrap">
                                    <span class="px-2 md:px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[10px] font-bold capitalize tracking-wider">{{ __(getGoalTypeLabel(goal.type)) }}</span>
                                    <span v-if="goal.target_date" class="text-xs md:text-sm text-slate-400 flex items-center gap-1 font-medium">
                                        <Calendar class="w-3.5 h-3.5" />
                                        {{ new Date(goal.target_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                    </span>
                                </div>
                            </div>
                        </div>




                        <!-- Desktop Actions (Top Right) -->
                        <div class="hidden md:flex items-center gap-2">
                            <button 
                                @click.stop="openEditModal(goal)"
                                class="p-2.5 rounded-xl bg-slate-50 hover:bg-white hover:shadow-md border border-slate-100 transition-all text-slate-400 hover:text-indigo-600"
                                :title="__('edit')"
                            >
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button 
                                @click.stop="deleteGoal(goal)"
                                class="p-2.5 rounded-xl bg-slate-50 hover:bg-white hover:shadow-md border border-slate-100 transition-all text-slate-400 hover:text-rose-600"
                                :title="__('delete')"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                            <button 
                                @click.stop="openDetails(goal)"
                                class="p-2.5 rounded-xl bg-slate-50 hover:bg-white hover:shadow-md border border-slate-100 transition-all text-slate-400 hover:text-indigo-600"
                                :title="__('view_details')"
                            >
                                <MoreVertical class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar Section -->
                    <div class="mt-8 md:mt-10 space-y-4 md:space-y-6 relative z-10">
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <p class="text-xs md:text-sm font-bold text-slate-400">{{ __('progress') }}</p>
                                <div class="flex items-baseline gap-1 md:gap-1.5">
                                    <span class="text-xl md:text-2xl font-bold text-slate-800 tracking-tight leading-none">{{ formatCurrency(getGoalSavedAmount(goal), goal.currency).split(',')[0] }}</span>
                                    <span class="text-xs md:text-sm font-bold text-slate-400">/ {{ formatCurrency(goal.target_amount, goal.currency).split(',')[0] }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm md:text-base font-bold text-slate-900 leading-none">{{ Math.round((getGoalSavedAmount(goal) / goal.target_amount) * 100) }}%</span>
                            </div>
                        </div>

                        <div class="h-3 md:h-4 bg-slate-100 rounded-full overflow-hidden shadow-inner border border-slate-200/50">
                            <div 
                                class="h-full rounded-full transition-all duration-1000 ease-out relative overflow-hidden"
                                :class="getProgressColor((getGoalSavedAmount(goal) / goal.target_amount) * 100)"
                                :style="{ width: `${Math.min((getGoalSavedAmount(goal) / goal.target_amount) * 100, 100)}%` }"
                            >
                                <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite] skew-x-12"></div>
                            </div>
                        </div>

                        <div class="pt-4 md:pt-6 border-t border-slate-100 grid md:grid-cols-2 gap-4 md:gap-6">
                            <div class="flex items-center justify-between">
                                <div class="flex -space-x-2 md:-space-x-3">
                                        <div v-for="wallet in goal.wallets.slice(0, 3)" :key="wallet.id" 
                                            class="w-6 h-6 md:w-8 md:h-8 rounded-full border-2 border-white flex items-center justify-center text-[8px] md:text-[10px] font-bold text-white ring-2 ring-slate-50 transition-transform hover:scale-110 hover:z-10"
                                            :class="getWalletColor(wallet.type)"
                                            :title="wallet.name"
                                        >
                                            {{ wallet.name.charAt(0) }}
                                        </div>
                                        <div v-if="goal.wallets.length > 3" class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-[8px] md:text-[10px] font-bold text-slate-500 ring-2 ring-slate-50">
                                            +{{ goal.wallets.length - 3 }}
                                        </div>
                                    </div>
                                <span class="text-[10px] md:text-xs font-bold text-slate-400">{{ __('linked_wallets') }}</span>
                                </div>

                            <div v-if="calculateMonthlySavings(goal) && goal.current_amount < goal.target_amount" class="w-full px-4 py-3 md:py-4 bg-indigo-50/50 text-indigo-700 rounded-2xl text-[10px] md:text-[11px] font-bold flex items-center justify-center gap-2 border border-indigo-100/50">
                                <Zap class="w-4 h-4" />
                                {{ __('requires_monthly').replace(':amount', formatCurrency(calculateMonthlySavings(goal), goal.currency).split(',')[0]) }}
                            </div>
                            <div v-else-if="goal.current_amount >= goal.target_amount" class="w-full px-4 py-3 md:py-4 bg-emerald-50 text-emerald-700 rounded-2xl text-[10px] md:text-[11px] font-bold flex items-center justify-center gap-2 border border-emerald-100">
                                <CheckCircle2 class="w-4 h-4" />
                                {{ __('goal_accomplished') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-else class="bg-white border border-slate-100 rounded-[2rem] p-12 text-center space-y-6 shadow-sm">
                <div class="relative w-32 h-32 mx-auto">
                    <div class="absolute inset-0 bg-indigo-100 rounded-full animate-ping opacity-20"></div>
                    <div class="relative bg-indigo-50 rounded-full w-32 h-32 flex items-center justify-center">
                        <Target class="w-12 h-12 text-indigo-400" />
                    </div>
                </div>
                <div class="space-y-1">
                    <h2 class="text-base font-bold text-slate-900">{{ __('no_goals') }}</h2>
                    <p class="text-slate-500 max-w-xs mx-auto font-medium text-sm">{{ __('start_planning') }}</p>
                </div>
                <button 
                    @click="openAddModal"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 active:scale-95 transition-all flex items-center justify-center gap-2 mx-auto"
                >
                    <Plus class="w-4 h-4" />
                    <span>{{ __('create_first_goal') }}</span>
                </button>
            </div>
            </div>
            <!-- ADD/EDIT MODAL -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto">
                <div @click="closeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                
                <div class="relative z-10 w-full max-w-4xl bg-white rounded-t-[2rem] sm:rounded-3xl shadow-2xl overflow-hidden animate-in slide-in-from-bottom-10 sm:zoom-in-95 duration-200 flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="p-6 md:p-8 border-b border-slate-200 flex items-center justify-between shrink-0">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">
                                {{ isEditing ? __('update_goal') : __('new_goal') }}
                            </h2>
                            <p class="text-xs md:text-sm text-slate-500 font-medium">{{ isEditing ? __('update_goal_desc') : __('new_goal_desc') }}</p>
                        </div>
                        <button @click="closeModal" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 transition-all">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
                    
                    <div class="p-6 md:p-8 overflow-y-auto custom-scrollbar">
                        <form @submit.prevent="submitForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Name -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('goal_name') }}</label>
                                    <input 
                                        v-model="form.name"
                                        type="text"
                                        :placeholder="__('goal_name_placeholder')"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm"
                                    />
                                    <p v-if="form.errors.name" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                        <AlertCircle class="w-3.5 h-3.5" />
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Type -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('goal_type') }}</label>
                                    <select 
                                        v-model="form.type"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm h-[46px]"
                                    >
                                        <option value="emergency">{{ __('emergency_fund') }}</option>
                                        <option value="retirement">{{ __('retirement') }}</option>
                                        <option value="saving">{{ __('general_saving') }}</option>
                                        <option value="custom">{{ __('custom') }}</option>
                                    </select>
                                    <p v-if="form.errors.type" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                        <AlertCircle class="w-3.5 h-3.5" />
                                        {{ form.errors.type }}
                                    </p>
                                </div>

                                <!-- Target Amount -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('target_amount') }}</label>
                                    <CurrencyInput v-model="form.target_amount" :currency="form.currency" placeholder="0.00" />
                                    <p v-if="form.errors.target_amount" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                        <AlertCircle class="w-3.5 h-3.5" />
                                        {{ form.errors.target_amount }}
                                    </p>
                                </div>

                                <!-- Target Date -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('target_deadline') }}</label>
                                    <input 
                                        v-model="form.target_date"
                                        type="date"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm"
                                        :min="new Date().toISOString().split('T')[0]"
                                    />
                                    <p v-if="form.errors.target_date" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                        <AlertCircle class="w-3.5 h-3.5" />
                                        {{ form.errors.target_date }}
                                    </p>
                                </div>
                            </div>

                            <!-- Wallet Allocation -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="text-xs font-bold text-slate-700">{{ __('allocate_wallets') }}</label>
                                    <span class="text-[10px] text-indigo-600 font-bold">{{ __('select_wallets_hint') }}</span>
                                </div>
                                <div class="max-h-[180px] overflow-y-auto pr-2 custom-scrollbar">
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        <label 
                                            v-for="wallet in wallets" 
                                            :key="wallet.id"
                                            class="relative flex items-center gap-3 p-3 rounded-xl border transition-all cursor-pointer group hover:bg-slate-50"
                                            :class="form.wallet_ids.includes(wallet.id) ? 'border-indigo-500 bg-indigo-50' : 'border-slate-100 bg-slate-50/30'"
                                        >
                                            <input 
                                                type="checkbox" 
                                                :value="wallet.id" 
                                                v-model="form.wallet_ids"
                                                class="hidden"
                                            />
                                            <div class="w-2 h-2 rounded-full flex-shrink-0" :class="getWalletColor(wallet.type)"></div>
                                            <div class="flex flex-col min-w-0">
                                                <span class="text-xs font-bold text-slate-900 truncate">{{ wallet.name }}</span>
                                                <span class="text-[9px] font-semibold text-slate-400">{{ formatCurrency(wallet.balance, wallet.currency).split(',')[0] }}</span>
                                            </div>
                                            
                                            <CheckCircle2 
                                                v-if="form.wallet_ids.includes(wallet.id)"
                                                class="absolute top-1 right-1 w-3 h-3 text-indigo-600"
                                            />
                                        </label>
                                    </div>
                                </div>
                                <p v-if="form.errors.wallet_ids" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                    <AlertCircle class="w-3.5 h-3.5" />
                                    {{ form.errors.wallet_ids }}
                                </p>
                            </div>

                            <!-- Catatan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('notes_optional') }}</label>
                                <textarea 
                                    v-model="form.notes"
                                    rows="2"
                                    :placeholder="__('notes_placeholder')"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"
                                ></textarea>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button 
                                    type="button"
                                    @click="closeModal"
                                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-4 rounded-xl font-bold text-sm transition-all"
                                >
                                    {{ __('cancel') }}
                                </button>
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-4 rounded-xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ isEditing ? __('update_goal') : __('create_goal_btn') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- DETAILS MODAL -->
        <Teleport to="body">
            <div v-if="showDetailsModal && selectedGoalForDetails" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto">
                <div @click="showDetailsModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                
                <div class="relative z-10 w-full max-w-4xl bg-white rounded-t-[2rem] sm:rounded-[2.5rem] shadow-2xl overflow-hidden animate-in slide-in-from-bottom-10 sm:zoom-in-95 duration-200 flex flex-col max-h-[90vh]">
                    <div class="p-8 md:p-10 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0">
                        <div class="flex items-center gap-4 md:gap-5">
                            <div 
                                class="w-14 h-14 md:w-16 md:h-16 rounded-2xl md:rounded-3xl flex items-center justify-center bg-gradient-to-br text-white shadow-lg shrink-0"
                                :class="getGoalColor(selectedGoalForDetails.type)"
                            >
                                <component :is="getGoalIcon(selectedGoalForDetails.type)" class="w-7 h-7 md:w-8 md:h-8" />
                            </div>
                            <div>
                                <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">{{ selectedGoalForDetails.name }}</h2>
                                <div class="flex flex-col items-start gap-1 md:flex-row md:items-center md:gap-3">
                                    <span class="px-3 py-1 bg-white text-slate-500 rounded-lg text-[10px] font-bold capitalize tracking-wider border border-slate-100">{{ __(getGoalTypeLabel(selectedGoalForDetails.type)) }}</span>
                                    <span v-if="selectedGoalForDetails.target_date" class="text-xs md:text-sm text-slate-400 flex items-center gap-1.5 font-medium">
                                        <Calendar class="w-3.5 h-3.5" />
                                        {{ new Date(selectedGoalForDetails.target_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button @click="showDetailsModal = false" class="p-3 hover:bg-white rounded-2xl text-slate-400 hover:text-slate-900 transition-all border border-transparent hover:border-slate-100 shadow-sm hover:shadow-md">
                            <X class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-8 md:p-10 space-y-8 md:space-y-10 overflow-y-auto custom-scrollbar">
                        <!-- Progress Summary -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 space-y-2">
                                <span class="text-xs font-bold text-slate-400">{{ __('total_saved') }}</span>
                                <h3 class="text-xl font-bold text-emerald-600 tabular-nums">{{ formatCurrency(getGoalSavedAmount(selectedGoalForDetails), selectedGoalForDetails.currency).split(',')[0] }}</h3>
                            </div>
                            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 space-y-2">
                                <span class="text-xs font-bold text-slate-400">{{ __('target_remaining') }}</span>
                                <h3 class="text-xl font-bold tabular-nums">{{ formatCurrency(selectedGoalForDetails.target_amount - getGoalSavedAmount(selectedGoalForDetails), selectedGoalForDetails.currency).split(',')[0] }}</h3>
                            </div>
                        </div>

                        <!-- Wallet Breakdown -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <WalletIcon class="w-4 h-4 text-slate-400" />
                                {{ __('wallet_allocations') }}
                            </h3>
                            <div class="max-h-[180px] overflow-y-auto pr-2 custom-scrollbar">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div 
                                        v-for="wallet in selectedGoalForDetails.wallets" 
                                        :key="wallet.id" 
                                        class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-100 transition-all hover:bg-white hover:shadow-sm"
                                    >
                                        <div class="w-2 h-2 rounded-full flex-shrink-0" :class="getWalletColor(wallet.type)"></div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-xs font-bold text-slate-900 truncate">{{ wallet.name }}</span>
                                            <span class="text-[10px] font-semibold text-slate-400 tabular-nums">
                                                {{ formatCurrency(wallet.balance, wallet.currency).split(',')[0] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes if any -->
                        <div v-if="selectedGoalForDetails.notes" class="p-6 bg-amber-50/50 rounded-3xl border border-amber-100/50 space-y-2">
                            <span class="text-xs font-bold text-amber-600 flex items-center gap-2">
                                <AlertCircle class="w-4 h-4" />
                                {{ __('notes') }}
                            </span>
                            <p class="text-sm text-amber-900 font-medium leading-relaxed italic">"{{ selectedGoalForDetails.notes }}"</p>
                        </div>

                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-3 pt-2">
                             <button 
                                @click="openEditModal(selectedGoalForDetails); showDetailsModal = false"
                                class="flex items-center justify-center gap-2 py-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl font-bold text-sm transition-colors"
                            >
                                <Edit2 class="w-4 h-4" />
                                {{ __('edit') }}
                            </button>
                            <button 
                                @click="deleteGoal(selectedGoalForDetails); showDetailsModal = false"
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

        <!-- Upgrade Modal -->
        <UpgradeModal 
            :show="showUpgradeModal" 
            @close="showUpgradeModal = false"
            :title="__('upsell_goals_title')"
            :description="__('upsell_goals_desc')"
        />

    </Layout>
</template>

<style scoped>
/* Custom animations if needed */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.2; }
    50% { opacity: 0.4; }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
