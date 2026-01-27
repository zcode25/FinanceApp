<script setup>
import { ref, computed } from 'vue';
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
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const inputRef = ref(null);

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

const selectCategory = (category) => {
    if (typeof category === 'string') {
        // Creating new (passed string)
        emit('update:modelValue', category);
    } else {
        // Selecting existing (passed object)
        emit('update:modelValue', category[props.itemValue]);
    }
    isOpen.value = false;
    searchQuery.value = '';
};

// Close when clicking outside (simple implementation)
</script>

<template>
    <div class="relative">
        <!-- Trigger/Input -->
        <div 
            class="w-full relative cursor-pointer"
            @click="isOpen = true"
        >
             <input 
                ref="inputRef"
                type="text" 
                v-model="searchQuery" 
                class="w-full input-premium px-4 py-3 pr-10 cursor-pointer"
                :placeholder="(isOpen && !modelValue) ? 'Search...' : (modelValue ? '' : (placeholder || 'Select or type category...'))"
                @focus="isOpen = true"
            >
            <div v-if="modelValue && !searchQuery" class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <span class="text-white font-medium">{{ selectedLabel }}</span>
            </div>
             <!-- Hide input text if modelValue exists and not searching to simulate select -->
             <div v-if="modelValue && !searchQuery" class="absolute inset-0 bg-transparent" @click="isOpen = true; searchQuery = ''"></div>

            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <ChevronsUpDown class="w-5 h-5 text-gray-400" />
            </span>
        </div>

        <!-- Dropdown -->
        <div 
            v-if="isOpen" 
            class="absolute z-50 w-full mt-1 max-h-60 overflow-auto bg-gray-800 border border-gray-700 rounded-lg shadow-xl py-1 transform transition-all"
        >
            <!-- Create New Option -->
            <div 
                v-if="searchQuery && !filteredCategories.find(c => c[itemLabel].toLowerCase() === searchQuery.toLowerCase())"
                @click="selectCategory(searchQuery)"
                class="px-4 py-2 hover:bg-indigo-600/20 text-indigo-400 cursor-pointer flex items-center gap-2 border-b border-white/5"
            >
                <Plus class="w-4 h-4" />
                <span>Create "<strong>{{ searchQuery }}</strong>"</span>
            </div>

            <!-- Existing Options -->
             <div 
                v-for="category in filteredCategories" 
                :key="category.id"
                @click="selectCategory(category)"
                class="px-4 py-2 hover:bg-white/5 cursor-pointer flex items-center justify-between group"
                :class="{'bg-indigo-500/10 text-indigo-400': modelValue === category[itemValue]}"
            >
                <div class="flex items-center gap-2">
                    <div :class="[category.color, 'w-2 h-2 rounded-full']"></div>
                    <span>{{ category[itemLabel] }}</span>
                </div>
                <Check v-if="modelValue === category[itemValue]" class="w-4 h-4" />
            </div>

            <div v-if="filteredCategories.length === 0 && !searchQuery" class="px-4 py-2 text-gray-500 text-sm">
                No categories found.
            </div>
        </div>
        
        <!-- Backdrop to close -->
        <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40 bg-transparent cursor-default"></div>
    </div>
</template>
