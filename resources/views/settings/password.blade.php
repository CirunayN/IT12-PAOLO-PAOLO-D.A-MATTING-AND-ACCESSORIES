@extends('layouts.app')

@section('content')

<div class="max-w-3xl mx-auto space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">

        <div>

            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">

                <i class="fas fa-gear text-red-500"></i>

                Settings

            </h1>


            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">

                Manage your account security settings.

            </p>

        </div>

    </div>


    <!-- SETTINGS NAV -->
    <div class="glass-card rounded-2xl p-3 border">

        <div class="flex items-center gap-2">

            <a
                href="{{ route('settings.password') }}"
                class="px-4 py-2.5 rounded-xl bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/30 font-bold text-sm flex items-center gap-2"
            >

                <i class="fas fa-key"></i>

                Password

            </a>

        </div>

    </div>


    <!-- PASSWORD CARD -->
    <div class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg">

        <div class="flex items-start gap-4 mb-6">

            <div class="w-12 h-12 rounded-2xl bg-red-500/15 text-red-500 flex items-center justify-center text-lg flex-shrink-0">

                <i class="fas fa-lock"></i>

            </div>


            <div>

                <h2 class="font-display font-black text-xl text-slate-900 dark:text-white">

                    Change Password

                </h2>


                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">

                    Enter your current password before creating a new password.

                </p>

            </div>

        </div>


        <!-- PASSWORD REQUIREMENTS -->
        <div class="mb-6 p-4 rounded-xl bg-slate-50 dark:bg-dark-900/70 border border-slate-200 dark:border-slate-700">

            <div class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">

                <i class="fas fa-circle-info text-red-500"></i>

                Password Requirements

            </div>


            <ul class="text-xs text-slate-500 dark:text-slate-400 space-y-1 ml-5 list-disc">

                <li>
                    Must contain 8 to 16 characters.
                </li>

                <li>
                    Only uppercase letters A-Z are allowed.
                </li>

                <li>
                    Only lowercase letters a-z are allowed.
                </li>

                <li>
                    Numbers 0-9 are allowed.
                </li>

                <li>
                    Spaces and special characters are not allowed.
                </li>

                <li>
                    New password must be different from the current password.
                </li>

            </ul>

        </div>


        <!-- PASSWORD FORM -->
        <form
            method="POST"
            action="{{ route('password.update') }}"
            class="space-y-5"
        >

            @csrf

            @method('PUT')


            <!-- CURRENT PASSWORD -->
            <div>

                <label
                    for="current_password"
                    class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5"
                >

                    Current Password

                    <span class="text-rose-500">
                        *
                    </span>

                </label>


                <div class="relative">

                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>


                    <input
                        type="password"
                        name="current_password"
                        id="current_password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your current password"
                        class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border {{ $errors->updatePassword->has('current_password') ? 'border-rose-500' : 'border-slate-300 dark:border-slate-700' }} text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                    >

                </div>


                @if(
                    $errors
                        ->updatePassword
                        ->has('current_password')
                )

                    <p class="mt-1.5 text-xs font-semibold text-rose-500 flex items-center gap-1">

                        <i class="fas fa-circle-exclamation"></i>

                        {{ $errors
                            ->updatePassword
                            ->first('current_password')
                        }}

                    </p>

                @endif

            </div>


            <!-- NEW PASSWORD -->
            <div>

                <label
                    for="password"
                    class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5"
                >

                    New Password

                    <span class="text-rose-500">
                        *
                    </span>

                </label>


                <div class="relative">

                    <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>


                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        minlength="8"
                        maxlength="16"
                        pattern="[A-Za-z0-9]{8,16}"
                        autocomplete="new-password"
                        placeholder="Enter your new password"
                        class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border {{ $errors->updatePassword->has('password') ? 'border-rose-500' : 'border-slate-300 dark:border-slate-700' }} text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                    >

                </div>


                @if(
                    $errors
                        ->updatePassword
                        ->has('password')
                )

                    <p class="mt-1.5 text-xs font-semibold text-rose-500 flex items-center gap-1">

                        <i class="fas fa-circle-exclamation"></i>

                        {{ $errors
                            ->updatePassword
                            ->first('password')
                        }}

                    </p>

                @endif

            </div>


            <!-- CONFIRM NEW PASSWORD -->
            <div>

                <label
                    for="password_confirmation"
                    class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5"
                >

                    Confirm New Password

                    <span class="text-rose-500">
                        *
                    </span>

                </label>


                <div class="relative">

                    <i class="fas fa-shield-halved absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>


                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        minlength="8"
                        maxlength="16"
                        pattern="[A-Za-z0-9]{8,16}"
                        autocomplete="new-password"
                        placeholder="Re-enter your new password"
                        class="w-full pl-11 pr-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                    >

                </div>


                @if(
                    $errors
                        ->updatePassword
                        ->has('password_confirmation')
                )

                    <p class="mt-1.5 text-xs font-semibold text-rose-500 flex items-center gap-1">

                        <i class="fas fa-circle-exclamation"></i>

                        {{ $errors
                            ->updatePassword
                            ->first('password_confirmation')
                        }}

                    </p>

                @endif

            </div>


            <!-- SECURITY NOTICE -->
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30">

                <div class="flex items-start gap-2">

                    <i class="fas fa-triangle-exclamation text-amber-500 mt-0.5"></i>


                    <div>

                        <div class="text-xs font-bold text-amber-600 dark:text-amber-400">

                            You will be logged out

                        </div>


                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">

                            After successfully changing your password, your current session will end and you must sign in again using your new password.

                        </p>

                    </div>

                </div>

            </div>


            <!-- BUTTON -->
            <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-800">

                <button
                    type="submit"
                    class="px-6 py-3 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-black text-sm shadow-lg shadow-red-600/25 flex items-center gap-2"
                >

                    <i class="fas fa-key"></i>

                    Change Password

                </button>

            </div>

        </form>

    </div>

</div>

@endsection
