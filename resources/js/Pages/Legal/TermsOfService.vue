<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from '@/Shared/LegalLayout.vue';
import { __ } from '@/Plugins/i18n';
</script>

<template>
    <LegalLayout>
        <Head :title="__('terms_of_service')" />
        
        <div class="prose prose-slate max-w-none">
            <h1 class="text-2xl md:text-3xl font-bold mb-8 text-slate-900">{{ __('terms_title') }}</h1>
            <p class="text-slate-500 mb-8">{{ __('last_updated') }}</p>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('terms_agreement_title') }}</h2>
                <p>{{ __('terms_agreement_text') }}</p>
            </section>

             <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('terms_desc_title') }}</h2>
                <p>{{ __('terms_desc_text') }}</p>
            </section>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('terms_user_title') }}</h2>
                <p>{{ __('terms_user_text') }}</p>
                <ul class="list-disc pl-5 space-y-2 mt-4">
                    <li>{{ __('terms_user_list_1') }}</li>
                    <li>{{ __('terms_user_list_2') }}</li>
                    <li>{{ __('terms_user_list_3') }}</li>
                    <li>{{ __('terms_user_list_4') }}</li>
                </ul>
            </section>

           <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('terms_prohibited_title') }}</h2>
                <p>{{ __('terms_prohibited_text') }}</p>
            </section>

            <section class="mb-8">
                <h2 class="text-lg md:text-xl font-semibold mb-4 text-slate-800">{{ __('terms_contact_title') }}</h2>
                <p>{{ __('terms_contact_text') }}</p>
            </section>
        </div>
    </LegalLayout>
</template>
