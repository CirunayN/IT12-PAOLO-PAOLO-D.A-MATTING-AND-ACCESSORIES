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
        <div class="lg:col-span-8 space-y-4">
            <div class="glass-card rounded-2xl border shadow-sm overflow-hidden space-y-0">
                <!-- Header with Tabs -->
                <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 dark:bg-dark-850/50">
                    <div class="flex items-center gap-2">
                        <button type="button" id="tabActiveBtn" onclick="switchBackupTab('active')"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all cursor-pointer bg-red-600 text-white shadow-sm shadow-red-600/20">
                            <i class="fas fa-database"></i>
                            <span>Active Backups</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-white/20 text-white">{{ count($files) }}</span>
                        </button>

                        <button type="button" id="tabArchiveBtn" onclick="switchBackupTab('archive')"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all cursor-pointer bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700">
                            <i class="fas fa-box-archive"></i>
                            <span>Recovery Archive</span>
                            <span id="archiveCountBadge" class="px-2 py-0.5 rounded-full text-[11px] font-black {{ count($archivedFiles) > 0 ? 'bg-amber-500/20 text-amber-500 border border-amber-500/30' : 'bg-slate-300 dark:bg-dark-700 text-slate-600 dark:text-slate-400' }}">
                                {{ count($archivedFiles) }}
                            </span>
                        </button>
                    </div>

                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <i class="fas fa-shield-halved text-emerald-500"></i>
                        <span>Protected Snapshot Storage</span>
                    </div>
                </div>

                <!-- TAB 1: ACTIVE BACKUPS TABLE -->
                <div id="activeBackupsTabContent" class="overflow-x-auto">
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
                                        <a href="{{ route('backup.download', $file['name']) }}" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-red-600 hover:text-white text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs transition-colors cursor-pointer" title="Download SQL File">
                                            <i class="fas fa-download"></i>
                                        </a>

                                        <!-- Restore from this backup -->
                                        <button type="button" onclick="promptRestoreBackup('{{ $file['name'] }}', '{{ $file['size'] }}')" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-amber-500 hover:text-slate-900 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs transition-colors cursor-pointer" title="Restore this backup">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>

                                        <!-- Archive / Delete (With Modal Confirmation) -->
                                        <button type="button" onclick="promptArchiveBackup('{{ $file['name'] }}', '{{ $file['size'] }}', '{{ $file['created_at']->format('M d, Y h:i A') }}')" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-rose-500 hover:text-white text-slate-400 flex items-center justify-center text-xs transition-colors cursor-pointer" title="Archive / Delete backup">
                                            <i class="fas fa-box-archive"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400 text-sm">
                                    <i class="fas fa-database text-3xl mb-2 opacity-40 block"></i>
                                    No active backups created yet. Click "Backup Database Now" to generate a snapshot.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- TAB 2: RECOVERY ARCHIVE TABLE -->
                <div id="archivedBackupsTabContent" class="hidden overflow-x-auto">
                    <div class="p-3 bg-amber-500/10 border-b border-amber-500/20 text-amber-800 dark:text-amber-300 text-xs flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-info-circle text-amber-500"></i>
                            <span>Archived backups are kept in safe retention. You can <strong>recover</strong> them back to active backups anytime or <strong>restore</strong> the database directly.</span>
                        </div>
                    </div>

                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-dark-850 border-b border-slate-200 dark:border-slate-800">
                                <th class="p-4">Archived Backup</th>
                                <th class="p-4">File Size</th>
                                <th class="p-4">Date Archived</th>
                                <th class="p-4 text-center">Recovery Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($archivedFiles as $archived)
                            <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/40 transition-colors">
                                <td class="p-4">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fas fa-box-archive text-amber-500 text-lg"></i>
                                        <div>
                                            <span class="font-mono font-bold text-xs sm:text-sm text-slate-900 dark:text-white block">{{ $archived['name'] }}</span>
                                            <span class="text-[10px] text-amber-500 font-semibold uppercase tracking-wider">Archived Snapshot</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 font-mono text-xs text-slate-600 dark:text-slate-300">
                                    {{ $archived['size'] }}
                                </td>
                                <td class="p-4 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $archived['archived_at']->format('M d, Y h:i A') }}
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Recover to Active -->
                                        <button type="button" onclick="promptRecoverBackup('{{ $archived['name'] }}', '{{ $archived['size'] }}', '{{ $archived['archived_at']->format('M d, Y h:i A') }}')"
                                            class="px-2.5 py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500 text-emerald-600 hover:text-white font-bold text-xs flex items-center gap-1.5 transition-colors cursor-pointer" title="Recover to Active Backups">
                                            <i class="fas fa-arrow-rotate-left"></i>
                                            <span>Recover</span>
                                        </button>

                                        <!-- Direct Restore from Archive -->
                                        <button type="button" onclick="promptRestoreBackup('{{ $archived['name'] }}', '{{ $archived['size'] }}')"
                                            class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-dark-800 hover:bg-amber-500 hover:text-slate-900 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs transition-colors cursor-pointer" title="Direct Restore from this Archive">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>

                                        <!-- Permanent Purge -->
                                        <button type="button" onclick="promptPurgeBackup('{{ $archived['name'] }}', '{{ $archived['size'] }}')"
                                            class="w-8 h-8 rounded-lg bg-rose-500/15 hover:bg-rose-600 text-rose-500 hover:text-white flex items-center justify-center text-xs transition-colors cursor-pointer" title="Permanently Delete">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400 text-sm">
                                    <i class="fas fa-box-open text-3xl mb-2 opacity-40 block"></i>
                                    Recovery Archive is empty. Deleted backups will be preserved here and can be recovered anytime.
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

<!-- ========================================== -->
<!-- 1. ARCHIVE CONFIRMATION MODAL -->
<!-- ========================================== -->
<div id="archiveBackupModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 transform transition-all">
        <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-500 flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-box-archive"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-display font-black text-lg text-slate-900 dark:text-white leading-tight">Move to Recovery Archive?</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Safe deletion with full recovery retention</p>
            </div>
            <button type="button" onclick="closeArchiveBackupModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl -mr-1 -mt-1 cursor-pointer">
                &times;
            </button>
        </div>

        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 space-y-2 text-xs font-mono">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 dark:text-slate-400 font-sans">File Name:</span>
                <span id="archiveModalFilename" class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]">-</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500 dark:text-slate-400 font-sans">File Size:</span>
                <span id="archiveModalSize" class="font-bold text-emerald-500">-</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500 dark:text-slate-400 font-sans">Created Date:</span>
                <span id="archiveModalDate" class="text-slate-700 dark:text-slate-300">-</span>
            </div>
        </div>

        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
            This backup will be removed from your active list and stored in the <strong>Recovery Archive</strong>. You can recover it or restore your database from it whenever you need.
        </p>

        <form id="archiveBackupForm" method="POST" action="{{ route('backup.delete') }}">
            @csrf
            <input type="hidden" name="filename" id="archiveFormFilenameInput">
            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="closeArchiveBackupModal()" class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-900 font-bold text-xs sm:text-sm shadow-md shadow-amber-500/25 flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <i class="fas fa-box-archive"></i>
                    <span>Move to Archive</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. RECOVER CONFIRMATION MODAL -->
<!-- ========================================== -->
<div id="recoverBackupModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4 transform transition-all">
        <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-500 flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-arrow-rotate-left"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-display font-black text-lg text-slate-900 dark:text-white leading-tight">Recover Archived Backup?</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Restore file back to active backups</p>
            </div>
            <button type="button" onclick="closeRecoverBackupModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl -mr-1 -mt-1 cursor-pointer">
                &times;
            </button>
        </div>

        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 space-y-2 text-xs font-mono">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 dark:text-slate-400 font-sans">File Name:</span>
                <span id="recoverModalFilename" class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]">-</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500 dark:text-slate-400 font-sans">File Size:</span>
                <span id="recoverModalSize" class="font-bold text-emerald-500">-</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500 dark:text-slate-400 font-sans">Archived Date:</span>
                <span id="recoverModalDate" class="text-slate-700 dark:text-slate-300">-</span>
            </div>
        </div>

        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
            This will move the backup back into your <strong>Active Backups</strong> list, making it immediately available for downloads and standard restores.
        </p>

        <form id="recoverBackupForm" method="POST" action="{{ route('backup.recover') }}">
            @csrf
            <input type="hidden" name="filename" id="recoverFormFilenameInput">
            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="closeRecoverBackupModal()" class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-600/25 flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <i class="fas fa-arrow-rotate-left"></i>
                    <span>Yes, Recover Backup</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. PERMANENT PURGE VALIDATION MODAL -->
<!-- ========================================== -->
<div id="purgeBackupModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-rose-500/30 shadow-2xl space-y-4 transform transition-all">
        <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-500 flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-display font-black text-lg text-slate-900 dark:text-white leading-tight">Permanently Delete Backup?</h3>
                <p class="text-xs text-rose-500 font-semibold mt-1">Irreversible destructive action</p>
            </div>
            <button type="button" onclick="closePurgeBackupModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl -mr-1 -mt-1 cursor-pointer">
                &times;
            </button>
        </div>

        <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 space-y-1 text-xs text-rose-700 dark:text-rose-300">
            <strong class="font-bold block">⚠️ Danger: No Recovery After This Point</strong>
            <p class="text-[11px] leading-relaxed">This backup file will be completely wiped from disk storage. It cannot be recovered from the archive once deleted.</p>
        </div>

        <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 space-y-1 text-xs font-mono">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 font-sans">File:</span>
                <span id="purgeModalFilename" class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]">-</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500 font-sans">Size:</span>
                <span id="purgeModalSize" class="text-slate-600 dark:text-slate-300">-</span>
            </div>
        </div>

        <!-- Validation Checkbox -->
        <label class="flex items-start gap-2.5 cursor-pointer p-2.5 rounded-xl bg-slate-100 dark:bg-dark-800 border border-slate-300 dark:border-slate-700 select-none">
            <input type="checkbox" id="purgeConfirmCheckbox" onchange="togglePurgeButton()" class="mt-0.5 w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 dark:border-slate-600">
            <span class="text-xs text-slate-700 dark:text-slate-300 font-semibold">
                I understand this backup cannot be recovered and I want to permanently destroy this file.
            </span>
        </label>

        <form id="purgeBackupForm" method="POST" action="{{ route('backup.purge') }}">
            @csrf
            <input type="hidden" name="filename" id="purgeFormFilenameInput">
            <div class="flex items-center gap-3 pt-1">
                <button type="button" onclick="closePurgeBackupModal()" class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="purgeSubmitBtn" disabled
                    class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs sm:text-sm shadow-md shadow-rose-600/25 flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <i class="fas fa-trash-can"></i>
                    <span>Permanently Purge</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 4. DATABASE RESTORE CONFIRMATION MODAL -->
<!-- ========================================== -->
<div id="restoreBackupModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card rounded-2xl max-w-sm sm:max-w-md w-full p-6 border border-amber-500/30 shadow-2xl space-y-4 transform transition-all">
        <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-500 flex items-center justify-center text-xl flex-shrink-0">
                <i class="fas fa-rotate-left"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-display font-black text-lg text-slate-900 dark:text-white leading-tight">Restore Database Snapshot?</h3>
                <p class="text-xs text-amber-500 font-semibold mt-1">Live database overwrite confirmation</p>
            </div>
            <button type="button" onclick="closeRestoreBackupModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl -mr-1 -mt-1 cursor-pointer">
                &times;
            </button>
        </div>

        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-800 dark:text-amber-200 space-y-1">
            <strong class="font-bold flex items-center gap-1.5">
                <i class="fas fa-triangle-exclamation"></i>
                Database Overwrite Warning
            </strong>
            <p class="text-[11px] leading-relaxed">Restoring this backup will replace current database tables and transactions with the records contained in this backup snapshot.</p>
        </div>

        <div class="p-3 rounded-xl bg-slate-50 dark:bg-dark-900/80 border border-slate-200 dark:border-slate-800 space-y-1 text-xs font-mono">
            <div class="flex items-center justify-between">
                <span class="text-slate-500 font-sans">Backup Target:</span>
                <span id="restoreModalFilename" class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]">-</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-500 font-sans">Size:</span>
                <span id="restoreModalSize" class="text-slate-600 dark:text-slate-300">-</span>
            </div>
        </div>

        <form id="restoreExistingBackupForm" method="POST" action="{{ route('backup.restore') }}">
            @csrf
            <input type="hidden" name="existing_file" id="restoreFormFilenameInput">
            <div class="flex items-center gap-3 pt-2">
                <button type="button" onclick="closeRestoreBackupModal()" class="flex-1 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 hover:bg-slate-300 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-slate-900 font-black text-xs sm:text-sm shadow-md shadow-amber-500/25 flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <i class="fas fa-rotate-left"></i>
                    <span>Yes, Overwrite &amp; Restore</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchBackupTab(tab) {
        const activeBtn = document.getElementById('tabActiveBtn');
        const archiveBtn = document.getElementById('tabArchiveBtn');
        const activeContent = document.getElementById('activeBackupsTabContent');
        const archiveContent = document.getElementById('archivedBackupsTabContent');

        if (tab === 'active') {
            activeBtn.className = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all cursor-pointer bg-red-600 text-white shadow-sm shadow-red-600/20';
            archiveBtn.className = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all cursor-pointer bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700';
            activeContent.classList.remove('hidden');
            archiveContent.classList.add('hidden');
        } else {
            archiveBtn.className = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all cursor-pointer bg-amber-600 text-white shadow-sm shadow-amber-600/20';
            activeBtn.className = 'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all cursor-pointer bg-slate-200 dark:bg-dark-800 text-slate-700 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-dark-700';
            activeContent.classList.add('hidden');
            archiveContent.classList.remove('hidden');
        }
    }

    // 1. Archive Modal
    function promptArchiveBackup(filename, size, date) {
        document.getElementById('archiveModalFilename').innerText = filename;
        document.getElementById('archiveModalSize').innerText = size;
        document.getElementById('archiveModalDate').innerText = date;
        document.getElementById('archiveFormFilenameInput').value = filename;

        const modal = document.getElementById('archiveBackupModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeArchiveBackupModal() {
        const modal = document.getElementById('archiveBackupModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // 2. Recover Modal
    function promptRecoverBackup(filename, size, date) {
        document.getElementById('recoverModalFilename').innerText = filename;
        document.getElementById('recoverModalSize').innerText = size;
        document.getElementById('recoverModalDate').innerText = date;
        document.getElementById('recoverFormFilenameInput').value = filename;

        const modal = document.getElementById('recoverBackupModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRecoverBackupModal() {
        const modal = document.getElementById('recoverBackupModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // 3. Purge Modal
    function promptPurgeBackup(filename, size) {
        document.getElementById('purgeModalFilename').innerText = filename;
        document.getElementById('purgeModalSize').innerText = size;
        document.getElementById('purgeFormFilenameInput').value = filename;

        const checkbox = document.getElementById('purgeConfirmCheckbox');
        checkbox.checked = false;
        togglePurgeButton();

        const modal = document.getElementById('purgeBackupModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function togglePurgeButton() {
        const checkbox = document.getElementById('purgeConfirmCheckbox');
        const submitBtn = document.getElementById('purgeSubmitBtn');
        submitBtn.disabled = !checkbox.checked;
    }

    function closePurgeBackupModal() {
        const modal = document.getElementById('purgeBackupModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // 4. Restore Modal
    function promptRestoreBackup(filename, size) {
        document.getElementById('restoreModalFilename').innerText = filename;
        document.getElementById('restoreModalSize').innerText = size;
        document.getElementById('restoreFormFilenameInput').value = filename;

        const modal = document.getElementById('restoreBackupModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRestoreBackupModal() {
        const modal = document.getElementById('restoreBackupModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeArchiveBackupModal();
            closeRecoverBackupModal();
            closePurgeBackupModal();
            closeRestoreBackupModal();
        }
    });
</script>
@endpush