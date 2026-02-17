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
            title: __('clear_all_data_title'),
            text: __('clear_all_data_text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: __('yes_delete_everything'),
            cancelButtonText: __('cancel'),
            customClass: {
                popup: '!rounded-[2rem] !p-10 !bg-white !shadow-2xl !border !border-slate-100 !font-sans !antialiased',
                title: '!text-xl !font-bold !text-slate-900 !pt-4 !pb-2 !px-0 !m-0 !leading-tight',
                htmlContainer: '!text-sm !font-semibold !text-slate-500 !leading-relaxed !pb-6 !px-0 !m-0',
                actions: '!flex !items-center !justify-center !gap-3 !mt-4 !w-full !px-0',
                confirmButton: '!inline-flex !items-center !justify-center !bg-rose-600 !text-white !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm hover:!shadow-rose-600/20 hover:!bg-rose-700 active:!scale-95 !border-none !outline-none !m-0 !cursor-pointer',
                cancelButton: '!inline-flex !items-center !justify-center !bg-slate-100 !text-slate-700 hover:!bg-slate-200 !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm !border-none !outline-none !m-0 !cursor-pointer active:!scale-95',
                icon: '!border-4 !border-rose-100 !text-rose-600 !scale-110 !mb-6 !mt-2',
                input: '!bg-slate-50 !border !border-slate-100 !rounded-xl !px-6 !py-4 !text-slate-900 !placeholder-slate-400 !focus:outline-none !focus:ring-2 !focus:ring-indigo-100 !focus:border-indigo-500 !transition-all !font-bold !mt-4'
            },
            buttonsStyling: false,
            backdrop: 'rgba(15, 23, 42, 0.4)',
            input: 'password',
            inputPlaceholder: __('confirm_with_master_key'),
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
                            title: __('reset_complete'),
                            text: __('portfolio_cleared'),
                            icon: 'success',
                            buttonsStyling: false
                        });
                        resetForm.reset();
                    },
                    onError: () => {
                        Swal.fire({
                            title: __('auth_failed'),
                            text: __('incorrect_master_key'),
                            icon: 'error',
                            buttonsStyling: false
                        });
                        resetForm.reset();
                    }
                });
            }
        });
    };
    
    const confirmDeleteAccount = () => {
        Swal.fire({
            title: __('delete_account_title'),
            text: __('delete_account_text'),
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: __('yes_delete_account'),
            cancelButtonText: __('keep_account'),
            customClass: {
                popup: '!rounded-[2rem] !p-10 !bg-white !shadow-2xl !border !border-slate-100 !font-sans !antialiased',
                title: '!text-xl !font-bold !text-slate-900 !pt-4 !pb-2 !px-0 !m-0 !leading-tight',
                htmlContainer: '!text-sm !font-semibold !text-slate-500 !leading-relaxed !pb-6 !px-0 !m-0',
                actions: '!flex !items-center !justify-center !gap-3 !mt-4 !w-full !px-0',
                confirmButton: '!inline-flex !items-center !justify-center !bg-rose-600 !text-white !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm hover:!shadow-rose-600/20 hover:!bg-rose-700 active:!scale-95 !border-none !outline-none !m-0 !cursor-pointer',
                cancelButton: '!inline-flex !items-center !justify-center !bg-slate-100 !text-slate-700 hover:!bg-slate-200 !font-bold !text-sm !rounded-xl !px-8 !py-3 !transition-all !shadow-sm !border-none !outline-none !m-0 !cursor-pointer active:!scale-95',
                icon: '!border-4 !border-rose-100 !text-rose-600 !scale-110 !mb-6 !mt-2',
                input: '!bg-slate-50 !border !border-slate-100 !rounded-xl !px-6 !py-4 !text-slate-900 !placeholder-slate-400 !focus:outline-none !focus:ring-2 !focus:ring-indigo-100 !focus:border-indigo-500 !transition-all !font-bold !mt-4'
            },
            buttonsStyling: false,
            backdrop: 'rgba(15, 23, 42, 0.4)',
            input: 'password',
            inputPlaceholder: __('confirm_with_master_key'),
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
                            title: __('termination_inhibited'),
                            text: __('verification_failed'),
                            icon: 'error',
                            buttonsStyling: false
                        });
                        deleteForm.reset();
                    }
                });
            }
        });
    };
    </script>
    
    <template>
        <div class="space-y-8 md:space-y-10 w-full">
            <!-- Export Data -->
            <div class="w-full bg-white rounded-3xl p-6 md:p-8 border border-slate-100 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-10 -top-10 w-64 h-64 bg-emerald-50/50 rounded-full blur-3xl transition-all group-hover:bg-emerald-100/50"></div>
                
                <div class="flex items-center gap-6 mb-10 relative z-10">
                    <div class="p-3.5 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm">
                        <Download class="w-6 h-6" />
                    </div>
                    <div>
                         <h3 class="text-xl font-bold text-slate-900 leading-none mb-1.5">{{ __('export_data') }}</h3>
                         <p class="text-sm font-semibold text-slate-400">{{ __('export_data_desc') }}</p>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-7 rounded-2xl bg-slate-50 border border-slate-100 gap-6 relative z-10">
                    <div class="text-center md:text-left">
                        <h4 class="text-slate-900 font-bold text-lg leading-snug">{{ __('transaction_history') }}</h4>
                        <p class="text-xs font-bold text-slate-500 mt-1">{{ __('transaction_history_desc') }}</p>
                    </div>
                    <button @click="exportData" class="w-full md:w-auto px-7 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-emerald-100 flex items-center justify-center gap-2.5 active:scale-95 text-sm">
                        <Download class="w-4.5 h-4.5" /> {{ __('download_csv') }}
                    </button>
                </div>
            </div>
    
            <!-- Danger Zone -->
            <div class="w-full bg-white rounded-3xl p-6 md:p-8 border border-rose-100 shadow-sm relative overflow-hidden group">
                <div class="flex items-center gap-6 mb-10">
                    <div class="p-3.5 rounded-2xl bg-rose-50 text-rose-600 border border-rose-100 shadow-sm">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <div>
                         <h3 class="text-xl font-bold text-slate-900 leading-none mb-1.5">{{ __('danger_zone') }}</h3>
                         <p class="text-sm font-semibold text-slate-400">{{ __('danger_zone_desc') }}</p>
                    </div>
                </div>
    
                <div class="space-y-6">
                    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-7 rounded-2xl bg-rose-50/30 border border-rose-100 gap-6">
                        <div class="text-center md:text-left">
                            <h4 class="text-rose-900 font-bold text-lg leading-none mb-1.5">{{ __('reset_all_data') }}</h4>
                            <p class="text-xs font-bold text-rose-500">{{ __('reset_all_data_desc') }}</p>
                        </div>
                        <button 
                            @click="confirmReset" 
                            :disabled="resetForm.processing"
                            class="w-full md:w-auto px-7 py-3.5 bg-white hover:bg-rose-600 text-rose-600 hover:text-white rounded-xl font-bold transition-all border border-rose-100 active:scale-95 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('reset_data') }}
                        </button>
                    </div>
    
                    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-7 rounded-2xl bg-rose-600 text-white gap-6 shadow-xl shadow-rose-200">
                        <div class="text-center md:text-left">
                            <h4 class="font-bold text-lg leading-none mb-1.5">{{ __('delete_account') }}</h4>
                            <p class="text-xs font-bold text-rose-100">{{ __('delete_account_desc') }}</p>
                        </div>
                        <button 
                            @click="confirmDeleteAccount" 
                            :disabled="deleteForm.processing"
                            class="w-full md:w-auto px-7 py-3.5 bg-white text-rose-600 rounded-xl font-bold transition-all shadow-lg hover:bg-rose-50 active:scale-95 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('delete_account') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
