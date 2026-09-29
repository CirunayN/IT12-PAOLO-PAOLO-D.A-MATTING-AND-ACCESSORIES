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
                    Administrator-only controls for employee accounts, offline password recovery, recovery codes, and system access.
                </p>
            </div>
        </div>
    </div>

    @if(session('approved_reset_code'))
        <div class="rounded-2xl p-5 border border-emerald-500/30 bg-emerald-500/10">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-500/15 text-emerald-500 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-black text-emerald-600 dark:text-emerald-400">Password Reset Approved</h2>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                        Give this code directly to <strong>{{ session('approved_reset_user') }}</strong>. It is valid until {{ session('approved_reset_expiry') }} and can be used only once.
                    </p>
                    <div class="mt-3 p-3 rounded-xl bg-white dark:bg-dark-900 border border-emerald-500/30 font-mono text-center text-lg sm:text-2xl font-black tracking-wider text-slate-900 dark:text-white select-all">
                        {{ session('approved_reset_code') }}
                    </div>
                    <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-2">
                        <i class="fas fa-triangle-exclamation mr-1"></i>
                        This plaintext code is shown only on this response. The database stores only its secure hash.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(session('generated_recovery_codes'))
        <div class="rounded-2xl p-5 border border-amber-500/30 bg-amber-500/10" id="recoveryCodesPrintArea">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div>
                    <h2 class="font-black text-amber-600 dark:text-amber-400 flex items-center gap-2">
                        <i class="fas fa-key"></i>
                        New Administrator Recovery Codes
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                        Save these 10 codes now. Every previous administrator recovery code is invalid.
                    </p>
                </div>
                <button type="button" onclick="printAdminRecoveryCodes()"
                    class="px-4 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold">
                    <i class="fas fa-print mr-1"></i> Print Codes Only
                </button>
            </div>

            <div class="grid sm:grid-cols-2 gap-2 mt-4 font-mono">
                @foreach(session('generated_recovery_codes') as $index => $code)
                    <div class="p-3 rounded-xl bg-white dark:bg-dark-900 border border-amber-500/25 flex gap-3">
                        <span class="text-slate-400 text-xs">{{ $index + 1 }}.</span>
                        <strong class="recovery-code-value tracking-wider text-slate-900 dark:text-white select-all" data-code="{{ $code }}">{{ $code }}</strong>
                    </div>
                @endforeach
            </div>

            <p class="text-[11px] text-amber-700 dark:text-amber-300 mt-3">
                Each code is one-time use. The plaintext values will not be available again after leaving or refreshing this page.
            </p>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-sm">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="grid xl:grid-cols-3 gap-5">
        <section class="xl:col-span-2 glass-card rounded-2xl border overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-unlock-keyhole text-red-500"></i>
                        Employee Password Reset Requests
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-1">Approve to create a one-time code valid for 24 hours.</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-red-500/10 text-red-500 text-xs font-black border border-red-500/20">
                    {{ $resetRequests->count() }} request(s)
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-dark-900/30">
                            <th class="px-4 py-3 text-left">Employee</th>
                            <th class="px-4 py-3 text-left">Requested</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resetRequests as $reset)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $reset->user->name ?? 'Unknown user' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $reset->user->username ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $reset->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($reset->approved_at && $reset->expires_at && $reset->expires_at->isFuture())
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-xs font-bold">
                                            <i class="fas fa-circle-check"></i> Approved
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-1">Expires {{ $reset->expires_at->format('M d, h:i A') }}</div>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs font-bold">
                                            <i class="fas fa-clock"></i> Waiting Approval
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('security.reset.approve', $reset) }}">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold whitespace-nowrap">
                                                <i class="fas fa-check mr-1"></i>
                                                {{ $reset->approved_at ? 'New Code' : 'Approve' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('security.reset.deny', $reset) }}"
                                            onsubmit="return confirm('Deny and remove this password reset request?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-600 text-rose-500 hover:text-white border border-rose-500/25 text-xs font-bold">
                                                Deny
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-400">
                                    <i class="fas fa-shield-check text-2xl mb-2 opacity-40"></i>
                                    <div>No employee password reset requests.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="glass-card rounded-2xl border p-5 space-y-4">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-life-ring text-amber-500"></i>
                    Admin Recovery Codes
                </h2>
                <p class="text-[11px] text-slate-400 mt-1">
                    Used only when the administrator forgets the password.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-200 dark:border-slate-800">
                <div class="text-xs text-slate-500 dark:text-slate-400">Unused recovery codes</div>
                <div class="text-3xl font-black mt-1 {{ $unusedRecoveryCodes > 0 ? 'text-emerald-500' : 'text-amber-500' }}">
                    {{ $unusedRecoveryCodes }} / 10
                </div>
            </div>

            <div class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                Generate the first set now if this is a newly upgraded system. Regenerating invalidates all existing codes immediately.
            </div>

            <form method="POST" action="{{ route('security.recovery.regenerate') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Current Admin Password</label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                        class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Confirm your password">
                </div>
                <button type="submit"
                    class="w-full py-3 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-black text-sm">
                    <i class="fas fa-rotate mr-1"></i>
                    {{ $unusedRecoveryCodes > 0 ? 'Regenerate 10 Codes' : 'Generate 10 Codes' }}
                </button>
            </form>

            <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/25 text-[11px] text-amber-700 dark:text-amber-300">
                After any administrator password change, the system automatically creates a completely new set of 10 codes.
            </div>
        </section>
    </div>

    <section class="glass-card rounded-2xl border overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-users-gear text-red-500"></i>
                    Employee Management
                </h2>
                <p class="text-[11px] text-slate-400 mt-1">
                    Create employee accounts directly with administrator password confirmation. No Gmail verification or registration code is required.
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
                        <th class="px-4 py-3 text-left">Access</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $employee)
                        <tr class="border-b border-slate-100 dark:border-slate-800 {{ !$employee->is_active ? 'opacity-70' : '' }}">
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white">{{ $employee->name }}</td>
                            <td class="px-4 py-3">{{ $employee->username }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $employee->email }}</td>
                            <td class="px-4 py-3">{{ $employee->role }}</td>
                            <td class="px-4 py-3">
                                @if($employee->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-xs font-bold">
                                        <i class="fas fa-circle-check"></i> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-rose-500/10 text-rose-500 border border-rose-500/20 text-xs font-bold">
                                        <i class="fas fa-ban"></i> Disabled
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
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
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No employee accounts found.</td></tr>
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
                    Stored only as account information. It is not used for Gmail verification or password recovery.
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
function printAdminRecoveryCodes() {
    const codeElements = document.querySelectorAll('.recovery-code-value');
    const codes = Array.from(codeElements)
        .map(element => element.dataset.code || element.textContent.trim())
        .filter(Boolean);

    if (codes.length === 0) {
        alert('No newly generated recovery codes are available to print.');
        return;
    }

    const printWindow = window.open('', '_blank', 'width=700,height=900');

    if (!printWindow) {
        alert('The print window was blocked by the browser. Please allow pop-ups for this system and try again.');
        return;
    }

    const rows = codes.map((code, index) =>
        `<div class="code-row"><span class="number">${index + 1}.</span><span>${code}</span></div>`
    ).join('');

    printWindow.document.open();
    printWindow.document.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Recovery Codes</title>
    <style>
        @page { margin: 18mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #000;
            background: #fff;
            font-family: "Courier New", Courier, monospace;
        }
        .codes {
            max-width: 520px;
            margin: 0 auto;
            padding-top: 8px;
        }
        .code-row {
            display: grid;
            grid-template-columns: 34px 1fr;
            align-items: baseline;
            gap: 8px;
            padding: 10px 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1.5px;
            border-bottom: 1px solid #ddd;
        }
        .number {
            font-size: 13px;
            font-weight: 400;
            letter-spacing: 0;
        }
        @media print {
            .code-row { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="codes">${rows}</div>
    <script>
        window.addEventListener('load', function () {
            window.focus();
            window.print();
        });
    <\/script>
</body>
</html>`);
    printWindow.document.close();
}

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
