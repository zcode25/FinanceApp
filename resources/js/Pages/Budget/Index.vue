<script setup>
import Layout from '../../Shared/Layout.vue';
import Swal from 'sweetalert2';
import { Head, Link, useForm, router, usePage, Deferred } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const driverObj = ref(null);
const skipHTML = computed(() => `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">${__('skip_tour')}</button>
</div>`);
import { 
    Calendar, 
    Plus, 
    Trash2, 
    BrainCircuit, 
    ArrowRight, 
    AlertCircle, 
    CheckCircle2,
    BarChart3,
    X,
    ChevronLeft,
    ChevronRight,
    Target,
    Zap,
    Home,
    Car,
    ArrowLeft,
    Wallet,
    Banknote,
    PieChart,
    Coins,
    TrendingDown,
    ShoppingBag,
    Utensils,
    Plane,
    Gamepad2,
    GraduationCap,
    HeartPulse,
    HelpCircle,
    Pencil,
    Lock,
    Sparkles,
    Crown,
    Rocket,
    Check,
    AlertTriangle as AlertTriangleIcon
} from 'lucide-vue-next';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";

// Toast Helper
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

const showToast = (title, icon = 'success') => {
    Toast.fire({
        icon: icon,
        title: title,
        background: '#ffffff',
        color: '#1e293b',
        customClass: {
            popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
            title: '!text-sm !font-bold !text-slate-900',
        }
    });
};
import CategoryCombobox from '../../Shared/CategoryCombobox.vue';
import CurrencyInput from '../../Shared/CurrencyInput.vue';
import PremiumUpsellModal from '@/Shared/PremiumUpsellModal.vue';

const props = defineProps({
    deferred_budgets: Object,
    deferred_recommendations: Object,
    summary: Object,
    categories: Array,
    filters: Object,
    is_premium: Boolean,
    auto_setup_usage: Number
});

// Centralized safe data access
const data = computed(() => {
    return {
        // budgets: props.deferred_budgets || [], // Now handled by localBudgets
        recommendations: props.deferred_recommendations || []
    };
});

// Optimistic State
const localBudgets = ref([]);

watch(() => props.deferred_budgets, (newBudgets) => {
    localBudgets.value = newBudgets || [];
}, { immediate: true, deep: true });

const showModal = ref(false);
const isEditing = ref(false);
const editingBudgetId = ref(null);

const form = useForm({
    category_id: '',
    limit: '',
    month: props.filters.month
});

const autoSetupForm = useForm({
    estimated_income: '',
    goal: 'balanced_life',
    lifestyle: 'standard',
    month: props.filters.month
});

const showAutoSetupModal = ref(false);
const showUpsellModal = ref(false);
const wizardStep = ref(1);
const upsellTitle = ref('Premium Feature');
const upsellDescription = ref('Unlock this feature with the Professional Plan.');

const triggerUpsell = (title, description) => {
    upsellTitle.value = title;
    upsellDescription.value = description;
    showUpsellModal.value = true;
};

const customCategoryCount = computed(() => props.categories.filter(c => c.user_id !== null).length);
const canCreateCategory = computed(() => props.is_premium || customCategoryCount.value < 3);
    
    // Recommendations Carousel
    const activeRecIndex = ref(0);
    const visibleItems = ref(3); // Default to desktop

    const updateVisibleItems = () => {
        if (typeof window !== 'undefined') {
            if (window.innerWidth < 768) {
                visibleItems.value = 1;
            } else if (window.innerWidth < 1024) {
                visibleItems.value = 2; // md
            } else {
                visibleItems.value = 3; // lg/xl
            }
        }
    };

    // Initialize & Listen for Resize


    const filteredRecommendations = computed(() => {
        // Strict filtering: If a category is in the current month's budget list, DO NOT show it in recommendations
        const existingIds = (data.value.budgets || []).map(b => b.category ? b.category.id : b.category_id);
        return data.value.recommendations.filter(rec => !existingIds.includes(rec.category_id));
    });

    const nextRec = () => {
        if (activeRecIndex.value < maxIndex.value) {
            activeRecIndex.value++;
        }
    };
    
    // Limits
    const maxIndex = computed(() => Math.max(0, filteredRecommendations.value.length - visibleItems.value));
    const prevRec = () => {
        if (activeRecIndex.value > 0) {
            activeRecIndex.value--;
        }
    };

    // Reset index if filtered list changes
    watch(filteredRecommendations, async () => {
        await nextTick();
        updateDimensions();
        if (activeRecIndex.value > maxIndex.value) {
            activeRecIndex.value = 0;
        }
    });

    // Pixel-perfect translation logic
    const carouselContainer = ref(null);
    const containerWidth = ref(0);
    const GAP = 24; // gap-6 is 24px

    let resizeObserver = null;
    const updateDimensions = () => {
        updateVisibleItems();
        if (carouselContainer.value) {
            containerWidth.value = carouselContainer.value.clientWidth;
        }
    };

    const carouselTransform = computed(() => {
        if (!containerWidth.value || visibleItems.value === 0) return 'translateX(0px)';
        
        // Calculate the width of one "slot" (card + gap)
        // stride = (effectiveWidth + (visibleItems - 1) * gap) / visibleItems
        // But simpler: each card is 1/visibleItems width minus part of the gap
        // Actually, the stride for translateX is exactly (width + gap) / visibleItems
        const stride = (containerWidth.value + GAP) / visibleItems.value;
        const translateX = activeRecIndex.value * stride;
        return `translateX(-${translateX}px)`;
    });

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
                        localStorage.setItem('tour_state', 'hub_to_goals');
                        router.visit('/dashboard');
                    } else {
                        localStorage.setItem('tour_state', 'goals_setup');
                        router.visit('/goals');
                    }
                    driverObj.value.destroy();
                } else {
                    driverObj.value.moveNext();
                }
            },
            steps: [
                {
                    element: '#step-budget-summary',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_budget_summary_title')}</span>`,
                        description: __('tour_budget_summary_desc') + skipHTML.value,
                        side: "bottom",
                        align: 'start'
                    }
                },
                {
                    element: '#step-add-budget',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_budget_manual_title')}</span>`,
                        description: __('tour_budget_manual_desc') + skipHTML.value,
                        side: "bottom",
                        align: 'start'
                    }
                },
                {
                    element: '#step-auto-setup',
                    popover: {
                        title: `<span class="text-lg font-bold">${__('tour_budget_auto_title')}</span>`,
                        description: __('tour_budget_auto_desc') + skipHTML.value,
                        side: "bottom",
                        align: 'start'
                    }
                },
                {
                    element: isMobile ? '#mobile-nav-home' : '#nav-goals',
                    popover: {
                        title: isMobile ? `<span class="text-lg font-bold">${__('tour_budget_return_title')}</span>` : `<span class="text-lg font-bold">${__('tour_budget_goals_title')}</span>`,
                        description: isMobile 
                            ? __('tour_budget_return_desc') + skipHTML.value
                            : __('tour_budget_goals_desc') + skipHTML.value,
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
            'analysis_intro', 'hub_to_budget', 'budget_setup'
        ];
        

        // Guard against duplicate triggers
        if (driverObj.value && document.querySelector('.driver-popover')) {
            return;
        }

        if (!tourState || (tourState && catchUpStates.includes(tourState))) {
            if (!tourState || tourState !== 'budget_setup') {
                // If it's null, check if they should see the tour
                if (!tourState && tourCompleted) {
                    return;
                }
                localStorage.setItem('tour_state', 'budget_setup');
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
        // Wait for next tick to ensure DOM is ready
        setTimeout(() => {
            updateDimensions();
        }, 100);
        window.addEventListener('resize', updateDimensions);

        if (carouselContainer.value) {
            resizeObserver = new ResizeObserver(() => {
                updateDimensions();
            });
            resizeObserver.observe(carouselContainer.value);
        }

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

    // Handle Inertia navigation triggers
    watch(() => page.url, () => {
        checkTourTriggers();
    });
    onUnmounted(() => {
        window.removeEventListener('resize', updateDimensions);
        if (resizeObserver) {
            resizeObserver.disconnect();
        }
        if (driverObj.value) {
            driverObj.value.destroy();
        }
    });

    // Month Management
    const currentMonth = computed(() => {
        const [year, month] = props.filters.month.split('-');
        const locale = page.props.locale === 'id' ? 'id-ID' : 'en-US';
        return new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(new Date(year, month - 1));
    });
    
    const changeMonth = (delta) => {
        const [year, month] = (props.filters.month || '').split('-').map(Number);
        const date = new Date(year, month - 1 + delta, 1);
        const newMonth = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        router.get('/budget', { month: newMonth }, { 
            preserveState: true,
            only: ['deferred_budgets', 'deferred_recommendations', 'summary', 'filters']
        });
    };
    
    // Actions
    const openAddModal = () => {
        isEditing.value = false;
        editingBudgetId.value = null;
        form.reset();
        form.clearErrors();
        form.month = props.filters.month;
        showModal.value = true;
    };
    
    const editBudget = (budget) => {
        isEditing.value = true;
        editingBudgetId.value = budget.id;
        form.clearErrors();
        // Check if category is object (from relationship) or ID
        form.category_id = budget.category ? budget.category.id : budget.category_id;
        form.limit = budget.limit;
        form.month = props.filters.month;
        showModal.value = true;
    };
    
    const deleteBudget = (id) => {
        Swal.fire({
            title: __('delete_budget_title') || 'Delete Budget?',
            text: __('delete_budget_confirm') || 'Are you sure you want to delete this budget?',
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
                // Optimistic Delete
                const originalBudgets = [...localBudgets.value];
                localBudgets.value = localBudgets.value.filter(b => b.id !== id);

                // Background Request
                router.delete(`/budget/${id}`, {
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
                        // Rollback
                        localBudgets.value = originalBudgets;
                        showToast(__('error_deleting'), 'error');
                    }
                });
            }
        });
    };
    
    const submit = () => {
        // If editing, use PUT/PATCH route with ID, otherwise POST to create logic
        // But backend might handle update via POST /budget with internal logic?
        // Usually update uses PUT /budget/{id}
        
        if (isEditing.value && editingBudgetId.value) {
            // Optimistic Update
            const originalBudgets = JSON.parse(JSON.stringify(localBudgets.value));
            const budgetIndex = localBudgets.value.findIndex(b => b.id === editingBudgetId.value);
            
            if (budgetIndex !== -1) {
                // Update local state immediately
                localBudgets.value[budgetIndex].limit = form.limit;
                // We might also need to update category if it changed, but form.category_id handling 
                // requires looking up the category object which might be complex here.
                // For now, assuming primarily Limit updates for optimistic feel.
            }

            form.put(`/budget/${editingBudgetId.value}`, {
                preserveScroll: true, 
                preserveState: true,
                onSuccess: () => {
                    showModal.value = false;
                    form.reset();
                    editingBudgetId.value = null;
                    showToast(__('budget_updated') || 'Budget updated successfully');
                },
                onError: () => {
                    // Rollback
                    localBudgets.value = originalBudgets;
                    showToast(__('error_updating'), 'error');
                }
            });
        } else {
            form.post('/budget', {
                onSuccess: () => {
                    showModal.value = false;
                    form.reset();
                    showToast(__('budget_created') || 'Budget created successfully');
                }
            });
        }
    };
    
    // Mobile Details
    const showDetailsModal = ref(false);
    const selectedBudgetForDetails = ref(null);
    
    const openDetailsMobile = (budget) => {
        if (window.innerWidth < 768) {
            selectedBudgetForDetails.value = budget;
            showDetailsModal.value = true;
        }
    };
    
    const addFromRecommendation = (rec) => {
        form.category_id = rec.category_id;
        form.limit = rec.recommended_limit;
        form.month = props.filters.month;
        // This is always a new add
        isEditing.value = false;
        editingBudgetId.value = null;
        
        // Use standard submit which handles POST
        form.post('/budget', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
                showToast(__('recommendation_adopted') || 'Recommendation adopted successfully');
            }
        });
    };
    
    const openAutoSetup = () => {
        autoSetupForm.clearErrors();
        showAutoSetupModal.value = true;
    };

    const handleAutoSetup = () => {
        autoSetupForm.month = props.filters.month;
        autoSetupForm.post('/budget/auto-setup', {
            onSuccess: () => {
                showAutoSetupModal.value = false;
                wizardStep.value = 1;
                autoSetupForm.reset();
                showToast(__('auto_setup_complete') || 'Auto-setup completed successfully');
            }
        });
    };
    
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };
    
    const getStatusColor = (status) => {
        switch (status) {
            case 'danger': return 'bg-rose-500';
            case 'warning': return 'bg-amber-500';
            default: return 'bg-emerald-500';
        }
    };
    
    const getStatusText = (status) => {
        switch (status) {
            case 'danger': return __('status_danger');
            case 'warning': return __('status_warning');
            default: return __('status_on_track');
        }
    };

    const existingBudgetCategoryIds = computed(() => {
        return localBudgets.value
            .filter(b => {
                if (isEditing.value && editingBudgetId.value && b.id === editingBudgetId.value) {
                    return false;
                }
                return true;
            })
            .map(b => b.category ? b.category.id : b.category_id)
            .filter(id => id !== null);
    });

    const availableCategories = computed(() => {
        return props.categories.filter(c => !existingBudgetCategoryIds.value.includes(c.id));
    });


    </script>
    
    <template>
        <Head :title="__('budget_planning')" />
        <Layout>
            <header class="mb-8 flex flex-col xl:flex-row xl:items-center justify-between gap-6 relative z-30">
                <div class="space-y-1">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('budget_planning') }}</h1>
                    <p class="text-sm md:text-base text-slate-500 font-medium">{{ __('manage_monthly_limits') }}</p>
                </div>
                
                <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                    <!-- Month Selector -->
                    <div class="flex items-center bg-white rounded-2xl p-1.5 border border-slate-200 shadow-sm w-full md:w-auto justify-between md:justify-start">
                        <button 
                            @click="changeMonth(-1)" 
                            class="p-2 hover:bg-slate-50 text-slate-400 hover:text-indigo-600 rounded-xl transition-all"
                        >
                            <ChevronLeft class="w-5 h-5" />
                        </button>
                        <span class="px-4 text-sm font-bold text-slate-700 min-w-[140px] text-center">{{ currentMonth }}</span>
                        <button 
                            @click="changeMonth(1)" 
                            class="p-2 hover:bg-slate-50 text-slate-400 hover:text-indigo-600 rounded-xl transition-all"
                        >
                            <ChevronRight class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:flex md:items-center gap-3 w-full md:w-auto">
                        <button 
                            id="step-add-budget"
                            @click="openAddModal"
                            class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl font-bold text-sm shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 active:scale-95 transition-all flex items-center justify-center gap-2"
                        >
                            <Plus class="w-4 h-4" />
                            <span>{{ __('add_budget') }}</span>
                        </button>

                        <div id="step-auto-setup" class="relative w-full md:w-auto">
                                <button 
                                    @click="is_premium || auto_setup_usage == 0 ? openAutoSetup() : triggerUpsell(__('upsell_auto_setup_title'), __('upsell_auto_setup_desc'))"
                                    class="w-full md:w-auto px-6 py-3 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-2 active:scale-95 bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-200 hover:shadow-xl hover:shadow-emerald-300"
                                >
                                    <BrainCircuit class="w-4 h-4" />
                                    <span class="truncate">{{ __('auto_setup') }}</span>
                                </button>
                        </div>
                    </div>
                </div>
            </header>


            <!-- Summary Cards -->
            <section id="step-budget-summary" class="flex overflow-x-auto snap-x snap-mandatory md:grid md:grid-cols-3 gap-4 md:gap-6 mb-10 -mx-4 px-4 py-4 -my-4 md:mx-0 md:px-0 md:py-0 md:mt-0 md:mb-10 no-scrollbar md:overflow-visible">
                <!-- Total Spent Card -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Banknote class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <Banknote class="w-5 h-5 md:w-6 md:h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-base md:text-lg text-white/90 tracking-tight">{{ __('total_spent') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-2xl md:text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(summary.total_spent) }}</h2>
                            <div class="flex items-center gap-2 text-rose-100 font-medium text-xs md:text-sm">
                                <span>{{ __('used') }}</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white text-[10px] md:text-xs font-bold backdrop-blur-sm border border-white/10">{{ Math.min(summary.percentage, 100) }}%</span>
                            </div>
                        </div>
                        <div class="mt-6 w-full h-2 bg-black/20 rounded-full overflow-hidden backdrop-blur-sm">
                            <div class="h-full bg-white rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(255,255,255,0.5)]" :style="{ width: Math.min(summary.percentage, 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
    
                <!-- Remaining Card -->
                <div class="w-[90vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2.5rem] p-8 bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Coins class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-inner">
                                <Coins class="w-5 h-5 md:w-6 md:h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-base md:text-lg text-white/90 tracking-tight">{{ __('remaining_budget') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-2xl md:text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(summary.remaining) }}</h2>
                            <p class="text-emerald-100 font-medium text-xs md:text-sm">{{ __('safe_to_spend') }}</p>
                        </div>
                    </div>
                </div>
    
                <!-- Daily Allowance Card -->
                <div class="w-[85vw] md:w-auto shrink-0 snap-center relative overflow-hidden rounded-[2rem] p-6 md:p-8 bg-gradient-to-br from-slate-800 to-slate-900 text-white shadow-lg shadow-slate-200">
                    <div class="absolute right-0 top-0 p-8 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <TrendingDown class="w-32 h-32 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/10 shadow-inner">
                                <TrendingDown class="w-5 h-5 md:w-6 md:h-6 text-white" />
                            </div>
                            <h3 class="font-bold text-base md:text-lg text-white/90 tracking-tight">{{ __('daily_allowance') }}</h3>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-2xl md:text-3xl font-bold tracking-tight tabular-nums">{{ formatCurrency(summary.daily_allowance) }}</h2>
                            <div class="flex items-center gap-2 text-slate-400 font-medium text-xs md:text-sm">
                                <span class="text-slate-300">{{ summary.days_remaining }} {{ __('days_left') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Spacer for mobile scroll -->
                <div class="md:hidden shrink-0 w-4"></div>
            </section>
    
            <!-- AI Recommendations (Carousel) -->
            <Deferred data="deferred_recommendations">
                <template #fallback>
                    <div class="mb-12 animate-pulse">
                        <div class="flex items-center justify-between mb-8">
                            <div class="space-y-4">
                                <div class="h-8 w-64 bg-slate-100 rounded-2xl"></div>
                                <div class="h-4 w-48 bg-slate-50 rounded-xl"></div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-10 h-10 bg-slate-50 rounded-xl"></div>
                                <div class="w-10 h-10 bg-slate-50 rounded-xl"></div>
                            </div>
                        </div>
                        <!-- Carousel Cards Skeleton -->
                        <div class="flex gap-6 overflow-hidden">
                            <div v-for="i in 3" :key="i" class="w-[85vw] md:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] shrink-0 h-72 bg-slate-50 rounded-[2rem] border border-slate-100 relative p-8 flex flex-col justify-between">
                                <div class="flex justify-between items-start">
                                    <div class="space-y-3">
                                        <div class="h-6 w-24 bg-slate-200 rounded-full opacity-50"></div>
                                        <div class="h-8 w-40 bg-slate-200 rounded-xl"></div>
                                    </div>
                                    <div class="w-12 h-12 bg-slate-200 rounded-2xl opacity-50"></div>
                                </div>
                                <div class="space-y-3">
                                    <div class="h-4 w-full bg-slate-200 rounded-lg opacity-30"></div>
                                    <div class="h-4 w-2/3 bg-slate-200 rounded-lg opacity-30"></div>
                                </div>
                                <div class="h-12 w-full bg-slate-200 rounded-2xl opacity-20"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <section v-if="is_premium ? (filteredRecommendations.length > 0) : true" class="mb-12">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('smart_recommendations') }}</h2>
                        <p class="text-sm text-slate-500 font-medium">{{ __('ai_powered_optimizations') }}</p>
                    </div>
                    <!-- Carousel Controls -->
                    <div v-if="filteredRecommendations.length > visibleItems" class="hidden md:flex items-center gap-3 relative z-40">
                        <button @click="prevRec" :disabled="activeRecIndex === 0" class="p-3 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 disabled:opacity-30 transition-all shadow-sm hover:shadow-md">
                            <ChevronLeft class="w-5 h-5" />
                        </button>
                        <button @click="nextRec" :disabled="activeRecIndex >= maxIndex" class="p-3 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 disabled:opacity-30 transition-all shadow-sm hover:shadow-md">
                            <ChevronRight class="w-5 h-5" />
                        </button>
                    </div>
                </div>
                
                <div class="relative -mx-4 px-4 md:mx-0 md:px-0 py-10 no-scrollbar overflow-x-auto md:overflow-hidden snap-x snap-mandatory md:snap-none" ref="carouselContainer">
                    <!-- Premium Lock Overlay for AI Recommendations -->
                    <div v-if="!is_premium" class="absolute inset-0 z-30 flex flex-col items-center justify-center text-center p-8 bg-white/40 backdrop-blur-[12px] rounded-[2rem] border-2 border-dashed border-indigo-200">
                        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-xl shadow-indigo-100 mb-5 border border-indigo-50 animate-bounce-subtle">
                            <Crown class="w-8 h-8 text-amber-500" />
                        </div>
                        <h4 class="text-slate-900 font-black text-xl mb-2 tracking-tight">{{ __('unlock_ai_recommendations') }}</h4>
                        <p class="text-sm text-slate-600 mb-8 max-w-[320px] font-medium leading-relaxed">{{ __('unlock_ai_recommendations_desc') }}</p>
                        <button @click="triggerUpsell(__('upsell_ai_recs_title'), __('upsell_ai_recs_desc'))" class="px-8 py-4 bg-emerald-600 text-white rounded-2xl text-sm font-black shadow-xl shadow-emerald-200 hover:bg-emerald-700 transition-all active:scale-95 flex items-center gap-2 group">
                            <Sparkles class="w-4 h-4 group-hover:rotate-12 transition-transform" />
                            {{ __('upgrade_to_professional') }}
                        </button>
                    </div>

                    <div 
                        class="flex transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] gap-4 md:gap-6"
                        :style="containerWidth >= 768 ? { transform: carouselTransform } : {}"
                    >
                        <!-- Real Recommendations (Professional Only) -->
                        <template v-if="is_premium">
                            <div 
                                v-for="rec in filteredRecommendations" 
                                :key="rec.category"
                                class="w-[85vw] md:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] shrink-0 snap-center bg-white rounded-[2rem] p-6 md:p-8 border border-slate-200 relative overflow-hidden group shadow-lg shadow-slate-200/50 hover:shadow-xl hover:shadow-indigo-500/10 hover:-translate-y-1 transition-all duration-300"
                            >
                                <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-50/80 rounded-full blur-3xl transition-all group-hover:bg-indigo-100/80"></div>
                                
                                <div class="relative z-10">
                                    <div class="flex items-start justify-between mb-4 md:mb-6">
                                    <div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] md:text-xs font-bold tracking-wider mb-2 md:mb-3 shadow-sm">
                                            {{ __('recommended_budget') }}
                                        </span>
                                        <h3 class="text-lg md:text-xl font-bold text-slate-900">{{ rec.category }}</h3>
                                    </div>
                                    <div class="p-2.5 md:p-3 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-200 transition-all duration-300">
                                        <BarChart3 class="w-5 h-5 md:w-6 md:h-6" />
                                    </div>
                                </div>
                                
                                <div class="bg-slate-50/50 rounded-2xl p-4 border border-slate-100 mb-6 md:mb-8">
                                    <p class="text-xs md:text-sm text-slate-600 leading-relaxed font-medium">
                                        {{ rec.reason }} {{ __('recommend_limit') }} <span class="text-slate-900 font-black text-xs md:text-lg">{{ formatCurrency(rec.recommended_limit) }}</span>.
                                    </p>
                                </div>
                                
                                <button 
                                    @click="addFromRecommendation(rec)"
                                    class="w-full flex items-center justify-center gap-2 md:gap-3 py-3.5 md:py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl transition-all shadow-lg shadow-indigo-200 hover:shadow-indigo-300 group/btn text-sm"
                                >
                                    <span>{{ __('adopt_recommendation') }}</span>
                                    <ArrowRight class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" />
                                </button>
                            </div>
                        </div>
                        </template>

                        <!-- Ghost Cards Placeholder (Starter Plan) -->
                        <template v-else>
                            <div v-for="n in 3" :key="n" class="w-[85vw] md:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] shrink-0 snap-center bg-slate-50/50 rounded-[2rem] p-6 md:p-8 border border-white relative overflow-hidden flex flex-col justify-between h-full min-h-[320px] shadow-sm">
                                <div>
                                    <div class="h-6 w-32 bg-slate-200 rounded-lg mb-6 shadow-sm opacity-50"></div>
                                    <div class="space-y-3 mb-8">
                                        <div class="h-4 w-full bg-slate-100 rounded-lg opacity-40"></div>
                                        <div class="h-4 w-2/3 bg-slate-100 rounded-lg opacity-40"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 mt-auto">
                                    <div class="w-12 h-12 rounded-xl bg-slate-200/50 border border-slate-100 flex items-center justify-center">
                                        <BarChart3 class="w-6 h-6 text-slate-200" />
                                    </div>
                                    <div class="space-y-2 flex-1">
                                        <div class="h-3 w-20 bg-slate-200/50 rounded"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Spacer for mobile scroll -->
                        <div class="md:hidden shrink-0 w-4"></div>
                    </div>
                </div>
            </section>
        </Deferred>
    
            <!-- Current Budgets -->
            <Deferred data="deferred_budgets">
                <template #fallback>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div class="h-8 w-48 bg-slate-100 rounded-xl animate-pulse"></div>
                            <div class="h-10 w-32 bg-slate-100 rounded-xl animate-pulse"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="i in 6" :key="i" class="bg-white rounded-[2rem] p-6 border border-slate-100 space-y-4 relative overflow-hidden">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-50 animate-pulse"></div>
                                        <div class="space-y-2">
                                            <div class="h-5 w-32 bg-slate-50 rounded-lg animate-pulse"></div>
                                            <div class="h-3 w-20 bg-slate-50 rounded-lg animate-pulse"></div>
                                        </div>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg bg-slate-50 animate-pulse"></div>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <div class="h-4 w-16 bg-slate-50 rounded animate-pulse"></div>
                                        <div class="h-4 w-16 bg-slate-50 rounded animate-pulse"></div>
                                    </div>
                                    <div class="h-3 w-full bg-slate-50 rounded-full animate-pulse"></div>
                                </div>
                                <div class="h-12 w-full bg-slate-50 rounded-xl animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <section class="pb-20">
                <div class="flex items-center gap-4 mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('monthly_allocation') }}</h2>
                        <p class="text-sm text-slate-500 font-medium">{{ __('track_spending_desc') }}</p>
                    </div>
                </div>
    
                <div v-if="localBudgets.length > 0" class="grid grid-cols-1 gap-4 mb-24 md:mb-0">
                    <div 
                        v-for="budget in localBudgets" 
                        :key="budget.id"
                        @click="openDetailsMobile(budget)"
                        class="bg-white rounded-[1.5rem] p-5 md:p-6 border border-slate-200 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 hover:border-indigo-100 transition-all duration-300 group cursor-pointer md:cursor-default active:scale-[0.98] active:md:scale-100"
                    >
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
                            <!-- Icon & Info -->
                            <div class="md:w-1/3 flex items-center gap-4 md:gap-5">
                                <div :class="['w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-sm transition-all duration-300', budget.category ? budget.category.color : 'bg-slate-50']">
                                    <Target class="w-6 h-6 md:w-7 md:h-7 text-white" />
                                </div>
                                <div class="space-y-0.5">
                                    <h3 class="text-base md:text-lg font-bold text-slate-900 leading-tight">{{ budget.category ? budget.category.name : __('unknown_category') }}</h3>
                                    <div class="flex flex-col items-start gap-1">
                                        <div class="flex items-center gap-1.5">
                                            <span :class="['w-2 h-2 rounded-full', getStatusColor(budget.status)]"></span>
                                            <span class="text-[10px] md:text-xs font-bold text-slate-500">{{ getStatusText(budget.status) }}</span>
                                        </div>
                                        <div v-if="budget.reason" class="hidden md:inline-flex items-center px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-[10px] font-bold tracking-wide">
                                            {{ __(budget.reason) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
        
                            <!-- Progress Section -->
                            <div class="flex-1 space-y-2 md:space-y-3">
                                <div class="flex items-end justify-between">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] md:text-xs font-bold text-slate-400 tracking-wider mb-0.5 md:mb-1">{{ __('current_balance') }}</span>
                                        <div class="flex items-baseline gap-1.5">
                                            <span class="text-sm md:text-2xl font-bold text-slate-900 tracking-tight tabular-nums">{{ formatCurrency(budget.spent).split(',')[0] }}</span>
                                            <span class="text-xs md:text-sm font-bold text-slate-300">/ {{ formatCurrency(budget.limit).split(',')[0] }}</span>
                                        </div>
                                    </div>
                                    <span :class="['text-sm md:text-base font-bold tabular-nums', budget.percentage > 100 ? 'text-rose-600' : 'text-slate-900']">
                                        {{ budget.percentage }}%
                                    </span>
                                </div>
                                <div class="w-full h-1 md:h-1.5 bg-slate-100 rounded-full overflow-hidden shadow-inner">
                                    <div 
                                        :class="['h-full transition-all duration-1000 ease-out rounded-full shadow-sm relative overflow-hidden', getStatusColor(budget.status)]"
                                        :style="{ width: Math.min(budget.percentage, 100) + '%' }"
                                    >
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent w-full -translate-x-full animate-[shimmer_2s_infinite]"></div>
                                    </div>
                                </div>
                            </div>
        
                            <!-- Actions (Desktop Only) -->
                            <div class="hidden md:flex items-center justify-end gap-2 md:gap-3 md:pl-6 md:border-l border-slate-100 pt-2 md:pt-0">
                                <button @click="editBudget(budget)" class="p-2.5 md:p-3 rounded-xl bg-slate-50 hover:bg-white hover:shadow-md border border-slate-100 transition-all text-slate-400 hover:text-indigo-600 group/edit">
                                    <BarChart3 class="w-5 h-5 transition-transform group-hover/edit:scale-110" />
                                </button>
                                <button @click="deleteBudget(budget.id)" class="p-2.5 md:p-3 rounded-xl bg-slate-50 hover:bg-white hover:shadow-md border border-slate-100 transition-all text-slate-400 hover:text-rose-600 group/del">
                                    <Trash2 class="w-5 h-5 transition-transform group-hover/del:scale-110" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Empty State -->
                <div v-else class="glass-card p-12 flex flex-col items-center justify-center text-center space-y-6 border-slate-200 border-dashed border-2 bg-slate-50/50 rounded-[2rem]">
                    <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center shadow-lg shadow-slate-200/50 border border-slate-100">
                        <AlertCircle class="w-8 h-8 text-slate-300" />
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-base font-bold text-slate-900">{{ __('no_budgets_title') }}</h2>
                        <p class="text-slate-500 max-w-sm mx-auto font-medium text-sm">{{ __('no_budgets_desc') }}</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 pt-2">
                        <div class="relative">
                            <button 
                                @click="is_premium || auto_setup_usage == 0 ? openAutoSetup() : triggerUpsell(__('upsell_auto_setup_title'), __('upsell_auto_setup_desc'))"
                                class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold text-sm transition-all shadow-xl shadow-emerald-600/20 flex items-center gap-2 active:scale-95"
                            >
                                <BrainCircuit class="w-5 h-5" />
                                <span>{{ __('start_auto_setup') }}</span>
                            </button>
                        </div>
                        <button @click="openAddModal" class="px-8 py-3 bg-white hover:bg-slate-50 text-slate-600 rounded-2xl font-bold text-sm transition-all border border-slate-200 shadow-sm">
                            {{ __('add_manual_budget') }}
                        </button>
                    </div>
                </div>
            </section>
        </Deferred>
    
            <Teleport to="body">
                <div v-if="showAutoSetupModal" class="fixed inset-0 z-[100] flex items-center justify-center p-0 md:p-4">
                    <div @click="showAutoSetupModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                    
                    <div class="relative z-10 w-full max-w-2xl bg-white rounded-t-[2.5rem] md:rounded-3xl shadow-2xl animate-in slide-in-from-bottom md:zoom-in-95 duration-300 overflow-hidden flex flex-col max-h-[92vh] md:max-h-[85vh] self-end md:self-center">
                        <!-- Header (Sticky) -->
                        <div class="p-6 md:p-8 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-20">
                            <div>
                                <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-0.5 md:mb-1">{{ __('auto_setup_wizard') }}</h2>
                                <p class="text-xs md:text-sm text-slate-500 font-medium">{{ __('step_of').replace(':step', wizardStep).replace(':total', 3) }}</p>
                            </div>
                            <button @click="showAutoSetupModal = false" class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 transition-all border border-slate-100">
                                <X class="w-5 h-5 md:w-6 md:h-6" />
                            </button>
                        </div>
        
                        <div class="p-8 overflow-y-auto custom-scrollbar">
                            <!-- Step 1: Income -->
                            <div v-if="wizardStep === 1" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ __('estimated_income_label') }}</h3>
                                    <p class="text-sm text-slate-500 leading-relaxed">{{ __('estimated_income_description') }}</p>
                                </div>
        
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('enter_monthly_income') }} (IDR)</label>
                                    <CurrencyInput 
                                        v-model="autoSetupForm.estimated_income"
                                        :placeholder="__('amount')"
                                    />
                                    <p v-if="autoSetupForm.errors.estimated_income" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                        <AlertCircle class="w-3.5 h-3.5" />
                                        {{ autoSetupForm.errors.estimated_income }}
                                    </p>
                                </div>
        
                                <div class="pt-2">
                                    <button 
                                        @click="wizardStep = 2"
                                        :disabled="!autoSetupForm.estimated_income"
                                        class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all flex items-center justify-center gap-3 shadow-lg shadow-indigo-200 disabled:opacity-50 disabled:shadow-none"
                                    >
                                        <span>{{ __('next_step') }}</span>
                                        <ArrowRight class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
        
                            <!-- Step 2: Goal -->
                            <div v-if="wizardStep === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ __('financial_goal') }}</h3>
                                    <p class="text-sm text-slate-500 leading-relaxed">{{ __('financial_goal_description') }}</p>
                                </div>
        
                                <div class="grid grid-cols-1 gap-4">
                                     <button 
                                        @click="autoSetupForm.goal = 'aggressive_saving'"
                                        :class="['p-6 rounded-2xl border-2 text-left transition-all group relative overflow-hidden', autoSetupForm.goal === 'aggressive_saving' ? 'bg-indigo-50 border-indigo-600 ring-4 ring-indigo-500/10' : 'bg-white border-slate-100 text-slate-400 hover:border-indigo-200 hover:bg-slate-50']"
                                    >
                                        <div class="flex items-center gap-4">
                                            <div :class="['p-3 rounded-xl border shrink-0', autoSetupForm.goal === 'aggressive_saving' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-50 text-slate-400 border-slate-100']">
                                                <Target class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <span :class="['text-base font-bold block mb-1', autoSetupForm.goal === 'aggressive_saving' ? 'text-indigo-900' : 'text-slate-700']">{{ __('goal_aggressive_title') }}</span>
                                                <p :class="['text-sm font-medium leading-relaxed transition-colors', autoSetupForm.goal === 'aggressive_saving' ? 'text-indigo-700' : 'text-slate-500 opacity-80']">{{ __('goal_aggressive_desc') }}</p>
                                            </div>
                                        </div>
                                    </button>
        
                                    <button 
                                        @click="autoSetupForm.goal = 'balanced_life'"
                                        :class="['p-6 rounded-2xl border-2 text-left transition-all group relative overflow-hidden', autoSetupForm.goal === 'balanced_life' ? 'bg-indigo-50 border-indigo-600 ring-4 ring-indigo-500/10' : 'bg-white border-slate-100 text-slate-400 hover:border-indigo-200 hover:bg-slate-50']"
                                    >
                                        <div class="flex items-center gap-4">
                                            <div :class="['p-3 rounded-xl border shrink-0', autoSetupForm.goal === 'balanced_life' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-50 text-slate-400 border-slate-100']">
                                                <Zap class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <span :class="['text-base font-bold block mb-1', autoSetupForm.goal === 'balanced_life' ? 'text-indigo-900' : 'text-slate-700']">{{ __('goal_balanced_title') }}</span>
                                                <p :class="['text-sm font-medium leading-relaxed transition-colors', autoSetupForm.goal === 'balanced_life' ? 'text-indigo-700' : 'text-slate-500 opacity-80']">{{ __('goal_balanced_desc') }}</p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
        
                                <div class="pt-4 flex gap-3 border-t border-slate-50">
                                    <button @click="wizardStep = 1" class="flex-1 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                                        {{ __('back') }}
                                    </button>
                                    <button @click="wizardStep = 3" class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all flex items-center justify-center gap-3 shadow-lg shadow-indigo-200">
                                        <span>{{ __('continue') }}</span>
                                        <ArrowRight class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
        
                            <!-- Step 3: Lifestyle -->
                            <div v-if="wizardStep === 3" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ __('daily_activity') }}</h3>
                                    <p class="text-sm text-slate-500 leading-relaxed">{{ __('activity_description') }}</p>
                                </div>
        
                                <div class="space-y-3">
                                    <button 
                                        @click="autoSetupForm.lifestyle = 'commuter'"
                                        :class="['p-5 rounded-xl border-2 text-left transition-all w-full flex items-center gap-4 group', autoSetupForm.lifestyle === 'commuter' ? 'bg-indigo-50 border-indigo-600 ring-2 ring-indigo-500/10' : 'bg-white border-slate-100 hover:border-indigo-200 hover:bg-slate-50']"
                                    >
                                        <div :class="['p-3 rounded-xl border shrink-0', autoSetupForm.lifestyle === 'commuter' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-50 text-slate-400 border-slate-100']">
                                            <Car class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <span :class="['block text-sm font-bold mb-1', autoSetupForm.lifestyle === 'commuter' ? 'text-indigo-900' : 'text-slate-700']">{{ __('lifestyle_commuter_title') }}</span>
                                            <p class="text-xs text-slate-500 font-medium leading-relaxed">{{ __('lifestyle_commuter_desc') }}</p>
                                        </div>
                                    </button>
        
                                    <button 
                                        @click="autoSetupForm.lifestyle = 'homebody'"
                                        :class="['p-5 rounded-xl border-2 text-left transition-all w-full flex items-center gap-4 group', autoSetupForm.lifestyle === 'homebody' ? 'bg-indigo-50 border-indigo-600 ring-2 ring-indigo-500/10' : 'bg-white border-slate-100 hover:border-indigo-200 hover:bg-slate-50']"
                                    >
                                        <div :class="['p-3 rounded-xl border shrink-0', autoSetupForm.lifestyle === 'homebody' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-50 text-slate-400 border-slate-100']">
                                            <Home class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <span :class="['block text-sm font-bold mb-1', autoSetupForm.lifestyle === 'homebody' ? 'text-indigo-900' : 'text-slate-700']">{{ __('lifestyle_homebody_title') }}</span>
                                            <p class="text-xs text-slate-500 font-medium leading-relaxed">{{ __('lifestyle_homebody_desc') }}</p>
                                        </div>
                                    </button>
                                    
                                    <button 
                                        @click="autoSetupForm.lifestyle = 'standard'"
                                        :class="['p-5 rounded-xl border-2 text-left transition-all w-full flex items-center gap-4 group', autoSetupForm.lifestyle === 'standard' ? 'bg-indigo-50 border-indigo-600 ring-2 ring-indigo-500/10' : 'bg-white border-slate-100 hover:border-indigo-200 hover:bg-slate-50']"
                                    >
                                        <div :class="['p-3 rounded-xl border shrink-0', autoSetupForm.lifestyle === 'standard' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-50 text-slate-400 border-slate-100']">
                                            <Zap class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <span :class="['block text-sm font-bold mb-1', autoSetupForm.lifestyle === 'standard' ? 'text-indigo-900' : 'text-slate-700']">{{ __('lifestyle_standard_title') }}</span>
                                            <p class="text-xs text-slate-500 font-medium leading-relaxed">{{ __('lifestyle_standard_desc') }}</p>
                                        </div>
                                    </button>
                                </div>
        
                                <div class="pt-4 flex gap-3 border-t border-slate-50">
                                    <button @click="wizardStep = 2" class="flex-1 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all">
                                        {{ __('back') }}
                                    </button>
                                    <button 
                                        @click="handleAutoSetup" 
                                        :disabled="autoSetupForm.processing"
                                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-200"
                                    >
                                        <span v-if="autoSetupForm.processing">{{ __('creating_plan') }}</span>
                                        <span v-else>{{ __('generate_plan') }}</span>
                                        <CheckCircle2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
    
            <!-- Budget Modal -->
            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-0 md:p-4">
                    <div @click="showModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                    
                    <div class="relative z-10 w-full max-w-2xl bg-white rounded-t-[2.5rem] md:rounded-3xl shadow-2xl animate-in slide-in-from-bottom md:zoom-in-95 duration-300 overflow-hidden flex flex-col max-h-[92vh] md:max-h-[85vh] self-end md:self-center">
                        <!-- Header (Sticky) -->
                        <div class="p-6 md:p-8 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-20">
                            <div>
                                <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-0.5 md:mb-1">{{ isEditing ? __('update_budget') : __('set_new_budget') }}</h2>
                                <p class="text-xs md:text-sm text-slate-500 font-medium">{{ __('for_month') }} {{ currentMonth }}</p>
                            </div>
                            <button @click="showModal = false" class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 transition-all border border-slate-100">
                                <X class="w-5 h-5 md:w-6 md:h-6" />
                            </button>
                        </div>
                        
                        <div class="overflow-y-auto custom-scrollbar">
        
                        <form @submit.prevent="submit" class="p-8 space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('category') }}</label>
                                <CategoryCombobox 
                                    v-model="form.category_id"
                                    :categories="availableCategories"
                                    type="expense"
                                    item-value="id" 
                                    item-label="name"
                                    :placeholder="__('select_or_type_category')"
                                    :can-create="canCreateCategory"
                                    @limit-reached="triggerUpsell(__('upsell_category_limit_title'), __('upsell_category_limit_desc'))"
                                />
                                <p v-if="form.errors.category_id" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                    <AlertCircle class="w-3.5 h-3.5" />
                                    {{ form.errors.category_id }}
                                </p>
                            </div>
        
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">{{ __('monthly_limit') }}</label>
                                <CurrencyInput 
                                    v-model="form.limit"
                                    :placeholder="__('set_budget_amount')"
                                />
                                <p v-if="form.errors.limit" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                    <AlertCircle class="w-3.5 h-3.5" />
                                    {{ form.errors.limit }}
                                </p>
                            </div>
        
                            <div class="flex gap-3 pt-2">
                                <button 
                                    type="button" 
                                    @click="showModal = false"
                                    class="flex-1 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all text-sm"
                                >
                                    {{ __('cancel') }}
                                </button>
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-200 hover:shadow-xl text-sm disabled:opacity-50 disabled:shadow-none disabled:cursor-not-allowed"
                                >
                                    {{ isEditing ? __('update_limit') : __('confirm_budget') }}
                                </button>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </Teleport>
            
            <!-- Budget Details Bottom Sheet (Mobile) -->
            <Teleport to="body">
                <div v-if="showDetailsModal" class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center p-0 sm:p-4">
                    <div @click="showDetailsModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                    
                    <div class="relative z-10 w-full max-w-lg bg-white rounded-t-[2.5rem] sm:rounded-3xl shadow-2xl animate-in slide-in-from-bottom duration-300 overflow-hidden">
                        <div v-if="selectedBudgetForDetails">
                            <!-- Header -->
                            <div class="p-6 md:p-8 border-b border-slate-100 flex items-center justify-between bg-white">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0">
                                        <Target class="w-6 h-6 text-indigo-600" />
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-bold text-slate-900 leading-tight">
                                            {{ selectedBudgetForDetails.category ? selectedBudgetForDetails.category.name : __('unknown_category') }}
                                        </h2>
                                        <p class="text-sm text-slate-500 font-medium">{{ __('budget_details') }}</p>
                                    </div>
                                </div>
                                <button @click="showDetailsModal = false" class="p-2 bg-slate-50 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 transition-all border border-slate-100">
                                    <X class="w-6 h-6" />
                                </button>
                            </div>
                            
                            <div class="p-6 md:p-8 space-y-6">
                                <!-- Status Card -->
                                <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 relative overflow-hidden">
                                    <div class="relative z-10">
                                        <div class="flex justify-between items-start mb-4">
                                            <div>
                                                <p class="text-xs font-bold text-slate-400 mb-1">{{ __('current_status') }}</p>
                                                <div class="flex items-center gap-2">
                                                    <span :class="['w-2.5 h-2.5 rounded-full', getStatusColor(selectedBudgetForDetails.status)]"></span>
                                                    <span class="text-base font-bold text-slate-900">{{ getStatusText(selectedBudgetForDetails.status) }}</span>
                                                </div>
                                            </div>
                                            <span class="text-lg font-bold text-slate-900 tabular-nums">{{ selectedBudgetForDetails.percentage }}%</span>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden mb-4">
                                            <div 
                                                :class="['h-full rounded-full transition-all duration-500', getStatusColor(selectedBudgetForDetails.status)]"
                                                :style="{ width: Math.min(selectedBudgetForDetails.percentage, 100) + '%' }"
                                            ></div>
                                        </div>
                                        
                                        <div class="flex justify-between items-end">
                                            <div>
                                                <p class="text-xs font-bold text-slate-400 mb-0.5">{{ __('spent') }}</p>
                                                <p class="text-lg font-bold text-slate-900 tabular-nums">{{ formatCurrency(selectedBudgetForDetails.spent) }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs font-bold text-slate-400 mb-0.5">{{ __('limit') }}</p>
                                                <p class="text-base font-bold text-slate-600 tabular-nums">{{ formatCurrency(selectedBudgetForDetails.limit) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
    
                                <!-- Reason Pill if exists -->
                                <div v-if="selectedBudgetForDetails.reason" class="flex justify-start">
                                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                                        <span class="text-sm font-semibold">{{ __(selectedBudgetForDetails.reason) }}</span>
                                    </div>
                                </div>
    
                                <!-- Actions -->
                                <div class="grid grid-cols-2 gap-3 pt-2">
                                     <button 
                                        @click="editBudget(selectedBudgetForDetails); showDetailsModal = false"
                                        class="flex items-center justify-center gap-2 py-3.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl font-bold text-sm transition-colors border border-indigo-100"
                                    >
                                        <Pencil class="w-4 h-4" />
                                        {{ __('edit') }}
                                    </button>
                                    <button 
                                        @click="deleteBudget(selectedBudgetForDetails.id); showDetailsModal = false"
                                        class="flex items-center justify-center gap-2 py-3.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl font-bold text-sm transition-colors border border-rose-100"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                        {{ __('delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Premium Upsell Modal (Standardized) -->
            <PremiumUpsellModal 
                :show="showUpsellModal" 
                :title="upsellTitle"
                :description="upsellDescription"
                @close="showUpsellModal = false" 
            />

        </Layout>
    </template>
    
<style scoped>
.flex-2 { flex: 2; }
</style>
