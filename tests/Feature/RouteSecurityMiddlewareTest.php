<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteSecurityMiddlewareTest extends TestCase
{
    public function test_public_risk_routes_have_specific_throttles(): void
    {
        $this->assertRouteHasMiddleware('auth', 'throttle:login');
        $this->assertRouteHasMiddleware('api.voucher.validate', 'throttle:voucher');
        $this->assertRouteUriHasMiddleware('api/checkout', 'POST', 'throttle:checkout');
        $this->assertRouteUriHasMiddleware('api/transaksi/{invoice}', 'GET', 'throttle:transaction-status');
    }

    private function assertRouteHasMiddleware(string $name, string $middleware): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route {$name} tidak ditemukan.");
        $this->assertContains($middleware, $route->gatherMiddleware());
    }

    private function assertRouteUriHasMiddleware(string $uri, string $method, string $middleware): void
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                $this->assertContains($middleware, $route->gatherMiddleware());
                return;
            }
        }

        $this->fail("Route {$method} {$uri} tidak ditemukan.");
    }
}
