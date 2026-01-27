<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Lock, Mail, User, ArrowRight } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen bg-gray-900 flex items-center justify-center p-4 relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute top-[-10%] right-[-10%] w-1/2 h-1/2 bg-indigo-500/20 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-1/2 h-1/2 bg-purple-500/20 rounded-full blur-[120px]"></div>
        </div>

        <div class="w-full max-w-md bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 rounded-2xl shadow-2xl overflow-hidden relative z-10">
            <div class="p-8">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-gradient-to-tr from-indigo-500 to-purple-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-500/20">
                        <span class="text-3xl font-bold text-white">F</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white">Create Account</h2>
                    <p class="text-gray-400 mt-2">Join FinanceApp today</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <User class="h-5 w-5 text-gray-500" />
                            </div>
                            <input 
                                v-model="form.name"
                                type="text" 
                                required 
                                autofocus
                                class="block w-full pl-10 pr-3 py-3 border border-gray-700 rounded-xl leading-5 bg-gray-900/50 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                                placeholder="John Doe"
                            >
                        </div>
                        <div v-if="form.errors.name" class="mt-2 text-sm text-rose-400">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Mail class="h-5 w-5 text-gray-500" />
                            </div>
                            <input 
                                v-model="form.email"
                                type="email" 
                                required 
                                class="block w-full pl-10 pr-3 py-3 border border-gray-700 rounded-xl leading-5 bg-gray-900/50 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                                placeholder="you@example.com"
                            >
                        </div>
                        <div v-if="form.errors.email" class="mt-2 text-sm text-rose-400">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-gray-500" />
                            </div>
                            <input 
                                v-model="form.password"
                                type="password" 
                                required 
                                class="block w-full pl-10 pr-3 py-3 border border-gray-700 rounded-xl leading-5 bg-gray-900/50 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                                placeholder="••••••••"
                            >
                        </div>
                        <div v-if="form.errors.password" class="mt-2 text-sm text-rose-400">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Lock class="h-5 w-5 text-gray-500" />
                            </div>
                            <input 
                                v-model="form.password_confirmation"
                                type="password" 
                                required 
                                class="block w-full pl-10 pr-3 py-3 border border-gray-700 rounded-xl leading-5 bg-gray-900/50 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                                placeholder="••••••••"
                            >
                        </div>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="group w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform active:scale-[0.98]"
                        >
                            <span v-if="form.processing">Creating Account...</span>
                            <span v-else class="flex items-center gap-2">
                                Register <ArrowRight class="w-4 h-4" />
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="px-8 py-4 bg-gray-900/50 border-t border-gray-700/50 text-center">
                <p class="text-sm text-gray-400">
                    Already have an account? 
                    <Link :href="route('login')" class="font-medium text-indigo-400 hover:text-indigo-300">
                        Sign in instead
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>
