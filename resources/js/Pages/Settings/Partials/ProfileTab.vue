<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { User, Mail, Lock, Camera, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;
    
    const props = defineProps({
        user: Object,
    });
    
    const profileForm = useForm({
        name: props.user.name,
        email: props.user.email,
        avatar: null,
    });
    
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    
    const fileInput = ref(null);
    const avatarPreview = ref(props.user.avatar ? `/storage/${props.user.avatar}` : null);
    
    const triggerFileInput = () => {
        fileInput.value.click();
    };
    
    const handleFileChange = (event) => {
        const file = event.target.files[0];
        if (file) {
            profileForm.avatar = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                avatarPreview.value = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    };
    
    const updateProfile = () => {
        profileForm.post('/settings/profile', {
            preserveScroll: true,
            onSuccess: () => {
                 // Optional: Show toast
            },
        });
    };
    
    const updatePassword = () => {
        passwordForm.put('/settings/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };
    
    const getInitials = (name) => {
        if (!name) return 'U';
        return name.charAt(0).toUpperCase();
    };
    </script>
    
    <template>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <!-- Public Profile Card -->
            <div class="glass-card p-8 border-indigo-500/20 relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-3 rounded-2xl bg-indigo-500/10 text-indigo-400">
                        <User class="w-6 h-6" />
                    </div>
                    <div>
                         <h3 class="text-lg font-bold text-white">{{ __('public_profile') }}</h3>
                         <p class="text-sm text-gray-400">{{ __('public_profile_desc') }}</p>
                    </div>
                </div>
    
                <form @submit.prevent="updateProfile" class="space-y-6">
                    <!-- Avatar -->
                    <div class="flex items-center gap-6">
                        <div class="relative group cursor-pointer" @click="triggerFileInput">
                            <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-800 ring-4 ring-white/5 group-hover:ring-indigo-500/50 transition-all">
                                 <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                                 <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-tr from-indigo-600 to-purple-600 text-2xl font-bold text-white">
                                     {{ getInitials(user.name) }}
                                 </div>
                            </div>
                            <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <Camera class="w-6 h-6 text-white" />
                            </div>
                            <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleFileChange" />
                        </div>
                        <div>
                            <button type="button" @click="triggerFileInput" class="text-sm border border-white/10 bg-white/5 hover:bg-white/10 px-4 py-2 rounded-lg text-white font-medium transition-colors flex items-center gap-2">
                                 <Upload class="w-4 h-4" /> {{ __('change_avatar') }}
                            </button>
                            <p class="text-xs text-gray-500 mt-2">{{ __('avatar_help') }}</p>
                        </div>
                    </div>
    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">{{ __('full_name') }}</label>
                            <div class="relative">
                                <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
                                <input v-model="profileForm.name" type="text" class="w-full bg-gray-900/50 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="John Doe">
                            </div>
                            <div v-if="profileForm.errors.name" class="text-rose-400 text-xs mt-1">{{ profileForm.errors.name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">{{ __('email_address') }}</label>
                             <div class="relative">
                                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" />
                                <input v-model="profileForm.email" type="email" class="w-full bg-gray-900/50 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="john@example.com">
                            </div>
                             <div v-if="profileForm.errors.email" class="text-rose-400 text-xs mt-1">{{ profileForm.errors.email }}</div>
                        </div>
                    </div>
    
                    <div class="pt-4 border-t border-white/5 flex justify-end">
                        <button type="submit" :disabled="profileForm.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ profileForm.processing ? __('saving') : __('save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
    
            <!-- Password Card -->
            <div class="glass-card p-8 border-white/5 relative overflow-hidden">
                 <div class="flex items-center gap-4 mb-8">
                    <div class="p-3 rounded-2xl bg-indigo-500/10 text-indigo-400">
                        <Lock class="w-6 h-6" />
                    </div>
                    <div>
                         <h3 class="text-lg font-bold text-white">{{ __('security') }}</h3>
                         <p class="text-sm text-gray-400">{{ __('security_desc') }}</p>
                    </div>
                </div>
    
                <form @submit.prevent="updatePassword" class="space-y-6">
                     <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">{{ __('current_password') }}</label>
                        <input v-model="passwordForm.current_password" type="password" class="w-full bg-gray-900/50 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <div v-if="passwordForm.errors.current_password" class="text-rose-400 text-xs mt-1">{{ passwordForm.errors.current_password }}</div>
                    </div>
    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">{{ __('new_password') }}</label>
                            <input v-model="passwordForm.password" type="password" class="w-full bg-gray-900/50 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                            <div v-if="passwordForm.errors.password" class="text-rose-400 text-xs mt-1">{{ passwordForm.errors.password }}</div>
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-400 mb-1">{{ __('confirm_password') }}</label>
                            <input v-model="passwordForm.password_confirmation" type="password" class="w-full bg-gray-900/50 border border-white/10 rounded-xl px-4 py-2.5 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        </div>
                    </div>
    
                     <div class="pt-4 border-t border-white/5 flex justify-end">
                        <button type="submit" :disabled="passwordForm.processing" class="px-6 py-2.5 bg-gray-700 hover:bg-gray-600 text-white rounded-xl font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ passwordForm.processing ? __('updating') : __('update_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
