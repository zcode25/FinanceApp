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
        <header class="mb-8 space-y-1 relative z-10">
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ __('settings') }}</h1>
            <p class="text-sm md:text-base text-slate-500 font-medium">{{ __('settings_desc') }}</p>
        </header>
     
        <div class="flex flex-col lg:flex-row gap-8 pb-28 md:pb-0 items-start">
          <!-- Sidebar Tabs Card -->
          <div class="w-full lg:w-80 shrink-0 bg-white border border-slate-100 rounded-3xl p-4 shadow-sm relative z-10 sticky top-[180px]">
            <div class="space-y-1">
              <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="activeTab = tab.id"
                class="w-full flex items-center gap-3 px-5 py-4 rounded-2xl transition-all font-bold text-sm group"
                :class="activeTab === tab.id ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50'"
              >
                <component :is="tab.icon" class="w-4.5 h-4.5 transition-transform group-hover:scale-110" />
                {{ tab.label }}
              </button>
            </div>
            
            <!-- Usage Metrics Wrapper -->
            <div class="mt-6 pt-6 border-t border-slate-50 px-2 pb-2">
              <h4 class="text-[11px] font-bold text-slate-400 mb-5 pl-1 tracking-wider">{{ __('account_stats') }}</h4>
              <div class="space-y-4">
                <div class="flex justify-between items-center text-[13px]">
                  <span class="font-semibold text-slate-500">{{ __('transactions') }}</span>
                  <span class="text-slate-900 font-bold tabular-nums">{{ stats.transactions }}</span>
                </div>
                <div class="flex justify-between items-center text-[13px]">
                  <span class="font-semibold text-slate-500">{{ __('wallets') }}</span>
                  <span class="text-slate-900 font-bold tabular-nums">{{ stats.wallets }}</span>
                </div>
                <div class="flex justify-between items-center text-[13px]">
                  <span class="font-semibold text-slate-500">{{ __('budgets') }}</span>
                  <span class="text-slate-900 font-bold tabular-nums">{{ stats.budgets }}</span>
                </div>
              </div>
            </div>
          </div>
 
            <!-- Main Content Area -->
            <div class="flex-1 w-full min-w-0">
                <Transition 
                    enter-active-class="transition duration-400 ease-out" 
                    enter-from-class="opacity-0 translate-y-4" 
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in" 
                    leave-from-class="opacity-100 scale-100" 
                    leave-to-class="opacity-0 scale-95"
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
        </Layout>
    </template>
