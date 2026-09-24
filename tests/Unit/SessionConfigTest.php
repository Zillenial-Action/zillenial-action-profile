<?php

namespace Tests\Unit;

use Tests\TestCase;

class SessionConfigTest extends TestCase
{
    public function test_wildcard_session_domain_is_ignored(): void
    {
        $this->assertNull($this->sessionDomainFor('*'));
    }

    public function test_valid_session_domain_is_kept(): void
    {
        $this->assertSame('.zillenialaction.id', $this->sessionDomainFor('.zillenialaction.id'));
    }

    private function sessionDomainFor(string $value): ?string
    {
        $env = $_ENV['SESSION_DOMAIN'] ?? null;
        $server = $_SERVER['SESSION_DOMAIN'] ?? null;

        $_ENV['SESSION_DOMAIN'] = $_SERVER['SESSION_DOMAIN'] = $value;

        try {
            return (require config_path('session.php'))['domain'];
        } finally {
            $env === null ? $this->forget($_ENV) : $_ENV['SESSION_DOMAIN'] = $env;
            $server === null ? $this->forget($_SERVER) : $_SERVER['SESSION_DOMAIN'] = $server;
        }
    }

    private function forget(array &$superglobal): void
    {
        unset($superglobal['SESSION_DOMAIN']);
    }
}
