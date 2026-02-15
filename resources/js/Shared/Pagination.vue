<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: Array
});

const cleanLabel = (label) => {
    if (label.includes('Previous')) return 'Prev';
    if (label.includes('Next')) return 'Next';
    return label;
};
</script>

<template>
    <div v-if="links.length > 3" class="flex items-center gap-1.5">
        <template v-for="(link, key) in links" :key="key">
            <!-- Ellipsis or Disabled State -->
            <div v-if="link.url === null" 
                class="px-3 py-2 text-xs font-bold text-slate-300 select-none" 
                v-html="cleanLabel(link.label)"
            ></div>
            
            <!-- Active/Normal Page Link -->
            <Link v-else 
                :href="link.url"
                v-html="cleanLabel(link.label)"
                preserve-scroll
                class="min-w-[38px] h-[38px] flex items-center justify-center px-3 py-2 text-xs font-bold rounded-xl border transition-all duration-300"
                :class="[
                    link.active 
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-100 ring-2 ring-indigo-600/10' 
                    : 'bg-white text-slate-500 border-slate-200 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-100 hover:shadow-sm'
                ]"
            />
        </template>
    </div>
</template>
