<?php

namespace Agentic\Tests\Unit;

use Agentic\Console\Support\EnvFileWriter;
use PHPUnit\Framework\TestCase;

final class EnvFileWriterTest extends TestCase
{
    public function test_merge_updates_existing_keys_and_appends_new(): void
    {
        $path = sys_get_temp_dir().'/agentic-env-'.uniqid('', true).'.env';
        file_put_contents($path, "APP_NAME=Laravel\nAGENTIC_MODE=production\n");

        $writer = new EnvFileWriter($path);
        $appended = $writer->merge([
            'AGENTIC_MODE' => 'local',
            'AGENTIC_AI_PROVIDER' => 'ollama',
        ]);

        $this->assertSame(['AGENTIC_AI_PROVIDER'], $appended);
        $contents = file_get_contents($path);
        $this->assertStringContainsString('AGENTIC_MODE=local', $contents);
        $this->assertStringContainsString('AGENTIC_AI_PROVIDER=ollama', $contents);
        $this->assertStringNotContainsString('AGENTIC_MODE=production', $contents);

        @unlink($path);
    }
}
