<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { __ } from '@/Plugins/i18n';
import { route } from 'ziggy-js';
import { getLocalizedPlans } from '@/Utilities/plans';

// Import Partials
import Navbar from './Partials/Navbar.vue';
import Hero from './Partials/Hero.vue';
import MockupDashboard from './Partials/MockupDashboard.vue';
import Features from './Partials/Features.vue';
import Pricing from './Partials/Pricing.vue';
import Cta from './Partials/Cta.vue';
import Footer from './Partials/Footer.vue';

// Import CSS
import '@/../css/landing.css';

const props = defineProps({
    plans: Array
});

const page = usePage();
const currentLocale = computed(() => page.props.locale || 'id');

const plans = computed(() => getLocalizedPlans(props.plans));

// Utilities
const switchLanguage = (lang) => {
    router.post(route('locale.update'), { locale: lang }, {
        preserveScroll: true
    });
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Scroll Reveal Logic
const observer = ref(null);

onMounted(() => {
    const options = {
        root: null,
        threshold: 0.1,
        rootMargin: '0px'
    };

    observer.value = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, options);

    document.querySelectorAll('.scroll-reveal').forEach(el => {
        observer.value.observe(el);
    });
});

onUnmounted(() => {
    if (observer.value) {
        observer.value.disconnect();
    }
});
</script>

<template>
    <div class="min-h-screen bg-white">
        <Head>
            <title>VibeFinance - {{ __('landing_tagline') }}</title>
            <meta name="description" :content="__('landing_hero_subtitle')">
        </Head>

        <!-- Dynamic Navbar -->
        <Navbar />

        <main>
            <!-- Hero Section with Integrated Mockup -->
            <Hero>
                <MockupDashboard />
            </Hero>

            <!-- Feature Highlights -->
            <Features />

            <!-- Pricing Tiers -->
            <Pricing :plans="plans" />

            <!-- Final Call to Action -->
            <Cta />
        </main>

        <!-- Footnote & Language Switcher -->
        <Footer 
            :currentLocale="currentLocale" 
            @switchLanguage="switchLanguage" 
            @scrollToTop="scrollToTop" 
        />
    </div>
</template>

<style scoped>
/* Scoped styles kept minimal, most moved to landing.css */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
