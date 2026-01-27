<script setup>
import Layout from '../../Shared/Layout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
    TrendingDown
} from 'lucide-vue-next';
import CategoryCombobox from '../../Shared/CategoryCombobox.vue';
import CurrencyInput from '../../Shared/CurrencyInput.vue';

const props = defineProps({
    budgets: Array,
    summary: Object,
    recommendations: Array,
    categories: Array,
    filters: Object
});

const showModal = ref(false);
const isEditing = ref(false);

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
const wizardStep = ref(1);

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;
    
const editingBudgetId = ref(null);

    // Month Management
    const currentMonth = computed(() => {
        const [year, month] = props.filters.month.split('-');
        const locale = page.props.locale === 'id' ? 'id-ID' : 'en-US';
        return new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(new Date(year, month - 1));
    });
    
    const changeMonth = (delta) => {
        const [year, month] = props.filters.month.split('-').map(Number);
        const date = new Date(year, month - 1 + delta, 1);
        const newMonth = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        router.get('/budget', { month: newMonth }, { preserveState: true });
    };
    
    // Actions
    const openAddModal = () => {
        isEditing.value = false;
        editingBudgetId.value = null;
        form.reset();
        form.month = props.filters.month;
        showModal.value = true;
    };
    
    const editBudget = (budget) => {
        isEditing.value = true;
        editingBudgetId.value = budget.id;
        // Check if category is object (from relationship) or ID
        form.category_id = budget.category ? budget.category.id : budget.category_id;
        form.limit = budget.limit;
        form.month = props.filters.month;
        showModal.value = true;
    };
    
    const deleteBudget = (id) => {
        if (confirm(__('delete_budget_confirm'))) {
            router.delete(`/budget/${id}`);
        }
    };
    
    const submit = () => {
        // If editing, use PUT/PATCH route with ID, otherwise POST to create logic
        // But backend might handle update via POST /budget with internal logic?
        // Usually update uses PUT /budget/{id}
        
        if (isEditing.value && editingBudgetId.value) {
            form.put(`/budget/${editingBudgetId.value}`, {
                onSuccess: () => {
                    showModal.value = false;
                    form.reset();
                    editingBudgetId.value = null;
                }
            });
        } else {
            form.post('/budget', {
                onSuccess: () => {
                    showModal.value = false;
                    form.reset();
                }
            });
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
            }
        });
    };
    
    const handleAutoSetup = () => {
        autoSetupForm.month = props.filters.month;
        autoSetupForm.post('/budget/auto-setup', {
            onSuccess: () => {
                showAutoSetupModal.value = false;
                wizardStep.value = 1;
                autoSetupForm.reset();
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
    
    const availableCategories = computed(() => {
        // Filter out categories that already have a budget for this month
        // We exclude the current budget being edited from the "exclusion list"
        const existingBudgetCategoryIds = props.budgets
            .filter(b => {
                // If editing, skip the current budget (so its category remains available)
                if (isEditing.value && editingBudgetId.value && b.id === editingBudgetId.value) {
                    return false;
                }
                return true;
            })
            .map(b => b.category ? b.category.id : null)
            .filter(id => id !== null);
            
        return props.categories.filter(c => !existingBudgetCategoryIds.includes(c.id));
    });
    </script>
    
    <template>
        <Head :title="__('budget_planning')" />
        <Layout>
            <header class="mb-8 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ __('budget_planning') }}</h1>
                    <p class="text-gray-400">{{ __('budget_planning_desc') }}</p>
                </div>
    
                <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                    <!-- Month Switcher -->
                    <div class="flex items-center justify-between w-full md:w-auto gap-3 bg-gray-900/50 p-1.5 rounded-xl border border-white/5">
                        <button @click="changeMonth(-1)" class="p-1.5 hover:bg-white/5 rounded-lg text-gray-400 transition-colors">
                            <ChevronLeft class="w-5 h-5" />
                        </button>
                        <span class="text-sm font-bold text-white min-w-[140px] text-center">{{ currentMonth }}</span>
                        <button @click="changeMonth(1)" class="p-1.5 hover:bg-white/5 rounded-lg text-gray-400 transition-colors">
                            <ChevronRight class="w-5 h-5" />
                        </button>
                    </div>
    
                    <div class="flex items-center w-full md:w-auto gap-2">
                        <button 
                            @click="showAutoSetupModal = true"
                            class="flex-1 md:flex-none flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 border border-emerald-500/20 rounded-xl font-bold transition-all"
                        >
                            <BrainCircuit class="w-5 h-5" />
                            <span>{{ __('auto_setup') }}</span>
                        </button>
                        <!-- Hidden on Mobile, uses FAB -->
                        <button @click="openAddModal" class="hidden md:flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-500/20">
                            <Plus class="w-5 h-5" />
                            <span>{{ __('set_budget') }}</span>
                        </button>
                    </div>
                </div>
            </header>
    
            <!-- Summary Cards -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Total Budget -->
                <div class="glass-card p-6 border-white/5 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/5 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-400">
                            <Wallet class="w-6 h-6" />
                        </div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('total_budget') }}</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white">{{ formatCurrency(summary.total_budget) }}</h3>
                    <p class="text-xs text-gray-500 mt-2">{{ __('target_allocation') }}</p>
                </div>
    
                <!-- Total Spent -->
                <div class="glass-card p-6 border-white/5 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/5 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 rounded-xl bg-rose-500/10 text-rose-400">
                            <Banknote class="w-6 h-6" />
                        </div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('total_spent') }}</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white">{{ formatCurrency(summary.total_spent) }}</h3>
                    <div class="flex items-center gap-1.5 mt-2">
                        <div class="w-full h-1.5 bg-gray-800 rounded-full overflow-hidden flex-1">
                            <div class="h-full bg-rose-500" :style="{ width: Math.min(summary.percentage, 100) + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold text-rose-400">{{ summary.percentage }}%</span>
                    </div>
                </div>
    
                <!-- Remaining -->
                <div class="glass-card p-6 border-white/5 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-white/5 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <Coins class="w-6 h-6" />
                        </div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('remaining_budget') }}</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white">{{ formatCurrency(summary.remaining) }}</h3>
                    <p class="text-xs text-emerald-400/70 mt-2 font-medium">{{ __('safe_to_spend') }}</p>
                </div>
    
                <!-- Daily Allowance (Our Recommendation) -->
                <div class="glass-card p-6 border-indigo-500/10 bg-indigo-500/5 relative overflow-hidden border">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-indigo-500/10 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 rounded-xl bg-indigo-500/20 text-indigo-300">
                            <TrendingDown class="w-6 h-6" />
                        </div>
                        <span class="text-xs font-bold text-indigo-300/60 uppercase tracking-widest">{{ __('daily_allowance') }}</span>
                    </div>
                    <h3 class="text-2xl font-bold text-white">{{ formatCurrency(summary.daily_allowance) }}</h3>
                    <p class="text-xs text-gray-500 mt-2">{{ __('daily_safe_limit') }} ({{ summary.days_remaining }} {{ __('days_left') }})</p>
                </div>
            </section>
    
            <!-- AI Recommendations -->
            <section v-if="recommendations.length > 0" class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <BrainCircuit class="w-5 h-5 text-indigo-400" />
                    <h2 class="text-xl font-bold text-white">{{ __('smart_recommendations') }}</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="rec in recommendations" 
                        :key="rec.category"
                        class="glass-card p-6 border-indigo-500/20 bg-indigo-500/5 relative overflow-hidden group"
                    >
                        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/10 rounded-full blur-2xl transition-all group-hover:bg-indigo-500/20"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest">{{ rec.category }}</span>
                                    <h3 class="text-lg font-bold text-white transition-all group-hover:text-indigo-300">{{ __('target_budget') }}</h3>
                                </div>
                                <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-400">
                                    <BarChart3 class="w-5 h-5" />
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-400 mb-6 leading-relaxed">
                                {{ rec.reason }} {{ __('recommend_limit') }} <strong class="text-white">{{ formatCurrency(rec.recommended_limit) }}</strong>.
                            </p>
                            
                            <button 
                                @click="addFromRecommendation(rec)"
                                class="w-full flex items-center justify-center gap-2 py-2.5 bg-white/5 hover:bg-white/10 text-white text-sm font-bold rounded-lg transition-all border border-white/5 hover:border-indigo-500/30"
                            >
                                <span>{{ __('adopt_recommendation') }}</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>
    
            <!-- Current Budgets -->
            <section>
                <div class="flex items-center gap-2 mb-6">
                    <Calendar class="w-5 h-5 text-gray-400" />
                    <h2 class="text-xl font-bold text-white">{{ __('monthly_allocation') }}</h2>
                </div>
    
                <div v-if="budgets.length > 0" class="grid grid-cols-1 gap-6">
                    <div 
                        v-for="budget in budgets" 
                        :key="budget.id"
                        class="glass-card p-6 border-white/5 hover:border-white/10 transition-all group"
                    >
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <!-- Info -->
                            <div class="md:w-1/4">
                                <h3 class="text-lg font-bold text-white capitalize mb-1">{{ budget.category ? budget.category.name : __('unknown_category') }}</h3>
                                <div class="flex items-center gap-2 mb-2">
                                    <span :class="['w-2 h-2 rounded-full', getStatusColor(budget.status)]"></span>
                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ getStatusText(budget.status) }}</span>
                                </div>
                                <!-- AI Reasoning -->
                                <p v-if="budget.reason" class="text-[10px] leading-relaxed text-indigo-400/60 bg-indigo-500/5 px-2 py-1 rounded border border-indigo-500/10 inline-block">
                                    💡 {{ __(budget.reason) }}
                                </p>
                            </div>
    
                            <!-- Progress Bar -->
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-400">
                                        <strong class="text-white">{{ formatCurrency(budget.spent) }}</strong> of {{ formatCurrency(budget.limit) }}
                                    </span>
                                    <span class="text-sm font-bold" :class="budget.percentage > 100 ? 'text-rose-500' : 'text-gray-400'">
                                        {{ budget.percentage }}%
                                    </span>
                                </div>
                                <div class="w-full h-2.5 bg-gray-800 rounded-full overflow-hidden">
                                    <div 
                                        :class="['h-full transition-all duration-1000', getStatusColor(budget.status)]"
                                        :style="{ width: Math.min(budget.percentage, 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
    
                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-2">
                                <button @click="editBudget(budget)" class="p-2 text-gray-500 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-all">
                                    <BarChart3 class="w-5 h-5" />
                                </button>
                                <button @click="deleteBudget(budget.id)" class="p-2 text-gray-500 hover:text-rose-500 hover:bg-rose-500/10 rounded-lg transition-all">
                                    <Trash2 class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Empty State -->
                <div v-else class="glass-card p-12 flex flex-col items-center justify-center text-center space-y-6">
                    <div class="w-20 h-20 rounded-full bg-white/5 flex items-center justify-center">
                        <AlertCircle class="w-10 h-10 text-gray-500" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">{{ __('no_active_budgets') }} {{ currentMonth }}</h3>
                        <p class="text-gray-400 max-w-sm mx-auto">{{ __('no_active_budgets_desc') }}</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button 
                            @click="showAutoSetupModal = true"
                            class="px-8 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2"
                        >
                            <BrainCircuit class="w-5 h-5" />
                            <span>{{ __('start_auto_setup') }}</span>
                        </button>
                        <button @click="openAddModal" class="px-8 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl font-bold transition-all border border-white/5">
                            {{ __('add_manual_budget') }}
                        </button>
                    </div>
                </div>
            </section>
    
            <!-- Auto-Setup Wizard Modal -->
            <div v-if="showAutoSetupModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/80 backdrop-blur-sm">
                <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl animate-in zoom-in-95 duration-200 overflow-hidden overflow-y-auto max-h-[90vh]">
                    <!-- Header -->
                    <div class="p-6 border-b border-white/5 flex items-center justify-between bg-emerald-500/5">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-400">
                                <BrainCircuit class="w-6 h-6" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">{{ __('auto_setup_wizard') }}</h2>
                                <p class="text-xs text-gray-500 mt-1 uppercase tracking-wider font-semibold">{{ __('step_of').replace(':step', wizardStep).replace(':total', 3) }}</p>
                            </div>
                        </div>
                        <button @click="showAutoSetupModal = false" class="p-2 text-gray-500 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
    
                    <div class="p-8">
                        <!-- Step 1: Income -->
                        <div v-if="wizardStep === 1" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                            <div class="space-y-2">
                                <h3 class="text-lg font-bold text-white">{{ __('estimated_income_title') }}</h3>
                                <p class="text-sm text-gray-400">{{ __('estimated_income_desc') }}</p>
                            </div>
    
                            <div>
                                <CurrencyInput 
                                    v-model="autoSetupForm.estimated_income"
                                    placeholder="Enter monthly income"
                                />
                                <p v-if="autoSetupForm.errors.estimated_income" class="mt-1 text-xs text-rose-500">{{ autoSetupForm.errors.estimated_income }}</p>
                            </div>
    
                            <div class="pt-4">
                                <button 
                                    @click="wizardStep = 2"
                                    :disabled="!autoSetupForm.estimated_income"
                                    class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold rounded-xl transition-all flex items-center justify-center gap-2"
                                >
                                    <span>{{ __('continue') }}</span>
                                    <ArrowRight class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
    
                        <!-- Step 2: Goal -->
                        <div v-if="wizardStep === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                            <div class="space-y-2">
                                <h3 class="text-lg font-bold text-white">{{ __('financial_goal_title') }}</h3>
                                <p class="text-sm text-gray-400">{{ __('financial_goal_desc') }}</p>
                            </div>
    
                            <div class="grid grid-cols-1 gap-4">
                                <button 
                                    @click="autoSetupForm.goal = 'aggressive_saving'"
                                    :class="['p-4 rounded-xl border text-left transition-all group', autoSetupForm.goal === 'aggressive_saving' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-white/5 border-white/5 text-gray-400 hover:bg-white/10']"
                                >
                                    <div class="flex items-center gap-3 mb-2">
                                        <Target :class="['w-5 h-5', autoSetupForm.goal === 'aggressive_saving' ? 'text-indigo-400' : 'text-gray-500']" />
                                        <span class="font-bold">{{ __('aggressive_saving') }}</span>
                                    </div>
                                    <p class="text-xs opacity-70">{{ __('aggressive_saving_desc') }}</p>
                                </button>
    
                                <button 
                                    @click="autoSetupForm.goal = 'balanced_life'"
                                    :class="['p-4 rounded-xl border text-left transition-all group', autoSetupForm.goal === 'balanced_life' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-white/5 border-white/5 text-gray-400 hover:bg-white/10']"
                                >
                                    <div class="flex items-center gap-3 mb-2">
                                        <Zap :class="['w-5 h-5', autoSetupForm.goal === 'balanced_life' ? 'text-indigo-400' : 'text-gray-500']" />
                                        <span class="font-bold">{{ __('balanced_lifestyle') }}</span>
                                    </div>
                                    <p class="text-xs opacity-70">{{ __('balanced_lifestyle_desc') }}</p>
                                </button>
                            </div>
    
                            <div class="pt-4 flex gap-3">
                                <button @click="wizardStep = 1" class="flex-1 py-4 bg-gray-800 text-white font-bold rounded-xl hover:bg-gray-700 transition-all">{{ __('back') }}</button>
                                <button @click="wizardStep = 3" class="flex-[2] py-4 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 transition-all flex items-center justify-center gap-2">
                                    <span>{{ __('continue') }}</span>
                                    <ArrowRight class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
    
                        <!-- Step 3: Lifestyle -->
                        <div v-if="wizardStep === 3" class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                            <div class="space-y-2">
                                <h3 class="text-lg font-bold text-white">{{ __('daily_activity_title') }}</h3>
                                <p class="text-sm text-gray-400">{{ __('daily_activity_desc') }}</p>
                            </div>
    
                            <div class="grid grid-cols-1 gap-4">
                                <button 
                                    @click="autoSetupForm.lifestyle = 'commuter'"
                                    :class="['p-4 rounded-xl border text-left transition-all group', autoSetupForm.lifestyle === 'commuter' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-white/5 border-white/5 text-gray-400 hover:bg-white/10']"
                                >
                                    <div class="flex items-center gap-3 mb-2">
                                        <Car :class="['w-5 h-5', autoSetupForm.lifestyle === 'commuter' ? 'text-indigo-400' : 'text-gray-500']" />
                                        <span class="font-bold">{{ __('commuter') }}</span>
                                    </div>
                                    <p class="text-xs opacity-70">{{ __('commuter_desc') }}</p>
                                </button>
    
                                <button 
                                    @click="autoSetupForm.lifestyle = 'homebody'"
                                    :class="['p-4 rounded-xl border text-left transition-all group', autoSetupForm.lifestyle === 'homebody' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-white/5 border-white/5 text-gray-400 hover:bg-white/10']"
                                >
                                    <div class="flex items-center gap-3 mb-2">
                                        <Home :class="['w-5 h-5', autoSetupForm.lifestyle === 'homebody' ? 'text-indigo-400' : 'text-gray-500']" />
                                        <span class="font-bold">{{ __('homebody') }}</span>
                                    </div>
                                    <p class="text-xs opacity-70">{{ __('homebody_desc') }}</p>
                                </button>
                                
                                <button 
                                    @click="autoSetupForm.lifestyle = 'standard'"
                                    :class="['p-4 rounded-xl border text-left transition-all group', autoSetupForm.lifestyle === 'standard' ? 'bg-indigo-600/20 border-indigo-500 text-white' : 'bg-white/5 border-white/5 text-gray-400 hover:bg-white/10']"
                                >
                                    <div class="flex items-center gap-3 mb-2">
                                        <Zap :class="['w-5 h-5', autoSetupForm.lifestyle === 'standard' ? 'text-indigo-400' : 'text-gray-500']" />
                                        <span class="font-bold">{{ __('standard') }}</span>
                                    </div>
                                    <p class="text-xs opacity-70">{{ __('standard_desc') }}</p>
                                </button>
                            </div>
    
                            <div class="pt-4 flex gap-3">
                                <button @click="wizardStep = 2" class="flex-1 py-4 bg-gray-800 text-white font-bold rounded-xl hover:bg-gray-700 transition-all">{{ __('back') }}</button>
                                <button 
                                    @click="handleAutoSetup" 
                                    :disabled="autoSetupForm.processing"
                                    class="flex-[2] py-4 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20"
                                >
                                    <span v-if="autoSetupForm.processing">{{ __('creating') }}</span>
                                    <span v-else>{{ __('generate_plan') }}</span>
                                    <CheckCircle2 class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Budget Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/80 backdrop-blur-sm">
                <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-md shadow-2xl animate-in zoom-in-95 duration-200 overflow-hidden overflow-y-auto max-h-[90vh]">
                    <div class="p-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                        <div>
                            <h2 class="text-xl font-bold text-white">{{ isEditing ? __('update_budget') : __('set_new_budget') }}</h2>
                            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wider font-semibold">{{ __('for_month') }} {{ currentMonth }}</p>
                        </div>
                        <button @click="showModal = false" class="p-2 text-gray-500 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
    
                    <form @submit.prevent="submit" class="p-8 space-y-8">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">{{ __('category') }}</label>
                            <CategoryCombobox 
                                v-model="form.category_id"
                                :categories="availableCategories"
                                type="expense"
                                item-value="id" 
                                item-label="name"
                                :placeholder="__('select_or_type_category')"
                            />
                            <p v-if="form.errors.category_id" class="mt-1 text-xs text-rose-500">{{ form.errors.category_id }}</p>
                        </div>
    
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">{{ __('monthly_limit') }}</label>
                            <CurrencyInput 
                                v-model="form.limit"
                                :placeholder="__('set_budget_amount')"
                            />
                            <p v-if="form.errors.limit" class="mt-1 text-xs text-rose-500">{{ form.errors.limit }}</p>
                        </div>
    
                        <div class="pt-4 flex gap-3">
                            <button 
                                type="button" 
                                @click="showModal = false"
                                class="flex-1 py-3 bg-gray-800 hover:bg-gray-700 text-white font-bold rounded-xl transition-all"
                            >
                                {{ __('cancel') }}
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="flex-2 py-3 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/20 px-8"
                            >
                                {{ isEditing ? __('save_changes') : __('confirm_budget') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Floating Add Budget Button (Mobile Only) -->
            <button 
                @click="openAddModal"
                class="md:hidden fixed bottom-24 right-6 w-14 h-14 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full shadow-2xl shadow-indigo-600/40 flex items-center justify-center z-40 transition-transform active:scale-95"
            >
                <Plus class="w-8 h-8" />
            </button>
        </Layout>
    </template>
    
<style scoped>
.flex-2 { flex: 2; }
</style>
