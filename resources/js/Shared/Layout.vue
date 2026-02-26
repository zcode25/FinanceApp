<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { LayoutDashboard, Wallet, PieChart, Banknote, Settings, Menu, X, User, ChevronDown, FileText, Tag, LogOut, TrendingUp, Target, BadgeCheck, Crown, ShieldAlert, Sparkles, Rocket, Zap } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { App } from '@capacitor/app';

const page = usePage();
const __ = (key) => page.props.translations?.[key] || key;
const user = computed(() => page.props.auth.user);

const isSidebarOpen = ref(false);
const isProfileOpen = ref(false);

const planIcon = computed(() => {
  if (!user.value?.is_premium) return Crown;
  switch (user.value.current_plan_id) {
    case 2: return Rocket;
    case 3: return Crown;
    case 4: return Zap;
    default: return Crown;
  }
});

const planColor = computed(() => {
  if (!user.value?.is_premium) return 'bg-slate-50 text-slate-400 border-slate-100';
  switch (user.value.current_plan_id) {
    case 2: return 'bg-indigo-50 text-indigo-600 border-indigo-100';
    case 3: return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 4: return 'bg-purple-50 text-purple-600 border-purple-100';
    default: return 'bg-indigo-50 text-indigo-600 border-indigo-100';
  }
});

const navigation = computed(() => [
  { id: 'dashboard', name: __('dashboard'), href: '/dashboard', icon: LayoutDashboard },
  { id: 'transactions', name: __('transactions'), href: '/transactions', icon: Banknote },
  { id: 'wallets', name: __('wallets'), href: '/wallets', icon: Wallet },
  { id: 'analysis', name: __('analysis'), href: '/analysis', icon: PieChart },
  { id: 'budget', name: __('budget'), href: '/budget', icon: Banknote },
  { id: 'goals', name: __('goals'), href: '/goals', icon: Target },
  { id: 'categories', name: __('categories'), href: '/categories', icon: Tag },
  { id: 'tracker', name: __('tracker'), href: '/tracker', icon: TrendingUp },
  { id: 'reports', name: __('reports'), href: '/reports', icon: FileText },
  { id: 'subscription', name: __('subscription'), href: '/subscription', icon: Crown },
]);

const bottomNavItems = computed(() => [
  { id: 'home', name: __('home'), href: '/dashboard', icon: LayoutDashboard },
  { id: 'transactions', name: __('transactions'), href: '/transactions', icon: Banknote },
  { id: 'analysis', name: __('analysis'), href: '/analysis', icon: PieChart }
]);

const isCollapsed = ref(false); // Desktop state

const toggleSidebar = () => isSidebarOpen.value = !isSidebarOpen.value;
const toggleCollapse = () => isCollapsed.value = !isCollapsed.value;

const logout = () => {
    router.post(route('logout'));
};

const restartTour = () => {
    localStorage.removeItem('tour_state');
    isProfileOpen.value = false;
    router.visit('/dashboard?restart_tour=true');
};

// Custom directive to handle clicks outside of an element
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event);
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  },
};
const getAvatarUrl = (avatar) => {
    if (!avatar) return null;
    if (avatar.startsWith('http')) return avatar;
    if (avatar.startsWith('avatars/')) return `/media/${avatar}`;
    return `/media/avatars/${avatar}`;
};

onMounted(() => {
  // Deep Link Listener for Capacitor
  App.addListener('appUrlOpen', (event) => {
    // Extract path from URL (e.g., https://vibefinance.terasweb.id/dashboard -> /dashboard)
    const slug = event.url.split('.id').pop();
    if (slug) {
      router.visit(slug);
    }
  });
});
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 font-sans flex flex-col relative overflow-x-hidden">
    
    <!-- Top Utility Navbar -->
    <header class="sticky top-0 z-50 bg-white/70 backdrop-blur-xl border-b border-slate-100 px-6 py-4">
      <div class="max-w-[1600px] mx-auto flex items-center justify-between gap-8">
        <!-- Logo & Account Selector -->
        <div class="flex items-center gap-6">
          <Link href="/dashboard" class="flex items-center gap-2">
            <div class="flex items-center justify-center transition-all hover:scale-110">
              <img src="/img/logo_vibefinance.png" class="h-7 w-auto object-contain" alt="VibeFinance Logo">
            </div>
            <div class="flex flex-col leading-tight">
              <span class="text-xl tracking-tight text-slate-900" style="font-family: 'Outfit', sans-serif;">
                <span class="font-semibold">Vibe</span><span class="font-light text-indigo-600">Finance</span>
              </span>
              <span class="text-[9px] font-medium text-slate-400">Powered by terasweb.id</span>
            </div>
          </Link>
        </div>


        <!-- Right Utilities -->
        <div class="flex items-center gap-4">
          <div class="w-px h-6 bg-slate-100 mx-2 hidden sm:block"></div>
          
          <div class="relative">
            <div 
              @click.stop="isProfileOpen = !isProfileOpen"
              class="flex items-center gap-3 pl-2 group cursor-pointer"
            >
              <div class="text-right hidden lg:block">
                <div class="flex items-center justify-end gap-1.5 pb-0.5">
                  <p class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ user?.name || __('user') }}</p>
                  <div v-if="user?.is_premium" :class="[planColor, 'w-6 h-6 rounded-lg flex items-center justify-center border shadow-sm']">
                    <component :is="planIcon" class="w-3.5 h-3.5 fill-current opacity-80" />
                  </div>
                </div>
                <div class="flex items-center justify-end gap-1">
                  <BadgeCheck v-if="user?.email_verified_at" class="w-2.5 h-2.5 text-emerald-500 fill-emerald-50" />
                  <p class="text-[10px] font-semibold text-slate-400">{{ user?.email }}</p>
                </div>
              </div>
              <div class="w-10 h-10 rounded-xl overflow-hidden ring-2 ring-transparent group-hover:ring-indigo-100 transition-all shadow-sm">
                <img v-if="user?.avatar" :src="getAvatarUrl(user.avatar)" class="w-full h-full object-cover" :alt="user.name">
                <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 font-bold">
                  {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                </div>
              </div>
              <ChevronDown 
                class="w-4 h-4 text-slate-400 transition-transform duration-200" 
                :class="{ 'rotate-180': isProfileOpen }"
              />
            </div>

            <!-- Profile Dropdown -->
            <div 
              v-if="isProfileOpen"
              v-click-outside="() => isProfileOpen = false"
              class="absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 z-[60] animate-in fade-in slide-in-from-top-2 duration-200"
            >
              <div class="px-4 py-3 border-b border-slate-50 mb-2">
                <div class="flex items-center gap-1.5 mb-1">
                  <p class="text-sm font-bold text-slate-900 leading-none">{{ user?.name }}</p>
                  <div v-if="user?.is_premium" :class="[planColor, 'w-5 h-5 rounded-md flex items-center justify-center border shadow-sm']">
                    <component :is="planIcon" class="w-3 h-3 fill-current opacity-80" />
                  </div>
                </div>
                <div class="flex items-center gap-1 min-w-0">
                  <BadgeCheck v-if="user?.email_verified_at" class="w-3 h-3 text-emerald-500 fill-emerald-50 shrink-0" />
                  <p class="text-xs font-medium text-slate-400 truncate">{{ user?.email }}</p>
                </div>
              </div>
              
              <Link 
                :href="route('settings.index')" 
                class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-all"
                @click="isProfileOpen = false"
              >
                <Settings class="w-4 h-4" />
                <span>{{ __('settings') }}</span>
              </Link>
              
              <button 
                @click="restartTour"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all text-left"
              >
                <Sparkles class="w-4 h-4" />
                <span>{{ __('restart_product_tour') }}</span>
              </button>

              <div class="my-2 border-t border-slate-50"></div>
              
              <button 
                @click="logout"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50 transition-all"
              >
                <LogOut class="w-4 h-4" />
                <span>{{ __('logout') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>
    
    <!-- Email Verification Banner (Soft Gate) -->
    <div v-if="user && !user.email_verified_at" class="bg-slate-900 border-b border-white/10 px-6 py-2.5 animate-in slide-in-from-top duration-500 relative z-[45]">
      <div class="max-w-[1600px] mx-auto flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-9 h-9 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center shrink-0 border border-white/10">
            <ShieldAlert class="w-5 h-5 text-amber-400" />
          </div>
          <div class="space-y-0.5">
            <p class="text-xs font-bold text-white leading-none">{{ __('security_check_required') }}</p>
            <p class="text-[10px] font-medium text-slate-400">{{ __('verify_email_desc') }}</p>
          </div>
        </div>
        <Link 
          :href="route('verification.notice')" 
          class="px-5 py-2 bg-white hover:bg-slate-50 text-slate-900 text-[10px] font-bold rounded-xl transition-all active:scale-95 shadow-lg shadow-black/20"
        >
          {{ __('verify_now') }}
        </Link>
      </div>
    </div>

    <!-- Sub-navigation Tabs (Desktop Only) -->
    <nav class="hidden lg:flex bg-white/70 backdrop-blur-xl border-b border-slate-100 px-6 sticky top-[73px] z-40 overflow-x-auto no-scrollbar">
      <div class="max-w-[1600px] w-full mx-auto flex items-center gap-8 pl-0">
        <Link 
          v-for="item in navigation" 
          :key="item.name" 
          :href="item.href"
          prefetch
          :id="`nav-${item.id}`"
          class="relative py-4 text-sm font-semibold transition-all whitespace-nowrap"
          :class="$page.url.split('?')[0] === item.href ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600'"
        >
          {{ item.name }}
          <div v-if="$page.url.split('?')[0] === item.href" class="absolute bottom-0 left-0 w-full h-0.5 bg-indigo-600 rounded-full"></div>
        </Link>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-[1600px] mx-auto w-full px-4 lg:px-6 py-8 relative z-10">
      <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-50 rounded-full blur-[100px] pointer-events-none opacity-50"></div>
      <slot />
    </main>

    <!-- FIXED BOTTOM NAVIGATION (MOBILE ONLY) -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-slate-100 px-6 py-4 pb-[calc(2rem + env(safe-area-inset-bottom))] shadow-[0_-8px_30_rgba(0,0,0,0.08)] rounded-t-[2.5rem]">
      <nav class="flex items-center justify-around">
        <Link 
          v-for="item in bottomNavItems" 
          :key="item.name" 
          :href="item.href"
          prefetch
          :id="`mobile-nav-${item.id}`"
          class="flex flex-col items-center gap-1.5 transition-all active:scale-95"
          :class="$page.url.split('?')[0] === item.href ? 'text-indigo-600' : 'text-slate-400'"
        >
          <component :is="item.icon" class="w-6 h-6" :stroke-width="$page.url.split('?')[0] === item.href ? 2.5 : 2" />
          <span class="text-[10px] font-bold tracking-tight">{{ item.name }}</span>
        </Link>
      </nav>
    </div>

  </div>
</template>
