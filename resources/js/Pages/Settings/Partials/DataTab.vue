<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { Database, Download, RefreshCw, Trash2, AlertTriangle } from 'lucide-vue-next';
import Swal from 'sweetalert2';

    const page = usePage();
    const __ = (key) => page.props.translations?.[key] || key;
    
    const resetForm = useForm({
        password: '',
    });
    
    const deleteForm = useForm({
        password: '',
    });
    
    const exportData = () => {
        window.location.href = '/settings/export';
    };
    
    const confirmReset = () => {
        Swal.fire({
            title: __('reset_confirm_title'),
            text: __('reset_confirm_text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48', // Rose 600
            cancelButtonColor: '#374151',
            confirmButtonText: __('yes_reset'),
            background: '#1f2937',
            color: '#ffffff',
            input: 'password',
            inputPlaceholder: __('enter_password_confirm'),
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            preConfirm: (password) => {
                if (!password) {
                    Swal.showValidationMessage(__('password_required'));
                    return false;
                }
                return password;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                resetForm.password = result.value;
                resetForm.post('/settings/reset', {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire({
                            title: __('reset_complete_title'),
                            text: __('reset_complete_text'),
                            icon: 'success',
                            background: '#1f2937',
                            color: '#ffffff',
                            confirmButtonColor: '#6366f1'
                        });
                        resetForm.reset();
                    },
                    onError: () => {
                        Swal.fire({
                            title: 'Error',
                            text: __('incorrect_password'),
                            icon: 'error',
                            background: '#1f2937',
                            color: '#ffffff',
                            confirmButtonColor: '#6366f1'
                        });
                        resetForm.reset();
                    }
                });
            }
        });
    };
    
    const confirmDeleteAccount = () => {
        Swal.fire({
            title: __('delete_account_confirm_title'),
            text: __('delete_account_confirm_text'),
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#374151',
            confirmButtonText: __('yes_delete_account'),
            background: '#1f2937',
            color: '#ffffff',
            input: 'password',
            inputPlaceholder: __('enter_password_confirm'),
            preConfirm: (password) => {
                if (!password) {
                    Swal.showValidationMessage(__('password_required'));
                    return false;
                }
                return password;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                deleteForm.password = result.value;
                deleteForm.delete('/settings/account', {
                    onSuccess: () => {
                        // Redirect happens automatically
                    },
                    onError: () => {
                        Swal.fire({
                            title: 'Error',
                            text: __('incorrect_password'),
                            icon: 'error',
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                        deleteForm.reset();
                    }
                });
            }
        });
    };
    </script>
    
    <template>
        <div class="space-y-8 max-w-4xl">
            <!-- Export Data -->
            <div class="glass-card p-8 border-emerald-500/20 relative overflow-hidden">
                <div class="flex items-center gap-4 mb-6">
                    <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-400">
                        <Download class="w-6 h-6" />
                    </div>
                    <div>
                         <h3 class="text-lg font-bold text-white">{{ __('export_data') }}</h3>
                         <p class="text-sm text-gray-400">{{ __('export_data_desc') }}</p>
                    </div>
                </div>
                
                <div class="flex items-center justify-between p-4 rounded-xl bg-white/5 border border-white/5">
                    <div>
                        <h4 class="text-white font-medium">{{ __('transaction_history') }}</h4>
                        <p class="text-xs text-gray-500 mt-1">{{ __('transaction_history_desc') }}</p>
                    </div>
                    <button @click="exportData" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-bold transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                        <Download class="w-4 h-4" /> {{ __('download_csv') }}
                    </button>
                </div>
            </div>
    
            <!-- Danger Zone -->
            <div class="glass-card p-8 border-rose-500/20 relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-400">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <div>
                         <h3 class="text-lg font-bold text-white">{{ __('danger_zone') }}</h3>
                         <p class="text-sm text-gray-400">{{ __('danger_zone_desc') }}</p>
                    </div>
                </div>
    
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 rounded-xl bg-rose-500/5 border border-rose-500/10">
                        <div>
                            <h4 class="text-white font-medium">{{ __('reset_all_data') }}</h4>
                            <p class="text-xs text-gray-500 mt-1">{{ __('reset_all_data_desc') }}</p>
                        </div>
                        <button @click="confirmReset" class="px-4 py-2 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white rounded-lg text-sm font-bold transition-all border border-rose-500/20">
                            {{ __('reset_data') }}
                        </button>
                    </div>
    
                    <div class="flex items-center justify-between p-4 rounded-xl bg-rose-500/5 border border-rose-500/10">
                        <div>
                            <h4 class="text-white font-medium">{{ __('delete_account') }}</h4>
                            <p class="text-xs text-gray-500 mt-1">{{ __('delete_account_desc') }}</p>
                        </div>
                        <button @click="confirmDeleteAccount" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-sm font-bold transition-all shadow-lg shadow-rose-500/20">
                            {{ __('delete_account') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
