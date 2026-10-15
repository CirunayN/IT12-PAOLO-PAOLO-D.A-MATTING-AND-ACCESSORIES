<?php

namespace App\Observers;

use App\Services\FilterCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

class RefreshFilterCache implements ShouldHandleEventsAfterCommit
{
    public function saved(Model $model): void { app(FilterCache::class)->invalidate(); }
    public function deleted(Model $model): void { app(FilterCache::class)->invalidate(); }
}
