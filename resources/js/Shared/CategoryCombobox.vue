<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { Check, ChevronsUpDown, Plus } from 'lucide-vue-next';

const props = defineProps({
    modelValue: [String, Number],
    categories: {
        type: Array,
        default: () => []
    },
    type: String, // 'income' or 'expense' to filter list
    placeholder: String,
    itemValue: {
        type: String,
        default: 'name' // Default to name for backward compatibility
    },
    itemLabel: {
        type: String,
        default: 'name'
    },
    canCreate: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue', 'limitReached']);

const isOpen = ref(false);
const searchQuery = ref('');
const inputRef = ref(null);
const triggerRef = ref(null);
const dropdownPosition = ref({ top: '0px', left: '0px', width: '0px' });

const filteredCategories = computed(() => {
    // Filter by type first
    const byType = props.categories.filter(c => c.type === props.type);
    
    if (!searchQuery.value) return byType;
    
    return byType.filter(c => 
        c[props.itemLabel].toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const selectedLabel = computed(() => {
    if (!props.modelValue) return '';
    // Find category where itemValue matches modelValue
    const found = props.categories.find(c => c[props.itemValue] === props.modelValue);
    return found ? found[props.itemLabel] : props.modelValue;
});

const updatePosition = () => {
    if (triggerRef.value) {
        const rect = triggerRef.value.getBoundingClientRect();
        dropdownPosition.value = {
            top: `${rect.bottom + 8}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`
        };
    }
};

const openDropdown = () => {
    updatePosition();
    isOpen.value = true;
};


// Handle scroll/resize to update position or close
const handleScroll = () => {
    if (isOpen.value) updatePosition();
};

onMounted(() => {
    window.addEventListener('resize', handleScroll);
    window.addEventListener('scroll', handleScroll, true); // true for capturing scroll in modal
});

onUnmounted(() => {
    window.removeEventListener('resize', handleScroll);
    window.removeEventListener('scroll', handleScroll, true);
});


const selectCategory = (category) => {
    if (typeof category === 'string') {
        if (!props.canCreate) {
            emit('limitReached');
            isOpen.value = false;
            searchQuery.value = '';
            return;
        }
        // Creating new (passed string)
        emit('update:modelValue', category);
    } else {
        // Selecting existing (passed object)
        emit('update:modelValue', category[props.itemValue]);
    }
    isOpen.value = false;
    searchQuery.value = '';
};
</script>

<template>
    <div ref="triggerRef" class="relative">
        <!-- Trigger/Input -->
        <div 
            class="w-full relative cursor-pointer"
            @click="openDropdown"
        >
             <input 
                ref="inputRef"
                type="text" 
                v-model="searchQuery" 
                class="w-full bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm px-4 py-3 pr-10 cursor-pointer"
                :placeholder="(isOpen && !modelValue) ? __('search_placeholder') : (modelValue ? '' : (placeholder || __('select_or_type_category')))"
                @focus="openDropdown"
            >
            <div v-if="modelValue && !searchQuery" class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <span class="text-slate-900 font-semibold text-sm">{{ selectedLabel }}</span>
            </div>
             <!-- Hide input text if modelValue exists and not searching to simulate select -->
             <div v-if="modelValue && !searchQuery" class="absolute inset-0 bg-transparent" @click="openDropdown; searchQuery = ''"></div>

            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <ChevronsUpDown class="w-5 h-5 text-gray-400" />
            </span>
        </div>

        <!-- Dropdown (Teleported) -->
        <Teleport to="body">
            <template v-if="isOpen">
                <!-- Backdrop -->
                <div @click="isOpen = false" class="fixed inset-0 z-[9998] bg-transparent cursor-default"></div>
                
                <!-- Menu -->
                <div 
                    class="fixed z-[9999] max-h-60 overflow-auto bg-white border border-slate-200 rounded-2xl shadow-2xl py-2 animate-in fade-in slide-in-from-top-2 duration-200 ring-1 ring-slate-900/5 origin-top"
                    :style="dropdownPosition"
                >
                    <div 
                        v-if="searchQuery && !filteredCategories.find(c => c[itemLabel].toLowerCase() === searchQuery.toLowerCase())"
                        @click="selectCategory(searchQuery)"
                        class="px-5 py-3 hover:bg-indigo-50 text-indigo-600 cursor-pointer flex items-center gap-2 border-b border-slate-100 font-semibold text-sm"
                    >
                        <Plus class="w-4 h-4" />
                        <span>{{ __('create_new') }} "<strong>{{ searchQuery }}</strong>"</span>
                    </div>

                     <div 
                        v-for="category in filteredCategories" 
                        :key="category.id"
                        @click="selectCategory(category)"
                        class="px-5 py-3 hover:bg-slate-50 cursor-pointer flex items-center justify-between group transition-colors"
                        :class="{'bg-indigo-50': modelValue === category[itemValue]}"
                    >
                        <div class="flex items-center gap-3">
                            <div :class="[category.color, 'w-2.5 h-2.5 rounded-full shadow-sm']"></div>
                            <span class="text-sm" :class="modelValue === category[itemValue] ? 'text-indigo-700 font-semibold' : 'text-slate-900 font-medium'">{{ category[itemLabel] }}</span>
                        </div>
                        <Check v-if="modelValue === category[itemValue]" class="w-4 h-4 text-indigo-700" />
                    </div>

                    <div v-if="filteredCategories.length === 0 && !searchQuery" class="px-4 py-2 text-gray-500 text-sm">
                        {{ __('no_categories_found') }}
                    </div>
                </div>
            </template>
        </Teleport>
    </div>
</template>
