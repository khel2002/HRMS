<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class PsgcController extends Controller
{
  private const BASE = 'https://psgc.cloud/api';
  private const TTL  = 60 * 60 * 24 * 7; // 7 days — PSGC data rarely changes

  // GET /admin/psgc/regions
  public function regions(): JsonResponse
  {
    return $this->proxy('psgc.regions', '/regions');
  }

  // GET /admin/psgc/regions/{code}/provinces
  public function provinces(string $code): JsonResponse
  {
    return $this->proxy("psgc.provinces.{$code}", "/regions/{$code}/provinces");
  }

  // GET /admin/psgc/provinces/{code}/cities
  public function cities(string $code): JsonResponse
  {
    return $this->proxy("psgc.cities.{$code}", "/provinces/{$code}/cities-municipalities");
  }

  // GET /admin/psgc/cities/{code}/barangays
  public function barangays(string $code): JsonResponse
  {
    return $this->proxy("psgc.barangays.{$code}", "/cities-municipalities/{$code}/barangays");
  }

  // ── Shared fetch + cache helper ───────────────────────────
  private function proxy(string $cacheKey, string $path): JsonResponse
  {
    try {
      $data = Cache::remember($cacheKey, self::TTL, function () use ($path) {
        return Http::timeout(10)
          ->get(self::BASE . $path)
          ->throw()
          ->json();
      });

      return response()->json($data);
    } catch (Throwable $e) {
      return response()->json(['error' => 'Address data unavailable. Please try again.'], 503);
    }
  }
}
