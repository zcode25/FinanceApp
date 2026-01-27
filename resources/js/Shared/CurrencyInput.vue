<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: [Number, String],
    placeholder: String,
    currency: {
        type: String,
        default: 'IDR'
    }
});

const emit = defineEmits(['update:modelValue']);

const displayValue = ref('');

const formatCurrency = (value) => {
    if (value === '' || value === null || value === undefined) return '';
    return new Intl.NumberFormat(props.currency === 'IDR' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: props.currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(value);
};

// Initialize display value
if (props.modelValue) {
    displayValue.value = formatCurrency(props.modelValue);
}

const errorMessage = ref('');
let errorTimeout;

const onInput = (event) => {
    // Determine allowed Decimal Separator based on currency
    // IDR uses comma (,), USD uses dot (.)
    const decimalSeparator = props.currency === 'IDR' ? ',' : '.';
    const allowedChars = new RegExp(`[^0-9${decimalSeparator}]`, 'g');

    // Validation
    if (event.inputType === 'insertText' && event.data) {
        // Check if data contains disallowed characters
        if (allowedChars.test(event.data)) {
            errorMessage.value = `Numbers and '${decimalSeparator}' only`;
            clearTimeout(errorTimeout);
            errorTimeout = setTimeout(() => errorMessage.value = '', 3000);
        } else {
             errorMessage.value = '';
        }
    }

    let rawValue = event.target.value;

    // Remove invalid characters for parsing
    // For IDR: 12.345,67 -> Remove dots, replace comma with dot -> 12345.67
    // For USD: 12,345.67 -> Remove commas, keep dot -> 12345.67
    
    let normalizedValue;
    if (props.currency === 'IDR') {
        // Remove dots (thousand separators)
        normalizedValue = rawValue.replace(/\./g, '');
        // Replace comma with dot (decimal separator)
        normalizedValue = normalizedValue.replace(/,/g, '.');
    } else {
        // Remove commas (thousand separators)
        normalizedValue = rawValue.replace(/,/g, '');
    }

    // Remove any other non-numeric chars except dot
    normalizedValue = normalizedValue.replace(/[^0-9.]/g, '');

    // Prevent multiple decimal points
    const parts = normalizedValue.split('.');
    if (parts.length > 2) {
        normalizedValue = parts[0] + '.' + parts.slice(1).join('');
    }

    // Parse to float
    // If ends with dot, don't parse completely yet or it strips the dot
    const isDecimal = normalizedValue.includes('.');
    let numericValue = parseFloat(normalizedValue);

    if (isNaN(numericValue)) numericValue = 0;

    // Emit number to parent
    emit('update:modelValue', numericValue);
    
    // Update display only on blur or if it's a finished number
    // We don't update displayValue while typing usually to avoid cursor jumping
    // BUT we need to show the raw input while typing so user can type separators
    // So we just update the model, but keep the display value as is (what user typed)?
    // Actually, keeping what user typed is tricky if we want live formatting.
    // For now, let's stick to "User types, we format on blur" OR "User types raw, we format".
    
    // Current simple approach improvement: 
    // Just allow the user to type freely, parsing in background.
    displayValue.value = rawValue;
};

// Format on Blur to ensure pretty display
const onBlur = () => {
    errorMessage.value = '';
    displayValue.value = formatCurrency(props.modelValue);
};

watch(() => props.modelValue, (newVal) => {
    displayValue.value = formatCurrency(newVal);
});

watch(() => props.currency, () => {
    // Re-format if currency changes
    if (props.modelValue) {
        displayValue.value = formatCurrency(props.modelValue);
    }
});
</script>

<template>
    <div class="relative">
        <input 
            type="text" 
            :value="displayValue" 
            @input="onInput"
            @blur="onBlur"
            class="w-full input-premium px-4 py-3 font-mono font-medium placeholder:font-sans transition-all duration-200"
            :class="{ 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20': errorMessage }"
            :placeholder="placeholder || 'Rp 0'"
        >
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-if="errorMessage" class="absolute -bottom-6 left-0 text-[10px] font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded flex items-center gap-1.5">
                <span class="w-1 h-1 rounded-full bg-rose-500 inline-block"></span>
                {{ errorMessage }}
            </div>
        </transition>
    </div>
</template>
