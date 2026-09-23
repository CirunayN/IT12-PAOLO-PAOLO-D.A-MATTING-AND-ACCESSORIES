<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Normalize user role and allowed roles
        $userRole = strtolower(trim($user->role ?? ''));
        $allowedRoles = array_map(fn($r) => strtolower(trim($r)), $roles);

        // Map legacy operational roles (Packer, Accessory Installer, Production Worker) to Cashier / Employee Level
        if (in_array($userRole, ['packer', 'accessory installer', 'accessory_installer', 'production worker', 'production_worker'])) {
            $effectiveRoles = [$userRole, 'cashier', 'employee'];
        } else {
            $effectiveRoles = [$userRole];
        }

        // Admin has super-admin access across routes
        if ($userRole === 'admin' || $userRole === 'owner') {
            return $next($request);
        }

        // Check if any of the user's effective roles matches the allowed roles
        foreach ($effectiveRoles as $role) {
            if (in_array($role, $allowedRoles)) {
                return $next($request);
            }
        }

        // Forbidden access
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthorized: You do not have permission to access this resource.',
            ], 403);
        }

        abort(403, "Access Denied: Your account role ({$user->role}) is restricted from accessing this area. Please contact the Owner/Admin.");
    }
}
