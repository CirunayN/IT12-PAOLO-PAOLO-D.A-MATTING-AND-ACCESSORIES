@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black font-display text-slate-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-hard-drive text-red-500"></i>
                Database Backup &amp; Restore
            </h1>
        </div>
        <form method="POST" action="{{ route('backup.create') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2.5 px-6 py-3 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-bold text-sm shadow-lg shadow-red-600/25 transition-all transform hover:-translate-y-0.5">
                <i class="fas fa-download text-base"></i>
                <span>Backup Database Now</span>
            </button>
        </form>
    </div>

    <!-- Active Storage Destination Indicator -->
    <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-500/20 text-red-500 flex items-center justify-center text-lg flex-shrink-0">
                <i class="fas fa-folder-tree"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400">Current Backup Destination</div>
                <div class="font-mono font-bold text-slate-900 dark:text-white text-xs sm:text-sm break-all">{{ $backupDir }}</div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if(str_starts_with($backupDir, 'E:'))
                <span class="px-3 py-1 rounded-lg text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                    <i class="fas fa-plug text-[10px]"></i> External Drive (E:) Active
                </span>
            @else
                <span class="px-3 py-1 rounded-lg text-xs font-bold bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 flex items-center gap-1.5">
                    <i class="fas fa-server text-[10px]"></i> Local Storage Fallback
                </span>
            @endif
        </div>
    </div>

    <!-- 2 Column Layout: Settings (Left 4 cols) & Backups Archive Table (Right 8 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- LEFT COLUMN: Backup Configuration & Manual Upload Restore -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Settings Form -->
            <div class="glass-card rounded-2xl p-5 sm:p-6 border shadow-sm space-y-5">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-gear text-red-500"></i> Backup Configuration
                    </h3>
                </div>

                <form method="POST" action="{{ route('backup.settings') }}" class="space-y-4 text-sm">
                    @csrf

                    <!-- Backup Mode -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Backup Mode</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center justify-center p-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-dark-800 cursor-pointer hover:border-red-500 transition-colors text-center has-[:checked]:border-red-500 has-[:checked]:bg-red-500/10">
                                <input type="radio" name="backup_mode" value="automatic" {{ ($settings['backup_mode'] ?? '') === 'automatic' ? 'checked' : '' }} class="hidden">
                                <div class="text-center">
                                    <i class="fas fa-arrows-rotate text-red-500 text-lg mb-1 block"></i>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">Automatic</span>
                                </div>
                            </label>
                            <label class="flex items-center justify-center p-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-dark-800 cursor-pointer hover:border-red-500 transition-colors text-center has-[:checked]:border-red-500 has-[:checked]:bg-red-500/10">
                                <input type="radio" name="backup_mode" value="manual" {{ ($settings['backup_mode'] ?? '') === 'manual' ? 'checked' : '' }} class="hidden">
                                <div class="text-center">
                                    <i class="fas fa-hand-pointer text-amber-500 text-lg mb-1 block"></i>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">Manual Only</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Frequency -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Auto-Backup Frequency</label>
                        <select name="frequency" class="w-full py-2.5 px-3.5 bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm focus:ring-1 focus:ring-red-500 font-semibold">
                            <option value="1_day" {{ ($settings['frequency'] ?? '') === '1_day' ? 'selected' : '' }}>Every 1 Day (Daily)</option>
                            <option value="1_week" {{ ($settings['frequency'] ?? '') === '1_week' ? 'selected' : '' }}>Every 1 Week (Weekly)</option>
                            <option value="1_month" {{ ($settings['frequency'] ?? '') === '1_month' ? 'selected' : '' }}>Every 1 Month (Monthly)</option>
                        </select>
                    </div>

                    <!-- Retention Rule -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Auto-Cleanup Retention</label>
                        <select name="retention" class="w-full py-2.5 px-3.5 bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm focus:ring-1 focus:ring-red-500 font-semibold">
                            <option value="1_week" {{ ($settings['retention'] ?? '') === '1_week' ? 'selected' : '' }}>Delete backups older than 1 Week</option>
                            <option value="1_month" {{ ($settings['retention'] ?? '') === '1_month' ? 'selected' : '' }}>Delete backups older than 1 Month</option>
                            <option value="1_year" {{ ($settings['retention'] ?? '') === '1_year' ? 'selected' : '' }}>Delete backups older than 1 Year</option>
                            <option value="keep_all" {{ ($settings['retention'] ?? '') === 'keep_all' ? 'selected' : '' }}>Keep All (No auto-delete)</option>
                        </select>
                    </div>

                    <!-- Storage Path (e.g. E:\ drive) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">External Drive / Folder Path</label>
                        <input type="text" name="storage_path" value="{{ $settings['storage_path'] ?? 'E:\\PaoloPaolo_Backups' }}"
                            class="w-full py-2 px-3 bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl font-mono text-xs text-slate-800 dark:text-slate-200 focus:ring-1 focus:ring-red-500">
                        <p class="text-[11px] text-slate-400 mt-1">E.g., <code class="text-red-500">E:\PaoloPaolo_Backups</code> (external drive) or local path.</p>
                    </div>

                    <!-- CLOUD / ONLINE BACKUP (Google Drive / Cloud) -->
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="gdrive_enabled" value="1" {{ !empty($settings['gdrive_enabled']) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300 dark:border-slate-700">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                                    <i class="fab fa-google-drive text-emerald-500 text-sm"></i>
                                    Online Cloud / Google Drive
                                </span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Google Drive Folder ID (Optional)</label>
                            <input type="text" name="gdrive_folder_id" value="{{ $settings['gdrive_folder_id'] ?? '' }}" placeholder="e.g. 1AbCdEfGhIjKlMnOpQrStUvWxYz"
                                class="w-full py-2 px-3 bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-mono text-slate-900 dark:text-white focus:ring-1 focus:ring-red-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Notification Email</label>
                            <input type="email" name="gdrive_email" value="{{ $settings['gdrive_email'] ?? '' }}" placeholder="admin@paolopaolo.com"
                                class="w-full py-2 px-3 bg-slate-50 dark:bg-dark-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white focus:ring-1 focus:ring-red-500">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 dark:bg-dark-700 dark:hover:bg-dark-600 text-white font-bold text-xs shadow-sm transition-colors">
                        Save Settings
                    </button>
                </form>
            </div>

            <!-- Manual File Restore -->
            <div class="glass-card rounded-2xl p-5 sm:p-6 border shadow-sm space-y-4">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="font-display font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-upload text-amber-500"></i> Restore from .SQL File
                    </h3>
                </div>

                <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data" onsubmit="return confirm('WARNING: Restoring will overwrite current database records with the backup file. Proceed?');" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">Upload SQL Backup File</label>
                        <input type="file" name="backup_file" accept=".sql" required
                            class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-red-500/10 file:text-red-500 hover:file:bg-red-500/20 cursor-pointer">
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold text-xs shadow-md transition-all">
                        Upload &amp; Restore Database
                    </button>
                </form>
            </div>

        </div>

        <!-- RIGHT COLUMN: Backups Archive List (8 cols) -->
        <div class="lg:col-span-8">
            <div class="glass-card rounded-2xl border shadow-sm overflow-hidden space-y-0">
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-clock-rotate-left text-red-500"></i>
                            Backup Archives
                        </h3>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-300">
                        {{ count($files) }} Available
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                                <th class="p-4">Filename</th>
                                <th class="p-4">File Size</th>
                                <th class="p-4">Date Created</th>
                                <th class="p-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($files as $file)
                            <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fas fa-file-code text-red-500 text-lg"></i>
                                        <span class="font-mono font-bold text-xs sm:text-sm text-slate-900 dark:text-white">{{ $file['name'] }}</span>
                                    </div>
                                </td>
                                <td class="p-4 font-mono text-xs text-slate-600 dark:text-slate-300">
                                    {{ $file['size'] }}
                                </td>
                                <td class="p-4 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $file['created_at']->format('M d, Y h:i A') }}
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Download -->
                                        <a href="{{ route('backup.download', $file['name']) }}" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-600 hover:text-white text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs transition-colors" title="Download SQL File">
                                            <i class="fas fa-download"></i>
                                        </a>

                                        <!-- Restore from this archive -->
                                        <form method="POST" action="{{ route('backup.restore') }}" onsubmit="return confirm('Restore database from {{ $file['name'] }}? Existing records will be replaced.');" class="inline-block">
                                            @csrf
                                            <input type="hidden" name="existing_file" value="{{ $file['name'] }}">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-amber-500 hover:text-slate-900 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs transition-colors" title="Restore this backup">
                                                <i class="fas fa-rotate-left"></i>
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('backup.delete') }}" onsubmit="return confirm('Delete backup file {{ $file['name'] }}?');" class="inline-block">
                                            @csrf
                                            <input type="hidden" name="filename" value="{{ $file['name'] }}">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-rose-500 hover:text-white text-slate-400 flex items-center justify-center text-xs transition-colors" title="Delete backup">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400 text-sm">
                                    <i class="fas fa-database text-3xl mb-2 opacity-40 block"></i>
                                    No backups created yet. Click "Backup Database Now" above to generate your first backup.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection