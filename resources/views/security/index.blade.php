@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-gear text-red-500"></i>
                Settings
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage your local account and security settings.</p>
        </div>
    </div>

    @include('settings.partials.tabs', ['active' => 'security'])

    <div class="glass-card rounded-2xl p-5 border">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-xl bg-red-500/15 text-red-500 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <h2 class="font-display font-black text-xl text-slate-900 dark:text-white">Security &amp; Access</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Administrator-only controls for employee accounts and system access. All users recover passwords with a saved private recovery code.
                </p>
            </div>
        </div>
    </div>

    @if($displayRecovery = app(\App\Services\AccountRecoveryService::class)->revealDisplay(session('created_employee_recovery_code')))
        <p class="text-sm text-slate-500 dark:text-slate-400">Give the new employee this recovery code privately so they can save it.</p>
        @include('shared.recovery-code-card', ['recovery' => $displayRecovery])
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-sm">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="glass-card rounded-2xl border overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-users-gear text-red-500"></i>
                    Employee Management
                </h2>
                <p class="text-[11px] text-slate-400 mt-1">
                    Confirm your administrator password to create an employee account and its private recovery code.
                </p>
            </div>

            <button type="button" onclick="openCreateEmployeeModal()"
                class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-xs shadow-md shadow-red-600/20 whitespace-nowrap">
                <i class="fas fa-user-plus mr-1.5"></i>
                Create Employee
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-dark-900/30">
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Username</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-right">Access</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $employee)
                        <tr class="border-b border-slate-100 dark:border-slate-800 {{ !$employee->is_active ? 'opacity-70' : '' }}">
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ $employee->name }}</td>
                            <td class="px-4 py-3">{{ $employee->username }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $employee->email }}</td>
                            <td class="px-4 py-3">{{ $employee->role }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('security.users.access', $employee) }}"
                                    onsubmit="return confirm('{{ $employee->is_active ? 'Disable this employee account and end its active sessions?' : 'Enable this employee account again?' }}');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="px-4 py-2 rounded-xl text-xs font-black {{ $employee->is_active ? 'bg-rose-500/10 hover:bg-rose-600 text-rose-500 hover:text-white border border-rose-500/25' : 'bg-emerald-600 hover:bg-emerald-500 text-white' }}">
                                        <i class="fas {{ $employee->is_active ? 'fa-user-slash' : 'fa-user-check' }} mr-1"></i>
                                        {{ $employee->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No employee accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="createEmployeeModal"
    class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card w-full max-w-2xl rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between gap-4 sticky top-0 bg-white/95 dark:bg-[#131926]/95 backdrop-blur z-10">
            <div>
                <h3 class="font-display font-black text-xl text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-user-plus text-red-500"></i>
                    Create Employee
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    The employee account becomes active immediately after your administrator password is confirmed.
                </p>
            </div>
            <button type="button" onclick="closeCreateEmployeeModal()"
                class="w-9 h-9 rounded-lg text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-dark-800 flex items-center justify-center text-xl">
                &times;
            </button>
        </div>

        <form method="POST" action="{{ route('security.users.store') }}" class="p-5 sm:p-6 space-y-5">
            @csrf

            @if($errors->createEmployee->any())
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-xs space-y-1">
                    @foreach($errors->createEmployee->all() as $error)
                        <div><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="employee_name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Full Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="employee_name" name="name" value="{{ old('name') }}" required maxlength="255"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Employee name">
                </div>

                <div>
                    <label for="employee_username" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Username <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="employee_username" name="username" value="{{ old('username') }}" required maxlength="100"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="employee_username">
                </div>
            </div>

            <div>
                <label for="employee_email" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    Email <span class="text-rose-500">*</span>
                </label>
                <input type="email" id="employee_email" name="email" value="{{ old('email') }}" required maxlength="255"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="employee@example.com">
                <p class="text-[11px] text-slate-400 mt-1.5">
                    Stored as employee contact information. Password recovery uses a saved private code.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="employee_password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Initial Password <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" id="employee_password" name="password" required minlength="8" maxlength="16" pattern="[A-Za-z0-9]{8,16}" autocomplete="new-password"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="8-16 letters and numbers">
                </div>

                <div>
                    <label for="employee_password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Confirm Password <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" id="employee_password_confirmation" name="password_confirmation" required minlength="8" maxlength="16" pattern="[A-Za-z0-9]{8,16}" autocomplete="new-password"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Re-enter employee password">
                </div>
            </div>

            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 space-y-3">
                <div class="flex items-start gap-2">
                    <i class="fas fa-shield-halved text-amber-500 mt-0.5"></i>
                    <div>
                        <div class="text-xs font-black text-amber-700 dark:text-amber-300">Administrator Confirmation Required</div>
                        <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-1">
                            Enter your own current administrator password. It is checked only to authorize this account creation and is never stored in the employee record.
                        </p>
                    </div>
                </div>

                <div>
                    <label for="admin_password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                        Your Admin Password <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" id="admin_password" name="admin_password" required autocomplete="current-password"
                        class="w-full px-4 py-3 rounded-xl bg-white dark:bg-dark-900 border border-amber-500/40 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"
                        placeholder="Confirm with your current password">
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeCreateEmployeeModal()"
                    class="px-5 py-3 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 font-bold text-sm">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-sm shadow-lg shadow-red-600/20">
                    <i class="fas fa-user-check mr-1.5"></i>
                    Confirm &amp; Create Employee
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateEmployeeModal() {
    const modal = document.getElementById('createEmployeeModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => document.getElementById('employee_name')?.focus(), 50);
}

function closeCreateEmployeeModal() {
    const modal = document.getElementById('createEmployeeModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeCreateEmployeeModal();
    }
});

@if($errors->createEmployee->any())
openCreateEmployeeModal();
@endif
</script>
@endpush
@endsection
