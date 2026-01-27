<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
import { Plus, Edit2, Trash2, Lock, X, Check } from 'lucide-vue-next';
import Swal from 'sweetalert2';

import { router } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
});

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;

const activeTab = ref('expense');
const showModal = ref(false);
const editingCategory = ref(null);

// Form handling
const form = useForm({
    name: '',
    type: 'expense',
    color: 'bg-gray-500',
});

const isEditing = computed(() => !!editingCategory.value);

const colors = [
    'bg-slate-500', 'bg-gray-500', 'bg-zinc-500', 
    'bg-red-500', 'bg-orange-500', 'bg-amber-500',
    'bg-yellow-500', 'bg-lime-500', 'bg-green-500',
    'bg-emerald-500', 'bg-teal-500', 'bg-cyan-500',
    'bg-sky-500', 'bg-blue-500', 'bg-indigo-500',
    'bg-violet-500', 'bg-purple-500', 'bg-fuchsia-500',
    'bg-pink-500', 'bg-rose-500'
];

const filteredCategories = (type) => {
    return props.categories.filter(c => c.type === type);
};

const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    form.type = activeTab.value; // Default to current tab
    form.color = 'bg-gray-500';
    showModal.value = true;
};

const openEditModal = (category) => {
    if (!category.user_id) return; 
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

const submit = () => {
    if (isEditing.value) {
        form.put(`/categories/${editingCategory.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/categories', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCategory = (category) => {
    if (!category.user_id) return;
    
    Swal.fire({
        title: __('delete_confirm_title') || 'Are you sure?',
        text: `Delete "${category.name}"? This will hide it from new transactions.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#374151',
        confirmButtonText: __('yes_delete') || 'Yes, delete it!',
        cancelButtonText: __('cancel') || 'Cancel',
        background: '#1f2937',
        color: '#ffffff'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/categories/${category.id}`, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Category has been removed.',
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
</script>

<template>
    <Head title="Categories" />

    <Layout>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">{{ __('categories_title') }}</h1>
                <p class="text-gray-400 mt-1">{{ __('categories_desc') }}</p>
            </div>
            <!-- Desktop & Mobile Button (Visible) -->
            <button 
                @click="openCreateModal"
                class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-bold transition-all shadow-lg shadow-indigo-500/20 w-fit"
            >
                <Plus class="w-5 h-5" />
                <span>{{ __('add_category') }}</span>
            </button>
        </div>

        <!-- Tabs -->
        <div class="flex p-1 bg-gray-800/50 rounded-xl w-full md:w-fit mb-8 border border-gray-700/50">
            <button 
                @click="activeTab = 'expense'"
                class="flex-1 md:flex-none px-6 py-2.5 rounded-lg font-medium transition-all duration-200 text-center"
                :class="activeTab === 'expense' ? 'bg-rose-500/10 text-rose-400 shadow-sm ring-1 ring-inset ring-rose-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-700/50'"
            >
                {{ __('expense') }}
            </button>
            <button 
                @click="activeTab = 'income'"
                class="flex-1 md:flex-none px-6 py-2.5 rounded-lg font-medium transition-all duration-200 text-center"
                :class="activeTab === 'income' ? 'bg-emerald-500/10 text-emerald-400 shadow-sm ring-1 ring-inset ring-emerald-500/20' : 'text-gray-400 hover:text-white hover:bg-gray-700/50'"
            >
                {{ __('income') }}
            </button>
        </div>

        <!-- Category Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pb-24 md:pb-8">
            <div 
                v-for="category in filteredCategories(activeTab)" 
                :key="category.id"
                class="group relative bg-gray-800/40 backdrop-blur-sm border border-gray-700/50 rounded-xl p-4 transition-all hover:bg-gray-800/60 hover:border-gray-600/50 hover:shadow-lg hover:-translate-y-1"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div 
                            class="w-10 h-10 rounded-full flex items-center justify-center shadow-lg bg-gradient-to-br"
                            :class="[category.color, 'from-white/10 to-transparent']"
                        >
                            <div class="w-3 h-3 rounded-full bg-white/90 shadow-sm"></div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-white group-hover:text-indigo-300 transition-colors">
                                {{ category.name }}
                            </h3>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity">
                        <template v-if="!category.is_system">
                            <button 
                                @click="openEditModal(category)"
                                class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                                :title="__('edit')"
                            >
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button 
                                @click="deleteCategory(category)"
                                class="p-2 text-gray-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors"
                                :title="__('delete')"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </template>
                        <div v-else class="p-2 text-gray-500" title="System Category (Read-only)">
                            <Lock class="w-4 h-4" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div 
                v-if="filteredCategories(activeTab).length === 0" 
                class="col-span-full py-12 text-center text-gray-500 border-2 border-dashed border-gray-800 rounded-xl"
            >
                {{ __('no_categories') }}
            </div>
        </div>
        
        <!-- FAB Mobile -->
        <button 
            @click="openCreateModal"
            class="md:hidden fixed bottom-24 right-6 w-14 h-14 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full shadow-2xl shadow-indigo-600/40 flex items-center justify-center z-40 transition-transform active:scale-95"
        >
            <Plus class="w-8 h-8" />
        </button>
    </Layout>

    <!-- Modal Inline (Transaction Style) -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="closeModal" class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
        
        <div class="glass-card w-full max-w-lg relative z-10 animate-in fade-in zoom-in-95 duration-200 overflow-y-auto max-h-[90vh]">
            <div class="p-6 border-b border-white/5 flex items-center justify-between">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    {{ isEditing ? __('edit_category') : __('new_category') }}
                </h2>
                <button @click="closeModal" class="text-gray-400 hover:text-white transition-colors">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <div class="p-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Type Selection -->
                    <div class="grid grid-cols-2 gap-4 p-1 bg-gray-800/50 rounded-xl border border-white/5">
                        <button 
                            type="button" 
                            @click="form.type = 'income'" 
                            :class="form.type === 'income' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'text-gray-400 hover:text-white'" 
                            class="py-2.5 rounded-lg transition-all font-bold text-sm"
                        >
                            {{ __('income') }}
                        </button>
                        <button 
                            type="button" 
                            @click="form.type = 'expense'" 
                            :class="form.type === 'expense' ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/20' : 'text-gray-400 hover:text-white'" 
                            class="py-2.5 rounded-lg transition-all font-bold text-sm"
                        >
                            {{ __('expense') }}
                        </button>
                    </div>
                    <div v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</div>

                    <!-- Name Input -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">{{ __('category_name') }}</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            class="w-full bg-gray-900/50 border border-white/5 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all font-medium"
                            :class="form.errors.name ? 'border-red-500 focus:ring-red-500' : ''"
                            :placeholder="__('category_name_placeholder')"
                            required
                        >
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <!-- Color Picker -->
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">{{ __('color_tag') }}</label>
                        <div class="grid grid-cols-10 gap-2">
                            <button 
                                v-for="color in colors" 
                                :key="color"
                                type="button"
                                @click="form.color = color"
                                class="w-8 h-8 rounded-full flex items-center justify-center transition-transform hover:scale-110"
                                :class="[color, form.color === color ? 'ring-2 ring-white ring-offset-2 ring-offset-gray-800' : '']"
                            >
                                <Check v-if="form.color === color" class="w-4 h-4 text-white" />
                            </button>
                        </div>
                        <div v-if="form.errors.color" class="text-red-500 text-xs mt-1">{{ form.errors.color }}</div>
                    </div>

                    <!-- Preview -->
                    <div class="mt-4 p-4 bg-gray-900/50 rounded-xl border border-white/5 flex items-center justify-center">
                        <span 
                            class="px-3 py-1 rounded-full text-sm font-medium text-white flex items-center gap-2"
                            :class="form.color"
                        >
                            {{ form.name || __('category_name') }}
                        </span>
                    </div>

                    <div class="pt-2">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full py-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing" class="mr-2">...</span>
                            {{ isEditing ? __('update_category') : __('create_category') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
