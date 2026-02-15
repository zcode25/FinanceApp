<script setup>
import Layout from '@/Shared/Layout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import VueApexCharts from "vue3-apexcharts";
import { 
    Calendar, 
    TrendingUp, 
    ArrowRight,
    PieChart,
    Filter
} from 'lucide-vue-next';

const props = defineProps({
    summary: Object,
    categorySpending: Array,
    cashFlowTrend: Object,
    walletAllocation: Array,
    filters: Object
});

const form = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date
});

const applyFilter = () => {
    router.get('/analysis', { 
        start_date: form.value.start_date,
        end_date: form.value.end_date 
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

// Check for deep changes in form to auto-refresh
let debounceTimer;
watch(() => form.value, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilter();
    }, 800); // 800ms debounce to wait for user to finish selecting if typing
}, { deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

// --- Common Options ---


</script>

<template>
    <Head title="Financial Analysis" />

    <Layout>
        <div class="space-y-8">
            
            <!-- HEADER & CONTROLS -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">Financial Analysis</h1>
                    <p class="text-gray-400">Deep dive into your cash flow and spending habits.</p>
                </div>

                <div class="flex items-center gap-3 bg-gray-900/50 p-2 rounded-2xl border border-white/10 backdrop-blur-md">
                    <div class="flex items-center gap-2 px-3">
                        <Filter class="w-4 h-4 text-indigo-400" />
                        <span class="text-xs font-semibold text-gray-500">Date Range</span>
                    </div>
                    
                    <div class="h-8 w-px bg-white/10"></div>
                    
                    <div class="flex items-center gap-2">
                        <input 
                            type="date" 
                            v-model="form.start_date"
                            @change="applyFilter"
                            class="bg-transparent text-white text-sm font-bold focus:outline-none focus:ring-0 border-none p-0 w-[130px] cursor-pointer"
                        />
                        <ArrowRight class="w-4 h-4 text-gray-600" />
                        <input 
                            type="date" 
                            v-model="form.end_date"
                            @change="applyFilter"
                            class="bg-transparent text-white text-sm font-bold focus:outline-none focus:ring-0 border-none p-0 w-[130px] cursor-pointer"
                        />
                    </div>
                </div>
            </div>



            <!-- SPENDING & STATS grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Spending By Category -->
                <div class="lg:col-span-2 glass-card p-6 md:p-8 border-white/5">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-400">
                            <PieChart class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Category Breakdown</h3>
                            <p class="text-xs text-gray-400">Where your money went</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(cat, index) in categorySpending" :key="index" class="relative group">
                            <div class="flex items-center justify-between mb-2 relative z-10">
                                <span class="text-sm font-bold text-gray-300">{{ cat.category }}</span>
                                <span class="text-sm font-bold text-white">{{ formatCurrency(cat.total) }}</span>
                            </div>
                            <div class="h-2 w-full bg-gray-800 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-rose-500 rounded-full transition-all duration-1000 ease-out"
                                    :style="{ width: `${(cat.total / summary.total_expense) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                        
                        <div v-if="categorySpending.length === 0" class="py-12 text-center text-gray-500">
                            No expense data for this period.
                        </div>
                    </div>
                </div>

                <!-- SUMMARY CARD -->
                <div class="glass-card p-6 md:p-8 border-emerald-500/10 flex flex-col justify-center gap-6">
                    <div>
                        <h4 class="text-2xl md:text-3xl font-bold text-emerald-400">{{ formatCurrency(summary.total_income) }}</h4>
                    </div>
                    <div>
                        <h4 class="text-2xl md:text-3xl font-bold text-rose-400">{{ formatCurrency(summary.total_expense) }}</h4>
                    </div>
                    <div class="pt-6 border-t border-white/10">
                        <p class="text-xs font-semibold text-gray-500 mb-1">Net Savings</p>
                        <div class="flex items-center gap-2">
                            <h4 :class="['text-3xl md:text-4xl font-bold', summary.net_savings >= 0 ? 'text-white' : 'text-rose-500']">
                                {{ formatCurrency(summary.net_savings) }}
                            </h4>
                        </div>
                        <span :class="['text-xs font-bold px-2 py-1 rounded inline-block mt-2', summary.net_savings >= 0 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400']">
                            {{ summary.savings_rate }}% Savings Rate
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </Layout>
</template>

<style scoped>
.glass-card {
    background: rgba(30, 41, 59, 0.4);
    backdrop-filter: blur(24px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}
</style>
