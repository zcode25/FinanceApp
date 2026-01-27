<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps({
    links: Array
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-2 mt-6">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" 
                class="px-4 py-2 text-sm text-gray-500 rounded-lg border border-transparent" 
                v-html="link.label"
            ></div>
            
            <Link v-else 
                :href="link.url"
                v-html="link.label"
                class="px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200"
                :class="link.active ? 'bg-indigo-600 text-white border-indigo-500 shadow-lg shadow-indigo-500/20' : 'bg-gray-800 text-gray-400 border-gray-700 hover:bg-gray-700 hover:text-white'"
            />
        </template>
    </div>
</template>
