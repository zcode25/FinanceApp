<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { driver } from "driver.js";
import "driver.js/dist/driver.css";
import { Head, usePage, useForm } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
import { Plus, Edit2, Trash2, Lock, X, Check, TrendingUp, TrendingDown, Crown, AlertCircle } from 'lucide-vue-next';
import PremiumUpsellModal from '@/Shared/PremiumUpsellModal.vue';
import Swal from 'sweetalert2';

import { router } from '@inertiajs/vue3';

const driverObj = ref(null);
const skipHTML = `<div class="mt-4 flex justify-start">
    <button onclick="window.dispatchEvent(new CustomEvent('skip-tour'))" class="text-[11px] font-semibold text-slate-400 hover:text-rose-500 transition-colors">Skip Tutorial</button>
</div>`;

const props = defineProps({
    categories: Array,
});

// Optimistic UI State
const localCategories = ref([...props.categories]);

watch(() => props.categories, (newVal) => {
    localCategories.value = [...newVal];
}, { deep: true });

const page = usePage();
const __ = (key, replacements = {}) => {
    let translation = page.props.translations?.[key] || key;
    Object.keys(replacements).forEach(r => {
        translation = translation.replace(`:${r}`, replacements[r]);
    });
    return translation;
};

const activeTab = ref('expense');
const showModal = ref(false);
const showDetailsModal = ref(false);
const editingCategory = ref(null);
const selectedCategoryForDetails = ref(null);
const showUpsellModal = ref(false);

const isPremium = computed(() => !!page.props.auth.user.is_premium);
const customCategoryCount = computed(() => localCategories.value.filter(c => c.user_id !== null).length);

const openDetailsMobile = (category) => {
    if (window.innerWidth < 768) {
        selectedCategoryForDetails.value = category;
        showDetailsModal.value = true;
    }
};

// Form handling
const form = useForm({
    name: '',
    type: 'expense',
    color: 'bg-indigo-500',
});

const isEditing = computed(() => !!editingCategory.value);

const colors = [
    'bg-red-500', 'bg-orange-500', 'bg-amber-500',
    'bg-yellow-500', 'bg-lime-500', 'bg-green-500',
    'bg-emerald-500', 'bg-teal-500', 'bg-cyan-500',
    'bg-sky-500', 'bg-blue-500', 'bg-indigo-500',
    'bg-violet-500', 'bg-purple-500', 'bg-fuchsia-500',
    'bg-pink-500'
];

const filteredCategories = (type) => {
    return localCategories.value.filter(c => c.type === type);
};

const openCreateModal = () => {
    // Check limit for non-premium users
    if (!isPremium.value && customCategoryCount.value >= 3) {
        showUpsellModal.value = true;
        return;
    }

    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    form.type = activeTab.value; // Default to current tab
    form.color = 'bg-indigo-500';
    showModal.value = true;
};

const openEditModal = (category) => {
    if (!category.user_id) return; 
    form.clearErrors();
    editingCategory.value = category;
    
    // Populate form
    form.name = category.name;
    form.type = category.type;
    form.color = category.color;
    
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingCategory.value = null;
};

const showToast = (title, icon = 'success') => {
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

    const colors = {
        success: '#1e293b',
        error: '#be123c',
    };

    const backgrounds = {
        success: '#ffffff',
        error: '#ffffff',
    };
    
    const borders = {
        success: 'border-slate-100',
        error: 'border-rose-100',
    };

    Toast.fire({
        icon: icon,
        title: title,
        background: backgrounds[icon],
        color: colors[icon],
        customClass: {
            popup: `swal2-toast !rounded-2xl !p-4 shadow-xl border ${borders[icon]}`,
            title: icon === 'error' ? '!text-sm !font-bold !text-rose-900' : '!text-sm !font-bold !text-slate-900',
        }
    });
};

const submit = () => {
    if (isEditing.value) {
        // Optimistic Update
        const originalCategories = JSON.parse(JSON.stringify(localCategories.value));
        const index = localCategories.value.findIndex(c => c?.id === editingCategory.value?.id);
        
        if (index !== -1) {
            localCategories.value[index] = {
                ...localCategories.value[index],
                name: form.name,
                type: form.type,
                color: form.color
            };
        }
        
        const categoryId = editingCategory.value.id;
        showModal.value = false; // Just hide, don't clear form yet

        form.put(`/categories/${categoryId}`, {
            onSuccess: () => {
                closeModal(); // Now clear form
                showToast(__('category_updated'));
            },
            onError: () => {
                // Rollback
                localCategories.value = originalCategories;
                showModal.value = true;
                showToast('Failed to update category', 'error');
            }
        });
    } else {
        form.post('/categories', {
            onSuccess: () => {
                closeModal();
                showToast(__('category_created'));
            },
        });
    }
};

const deleteCategory = (category) => {
    if (!category.user_id) return;
    
    Swal.fire({
        title: __('delete_category_title', { name: category.name }),
        text: __('delete_category_text', { name: category.name }),
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
            const originalCategories = [...localCategories.value];
            localCategories.value = localCategories.value.filter(c => c.id !== category.id);

            router.delete(`/categories/${category.id}`, {
                onSuccess: () => {
                    showToast(__('category_deleted'));
                },
                onError: () => {
                    // Rollback
                    localCategories.value = originalCategories;
                    showToast('Failed to delete category', 'error');
                }
            });
        }
    });
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
                    localStorage.setItem('tour_state', 'hub_to_tracker');
                    router.visit('/dashboard');
                } else {
                    localStorage.setItem('tour_state', 'tracker_intro');
                    router.visit('/tracker');
                }
                driverObj.value.destroy();
            } else {
                driverObj.value.moveNext();
            }
        },
        steps: [
            {
                element: '#step-add-category',
                popover: {
                    title: `<span class="text-xl font-bold">${__('tour_personalize_categories_title')}</span>`,
                    description: __('tour_personalize_categories_desc') + skipHTML,
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: '#step-category-tabs',
                popover: {
                    title: `<span class="text-xl font-bold">${__('tour_income_expense_title')}</span>`,
                    description: __('tour_income_expense_desc') + skipHTML,
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: '#tour-categories-grid',
                popover: {
                    title: `<span class="text-xl font-bold">${__('tour_category_system_title')}</span>`,
                    description: __('tour_category_system_desc') + skipHTML,
                    side: "bottom",
                    align: 'start'
                }
            },
            {
                element: isMobile ? '#mobile-nav-home' : '#nav-tracker',
                popover: {
                    title: isMobile ? __('tour_return_dashboard_title') : `<span class="text-xl font-bold">${__('tour_wealth_map_title')}</span>`,
                    description: isMobile 
                        ? __('tour_return_dashboard_desc') + skipHTML
                        : __('tour_wealth_map_desc') + skipHTML,
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
        'analysis_intro', 'budget_setup', 'goals_setup', 'hub_to_categories', 'categories_setup'
    ];
    

    // Guard against duplicate triggers
    if (driverObj.value && document.querySelector('.driver-popover')) {
        return;
    }

    if (!tourState || (tourState && catchUpStates.includes(tourState))) {
        if (!tourState || tourState !== 'categories_setup') {
            // If it's null, check if they should see the tour
            if (!tourState && tourCompleted) {
                return;
            }
            localStorage.setItem('tour_state', 'categories_setup');
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
    <Head title="Categories" />

    <Layout>
        <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-30">
            <div class="space-y-1">
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('categories_title') }}</h1>
                <p class="text-sm md:text-base text-slate-500 font-medium">{{ __('categories_desc') }}</p>
            </div>
            <button id="step-add-category" @click="openCreateModal" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all shadow-lg shadow-indigo-200 hover:scale-[1.02] active:scale-95">
                <Plus class="w-5 h-5" />
                <span>{{ __('add_category') }}</span>
            </button>
        </header>

        <!-- Tabs -->
        <div id="step-category-tabs" class="flex md:inline-flex p-1 bg-slate-100 rounded-2xl mb-8 border border-slate-200/50 shadow-inner">
            <button 
                @click="activeTab = 'expense'"
                class="flex-1 md:flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all duration-300"
                :class="activeTab === 'expense' ? 'bg-white text-rose-600 shadow-sm ring-1 ring-slate-900/5 scale-100' : 'text-slate-500 hover:text-slate-700'"
            >
                {{ __('expense_flows') }}
            </button>
            <button 
                @click="activeTab = 'income'"
                class="flex-1 md:flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all duration-300"
                :class="activeTab === 'income' ? 'bg-white text-emerald-600 shadow-sm ring-1 ring-slate-900/5 scale-100' : 'text-slate-500 hover:text-slate-700'"
            >
                {{ __('income_sources') }}
            </button>
        </div>

        <!-- Category Grid -->
        <div id="tour-categories-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-12 pb-24 md:pb-0">
            <div 
                v-for="category in filteredCategories(activeTab)" 
                :key="category.id"
                @click="openDetailsMobile(category)"
                class="group relative glass-card p-5 md:p-6 transition-all duration-300 hover:bg-slate-50/50 cursor-pointer md:cursor-default active:scale-[0.98] active:md:scale-100"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 md:gap-5">
                        <div 
                            class="w-2.5 h-2.5 rounded-full shrink-0 shadow-sm"
                            :class="category.color"
                        ></div>
                        <div>
                            <h3 class="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors tracking-tight text-[14px] md:text-lg leading-tight">
                                {{ category.name }}
                            </h3>
                            <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ category.is_system ? 'System' : 'Custom' }}</p>
                        </div>
                    </div>
 
                    <div class="hidden md:flex items-center gap-2">
                        <template v-if="!category.is_system">
                            <button 
                                @click.stop="openEditModal(category)"
                                class="p-2.5 text-slate-400 hover:text-indigo-600 rounded-xl transition-all border border-slate-100 hover:shadow-sm"
                                :title="__('edit_category')"
                            >
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button 
                                @click.stop="deleteCategory(category)"
                                class="p-2.5 text-slate-400 hover:text-rose-600 rounded-xl transition-all border border-slate-100 hover:shadow-sm"
                                :title="__('delete_category_title', { name: category.name })"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </template>
                        <div v-else class="p-2.5 text-slate-200" :title="__('system_category_lock_msg')">
                            <Lock class="w-4 h-4" />
                        </div>
                    </div>
                </div>
            </div>
 
            <!-- Empty State -->
            <div 
                v-if="filteredCategories(activeTab).length === 0" 
                class="col-span-full py-20 text-center glass-card border-dashed border-slate-200"
            >
                <p class="font-bold text-sm text-slate-300">{{ __('no_categories') }}</p>
            </div>
        </div>
        

    </Layout>

    <!-- ADD/EDIT MODAL -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-[60] flex items-center justify-center px-4 sm:px-6">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
            <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="px-8 pt-8 pb-6 border-b border-slate-100 bg-white z-10">
                    <h2 class="text-xl font-bold text-slate-900">
                        {{ isEditing ? __('edit_category') : __('new_category') }}
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ isEditing ? __('update_category_desc') : __('new_category_desc') }}
                    </p>
                </div>

                <div class="p-8 overflow-y-auto custom-scrollbar">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Type Selection -->
                        <div class="flex p-1.5 bg-slate-100 rounded-2xl border border-slate-200">
                            <button 
                                type="button" 
                                @click="form.type = 'income'" 
                                :class="form.type === 'income' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'text-slate-500 hover:text-slate-900'" 
                                class="flex-1 py-3.5 rounded-xl transition-all font-bold text-sm flex items-center justify-center gap-2"
                            >
                                <TrendingUp class="w-4 h-4" />
                                {{ __('income') }}
                            </button>
                            <button 
                                type="button" 
                                @click="form.type = 'expense'" 
                                :class="form.type === 'expense' ? 'bg-rose-500 text-white shadow-lg shadow-rose-200' : 'text-slate-500 hover:text-slate-900'" 
                                class="flex-1 py-3.5 rounded-xl transition-all font-bold text-sm flex items-center justify-center gap-2"
                            >
                                <TrendingDown class="w-4 h-4" />
                                {{ __('expense') }}
                            </button>
                        </div>
                        <p v-if="form.errors.type" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                            <AlertCircle class="w-3.5 h-3.5" />
                            {{ form.errors.type }}
                        </p>
    
                        <!-- Name Input -->
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-1">{{ __('category_name') }}</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-semibold text-slate-700 placeholder-slate-400"
                                :placeholder="__('category_name_placeholder')"
                                required
                            >
                            <p v-if="form.errors.name" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                <AlertCircle class="w-3.5 h-3.5" />
                                {{ form.errors.name }}
                            </p>
                        </div>
    
                        <!-- Color Picker -->
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-slate-700 ml-1">{{ __('color_tag') }}</label>
                            <div class="grid grid-cols-8 gap-3">
                                <button 
                                    v-for="color in colors" 
                                    :key="color"
                                    type="button"
                                    @click="form.color = color"
                                    class="w-9 h-9 rounded-full flex items-center justify-center transition-all hover:scale-110 active:scale-95 shadow-sm ring-2 ring-white"
                                    :class="[color, form.color === color ? 'ring-offset-2 ring-indigo-500 scale-110' : 'opacity-70 hover:opacity-100']"
                                >
                                    <Check v-if="form.color === color" class="w-4 h-4 text-white drop-shadow-md" />
                                </button>
                            </div>
                            <p v-if="form.errors.color" class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1.5">
                                <AlertCircle class="w-3.5 h-3.5" />
                                {{ form.errors.color }}
                            </p>
                        </div>
                    </form>
                </div>
                
                <!-- Footer Actions -->
                <div class="p-6 border-t border-slate-100 bg-slate-50 flex gap-3 z-10">
                    <button 
                        @click="closeModal"
                        type="button"
                        class="flex-1 px-6 py-3.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-sm hover:bg-white hover:border-slate-300 hover:text-slate-800 transition-all shadow-sm active:scale-95"
                    >
                        {{ __('cancel') }}
                    </button>
                    <button 
                        @click="submit"
                        :disabled="form.processing"
                        class="flex-1 px-6 py-3.5 rounded-xl bg-indigo-600 text-white font-bold text-sm hover:bg-indigo-700 shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
                        <span v-else>{{ isEditing ? __('update_category_btn') : __('save_category_btn') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- DETAILS MODAL -->
    <Teleport to="body">
        <div v-if="showDetailsModal && selectedCategoryForDetails" class="fixed inset-0 z-[60] flex items-center justify-center px-4 sm:px-6">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="showDetailsModal = false"></div>
            <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden transform transition-all p-6">
                <!-- Header -->
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner" :class="selectedCategoryForDetails.color">
                             <div class="w-6 h-6 bg-white/30 rounded-full"></div>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 leading-tight">{{ selectedCategoryForDetails.name }}</h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 mt-1 capitalize border border-slate-200">
                                {{ selectedCategoryForDetails.type }}
                            </span>
                        </div>
                    </div>
                    <button @click="showDetailsModal = false" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-900 transition-all">
                        <X class="w-6 h-6" />
                    </button>
                </div>
                
                <div class="space-y-6">
                    <!-- Status & System Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Type</p>
                            <p class="text-sm font-bold text-slate-700 capitalize">{{ selectedCategoryForDetails.type }}</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Source</p>
                            <p class="text-sm font-bold text-slate-700">{{ selectedCategoryForDetails.is_system ? 'System' : 'Custom' }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="!selectedCategoryForDetails.is_system" class="grid grid-cols-2 gap-3 pt-2">
                            <button 
                            @click="openEditModal(selectedCategoryForDetails); showDetailsModal = false"
                            class="flex items-center justify-center gap-2 py-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-xl font-bold text-sm transition-colors"
                        >
                            <Edit2 class="w-4 h-4" />
                            {{ __('edit') }}
                        </button>
                        <button 
                            @click="deleteCategory(selectedCategoryForDetails); showDetailsModal = false"
                            class="flex items-center justify-center gap-2 py-3 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl font-bold text-sm transition-colors"
                        >
                            <Trash2 class="w-4 h-4" />
                            {{ __('delete') }}
                        </button>
                    </div>
                    <div v-else class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-center gap-3">
                        <Lock class="w-5 h-5 text-amber-500 shrink-0" />
                        <p class="text-xs font-bold text-amber-700 leading-relaxed">{{ __('system_category_lock_msg') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Premium Upsell Modal -->
    <PremiumUpsellModal 
        :show="showUpsellModal" 
        title="Category Limit Reached"
        description="Unlock unlimited categories to organize your expenses exactly the way you want with the Professional Plan."
        @close="showUpsellModal = false" 
    />
</template>

