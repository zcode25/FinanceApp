<script setup>
import { Link } from '@inertiajs/vue3';
import { __ } from '@/Plugins/i18n';
import { route } from 'ziggy-js';

const props = defineProps({
    currentLocale: String
});

const emit = defineEmits(['switchLanguage', 'scrollToTop']);

const handleLanguageSwitch = (lang) => {
    emit('switchLanguage', lang);
};

const handleScrollToTop = () => {
    emit('scrollToTop');
};
</script>

<template>
    <footer class="pt-16 md:pt-24 pb-8 md:pb-12 bg-slate-950 text-slate-400 border-t border-slate-800/50 overflow-hidden relative">
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-10 md:gap-12 mb-12 md:mb-20">
                <!-- Brand Column -->
                <div class="space-y-6">
                    <Link href="/" class="flex items-center gap-3 group/logo cursor-pointer" @click="handleScrollToTop">
                        <div class="flex items-center justify-center transition-all hover:scale-110">
                            <img src="/img/logo_vibefinance.png" class="h-8 w-auto object-contain brightness-110" alt="VibeFinance Logo">
                        </div>
                        <div class="flex flex-col leading-tight">
                            <span class="text-2xl tracking-tight text-white" style="font-family:'Outfit', sans-serif;">
                                <span class="font-semibold text-white">Vibe</span><span class="font-light text-indigo-400">Finance</span>
                            </span>
                            <span class="text-[10px] font-medium text-slate-500">Powered by terasweb.id</span>
                        </div>
                    </Link>
                    <p class="text-slate-400 max-w-sm leading-relaxed font-medium text-sm md:text-base">
                        {{ __('footer_tagline') }}
                    </p>
                </div>

                <!-- Legal & Contact Column -->
                <div class="space-y-6 w-full md:w-auto">
                    <ul class="flex flex-col md:flex-row md:flex-wrap gap-4 md:gap-x-8 md:gap-y-4">
                        <li><a href="mailto:info@terasweb.id" class="hover:text-white transition-colors flex items-center gap-2 group/link w-full md:w-auto p-2 md:p-0 rounded-lg md:rounded-none bg-slate-900/50 md:bg-transparent border border-slate-800 md:border-none"><div class="w-1.5 h-1.5 rounded-full bg-indigo-500 md:scale-0 group-hover/link:scale-100 transition-transform"></div> info@terasweb.id</a></li>
                        <li><Link :href="route('privacy.policy')" class="hover:text-white transition-colors flex items-center gap-2 group/link w-full md:w-auto p-2 md:p-0 rounded-lg md:rounded-none bg-slate-900/50 md:bg-transparent border border-slate-800 md:border-none"><div class="w-1.5 h-1.5 rounded-full bg-indigo-500 md:scale-0 group-hover/link:scale-100 transition-transform"></div> {{ __('privacy_policy') }}</Link></li>
                        <li><Link :href="route('terms.service')" class="hover:text-white transition-colors flex items-center gap-2 group/link w-full md:w-auto p-2 md:p-0 rounded-lg md:rounded-none bg-slate-900/50 md:bg-transparent border border-slate-800 md:border-none"><div class="w-1.5 h-1.5 rounded-full bg-indigo-500 md:scale-0 group-hover/link:scale-100 transition-transform"></div> {{ __('terms_of_service') }}</Link></li>
                        <li><Link :href="route('cookies.policy')" class="hover:text-white transition-colors flex items-center gap-2 group/link w-full md:w-auto p-2 md:p-0 rounded-lg md:rounded-none bg-slate-900/50 md:bg-transparent border border-slate-800 md:border-none"><div class="w-1.5 h-1.5 rounded-full bg-indigo-500 md:scale-0 group-hover/link:scale-100 transition-transform"></div> {{ __('cookies_policy') }}</Link></li>
                    </ul>
                </div>
            </div>
            <!-- Bottom Footer -->
            <div class="pt-8 border-t border-slate-900 flex flex-col-reverse md:flex-row justify-between items-center gap-6 text-center md:text-left">
                <p class="text-sm font-bold text-slate-600 italic">
                    &copy; 2026 VibeFinance powered by terasweb.id
                </p>
                <div class="flex items-center gap-4 text-sm font-bold text-slate-500 bg-slate-900/50 px-4 py-2 rounded-full border border-slate-800/50">
                    <button @click="handleLanguageSwitch('en')" :class="['transition-colors hover:text-white', currentLocale === 'en' ? 'text-white' : 'text-slate-500']">EN</button>
                    <span class="text-slate-700">|</span>
                    <button @click="handleLanguageSwitch('id')" :class="['transition-colors hover:text-white', currentLocale === 'id' ? 'text-white' : 'text-slate-500']">ID</button>
                </div>
            </div>
        </div>
    </footer>
</template>
