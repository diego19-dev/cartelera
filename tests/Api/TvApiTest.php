<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;

class TvApiTest extends TestCase {

    public function test_tv_endpoint_returns_scenarios(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/tv/api/tv';
        ob_start();
        require dirname(__DIR__, 2) . '/public/api/index.php';
        $out = ob_get_clean();
        $data = json_decode($out, true);
        $this->assertTrue($data['ok']);
        $this->assertArrayHasKey('scenarios', $data);
        $this->assertArrayHasKey('welcome', $data['scenarios']);
        $this->assertArrayHasKey('academic', $data['scenarios']);
        $this->assertArrayHasKey('gallery', $data['scenarios']);
        $this->assertSame('bienvenida', $data['scenarios']['welcome'][0]['layout'] ?? null);
        $this->assertContains('welcome', $data['config']['scenario_order'] ?? []);
        $this->assertIsArray($data['scenarios']['gallery']);
        $welcome = $data['scenarios']['welcome'][0];
        $this->assertArrayHasKey('saludo', $welcome);
        $this->assertArrayHasKey('titulo', $welcome);
        $this->assertArrayHasKey('bg_color', $welcome);
        $this->assertArrayHasKey('accent_color', $welcome);
        foreach ([
            'saludo_color',
            'titulo_color',
            'centro_color',
            'regional_color',
            'lema_color',
            'hashtag_color',
            'pie_color',
            'deco_color',
        ] as $colorKey) {
            $this->assertArrayHasKey($colorKey, $welcome);
            $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $welcome[$colorKey]);
        }
        $this->assertSame('El conocimiento es de todos', $welcome['saludo']);
    }
}
