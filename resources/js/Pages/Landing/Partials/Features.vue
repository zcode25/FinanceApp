<script setup>
import { Wallet, BarChart3, Target, Check, CreditCard, GripVertical, ChartPie, Lightbulb, Flame, Calendar, ShieldCheck, Zap, Pencil, Lock } from 'lucide-vue-next';
import draggable from 'vuedraggable';
import { ref } from 'vue';
import { __ } from '@/Plugins/i18n';

const trustPillars = [
    { title: __('trust_pilar_1_title'), desc: __('trust_pilar_1_desc'), icon: ShieldCheck, color: 'text-indigo-600', bg: 'bg-indigo-50' },
    { title: __('trust_pilar_2_title'), desc: __('trust_pilar_2_desc'), icon: Pencil, color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { title: __('trust_pilar_3_title'), desc: __('trust_pilar_3_desc'), icon: Lock, color: 'text-purple-600', bg: 'bg-purple-50' },
    { title: __('trust_pilar_4_title'), desc: __('trust_pilar_4_desc'), icon: Zap, color: 'text-amber-600', bg: 'bg-amber-50' }
];

const mockupWallets = ref([
    { id: 1, name: 'Bank Utama', balance: 'Rp 15.000.000', accountNumber: '987654321', iconColor: 'bg-indigo-100 text-indigo-600 border-indigo-200', cardTheme: 'bg-gradient-to-br from-white to-indigo-50 border-2 border-indigo-200 shadow-xl shadow-indigo-100/50', currency: 'IDR' },
    { id: 2, name: 'Dompet Digital', balance: 'Rp 2.450.000', accountNumber: '08123456789', iconColor: 'bg-orange-100 text-orange-600 border-orange-200', cardTheme: 'bg-gradient-to-br from-white to-orange-50 border-2 border-orange-200 shadow-xl shadow-orange-100/50', currency: 'IDR' },
    { id: 3, name: 'Uang Tunai', balance: 'Rp 500.000', accountNumber: 'Kas Tunai', iconColor: 'bg-emerald-100 text-emerald-600 border-emerald-200', cardTheme: 'bg-gradient-to-br from-white to-emerald-50 border-2 border-emerald-200 shadow-xl shadow-emerald-100/50', currency: 'IDR' }
]);
</script>

<template>
    <div>
        <!-- Value Pillars -->
        <section class="py-16 bg-white relative z-20 overflow-hidden scroll-reveal animate-fade-up">
            <div class="absolute top-0 left-0 w-full flex justify-center overflow-hidden">
                <div class="w-2/3 h-[4px] bg-gradient-to-r from-transparent via-indigo-600 via-indigo-400 via-indigo-600 to-transparent opacity-100 blur-[1px] animate-neon-pulse shadow-[0_0_30px_rgba(79,70,229,1),0_0_70px_rgba(79,70,229,0.6)]"></div>
            </div>
            <div class="absolute bottom-0 left-0 w-full flex justify-center overflow-hidden">
                <div class="w-2/3 h-[4px] bg-gradient-to-r from-transparent via-purple-600 via-purple-400 via-purple-600 to-transparent opacity-100 blur-[1px] animate-neon-pulse shadow-[0_0_30px_rgba(168,85,247,1),0_0_70px_rgba(168,85,247,0.6)]" style="animation-delay: 1.5s"></div>
            </div>
            <div class="max-w-7xl mx-auto px-6 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="(pillar, index) in trustPillars" :key="index" class="group relative p-8 rounded-[2.5rem] bg-white border border-slate-100/50 shadow-sm transition-all duration-500 hover:shadow-xl hover:shadow-indigo-500/5 hover:-translate-y-2 hover:border-indigo-100 flex flex-col items-center text-center scroll-reveal animate-fade-up" :style="{ animationDelay: (0.1 + (index * 0.1)) + 's' }">
                        <div :class="['relative z-10 w-16 h-16 rounded-2xl flex items-center justify-center mb-6 transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 border border-slate-100 bg-gradient-to-br shadow-inner', pillar.bg]">
                            <component :is="pillar.icon" :class="['w-8 h-8', pillar.color]" />
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-lg font-bold text-slate-900 mb-2">{{ pillar.title }}</h3>
                            <p class="text-sm text-slate-500 leading-relaxed max-w-[240px] font-medium">{{ pillar.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Showcase -->
        <section class="py-20 bg-slate-50 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 text-center relative z-10">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 rounded-full text-xs font-bold mb-6 scroll-reveal animate-fade-up">
                    <Zap class="w-4 h-4" /> {{ __('sophisticated_tools') }}
                </div>
                <h2 class="text-2xl md:text-5xl font-bold text-slate-900 mb-6 scroll-reveal animate-fade-up" style="animation-delay: 0.1s;">{{ __('features_desc_title_simplified') }}</h2>
                <p class="text-base md:text-lg text-slate-500 max-w-2xl mx-auto font-medium mb-16 md:mb-24 leading-relaxed scroll-reveal animate-fade-up" style="animation-delay: 0.2s;">{{ __('features_desc') }}</p>
                
                <div class="space-y-20">
                    <!-- Feature 1: Wallets -->
                    <div class="scroll-reveal animate-fade-up">
                        <div class="bg-white/70 backdrop-blur-2xl px-4 py-6 md:py-14 md:px-16 rounded-[2.5rem] md:rounded-[4rem] border border-white shadow-2xl shadow-indigo-100/50 relative group/card overflow-hidden text-left">
                            <div class="relative z-20 flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                                <div class="flex-1 w-full relative">
                                    <draggable v-model="mockupWallets" item-key="id" class="relative flex flex-col gap-0 items-center lg:items-center w-full max-w-[420px] mx-auto" ghost-class="opacity-0" drag-class="grabbing-state" :animation="400">
                                        <template #item="{element: w, index}">
                                            <div :class="['group relative w-full rounded-[2.5rem] p-8 overflow-hidden transition-all duration-700 flex flex-col shadow-2xl hover:scale-[1.02] hover:shadow-xl cursor-grab active:cursor-grabbing', w.cardTheme]" :style="{ marginTop: index === 0 ? '0' : '-100px', zIndex: index + 10, transform: `translateX(${index * -20}px) rotate(${index * -1.5}deg)` }">
                                                <div class="relative z-10 flex flex-col justify-between h-full gap-4 md:gap-0">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex items-center gap-3 md:gap-4">
                                                            <div :class="['p-2.5 md:p-3 rounded-2xl shadow-sm border backdrop-blur-md', w.iconColor]"><CreditCard class="w-5 h-5 md:w-6 md:h-6" /></div>
                                                            <div class="text-left"><h3 class="font-semibold text-base md:text-xl leading-none tracking-tight text-slate-900 mt-1">{{ w.name }}</h3></div>
                                                        </div>
                                                        <div class="p-2 md:p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 group-hover:text-indigo-600 transition-colors"><GripVertical class="w-3.5 h-3.5 md:w-4 md:h-4" /></div>
                                                    </div>
                                                    <div class="mt-4 md:mt-6 text-left"><h2 class="text-xl md:text-[30px] font-bold tracking-tight tabular-nums text-slate-900">{{ w.balance }}</h2></div>
                                                    <div class="flex justify-between items-end mt-4 pt-4 border-t border-slate-50">
                                                        <div class="flex flex-col text-left"><span class="text-[10px] font-bold text-slate-400 mb-0.5">{{ __('feature_label_account_id') }}</span><span class="text-xs md:text-sm font-bold text-slate-600 truncate max-w-[120px] md:max-w-[150px]">{{ w.accountNumber }}</span></div>
                                                        <div class="flex items-center gap-2"><span class="text-[10px] md:text-xs font-bold text-slate-400 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100 uppercase">{{ w.currency }}</span><div class="flex items-center gap-2 px-2 md:px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100"><div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div><span class="text-[10px] font-bold uppercase tracking-tight">{{ __('active') }}</span></div></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </draggable>
                                </div>
                                <div class="flex-1 text-left relative z-20">
                                    <div class="w-14 h-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-indigo-100/50"><Wallet class="w-7 h-7" /></div>
                                    <h3 class="text-xl md:text-3xl font-bold text-slate-900 mb-6 leading-tight">{{ __('feature_wallets_title') }}</h3>
                                    <p class="text-sm md:text-base text-slate-500 leading-relaxed font-medium mb-8">{{ __('feature_wallets_desc') }}</p>
                                    <div class="space-y-4"><div v-for="i in [__('feature_wallets_li_1'), __('feature_wallets_li_2'), __('feature_wallets_li_3')]" :key="i" class="flex items-center gap-4 p-4 rounded-2xl bg-white/50 border border-slate-100 transition-all hover:border-indigo-200 hover:bg-white group/li"><div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0 border border-emerald-100 group-hover/li:scale-110 transition-transform"><Check class="w-5 h-5 text-emerald-600" /></div><span class="font-bold text-slate-700 leading-tight text-sm md:text-base">{{ i }}</span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 2: Analytics -->
                    <div class="scroll-reveal animate-fade-up">
                        <div class="bg-white/70 backdrop-blur-2xl px-4 py-6 md:py-14 md:px-16 rounded-[2.5rem] md:rounded-[4rem] border border-white shadow-2xl shadow-purple-100/50 relative group/card overflow-hidden text-left">
                            <div class="relative z-20 flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                                <div class="flex-1 w-full relative">
                                    <div class="grid grid-cols-1 gap-6 relative py-12 place-items-center">
                                        <div class="relative bg-white border border-slate-200 rounded-[2rem] p-6 md:p-8 shadow-sm transition-all duration-700 hover:shadow-xl hover:scale-[1.02] z-20 w-full max-w-[500px]">
                                            <div class="flex items-center justify-between mb-8">
                                                <div class="text-left"><h4 class="font-bold text-lg md:text-xl text-slate-900 tracking-tight leading-none mb-1">{{ __('spending_mix') }}</h4><p class="text-[10px] md:text-xs text-slate-500 font-medium">{{ __('category_distribution') }}</p></div>
                                                <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 shadow-sm border border-indigo-100"><ChartPie class="w-4 h-4 md:w-5 md:h-5" /></div>
                                            </div>
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-10 items-center">
                                                <div class="relative flex items-center justify-center">
                                                    <div class="relative w-40 h-40 md:w-48 md:h-48">
                                                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                                            <circle cx="50" cy="50" r="40" stroke="#f8fafc" stroke-width="8" fill="none" />
                                                            <circle cx="50" cy="50" r="40" stroke="#4f46e5" stroke-width="8" fill="none" stroke-dasharray="191.5 251.2" />
                                                            <circle cx="50" cy="50" r="40" stroke="#10b981" stroke-width="8" fill="none" stroke-dasharray="30.1 251.2" stroke-dashoffset="-191.5" />
                                                            <circle cx="50" cy="50" r="40" stroke="#f43f5e" stroke-width="8" fill="none" stroke-dasharray="30.1 251.2" stroke-dashoffset="-221.6" />
                                                        </svg>
                                                        <div class="absolute inset-0 flex flex-col items-center justify-center"><span class="text-xs font-bold text-slate-400 uppercase mb-1">Total</span><span class="text-xl md:text-2xl font-bold text-slate-900 leading-none">Rp 1.0M</span></div>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col gap-3">
                                                    <div v-for="(cat, idx) in [ {name: __('food_drink'), color: '#4f46e5', amount: 'Rp 766.500', percent: '76.2%'}, {name: __('shopping'), color: '#10b981', amount: 'Rp 120.000', percent: '11.9%'}, {name: __('groceries'), color: '#f43f5e', amount: 'Rp 120.000', percent: '11.9%'} ]" :key="idx" class="p-3 rounded-2xl border border-slate-100 hover:border-slate-200 hover:shadow-sm transition-all group bg-slate-50/50">
                                                        <div class="flex items-center justify-between mb-2"><div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full shadow-sm" :style="{ backgroundColor: cat.color }"></span><span class="text-[11px] font-bold text-slate-700">{{ cat.name }}</span></div><span class="text-[11px] font-bold text-slate-900 tabular-nums">{{ cat.amount }}</span></div>
                                                        <div class="w-full bg-slate-200 rounded-full h-1 overflow-hidden mb-1"><div class="h-full rounded-full transition-all duration-1000" :style="{ width: cat.percent, backgroundColor: cat.color }"></div></div>
                                                        <p class="text-[10px] text-slate-400 font-medium text-right">{{ cat.percent }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative bg-white border border-slate-100 rounded-[2.5rem] p-8 shadow-2xl transition-all duration-700 hover:shadow-2xl hover:scale-[1.05] z-30 lg:-mt-16 lg:ml-20">
                                            <div class="flex items-center gap-4 mb-6"><div class="p-3 rounded-2xl bg-violet-50 text-violet-600 border border-violet-100 shadow-sm"><Lightbulb class="w-6 h-6" /></div><div class="text-left"><h4 class="font-bold text-lg text-slate-900 leading-none">{{ __('smart_insights') }}</h4><p class="text-[9px] font-bold text-slate-400 mt-1">{{ __('feature_label_ai_analysis') }}</p></div></div>
                                            <div class="grid grid-cols-1 gap-4"><div class="p-5 bg-slate-50/50 rounded-2xl border border-slate-100 flex gap-4 group/insight transition-all hover:bg-white hover:border-violet-100"><div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center shrink-0 group-hover/insight:scale-110 transition-transform"><Flame class="w-5 h-5 text-orange-500" /></div><div class="text-left"><h5 class="text-sm font-bold text-slate-900 mb-1">{{ __('insight_burn_rate_title') }}</h5><p class="text-[11px] text-slate-500 leading-relaxed font-medium">{{ __('insight_burn_rate_message', {amount: 'Rp 2M'}) }}</p></div></div><div class="p-5 bg-indigo-50/50 rounded-2xl border border-indigo-100 flex gap-4 group/insight transition-all hover:bg-white hover:border-indigo-200"><div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center shrink-0 group-hover/insight:scale-110 transition-transform"><Calendar class="w-5 h-5 text-indigo-600" /></div><div class="text-left"><h5 class="text-sm font-bold text-indigo-900 mb-1">{{ __('insight_weekend_title') }}</h5><p class="text-[11px] text-indigo-600/80 leading-relaxed font-medium">{{ __('insight_weekend_message', {percentage: '85'}) }}</p></div></div></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1 text-left relative z-20">
                                    <div class="w-14 h-14 bg-purple-600 text-white rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-purple-100/50"><BarChart3 class="w-7 h-7" /></div>
                                    <h3 class="text-xl md:text-3xl font-bold text-slate-900 mb-6 leading-tight">{{ __('feature_analytics_title') }}</h3>
                                    <p class="text-sm md:text-base text-slate-500 leading-relaxed font-medium mb-8">{{ __('feature_analytics_desc') }}</p>
                                    <div class="space-y-4"><div v-for="i in [__('feature_analytics_li_1'), __('feature_analytics_li_2'), __('feature_analytics_li_3')]" :key="i" class="flex items-center gap-4 p-4 rounded-2xl bg-white/50 border border-slate-100 transition-all hover:border-purple-200 hover:bg-white group/li"><div class="w-9 h-9 bg-purple-50 rounded-xl flex items-center justify-center shrink-0 border border-purple-100 group-hover/li:scale-110 transition-transform"><Check class="w-5 h-5 text-purple-600" /></div><span class="font-bold text-slate-700 leading-tight text-sm md:text-base">{{ i }}</span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 3: Goals -->
                    <div class="scroll-reveal animate-fade-up">
                        <div class="bg-white/70 backdrop-blur-2xl px-4 py-6 md:py-14 md:px-16 rounded-[2.5rem] md:rounded-[4rem] border border-white shadow-2xl shadow-emerald-100/50 relative group/card overflow-hidden text-left">
                            <div class="relative z-20 flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                                <div class="flex-1 w-full relative">
                                    <div class="relative w-full max-w-[420px] space-y-6 mx-auto transition-all duration-700 hover:scale-[1.02]">
                                        <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-xl transition-all duration-500 hover:border-orange-200 group/budget cursor-default">
                                            <div class="flex flex-col gap-6"><div class="flex items-center gap-5"><div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-lg bg-orange-500 group-hover/budget:scale-110 transition-transform"><Target class="w-7 h-7 text-white" /></div><div class="text-left"><h3 class="text-lg font-bold text-slate-900 leading-tight mb-1">{{ __('food_drink') }}</h3><div class="flex items-center gap-2"><span class="inline-block w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span><span class="text-[11px] font-bold text-orange-600 tracking-wider">{{ __('status_warning') }}</span></div></div></div><div class="space-y-3"><div class="flex justify-between items-end"><span class="text-[11px] font-bold text-slate-400 tracking-tight">Rp 766.500 / Rp 800.000</span><span class="text-sm font-bold text-slate-900">96%</span></div><div class="w-full h-3 bg-slate-50 rounded-full overflow-hidden border border-slate-100 shadow-inner"><div class="h-full bg-orange-500 rounded-full" style="width: 96%"></div></div></div></div>
                                        </div>
                                        <div class="bg-white border border-slate-100 rounded-[2rem] p-6 shadow-xl transition-all duration-500 hover:border-emerald-200 group/goal cursor-default">
                                            <div class="flex items-start justify-between mb-8"><div class="flex gap-4 md:gap-5"><div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-200 shrink-0 group-hover/goal:scale-110 transition-transform"><ShieldCheck class="w-7 h-7" /></div><div class="space-y-1"><h3 class="text-lg font-bold text-slate-900 leading-none">{{ __('emergency_fund') }}</h3><div class="flex items-center gap-2 flex-wrap"><span class="px-2 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold capitalize tracking-wider">{{ __('emergency_fund') }}</span><span class="text-xs text-slate-400 flex items-center gap-1 font-medium"><Calendar class="w-3.5 h-3.5" /> 25 Des 2026</span></div></div></div></div>
                                            <div class="space-y-4"><div class="flex justify-between items-end"><div class="space-y-1"><p class="text-xs font-bold text-slate-400">{{ __('progress') }}</p><div class="flex items-baseline gap-1.5"><span class="text-xl font-bold text-slate-800 tracking-tight leading-none">Rp 15.816.390</span><span class="text-[10px] font-bold text-slate-400">/ Rp 23.000.000</span></div></div><span class="text-sm font-bold text-slate-900">69%</span></div><div class="h-3.5 bg-slate-100 rounded-full overflow-hidden shadow-inner border border-slate-200/50"><div class="h-full bg-indigo-500 rounded-full relative overflow-hidden transition-all duration-1000 ease-out" style="width: 69%"><div class="absolute inset-0 bg-white/20 animate-shimmer scale-x-150 -skew-x-12"></div></div></div><div class="pt-4 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4"><div class="flex items-center justify-between w-full md:w-auto md:gap-4"><div class="flex -space-x-2"><div class="w-7 h-7 rounded-full border-2 border-white flex items-center justify-center text-[8px] font-bold text-white bg-orange-500 ring-1 ring-slate-100">D</div><div class="w-7 h-7 rounded-full border-2 border-white flex items-center justify-center text-[8px] font-bold text-white bg-indigo-500 ring-1 ring-slate-100">B</div></div><span class="text-[10px] font-bold text-slate-400">{{ __('connected_wallets') }}</span></div><div class="w-full md:w-auto px-4 py-2 bg-indigo-50/50 text-indigo-700 rounded-xl text-[10px] font-bold flex items-center justify-center gap-2 border border-indigo-100/50"><Zap class="w-3.5 h-3.5" /> Butuh Rp 718.361 / {{ __('month') }}</div></div></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1 text-left relative z-20">
                                    <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-emerald-100/50"><Target class="w-7 h-7" /></div>
                                    <h3 class="text-xl md:text-3xl font-bold text-slate-900 mb-6 leading-tight">{{ __('feature_goals_title') }}</h3>
                                    <p class="text-sm md:text-base text-slate-500 leading-relaxed font-medium mb-8">{{ __('feature_goals_desc') }}</p>
                                    <div class="space-y-4"><div v-for="i in [__('feature_goals_li_1'), __('feature_goals_li_2'), __('feature_goals_li_3')]" :key="i" class="flex items-center gap-4 p-4 rounded-2xl bg-white/50 border border-slate-100 transition-all hover:border-emerald-200 hover:bg-white group/li"><div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0 border border-emerald-100 group-hover/li:scale-110 transition-transform"><Check class="w-5 h-5 text-emerald-600" /></div><span class="font-bold text-slate-700 leading-tight text-sm md:text-base">{{ i }}</span></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
