<?php

namespace App\Http\Controllers;

use App\Services\ExpiryAlerts;
use Illuminate\Http\JsonResponse;

class ExpiryNotificationController extends Controller
{
    public function __invoke(ExpiryAlerts $alerts): JsonResponse
    {
        return response()->json($alerts->summary())->header('Cache-Control', 'no-store, private');
    }
}
