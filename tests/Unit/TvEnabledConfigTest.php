<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TvEnabledConfigTest extends TestCase {

    public function test_get_all_for_tv_includes_enabled_switch(): void {
        $config = \DisplayConfig::getAllForTv();
        $this->assertArrayHasKey('tv_enabled', $config);
        $this->assertArrayHasKey('tv_off_message', $config);
        $this->assertContains((string) $config['tv_enabled'], ['0', '1'], 'tv_enabled must be "0" or "1"');
        $this->assertIsString($config['tv_off_message']);
        $this->assertNotSame('', $config['tv_off_message']);
    }

    public function test_get_all_for_tv_includes_tv_color_keys(): void {
        $config = \DisplayConfig::getAllForTv();
        foreach (['tv_color_header', 'tv_color_badge', 'tv_color_icon', 'tv_color_live', 'tv_color_progress'] as $key) {
            $this->assertArrayHasKey($key, $config);
            $this->assertIsString($config[$key]);
        }
    }

    /**
     * El heartbeat debe exponer tv_enabled: last_update solo tiene resolución
     * de segundos y un apagado/encendido rápido no dispara refetch en la TV.
     */
    public function test_tv_ping_includes_enabled_switch(): void {
        $url = rtrim((string) (getenv('APP_URL') ?: 'http://127.0.0.1/tv'), '/') . '/api/tv/ping';
        $raw = @file_get_contents($url);
        $this->assertNotFalse($raw, 'No se pudo llamar a /api/tv/ping');
        $data = json_decode($raw, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['ok'] ?? false);
        $this->assertArrayHasKey('tv_enabled', $data);
        $this->assertContains((string) $data['tv_enabled'], ['0', '1']);
    }
}
