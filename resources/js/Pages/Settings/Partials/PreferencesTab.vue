<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { Globe } from 'lucide-vue-next';

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;
    
    const props = defineProps({
        user: Object,
    });
    
    const form = useForm({
        locale: props.user.locale || 'en',
    });
    
    const updatePreferences = () => {
        form.patch('/settings/preferences', {
            preserveScroll: true,
            onSuccess: () => {
                 // Optional: Show toast
            },
        });
    };
    </script>
    
    <template>
        <div class="glass-card p-8 border-indigo-500/20 relative overflow-hidden max-w-2xl">
            <div class="flex items-center gap-4 mb-8">
                <div class="p-3 rounded-2xl bg-indigo-500/10 text-indigo-400">
                    <Globe class="w-6 h-6" />
                </div>
                <div>
                     <h3 class="text-lg font-bold text-white">{{ __('app_preferences_title') }}</h3>
                     <p class="text-sm text-gray-400">{{ __('app_preferences_desc') }}</p>
                </div>
            </div>
    
            <form @submit.prevent="updatePreferences" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">{{ __('language') }}</label>
                    <div class="grid grid-cols-2 gap-4">
                        <button 
                            type="button"
                            @click="form.locale = 'en'"
                            class="p-4 rounded-xl border transition-all flex items-center justify-center gap-2"
                            :class="form.locale === 'en' ? 'bg-indigo-500/20 border-indigo-500 text-white shadow-[0_0_15px_rgba(99,102,241,0.3)]' : 'bg-white/5 border-white/5 text-gray-400 hover:bg-white/10 hover:text-white'"
                        >
                            <span class="text-2xl">🇺🇸</span>
                            <span class="font-bold">English</span>
                        </button>
                        <button 
                            type="button"
                            @click="form.locale = 'id'"
                            class="p-4 rounded-xl border transition-all flex items-center justify-center gap-2"
                            :class="form.locale === 'id' ? 'bg-indigo-500/20 border-indigo-500 text-white shadow-[0_0_15px_rgba(99,102,241,0.3)]' : 'bg-white/5 border-white/5 text-gray-400 hover:bg-white/10 hover:text-white'"
                        >
                            <span class="text-2xl">🇮🇩</span>
                            <span class="font-bold">Indonesia</span>
                        </button>
                    </div>
                </div>
    
                <div class="pt-4 border-t border-white/5 flex justify-end">
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ form.processing ? __('saving') : __('save_preferences') }}
                    </button>
                </div>
            </form>
        </div>
    </template>
