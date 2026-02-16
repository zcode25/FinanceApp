<script setup>
import { Link } from '@inertiajs/vue3';
import { Zap, ArrowRight, Target, CheckCircle2, BarChart3, Wallet, Sparkles } from 'lucide-vue-next';
import { __ } from '@/Plugins/i18n';
import { route } from 'ziggy-js';

const scrollToPricing = () => {
    const el = document.getElementById('pricing');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <header class="relative pt-28 md:pt-40 pb-16 md:pb-20 overflow-hidden">
        <!-- Ambient Background Texture -->
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <!-- Subtle Blueprint Grid -->
            <div class="absolute inset-0 opacity-[0.08]" 
                 style="background-image: linear-gradient(#4f46e5 1px, transparent 1px), linear-gradient(90deg, #4f46e5 1px, transparent 1px); background-size: 60px 60px;">
            </div>
            
            <!-- Moving Data Particles -->
            <div v-for="i in 15" :key="i" 
                 class="absolute w-2 h-2 bg-indigo-500/50 rounded-full animate-float"
                 :style="{
                    left: (Math.random() * 100) + '%',
                    top: (Math.random() * 100) + '%',
                    animationDelay: (Math.random() * 5) + 's',
                    animationDuration: (8 + Math.random() * 6) + 's'
                 }">
            </div>

            <!-- Abstract Glow Orbs (Enhanced) -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-screen h-screen">
                <div class="absolute top-20 left-1/4 w-[300px] md:w-[600px] h-[300px] md:h-[600px] bg-indigo-100 rounded-full blur-[80px] md:blur-[120px] opacity-20 animate-pulse"></div>
                <div class="absolute bottom-40 right-1/4 w-[300px] md:w-[600px] h-[300px] md:h-[600px] bg-purple-100 rounded-full blur-[80px] md:blur-[120px] opacity-20 animate-pulse" style="animation-delay: 2s;"></div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 md:px-6 relative z-10 text-center">
            <div class="relative inline-block z-10 w-full mb-6 md:mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2 bg-indigo-50 text-indigo-600 rounded-full text-[10px] md:text-xs font-bold mb-6 md:mb-8 relative z-20">
                    <Zap class="w-3 md:w-4 h-3 md:h-4" /> {{ __('next_gen_finance') }}
                </div>

                <!-- Floating Icons (Minimalist) -->
                <div class="hidden xl:flex absolute left-0 top-0 w-12 h-12 bg-white/40 backdrop-blur-xl border border-white/40 rounded-2xl shadow-2xl items-center justify-center animate-float z-30 text-emerald-500 hover:scale-110 transition-transform" style="animation-delay: 0.5s; animation-duration: 5s;">
                    <Target class="w-6 h-6" />
                </div>
                <div class="hidden xl:flex absolute right-4 top-6 w-14 h-14 bg-white/40 backdrop-blur-xl border border-white/40 rounded-2xl shadow-2xl items-center justify-center animate-float z-30 text-indigo-600 hover:scale-110 transition-transform" style="animation-delay: 1.2s; animation-duration: 4s;">
                    <CheckCircle2 class="w-7 h-7" />
                </div>
                <div class="hidden xl:flex absolute left-8 bottom-4 w-14 h-14 bg-white/40 backdrop-blur-xl border border-white/40 rounded-2xl shadow-2xl items-center justify-center animate-float z-30 text-purple-600 hover:scale-110 transition-transform" style="animation-delay: 2.1s; animation-duration: 6s;">
                    <BarChart3 class="w-7 h-7" />
                </div>
                <div class="hidden xl:flex absolute right-12 bottom-0 w-12 h-12 bg-white/40 backdrop-blur-xl border border-white/40 rounded-2xl shadow-2xl items-center justify-center animate-float z-30 text-amber-500 hover:scale-110 transition-transform" style="animation-delay: 1.5s; animation-duration: 5.5s;">
                    <Wallet class="w-6 h-6" />
                </div>
                <div class="hidden xl:flex absolute left-1/4 -top-12 animate-pulse z-30 text-indigo-400">
                    <Sparkles class="w-5 h-5" />
                </div>

                <h1 class="text-4xl sm:text-5xl md:text-7xl font-bold text-slate-900 leading-[1.1] scroll-reveal animate-fade-up relative z-10" style="font-family: 'Inter', sans-serif;">
                    {{ __('landing_hero_title_1') }} <br />
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 scroll-reveal animate-fade-up" style="animation-delay: 0.2s">{{ __('landing_hero_title_2') }}</span>
                </h1>
            </div>
            
            <p class="text-base md:text-lg text-slate-500 max-w-2xl mx-auto mb-12 leading-relaxed font-medium scroll-reveal animate-fade-up" style="animation-delay: 0.4s">
                {{ __('landing_hero_subtitle') }}
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 scroll-reveal animate-fade-up" style="animation-delay: 0.6s">
                <Link :href="route('register')" class="w-full sm:w-auto px-6 py-3.5 bg-slate-900 text-white rounded-2xl text-base font-bold shadow-2xl shadow-slate-200 hover:bg-slate-800 transition-all hover:scale-105 flex items-center justify-center gap-2 relative overflow-hidden group/btn">
                    <span class="relative z-10 flex items-center gap-2">
                        {{ __('start_for_free') }} <ArrowRight class="w-5 h-5" />
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover/btn:animate-shine"></div>
                </Link>
                <button @click="scrollToPricing" class="w-full sm:w-auto px-6 py-3.5 bg-white text-slate-900 border border-slate-100 rounded-2xl text-base font-bold shadow-xl shadow-slate-100 hover:border-indigo-100 hover:text-indigo-600 transition-all flex items-center justify-center gap-2">
                    {{ __('view_pricing') }}
                </button>
            </div>
            
            <slot />
        </div>
    </header>
</template>
