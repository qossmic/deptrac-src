<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Supportive\OutputFormatter;

use Deptrac\Deptrac\Supportive\OutputFormatter\Configuration\ConfigurationCodeclimate;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Deptrac\Deptrac\Supportive\OutputFormatter\Configuration\ConfigurationCodeclimate
 */
final class ConfigurationCodeclimateTest extends TestCase
{
    public function testFromArray(): void
    {
        $arr = [
            'severity' => [
                'failure' => 'blocker',
                'skipped' => 'critical',
                'uncovered' => 'info',
            ],
        ];
        $config = ConfigurationCodeclimate::fromArray($arr);

        self::assertSame('blocker', $config->getSeverity('failure'));
        self::assertSame('critical', $config->getSeverity('skipped'));
        self::assertSame('info', $config->getSeverity('uncovered'));
    }
}
