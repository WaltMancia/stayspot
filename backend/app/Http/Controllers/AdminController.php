<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function resetDemo(Request $request): JsonResponse
    {
        $secret = $request->header('X-Reset-Secret');

        if ($secret !== config('app.reset_secret')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Orden correcto: primero las tablas hijas, luego las padres
            DB::table('reviews')->truncate();
            DB::table('reservations')->truncate();
            DB::table('spaces')->truncate();
            DB::table('personal_access_tokens')->truncate();
            DB::table('users')->truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            Artisan::call('db:seed', ['--force' => true]);

            return response()->json([
                'message'  => 'Demo data reset successfully',
                'reset_at' => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Reset failed: ' . $e->getMessage());
            return response()->json(['message' => 'Reset failed'], 500);
        }
    }
}
