<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Lock, Mail, ArrowRight } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen bg-gray-900 flex items-center justify-center p-4 relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-1/2 h-1/2 bg-indigo-500/20 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-1/2 h-1/2 bg-purple-500/20 rounded-full blur-[120px]"></div>
        </div>

        <div class="w-full max-w-md bg-gray-800/50 backdrop-blur-xl border border-gray-700/50 rounded-2xl shadow-2xl overflow-hidden relative z-10">
            <div class="p-8">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-gradient-to-tr from-indigo-500 to-purple-500 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-500/20">
                        <span class="text-3xl font-bold text-white">F</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white">Welcome Back</h2>
                    <p class="text-gray-400 mt-2">Sign in to continue to FinanceApp</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
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
                                autofocus
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

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="relative flex items-center">
                                <input 
                                    v-model="form.remember"
                                    id="remember-me" 
                                    name="remember-me" 
                                    type="checkbox" 
                                    class="peer h-5 w-5 cursor-pointer appearance-none rounded border border-gray-600 bg-gray-800/50 checked:border-indigo-500 checked:bg-indigo-500 hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
                                >
                                <div class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-white opacity-0 peer-checked:opacity-100 transition-opacity duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <label for="remember-me" class="ml-2.5 block text-sm text-gray-400 cursor-pointer select-none group-hover:text-gray-300 transition-colors">
                                Remember me
                            </label>
                        </div>

                    </div>

                    <div>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="group w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform active:scale-[0.98]"
                        >
                            <span v-if="form.processing">Signing in...</span>
                            <span v-else class="flex items-center gap-2">
                                Sign in <ArrowRight class="w-4 h-4" />
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="px-8 py-4 bg-gray-900/50 border-t border-gray-700/50 text-center">
                <p class="text-sm text-gray-400">
                    Don't have an account? 
                    <Link :href="route('register')" class="font-medium text-indigo-400 hover:text-indigo-300">
                        Create one now
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>
