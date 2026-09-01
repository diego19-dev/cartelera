<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WelcomeSanitizeTest extends TestCase {

    public function test_sanitize_welcome_html_allows_bold(): void {
        $out = \DisplayConfig::sanitizeWelcomeHtml('Hola <b>mundo</b> <script>x</script>');
        $this->assertStringContainsString('<b>mundo</b>', $out);
        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    public function test_welcome_defaults_shape(): void {
        $d = \DisplayConfig::welcomeDefaults();
        $this->assertSame('El conocimiento es de todos', $d['saludo']);
        $this->assertSame(12, $d['duration']);
        $this->assertSame('#39a900', $d['accent_color']);
        $this->assertSame('#39a900', $d['saludo_color']);
        $this->assertSame('#39a900', $d['titulo_color']);
        $this->assertSame('#4d4d4d', $d['centro_color']);
        $this->assertSame('#6b6b6b', $d['regional_color']);
        $this->assertSame('#4d4d4d', $d['lema_color']);
        $this->assertSame('#39a900', $d['hashtag_color']);
        $this->assertSame('#6b6b6b', $d['pie_color']);
        $this->assertSame('#a6ce39', $d['deco_color']);
    }

    public function test_welcome_editable_keys_include_block_colors(): void {
        $keys = \DisplayConfig::welcomeEditableKeys();
        foreach ([
            'welcome_saludo_color',
            'welcome_titulo_color',
            'welcome_centro_color',
            'welcome_regional_color',
            'welcome_lema_color',
            'welcome_hashtag_color',
            'welcome_pie_color',
            'welcome_deco_color',
        ] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_get_welcome_returns_defaults(): void {
        $w = \DisplayConfig::getWelcome();
        $this->assertArrayHasKey('saludo', $w);
        $this->assertArrayHasKey('duration', $w);
        $this->assertGreaterThanOrEqual(3, $w['duration']);
    }
}
