<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { LayoutDashboard, Wallet, PieChart, Banknote, Settings, Menu, X, Bell, User, ChevronDown, FileText, Tag, LogOut } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
const user = computed(() => page.props.auth.user);

const isSidebarOpen = ref(false);

const navigation = computed(() => [
  { name: __('dashboard'), href: '/', icon: LayoutDashboard },
  { name: __('transactions'), href: '/transactions', icon: Banknote },
  { name: __('wallets'), href: '/wallets', icon: Wallet },
  { name: __('analysis'), href: '/analysis', icon: PieChart },
  { name: __('budget'), href: '/budget', icon: Banknote },
  { name: __('categories'), href: '/categories', icon: Tag },
  { name: __('reports'), href: '/reports', icon: FileText },
]);

const isCollapsed = ref(false); // Desktop state

const toggleSidebar = () => isSidebarOpen.value = !isSidebarOpen.value;
const toggleCollapse = () => isCollapsed.value = !isCollapsed.value;

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white font-sans flex relative overflow-hidden">
    <!-- Mobile Menu Button Removed (Moved to Navbar) -->


    <!-- Sidebar -->
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-40 bg-gray-900/80 backdrop-blur-xl border-r border-white/5 transition-all duration-300',
        // Mobile: Off-canvas toggle
        isSidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full w-64',
        // Desktop: Fixed and Collapsible
        'md:translate-x-0 md:fixed',
        isCollapsed ? 'md:w-20' : 'md:w-64'
      ]"
    >
      <div class="h-full flex flex-col">
        <div class="p-6 flex items-center justify-between gap-3 relative">
            <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap">
                <div class="min-w-8 w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center shrink-0">
                    <span class="font-bold text-white">F</span>
                </div>
                <span 
                    class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-purple-400 transition-opacity duration-300"
                    :class="isCollapsed ? 'opacity-0 w-0 hidden' : 'opacity-100'"
                >
                FinanceApp
                </span>
            </div>
            
            <!-- Desktop Collapse Toggle -->
            <!-- Desktop Collapse Toggle Removed -->

        </div>

        <nav class="mt-4 px-3 space-y-2 flex-1">
            <Link 
            v-for="item in navigation" 
            :key="item.name" 
            :href="item.href"
            :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 group relative',
                $page.url === item.href 
                ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' 
                : 'text-gray-400 hover:bg-white/5 hover:text-white',
                isCollapsed ? 'justify-center' : ''
            ]"
            :title="isCollapsed ? item.name : ''"
            >
            <component :is="item.icon" class="w-5 h-5 transition-colors shrink-0" />
            <span 
                class="font-medium whitespace-nowrap transition-all duration-300 overflow-hidden"
                :class="isCollapsed ? 'w-0 opacity-0 hidden' : 'w-auto opacity-100'"
            >
                {{ item.name }}
            </span>
            </Link>
        </nav>

        <div class="p-4 border-t border-white/5">
                <Link 
                    href="/settings" 
                    class="flex items-center gap-3 px-3 py-3 text-gray-400 hover:text-white transition-colors rounded-xl hover:bg-white/5"
                    :class="isCollapsed ? 'justify-center' : ''"
                    :title="isCollapsed ? __('settings') : ''"
                >
                    <Settings class="w-5 h-5 shrink-0" />
                    <span 
                        class="whitespace-nowrap transition-all duration-300 overflow-hidden" 
                        :class="isCollapsed ? 'w-0 opacity-0 hidden' : 'w-auto opacity-100'"
                    >
                        {{ __('settings') }}
                    </span>
                </Link>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main 
        class="flex-1 bg-gradient-to-br from-gray-900 to-gray-800 relative z-0 min-h-screen transition-all duration-300 flex flex-col"
        :class="isCollapsed ? 'md:ml-20' : 'md:ml-64'"
    >
      <!-- Background Glow Effects -->
      <div class="absolute top-0 left-0 w-full h-96 bg-indigo-500/10 blur-[100px] rounded-full pointer-events-none -translate-y-1/2"></div>
      
      <!-- Top Navbar -->
      <header class="sticky top-0 z-30 px-4 py-3 md:px-6 md:py-4 flex items-center justify-between glass-header border-b border-white/5 bg-gray-900/50 backdrop-blur-lg">
          <div class="flex items-center gap-4">
              <!-- Mobile Menu Button (Moved here) -->
              <button @click="toggleSidebar" class="md:hidden p-2 bg-gray-800 rounded-lg text-gray-400 hover:text-white transition-colors">
                  <Menu v-if="!isSidebarOpen" class="w-6 h-6" />
                  <X v-else class="w-6 h-6" />
              </button>
              
              <!-- Desktop Collapse Toggle -->
              <button @click="toggleCollapse" class="hidden md:block p-2 text-gray-400 hover:text-white transition-colors">
                  <Menu class="w-6 h-6" />
              </button>
              
              <!-- Optional Breadcrumb or Page Title Placeholder -->
              <h2 class="text-sm font-medium text-gray-400 hidden sm:block">{{ __('welcome_back') }}, {{ user?.name || 'User' }}!</h2>
          </div>

          <div class="flex items-center gap-4">
              <!-- User Profile -->
              <div class="flex items-center gap-3 pl-4 ">
                  <div class="text-right hidden sm:block">
                      <p class="text-sm font-bold text-white leading-none">{{ user?.name || 'Guest' }}</p>
                      <button @click="logout" class="text-xs text-rose-400 mt-1 hover:text-rose-300 transition-colors flex items-center justify-end gap-1">
                          {{ __('logout') }} <LogOut class="w-3 h-3" />
                      </button>
                  </div>
                  <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-800 ring-2 ring-white/10 hover:ring-indigo-500 transition-all cursor-pointer">
                      <img v-if="user?.avatar" :src="`/storage/${user.avatar}`" class="w-full h-full object-cover" :alt="user.name">
                      <div v-else class="w-full h-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold">
                          {{ user?.name ? user.name.charAt(0).toUpperCase() : 'G' }}
                      </div>
                  </div>
              </div>
          </div>
      </header>

      <div class="container mx-auto px-4 py-8 md:p-10 relative z-10">
        <slot />
      </div>
    </main>

    <!-- Mobile Overlay -->
    <div 
        v-if="isSidebarOpen" 
        @click="isSidebarOpen = false"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 md:hidden"
    ></div>
  </div>
</template>
