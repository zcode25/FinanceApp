<script setup>
import { Sparkles, Check, ArrowRight, CircleCheck } from 'lucide-vue-next';
import { __ } from '@/Plugins/i18n';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
    plans: Array
});

const navigateToCheckout = (planId) => {
    router.visit(route('register', { plan: planId }));
};
</script>

<template>
    <section id="pricing" class="py-16 md:py-20 bg-white">
        <div class="max-w-[1500px] mx-auto px-4 md:px-6">
            <div class="text-center mb-12 md:mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold mb-4 scroll-reveal animate-fade-up">
                    <Sparkles class="w-4 h-4" /> {{ __('pricing_plans') }}
                </div>
                <h2 class="text-2xl md:text-5xl font-bold text-slate-900 mb-6 scroll-reveal animate-fade-up" style="animation-delay: 0.1s;">{{ __('transparent_pricing_title') }}</h2>
                <p class="text-base md:text-lg text-slate-500 max-w-2xl mx-auto font-medium scroll-reveal animate-fade-up" style="animation-delay: 0.2s;">
                    {{ __('transparent_pricing_desc') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8 items-stretch">
                <div v-for="(plan, index) in plans" :key="plan.id" class="scroll-reveal animate-fade-up" :style="{ animationDelay: (0.1 + (index * 0.1)) + 's' }">
                    <div :class="['relative flex flex-col p-6 lg:p-8 rounded-3xl lg:rounded-[2.5rem] transition-all duration-700 h-full backdrop-blur-2xl glass-card border border-white/40 overflow-hidden group hover:scale-[1.02] shadow-xl shadow-slate-200/50', plan.color === 'slate' ? 'bg-slate-50/10' : plan.color === 'indigo' ? 'bg-indigo-50/10' : plan.color === 'emerald' ? 'bg-emerald-50/10' : 'bg-purple-50/10', plan.id === 3 ? 'ring-4 ring-emerald-500/10 scale-[1.02] shadow-[0_20px_50px_-12px_rgba(16,185,129,0.3)] z-10 bg-white/80' : '']">
                        <div :class="['absolute -top-24 -right-24 w-48 h-48 rounded-full opacity-5 pointer-events-none group-hover:scale-150 transition-transform duration-1000', plan.color === 'slate' ? 'bg-slate-500' : plan.color === 'indigo' ? 'bg-indigo-500' : plan.color === 'emerald' ? 'bg-emerald-500' : 'bg-purple-500']"></div>
                        <div v-if="plan.popular" class="absolute top-4 right-6 bg-emerald-600 text-white text-[10px] font-bold px-4 py-1.5 rounded-full shadow-lg">{{ __('best_value') }}</div>
                        <div class="mb-8 relative shrink-0 text-left">
                            <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center mb-6 shadow-inner border transition-all duration-500', plan.color === 'slate' ? 'bg-slate-50 border-slate-100 text-slate-500 shadow-slate-100/50' : plan.color === 'indigo' ? 'bg-indigo-50 border-indigo-100 text-indigo-600 shadow-indigo-100/50 animate-tilt' : plan.color === 'emerald' ? 'bg-emerald-50 border-emerald-100 text-emerald-600 shadow-emerald-100/50 animate-bounce-slow' : 'bg-purple-50 border-purple-100 text-purple-600 shadow-purple-100/50 animate-float']">
                                <component :is="plan.icon == 'CheckCircle2' || plan.icon?.name == 'CheckCircle2' ? CircleCheck : plan.icon" class="w-7 h-7" />
                            </div>
                            <h3 class="text-2xl font-bold text-slate-900 mb-2 tracking-tight">{{ plan.name }}</h3>
                            <p class="text-[12px] font-bold text-slate-400 leading-snug line-clamp-2">{{ plan.description }}</p>
                        </div>
                        <div class="mb-7 p-4 bg-slate-50/50 rounded-3xl border border-slate-100/50 flex flex-wrap items-center justify-between gap-2 shrink-0">
                            <div class="flex items-baseline gap-1.5"><span class="text-2xl font-bold text-slate-900 tracking-tight">{{ plan.price }}</span><span class="text-slate-400 font-bold text-[11px]">{{ plan.period }}</span></div>
                        </div>
                        <div class="mb-10 flex-grow text-left">
                            <h4 class="text-[11px] font-bold text-slate-400 mb-3 ml-1 text-left">{{ __('key_benefits') }}</h4>
                            <ul class="grid grid-cols-1 gap-y-2.5 gap-x-2 h-full">
                                <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-3 group/feat"><div class="mt-0.5 w-5 h-5 bg-white shadow-sm border border-slate-100 rounded-full flex items-center justify-center flex-shrink-0 transition-colors group-hover/feat:border-indigo-200"><Check class="w-3 h-3 text-emerald-500 stroke-[4]" /></div><span class="text-xs font-bold text-slate-600 leading-tight">{{ feature }}</span></li>
                            </ul>
                        </div>
                        <button @click="navigateToCheckout(plan.id)" :class="['w-full py-4.5 px-6 rounded-2xl font-bold text-[13px] flex items-center justify-center gap-3 transition-all duration-500 active:scale-95', plan.color === 'slate' ? 'bg-slate-900 text-white hover:bg-slate-800' : plan.color === 'indigo' ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-100' : plan.color === 'emerald' ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-100' : 'bg-purple-600 text-white hover:bg-purple-700 shadow-lg shadow-purple-100']">{{ plan.buttonText }} <ArrowRight class="w-4 h-4" /></button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
