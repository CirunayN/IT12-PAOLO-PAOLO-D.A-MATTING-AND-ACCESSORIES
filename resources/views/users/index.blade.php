@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-users-gear text-red-500"></i>
                Staff &amp; Users
            </h1>
        </div>
        <button type="button" onclick="document.getElementById('addUserModal').classList.remove('hidden')" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-md shadow-red-600/25 flex items-center gap-2">
            <i class="fas fa-user-plus"></i>
            <span>Add Staff</span>
        </button>
    </div>

    <!-- Users Table -->
    <div class="glass-card rounded-2xl border shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                        <th class="p-4">ID</th>
                        <th class="p-4">Staff Name</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Role</th>
                        <th class="p-4">Joined Date</th>
                        <th class="p-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                        <td class="p-4 font-mono font-bold text-red-500">#{{ $user->id }}</td>
                        <td class="p-4 font-bold text-slate-900 dark:text-white">
                            {{ $user->name }}
                        </td>
                        <td class="p-4 text-slate-600 dark:text-slate-300">
                            {{ $user->email }}
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $user->role === 'Admin' ? 'bg-purple-500/15 text-purple-500' : 'bg-red-500/15 text-red-500' }}">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-slate-400">
                            {{ $user->created_at ? $user->created_at->format('M d, Y') : '-' }}
                        </td>
                        <td class="p-4 text-center">
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user->id) }}" onsubmit="return confirm('Remove staff member {{ $user->name }}?');" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-rose-500 hover:text-white text-slate-400 flex items-center justify-center text-xs transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @else
                            <span class="text-xs text-slate-400 italic">Current User</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-lg w-full p-6 border shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="font-display font-black text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-user-plus text-red-500"></i> Add Staff Member
            </h3>
            <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Full Name</label>
                <input type="text" name="name" required class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Role</label>
                <select name="role" required class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm">
                    <option value="Admin">Admin</option>
                    <option value="Cashier">Cashier</option>
                    <option value="Packer">Packer</option>
                    <option value="Accessory Installer">Accessory Installer</option>
                    <option value="Production Worker">Production Worker</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Password</label>
                <input type="password" name="password" required minlength="6" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 text-sm">
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-dark-800 text-sm font-bold">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-bold">Create Account</button>
            </div>
        </form>
    </div>
</div>
@endsection