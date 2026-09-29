@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-black font-display text-slate-900 dark:text-white flex items-center gap-2.5">
                <i class="fas fa-user-plus text-red-500"></i>
                Register Employee
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Registration stays pending until the employee's email code is verified.
            </p>
        </div>

        <a href="{{ route('employees.index') }}"
            class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-bold text-xs">
            &larr; Back
        </a>
    </div>

    @if($errors->any())
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-sm">
        <ul class="list-disc pl-5 space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
        action="{{ route('employees.store') }}"
        class="glass-card rounded-2xl p-6 sm:p-8 border shadow-lg space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                Full Name <span class="text-rose-500">*</span>
            </label>

            <input type="text"
                name="name"
                value="{{ old('name') }}"
                required
                maxlength="255"
                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Username <span class="text-rose-500">*</span>
                </label>

                <input type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    maxlength="100"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Gmail / Email <span class="text-rose-500">*</span>
                </label>

                <input type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    maxlength="255"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold"
                    placeholder="employee@gmail.com">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Temporary Password <span class="text-rose-500">*</span>
                </label>

                <input type="password"
                    name="password"
                    required
                    minlength="8"
                    maxlength="16"
                    pattern="[A-Za-z0-9]{8,16}"
                    autocomplete="new-password"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Confirm Password <span class="text-rose-500">*</span>
                </label>

                <input type="password"
                    name="password_confirmation"
                    required
                    minlength="8"
                    maxlength="16"
                    pattern="[A-Za-z0-9]{8,16}"
                    autocomplete="new-password"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm font-semibold">
            </div>
        </div>

        <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-slate-600 dark:text-slate-300 space-y-2">
            <div>
                <i class="fas fa-envelope-circle-check text-amber-500 mr-1"></i>
                A 6-digit confirmation code will be sent to the employee's email.
            </div>

            <div>
                <i class="fas fa-user-clock text-amber-500 mr-1"></i>
                If the employee cannot give the code immediately, you may leave this page.
                The employee will appear under <strong>Pending Verification</strong> with a
                <strong>Verify</strong> button so you can continue later.
            </div>

            <div>
                <i class="fas fa-lock text-amber-500 mr-1"></i>
                The employee cannot sign in until verification is completed.
            </div>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-800">
            <button type="submit"
                class="px-6 py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-sm shadow-md shadow-red-600/25">
                Send Confirmation Code
            </button>
        </div>
    </form>
</div>
@endsection
