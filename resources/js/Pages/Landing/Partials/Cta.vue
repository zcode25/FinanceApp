<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { __ } from '@/Plugins/i18n';
import { route } from 'ziggy-js';
</script>

<template>
    <section class="py-32 px-6">
        <div class="max-w-5xl mx-auto relative group">
            <div class="absolute inset-0 bg-indigo-600 rounded-[2.5rem] md:rounded-[3rem] blur-[80px] opacity-10 group-hover:opacity-20 transition-opacity"></div>
            <div class="relative bg-slate-900 rounded-[2.5rem] md:rounded-[3rem] p-12 md:p-20 text-center text-white overflow-hidden">
                <div class="absolute -top-20 -right-20 w-80 h-80 bg-indigo-500 rounded-full blur-[100px] opacity-20"></div>
                <div class="relative z-10 scroll-reveal animate-fade-up">
                    <h2 class="text-xl md:text-5xl font-bold mb-8 leading-tight">{{ __('ready_to_master') }}</h2>
                    <p class="text-sm md:text-lg text-slate-400 mb-12 max-w-2xl mx-auto font-medium">
                        {{ __('cta_desc') }}
                    </p>
                    <Link :href="route('register')" class="inline-flex items-center gap-2 px-6 py-3.5 md:px-8 md:py-4 bg-white text-slate-900 rounded-2xl text-sm md:text-lg font-bold hover:bg-slate-50 transition-all hover:scale-105">
                        {{ __('get_free_account') }} <ArrowRight class="w-5 h-5" />
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
