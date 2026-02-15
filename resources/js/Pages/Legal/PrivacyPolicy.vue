<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/Shared/LegalLayout.vue';
import { __ } from '@/Plugins/i18n';
</script>

<template>
    <LegalLayout>
        <Head :title="__('privacy_policy')" />
        
        <div class="prose prose-slate max-w-none">
            <h1 class="text-2xl md:text-3xl font-bold mb-8 text-slate-900">{{ __('privacy_title') }}</h1>
            <p class="text-slate-500 mb-8">{{ __('last_updated') }}</p>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('privacy_intro_title') }}</h2>
                <p>{{ __('privacy_intro_text') }}</p>
            </section>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('privacy_collect_title') }}</h2>
                <p>{{ __('privacy_collect_text') }}</p>
                <ul class="list-disc pl-5 space-y-2 mt-4">
                    <li>{{ __('privacy_collect_list_1') }}</li>
                    <li>{{ __('privacy_collect_list_2') }}</li>
                    <li>{{ __('privacy_collect_list_3') }}</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('privacy_use_title') }}</h2>
                <p>{{ __('privacy_use_text') }}</p>
                <ul class="list-disc pl-5 space-y-2 mt-4">
                    <li>{{ __('privacy_use_list_1') }}</li>
                    <li>{{ __('privacy_use_list_2') }}</li>
                    <li>{{ __('privacy_use_list_3') }}</li>
                    <li>{{ __('privacy_use_list_4') }}</li>
                </ul>
            </section>

             <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('privacy_security_title') }}</h2>
                <p>{{ __('privacy_security_text') }}</p>
            </section>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('privacy_contact_title') }}</h2>
                <p>{{ __('privacy_contact_text') }}</p>
            </section>
        </div>
    </LegalLayout>
</template>
