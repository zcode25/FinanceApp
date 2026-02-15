<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/Shared/LegalLayout.vue';
import { __ } from '@/Plugins/i18n';
</script>

<template>
    <LegalLayout>
        <Head :title="__('cookies_policy')" />
        
        <div class="prose prose-slate max-w-none">
            <h1 class="text-2xl md:text-3xl font-bold mb-8 text-slate-900">{{ __('cookies_title') }}</h1>
            <p class="text-slate-500 mb-8">{{ __('last_updated') }}</p>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('cookies_what_title') }}</h2>
                <p>{{ __('cookies_what_text') }}</p>
            </section>

             <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('cookies_how_title') }}</h2>
                <p>{{ __('cookies_how_text') }}</p>
                <ul class="list-disc pl-5 space-y-2 mt-4">
                    <li v-html="__('cookies_how_list_1')"></li>
                    <li v-html="__('cookies_how_list_2')"></li>
                    <li v-html="__('cookies_how_list_3')"></li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('cookies_manage_title') }}</h2>
                <p v-html="__('cookies_manage_text')"></p>
            </section>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('cookies_contact_title') }}</h2>
                <p>{{ __('cookies_contact_text') }}</p>
            </section>
        </div>
    </LegalLayout>
</template>
