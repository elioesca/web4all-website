<?php

use PHPUnit\Framework\TestCase;

class LegalControllerTest extends TestCase
{
    public function testLegalPageReturns200(): void
    {
        $ch = curl_init('http://web4all.local/mentions-legales');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode);
    }

    public function testLegalPageContainsMentionsLegales(): void
    {
        $ch = curl_init('http://web4all.local/mentions-legales');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $this->assertStringContainsString('Mentions légales', $response);
    }

    public function testLegalPageContainsCookiesSection(): void
    {
        $ch = curl_init('http://web4all.local/mentions-legales');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $this->assertStringContainsString('Cookies', $response);
    }

    public function testLegalPageContainsConfidentialiteSection(): void
    {
        $ch = curl_init('http://web4all.local/mentions-legales');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $this->assertStringContainsString('Politique de confidentialité', $response);
    }
}