<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Globe, ChevronDown } from 'lucide-vue-next';

const page = usePage();
const currentLocale = computed(() => page.props.locale || 'en');
const isOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
    document.addEventListener('touchstart', closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
    document.removeEventListener('touchstart', closeDropdown);
});

const switchLanguage = (lang) => {
    router.post(route('locale.update'), {
        locale: lang
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
        }
    });
};
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <button 
            @click.stop="toggleDropdown"
            class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all active:scale-95"
            :class="{ 'bg-indigo-50 text-indigo-600 ring-2 ring-indigo-100 ring-offset-2': isOpen }"
        >
            <Globe class="w-4 h-4" />
            <span class="text-xs font-bold uppercase tracking-wider">{{ currentLocale }}</span>
            <ChevronDown class="w-3 h-3 transition-transform duration-300" :class="{ 'rotate-180': isOpen }" />
        </button>
        
        <!-- Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-show="isOpen" class="absolute right-0 top-full mt-2 z-50">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-2xl shadow-slate-200/50 p-2 min-w-[140px] backdrop-blur-3xl">
                    <button 
                        @click="switchLanguage('en')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition-all',
                            currentLocale === 'en' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50'
                        ]"
                    >
                        English
                        <span v-if="currentLocale === 'en'" class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                    </button>
                    <button 
                        @click="switchLanguage('id')"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition-all',
                            currentLocale === 'id' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-600 hover:bg-slate-50'
                        ]"
                    >
                        Indonesia
                        <span v-if="currentLocale === 'id'" class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
