<script setup>
import Layout from '../Shared/Layout.vue';
</script>

<template>
    <Layout>
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-white">Budget & Planning</h1>
            <p class="text-gray-400">Plan your future and get recommendations.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
             <div class="glass-card p-6">
                <h2 class="text-xl font-bold text-white mb-4">Current Budgets</h2>
                <div class="text-gray-500">No active budgets.</div>
            </div>

             <div class="glass-card p-6 border-indigo-500/30 bg-indigo-500/5">
                <h2 class="text-xl font-bold text-indigo-400 mb-4">AI Recommendations</h2>
                <p class="text-gray-300">Based on your recent activity, we recommend setting a limit for "Food" category.</p>
            </div>
        </div>
    </Layout>
</template>
