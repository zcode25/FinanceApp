<script setup>
import Layout from '../../Shared/Layout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { User, Settings, Database } from 'lucide-vue-next';
import ProfileTab from './Partials/ProfileTab.vue';
import PreferencesTab from './Partials/PreferencesTab.vue';
import DataTab from './Partials/DataTab.vue';

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;
    
    const props = defineProps({
        user: Object,
        stats: Object,
    });
    
    const activeTab = ref('profile');
    
    const tabs = computed(() => [
        { id: 'profile', label: __('profile_security'), icon: User },
        { id: 'preferences', label: __('app_preferences'), icon: Settings },
        { id: 'data', label: __('data_management'), icon: Database },
    ]);
    </script>
    
    <template>
        <Head :title="__('settings')" />
        <Layout>
            <div class="max-w-7xl mx-auto">
                 <header class="mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">{{ __('settings') }}</h1>
                    <p class="text-gray-400">{{ __('settings_desc') }}</p>
                </header>
    
                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Sidebar Tabs -->
                    <div class="w-full lg:w-64 space-y-2 shrink-0">
                        <button 
                            v-for="tab in tabs" 
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all font-medium text-sm"
                            :class="activeTab === tab.id ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 shadow-lg shadow-indigo-500/10' : 'text-gray-400 hover:text-white hover:bg-white/5'"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            {{ tab.label }}
                        </button>
                        
                        <!-- Stats Widget -->
                        <div class="mt-8 p-6 rounded-2xl bg-white/5 border border-white/5">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">{{ __('account_stats') }}</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">{{ __('transactions') }}</span>
                                    <span class="text-white font-mono">{{ stats.transactions }}</span>
                                </div>
                                 <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">{{ __('wallets') }}</span>
                                    <span class="text-white font-mono">{{ stats.wallets }}</span>
                                </div>
                                 <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">{{ __('budgets') }}</span>
                                    <span class="text-white font-mono">{{ stats.budgets }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Main Content Area -->
                <div class="flex-1 min-w-0">
                    <Transition 
                        enter-active-class="transition duration-300 ease-out" 
                        enter-from-class="opacity-0 translate-y-2" 
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-200 ease-in" 
                        leave-from-class="opacity-100" 
                        leave-to-class="opacity-0"
                        mode="out-in"
                    >
                        <div :key="activeTab">
                            <ProfileTab v-if="activeTab === 'profile'" :user="user" />
                            <PreferencesTab v-if="activeTab === 'preferences'" :user="user" />
                            <DataTab v-if="activeTab === 'data'" />
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </Layout>
</template>
