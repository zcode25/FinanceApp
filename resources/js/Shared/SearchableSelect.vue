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
    },
    theme: {
        type: String,
        default: 'light' // 'light' or 'dark'
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
                class="w-full px-4 py-3 pr-10 cursor-pointer overflow-hidden truncate"
                :class="[
                    theme === 'dark' 
                        ? 'bg-white/5 border-white/10 text-white placeholder-indigo-300/50 rounded-2xl border backdrop-blur-md focus:ring-0 focus:border-white/20 transition-all font-semibold text-sm' 
                        : 'bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-sm'
                ]"
                :placeholder="(isOpen && !selectedLabel) ? __('search_placeholder') : (selectedLabel ? '' : placeholder)"
                @focus="isOpen = true"
            >
            <!-- Overlay to show selected value when not searching/focused -->
            <div v-if="selectedLabel && !searchQuery && !isOpen" class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <span :class="[theme === 'dark' ? 'text-white' : 'text-slate-900', 'font-semibold text-sm']">{{ selectedLabel }}</span>
            </div>
             <!-- Hide input placeholder text visually if value exists to avoid overlap -->
             <div v-if="selectedLabel && !searchQuery && !isOpen" class="absolute inset-0 bg-transparent" @click="isOpen = true; searchQuery = ''"></div>

            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <ChevronsUpDown :class="[theme === 'dark' ? 'text-indigo-400' : 'text-slate-400', 'w-5 h-5 transition-colors']" />
            </span>
        </div>

        <!-- Dropdown -->
        <div 
            v-if="isOpen" 
            class="absolute z-[100] w-full mt-2 max-h-60 overflow-auto bg-white border border-slate-200 rounded-2xl shadow-3xl py-2 transform transition-all animate-in fade-in slide-in-from-top-2 duration-200"
        >
             <div 
                v-for="option in filteredOptions" 
                :key="option.value"
                @click="selectOption(option)"
                class="px-5 py-3 hover:bg-slate-50 cursor-pointer flex items-center justify-between group transition-colors"
                :class="{'bg-indigo-50 text-indigo-600 font-semibold': modelValue === option.value, 'text-slate-900 font-medium': modelValue !== option.value}"
            >
                <span class="text-sm">{{ option.label }}</span>
                <Check v-if="modelValue === option.value" class="w-4 h-4" />
            </div>

            <div v-if="filteredOptions.length === 0" class="px-5 py-4 text-slate-400 text-xs font-semibold text-center">
                {{ __('no_results_found') }}
            </div>
        </div>
        
        <!-- Backdrop to close -->
        <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40 bg-transparent cursor-default"></div>
    </div>
</template>
