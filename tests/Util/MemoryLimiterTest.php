<?php

declare(strict_types=1);

namespace Rector\Tests\Util;

use PHPUnit\Framework\TestCase;
use Rector\Exception\Configuration\InvalidConfigurationException;
use Rector\Util\MemoryLimiter;
use Rector\ValueObject\Configuration;

final class MemoryLimiterTest extends TestCase
{
    private MemoryLimiter $limiter;

    private string $originalLimit;

    protected function setUp(): void
    {
        $this->limiter = new MemoryLimiter();
        $original = ini_get('memory_limit');
        $this->originalLimit = is_string($original) ? $original : '128M';
    }

    protected function tearDown(): void
    {
        ini_set('memory_limit', $this->originalLimit);
    }

    public function testRejectsInvalidAndDoesNotChangeIni(): void
    {
        $before = ini_get('memory_limit');
        foreach (['128MB', '1.5G', ''] as $bad) {
            try {
                $this->limiter->adjust(new Configuration(false, true, false, 'console', ['php'], [], true, null, null, false, $bad));
                $this->fail('Expected exception for ' . $bad);
            } catch (InvalidConfigurationException $e) {
            }
        }
        $this->assertSame($before, ini_get('memory_limit'));
    }

    public function testAcceptsMegabytesAndRestores(): void
    {
        $this->limiter->adjust(new Configuration(false, true, false, 'console', ['php'], [], true, null, null, false, '256M'));
        $this->assertSame('256M', ini_get('memory_limit'));
        ini_set('memory_limit', $this->originalLimit);
    }

    public function testNullLimitIsNoop(): void
    {
        $before = ini_get('memory_limit');
        $this->limiter->adjust(new Configuration());
        $this->assertSame($before, ini_get('memory_limit'));
    }
}
