<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import LandingLanguageSwitcher from '@/Shared/LandingLanguageSwitcher.vue';
import { __ } from '@/Plugins/i18n';
import { Menu, X } from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const scrollToPricing = () => {
    isMobileMenuOpen.value = false; // Close menu if open
    const el = document.getElementById('pricing');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <nav class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-xl border-b border-slate-100 px-4 md:px-6 py-3 md:py-4 transition-all duration-300">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <Link href="/" class="flex items-center gap-2 group cursor-pointer relative z-50">
                <div class="flex items-center justify-center transition-all group-hover:scale-110 duration-300">
                    <img src="/img/logo_vibefinance.png" class="h-6 md:h-7 w-auto object-contain" alt="VibeFinance Logo">
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-lg md:text-xl tracking-tight text-slate-900" style="font-family:'Outfit', sans-serif;">
                        <span class="font-semibold">Vibe</span><span class="font-light text-indigo-600">Finance</span>
                    </span>
                    <span class="text-[9px] font-medium text-slate-400 md:block hidden">Powered by terasweb.id</span>
                </div>
            </Link>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-6">
                <LandingLanguageSwitcher />
                <button @click="scrollToPricing" class="text-sm font-bold text-slate-600 hover:text-indigo-600 transition-colors">{{ __('pricing') }}</button>
                <Link :href="route('login')" class="text-sm font-bold text-slate-600 hover:text-indigo-600 transition-colors">{{ __('login') }}</Link>
                <Link :href="route('register')" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all hover:scale-[1.02] active:scale-95">
                    {{ __('start_for_free') }}
                </Link>
            </div>

            <!-- Mobile Menu Toggle -->
            <button @click="toggleMobileMenu" class="md:hidden relative z-50 p-2 text-slate-600 hover:text-indigo-600 transition-colors">
                <Menu v-if="!isMobileMenuOpen" class="w-6 h-6" />
                <X v-else class="w-6 h-6" />
            </button>

            <!-- Mobile Menu Overlay -->
            <div 
                class="fixed inset-0 bg-white/95 backdrop-blur-xl z-40 flex flex-col pt-24 px-6 md:hidden transition-all duration-300 ease-in-out"
                :class="isMobileMenuOpen ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-full pointer-events-none'"
            >
                <div class="flex flex-col gap-2 items-center w-full bg-white rounded-3xl p-6 shadow-2xl border border-slate-100">
                    <LandingLanguageSwitcher />
                    
                    <button @click="scrollToPricing" class="text-sm font-bold text-slate-600 hover:text-indigo-600 transition-colors w-full text-center py-3 border-b border-slate-50">
                        {{ __('pricing') }}
                    </button>
                    
                    <Link :href="route('login')" class="text-sm font-bold text-slate-600 hover:text-indigo-600 transition-colors w-full text-center py-3 border-b border-slate-50">
                        {{ __('login') }}
                    </Link>
                    
                    <Link :href="route('register')" class="w-full text-center px-6 py-3 bg-indigo-600 text-white rounded-2xl text-sm font-bold shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95 mt-2">
                        {{ __('start_for_free') }}
                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>
