<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_response_includes_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_probe_paths_return_not_found(): void
    {
        $this->get('/.env')->assertNotFound();
        $this->get('/wp-admin')->assertNotFound();
        $this->get('/phpmyadmin')->assertNotFound();
    }

    public function test_public_registration_is_blocked_when_disabled(): void
    {
        config(['security.allow_public_registration' => false]);

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hack@evil.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertNotFound();
    }

    public function test_contact_form_is_rate_limited(): void
    {
        $payload = [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'message' => 'Mensagem de teste com conteúdo suficiente.',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/contato', $payload)->assertRedirect();
        }

        $this->post('/contato', $payload)->assertStatus(429);
    }
}
