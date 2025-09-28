<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/utils/helpers.php';

class HelpersTest extends TestCase
{
    public function testSendJsonReturnsProperStructure()
    {
        // Simula salida
        ob_start();
        send_json(['success' => true], 200);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    public function testHandleErrorReturnsErrorMessage()
    {
        ob_start();
        handle_error('Error de prueba', 400);
        $output = ob_get_clean();

        $this->assertStringContainsString('"error":"Error de prueba"', $output);
    }
}
