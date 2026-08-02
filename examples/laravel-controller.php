<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Refatbd\GameAccountLookup\GameAccountLookup;

final class PlayerLookupController
{
    public function __invoke(Request $request, GameAccountLookup $lookup): JsonResponse
    {
        $validated = $request->validate([
            'game' => ['required', 'string', 'max:80'],
            'player_id' => ['required', 'string', 'max:120'],
            'zone_id' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $lookup->check(
            $validated['game'],
            $validated['player_id'],
            $validated['zone_id'] ?? null,
        );

        return response()->json($result, $result->ok ? 200 : 422);
    }
}
