<?php

declare(strict_types=1);

namespace Tests\Qossmic\Deptrac\Supportive\Console;

use PHPUnit\Framework\TestCase;
use Qossmic\Deptrac\Supportive\Console\Env;

class EnvTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('TEST=test');
        putenv('FOO=true');
        parent::setUp();
    }

    public function testEnvPresent(): void
    {
        $env = new Env();
        self::assertSame('test', $env->get('TEST'));
        self::assertSame('true', $env->get('FOO'));
    }

    public function testEnvMissing(): void
    {
        $env = new Env();
        self::assertSame(false, $env->get('BAR'));
    }
}
