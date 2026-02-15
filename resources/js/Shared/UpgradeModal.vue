<script setup>
import { Link } from '@inertiajs/vue3';
import { X, Crown, Check, Sparkles, Zap, Rocket } from 'lucide-vue-next';

defineProps({
    show: Boolean,
    title: {
        type: String,
        default: 'Unlock Unlimited Power'
    },
    description: {
        type: String,
        default: 'Take control of your financial future with our Professional plan.'
    }
});

defineEmits(['close']);

const benefits = [
    { title: 'Unlimited Wallets', desc: 'No more caps on your bank accounts or cash tracking.', icon: Zap },
    { title: 'Unlimited Goals', desc: 'Plan for everything from a vacation to retirement.', icon: Rocket },
    { title: 'Smart AI Insights', desc: 'Deep analysis of your spending habits and trends.', icon: Sparkles },
    { title: 'Advanced Export', desc: 'Download your data in professional PDF & Excel formats.', icon: Check }
];
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto">
            <!-- Backdrop -->
            <div @click="$emit('close')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity"></div>

            <!-- Modal Content -->
            <div class="relative z-10 w-full max-w-lg bg-white rounded-t-[2.5rem] sm:rounded-[3rem] shadow-2xl overflow-hidden animate-in slide-in-from-bottom-10 sm:zoom-in-95 duration-500">
                <!-- Premium Header -->
                <div class="relative p-8 md:p-10 bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 text-white overflow-hidden">
                    <!-- Decorative Elements -->
                    <div class="absolute top-0 right-0 p-10 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                        <Crown class="w-48 h-48" />
                    </div>
                    
                    <div class="relative z-10">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-[10px] font-bold uppercase tracking-widest mb-4 border border-white/20">
                            <Crown class="w-3 h-3" /> Professional Membership
                        </div>
                        <h2 class="text-2xl md:text-3xl font-black mb-2 leading-tight">{{ title }}</h2>
                        <p class="text-indigo-100/80 font-medium text-sm leading-relaxed">{{ description }}</p>
                    </div>

                    <!-- Close Button -->
                    <button @click="$emit('close')" class="absolute top-6 right-6 p-2 bg-white/10 hover:bg-white/20 rounded-xl text-white transition-all">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Benefits List -->
                <div class="p-8 md:p-10 space-y-6">
                    <div v-for="benefit in benefits" :key="benefit.title" class="flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0 border border-indigo-100">
                            <component :is="benefit.icon" class="w-5 h-5 text-indigo-600" />
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 text-sm mb-0.5">{{ benefit.title }}</h4>
                            <p class="text-slate-500 text-xs font-medium leading-relaxed">{{ benefit.desc }}</p>
                        </div>
                    </div>

                    <!-- CTA Section -->
                    <div class="pt-6 space-y-4">
                        <Link 
                            href="/subscription" 
                            class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-sm shadow-xl shadow-indigo-200 hover:bg-indigo-700 transition-all flex items-center justify-center gap-2 active:scale-95 group"
                        >
                            <span>Upgrade to Professional</span>
                            <Rocket class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        </Link>
                        <button 
                            @click="$emit('close')"
                            class="w-full text-center text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors py-2"
                        >
                            Maybe Later
                        </button>
                    </div>
                </div>

                <!-- Trust Badge -->
                <div class="bg-slate-50 p-4 border-t border-slate-100 flex items-center justify-center gap-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Powered by Midtrans Secure Checkout</span>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.animate-in {
    animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
