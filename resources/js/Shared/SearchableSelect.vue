<script setup>
import { ref, computed, watch } from 'vue';
import { Check, ChevronsUpDown } from 'lucide-vue-next';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array, 
        required: true,
        // Expects array of objects: { label: 'Display Name', value: 'value' } 
        // OR array of strings
    },
    placeholder: {
        type: String,
        default: 'Select option...'
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');

// Normalize options to consistent { label, value } format
const normalizedOptions = computed(() => {
    return props.options.map(opt => {
        if (typeof opt === 'object' && opt !== null) {
            return opt;
        }
        return { label: opt, value: opt };
    });
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return normalizedOptions.value;
    
    return normalizedOptions.value.filter(opt => 
        opt.label.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const selectedLabel = computed(() => {
    const found = normalizedOptions.value.find(opt => opt.value === props.modelValue);
    return found ? found.label : '';
});

const selectOption = (opt) => {
    emit('update:modelValue', opt.value);
    isOpen.value = false;
    searchQuery.value = '';
};
</script>

<template>
    <div class="relative">
        <!-- Trigger/Input -->
        <div 
            class="w-full relative cursor-pointer"
            @click="isOpen = true"
        >
             <input 
                type="text" 
                v-model="searchQuery" 
                class="w-full input-premium px-4 py-3 pr-10 cursor-pointer"
                :placeholder="(isOpen && !selectedLabel) ? 'Search...' : (selectedLabel ? '' : placeholder)"
                @focus="isOpen = true"
            >
            <!-- Overlay to show selected value when not searching/focused -->
            <div v-if="selectedLabel && !searchQuery && !isOpen" class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <span class="text-white font-medium">{{ selectedLabel }}</span>
            </div>
             <!-- Hide input placeholder text visually if value exists to avoid overlap, mainly hacky -->
             <div v-if="selectedLabel && !searchQuery && !isOpen" class="absolute inset-0 bg-transparent" @click="isOpen = true; searchQuery = ''"></div>

            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <ChevronsUpDown class="w-5 h-5 text-gray-400" />
            </span>
        </div>

        <!-- Dropdown -->
        <div 
            v-if="isOpen" 
            class="absolute z-[100] w-full mt-1 max-h-60 overflow-auto bg-gray-800 border border-gray-700 rounded-lg shadow-xl py-1 transform transition-all"
        >
             <div 
                v-for="option in filteredOptions" 
                :key="option.value"
                @click="selectOption(option)"
                class="px-4 py-2 hover:bg-white/5 cursor-pointer flex items-center justify-between group"
                :class="{'bg-indigo-500/10 text-indigo-400': modelValue === option.value}"
            >
                <span>{{ option.label }}</span>
                <Check v-if="modelValue === option.value" class="w-4 h-4" />
            </div>

            <div v-if="filteredOptions.length === 0" class="px-4 py-2 text-gray-500 text-sm">
                No results found.
            </div>
        </div>
        
        <!-- Backdrop to close -->
        <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40 bg-transparent cursor-default"></div>
    </div>
</template>
