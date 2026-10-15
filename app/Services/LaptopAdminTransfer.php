<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class LaptopAdminTransfer
{
    public function path(): string
    {
        return storage_path('app/laptop-setup/admin-account.json');
    }

    public function export(?string $username = null): void
    {
        $query = User::whereIn(DB::raw('LOWER(role)'), ['admin', 'owner'])->where('is_active', true);
        if ($username !== null) $query->where('username', $username);
        $admins = $query->get();
        if ($admins->count() !== 1) {
            throw new InvalidArgumentException('Select exactly one active administrator with --username. No account was exported.');
        }
        $admin = $admins->sole();
        $document = ['format' => 'paolo-laptop-admin', 'version' => 1, 'account' => [
            'name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email,
            'password_hash' => $admin->getRawOriginal('password'),
        ]];
        $this->validate($document);
        File::ensureDirectoryExists(dirname($this->path()));
        File::replace($this->path(), json_encode($document, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    public function read(): array
    {
        if (File::size($this->path()) > 16384) throw new InvalidArgumentException('The administrator transfer file is invalid. Export it again.');
        return $this->validate(json_decode(File::get($this->path()), true));
    }

    private function validate(mixed $document): array
    {
        if (! is_array($document) || ($document['format'] ?? null) !== 'paolo-laptop-admin' || ($document['version'] ?? null) !== 1) {
            throw new InvalidArgumentException('The administrator transfer file is invalid. Export it again.');
        }
        $account = $document['account'] ?? [];
        if (! is_array($account) || Validator::make($account, [
            'name' => 'required|string|max:255', 'username' => 'required|string|max:255',
            'email' => 'required|email|max:255', 'password_hash' => 'required|string|max:255',
        ])->fails() || ! in_array(password_get_info($account['password_hash'] ?? '')['algoName'], ['bcrypt', 'argon2i', 'argon2id'], true)) {
            throw new InvalidArgumentException('The administrator transfer file is invalid. Export it again.');
        }
        return array_intersect_key($account, array_flip(['name', 'username', 'email', 'password_hash']));
    }

    public function hasBusinessRecords(): bool
    {
        foreach (['tbl_category', 'tbl_product', 'tbl_stock_in', 'tbl_sale', 'tbl_sold_item'] as $table) {
            if (DB::table($table)->exists()) return true;
        }
        return false;
    }

    public function wasInitialized(): bool
    {
        return File::exists(storage_path('app/laptop-setup/initialized.json'));
    }

    public function finish(): void
    {
        $directory = storage_path('app/laptop-setup');
        File::ensureDirectoryExists($directory);
        File::replace($directory.'/initialized.json', json_encode(['version' => 1, 'initialized_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR));
        // The portable ZIP still contains the transfer; an installed copy no longer needs it.
        if (File::exists($this->path())) File::delete($this->path());
    }
}
