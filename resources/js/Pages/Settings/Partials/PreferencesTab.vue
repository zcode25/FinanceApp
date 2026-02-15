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
        <div class="w-full bg-white rounded-3xl p-6 md:p-8 border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="flex items-center gap-6 mb-10">
                <div class="p-3.5 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm">
                    <Globe class="w-6 h-6" />
                </div>
                <div>
                     <h3 class="text-xl font-bold text-slate-900 leading-none mb-1.5">{{ __('language') }}</h3>
                     <p class="text-sm font-semibold text-slate-400">{{ __('app_preferences_desc') }}</p>
                </div>
            </div>
    
            <form @submit.prevent="updatePreferences" class="space-y-8">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-4 ml-1">{{ __('language') }}</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                        <button 
                            type="button"
                            @click="form.locale = 'en'"
                            class="p-5 md:p-6 rounded-2xl border transition-all flex items-center md:flex-col justify-start md:justify-center gap-4 md:gap-3 group/lang"
                            :class="form.locale === 'en' ? 'bg-white border-indigo-200 shadow-xl shadow-indigo-100 text-indigo-700' : 'bg-slate-50 border-slate-100 text-slate-400 hover:bg-white hover:border-slate-200'"
                        >
                            <span class="text-2xl md:text-3xl transition-transform group-hover/lang:scale-110">🇺🇸</span>
                            <span class="font-bold text-sm tracking-tight text-inherit">{{ __('english_us') }}</span>
                        </button>
                        <button 
                            type="button"
                            @click="form.locale = 'id'"
                            class="p-5 md:p-6 rounded-2xl border transition-all flex items-center md:flex-col justify-start md:justify-center gap-4 md:gap-3 group/lang"
                            :class="form.locale === 'id' ? 'bg-white border-indigo-200 shadow-xl shadow-indigo-100 text-indigo-700' : 'bg-slate-50 border-slate-100 text-slate-400 hover:bg-white hover:border-slate-200'"
                        >
                            <span class="text-2xl md:text-3xl transition-transform group-hover/lang:scale-110">🇮🇩</span>
                            <span class="font-bold text-sm tracking-tight text-inherit">{{ __('bahasa_indonesia') }}</span>
                        </button>
                    </div>
                </div>
    
                <div class="pt-8 border-t border-slate-50 flex justify-end">
                    <button type="submit" :disabled="form.processing" class="w-full md:w-auto px-8 py-3.5 bg-indigo-600 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 disabled:opacity-50">
                        {{ form.processing ? __('saving') : __('save_changes') }}
                    </button>
                </div>
            </form>
        </div>
    </template>
