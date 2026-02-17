<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { User, Mail, Lock, Camera, Upload, BadgeCheck } from 'lucide-vue-next';
import { ref } from 'vue';
import Swal from 'sweetalert2';

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;
    
    // Toast Helper
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    const showToast = (title, icon = 'success') => {
        Toast.fire({
            icon: icon,
            title: title,
            background: '#ffffff',
            color: '#1e293b',
            customClass: {
                popup: 'swal2-toast !rounded-2xl !p-4 shadow-xl border border-slate-100',
                title: '!text-sm !font-bold !text-slate-900',
            }
        });
    };
    
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
    const avatarPreview = ref(props.user.avatar ? `/media/${props.user.avatar}` : null);
    
    const triggerFileInput = () => {
        fileInput.value.click();
    };
    
    const handleFileChange = (event) => {
        const file = event.target.files[0];
        if (file) {
            // Check file size (1MB = 1024 * 1024 bytes)
            if (file.size > 1024 * 1024) {
                profileForm.setError('avatar', __('avatar_max'));
                profileForm.avatar = null;
                fileInput.value.value = ''; // Reset input
                avatarPreview.value = props.user.avatar ? `/media/${props.user.avatar}` : null;
                return;
            }

            // Check file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                profileForm.setError('avatar', __('avatar_mimes'));
                profileForm.avatar = null;
                fileInput.value.value = ''; // Reset input
                avatarPreview.value = props.user.avatar ? `/media/${props.user.avatar}` : null;
                return;
            }

            // Clear previous error if valid
            profileForm.clearErrors('avatar');
            
            profileForm.avatar = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                avatarPreview.value = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    };
    
    const updateProfile = () => {
        // Validation check for local errors
        if (profileForm.errors.avatar) {
            showToast(profileForm.errors.avatar, 'error');
            return;
        }

        profileForm.post('/settings/profile', {
            preserveScroll: true,
            onSuccess: () => {
                showToast(__('profile_updated'));
            },
            onError: (errors) => {
                if (errors.avatar) {
                    showToast(errors.avatar, 'error');
                }
            }
        });
    };
    
    const updatePassword = () => {
        passwordForm.put('/settings/password', {
            preserveScroll: true,
            onSuccess: () => {
                passwordForm.reset();
                showToast(__('password_updated'));
            },
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
            <div class="w-full bg-white rounded-3xl p-6 pb-12 md:p-8 border border-slate-100 shadow-sm relative overflow-hidden h-fit">
                <div class="flex items-center gap-6 mb-10">
                    <div class="p-3.5 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm">
                        <User class="w-6 h-6" />
                    </div>
                    <div>
                         <h3 class="text-xl font-bold text-slate-900 leading-none mb-1.5">{{ __('profile_security') }}</h3>
                         <p class="text-sm font-semibold text-slate-400">{{ __('public_profile_desc') }}</p>
                    </div>
                </div>
    
                <form @submit.prevent="updateProfile" class="space-y-8">
                    <!-- Avatar -->
                    <div class="flex items-center gap-6 p-6 bg-slate-50/50 rounded-2xl border border-slate-100">
                        <div class="relative group cursor-pointer" @click="triggerFileInput">
                            <div class="w-20 h-20 rounded-full overflow-hidden bg-slate-100 ring-4 ring-white shadow-xl shadow-slate-200/50 group-hover:ring-indigo-600 transition-all duration-300">
                                 <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                                 <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-tr from-indigo-500 to-indigo-700 text-2xl font-bold text-white">
                                     {{ getInitials(user.name) }}
                                 </div>
                            </div>
                            <div class="absolute inset-0 bg-indigo-900/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm">
                                <Camera class="w-6 h-6 text-white" />
                            </div>
                            <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleFileChange" />
                        </div>
                        <div>
                            <button type="button" @click="triggerFileInput" class="px-5 py-2.5 bg-white hover:bg-indigo-600 hover:text-white rounded-xl text-slate-900 font-bold transition-all flex items-center gap-2.5 border border-slate-100 shadow-sm active:scale-95 text-sm">
                                 <Upload class="w-4 h-4" /> <span>{{ __('change_avatar') }}</span>
                            </button>
                            <p class="text-[11px] text-slate-400 font-semibold mt-3 ml-1">{{ __('avatar_help') }}</p>
                            <div v-if="profileForm.errors.avatar" class="text-rose-500 text-[11px] font-bold mt-2 ml-1">{{ profileForm.errors.avatar }}</div>
                        </div>
                    </div>
    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('full_name') }}</label>
                            <div class="relative group">
                                <User class="absolute left-4 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                                <input v-model="profileForm.name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm" placeholder="John Doe">
                            </div>
                            <div v-if="profileForm.errors.name" class="text-rose-500 text-[11px] font-bold mt-2 ml-1">{{ profileForm.errors.name }}</div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2 ml-1">
                                <label class="block text-xs font-bold text-slate-700">{{ __('email_address') }}</label>
                                <div v-if="user.email_verified_at" class="flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-lg border border-emerald-100 shadow-sm shadow-emerald-50/50 transition-all hover:scale-105">
                                    <BadgeCheck class="w-3.5 h-3.5 fill-emerald-50" />
                                    <span class="text-[10px] font-black tracking-wider">{{ __('verified') }}</span>
                                </div>
                            </div>
                             <div class="relative group">
                                <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-slate-400 group-focus-within:text-indigo-600 transition-colors" />
                                <input v-model="profileForm.email" type="email" readonly class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-12 pr-4 py-3 text-slate-400 cursor-not-allowed font-semibold text-sm" placeholder="john@example.com">
                            </div>
                             <div v-if="profileForm.errors.email" class="text-rose-500 text-[11px] font-bold mt-2 ml-1">{{ profileForm.errors.email }}</div>
                        </div>
                    </div>
    
                    <div class="pt-8 border-t border-slate-50 flex justify-end">
                        <button type="submit" :disabled="profileForm.processing" class="w-full md:w-auto px-8 py-3.5 bg-indigo-600 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 disabled:opacity-50">
                            {{ profileForm.processing ? __('saving') : __('save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
    
            <!-- Password Card -->
            <div class="w-full bg-white rounded-3xl p-6 pb-12 md:p-8 border border-slate-100 shadow-sm relative overflow-hidden h-fit">
                <div class="flex items-center gap-6 mb-10">
                    <div class="p-3.5 rounded-2xl bg-slate-50 text-slate-900 border border-slate-100 shadow-sm">
                        <Lock class="w-6 h-6" />
                    </div>
                    <div>
                         <h3 class="text-xl font-bold text-slate-900 leading-none mb-1.5">{{ __('update_password') }}</h3>
                         <p class="text-sm font-semibold text-slate-400">{{ __('security_desc') }}</p>
                    </div>
                </div>
    
                <form @submit.prevent="updatePassword" class="space-y-8">
                     <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('current_password') }}</label>
                        <input v-model="passwordForm.current_password" type="password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm">
                        <div v-if="passwordForm.errors.current_password" class="text-rose-500 text-[11px] font-bold mt-2 ml-1">{{ passwordForm.errors.current_password }}</div>
                    </div>
    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('new_password') }}</label>
                            <input v-model="passwordForm.password" type="password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm">
                            <div v-if="passwordForm.errors.password" class="text-rose-400 text-[11px] font-bold mt-2 ml-1">{{ passwordForm.errors.password }}</div>
                        </div>
                         <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2 ml-1">{{ __('confirm_password') }}</label>
                            <input v-model="passwordForm.password_confirmation" type="password" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-semibold text-sm">
                        </div>
                    </div>
    
                     <div class="pt-8 border-t border-slate-50 flex justify-end">
                        <button type="submit" :disabled="passwordForm.processing" class="w-full md:w-auto px-8 py-3.5 bg-slate-900 text-white rounded-xl font-bold transition-all shadow-lg shadow-slate-200 hover:bg-slate-800 active:scale-95 disabled:opacity-50">
                            {{ passwordForm.processing ? __('updating') : __('update_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
