<?php

declare(strict_types=1);

namespace Rector\Tests\Util\Reflection;

use PHPUnit\Framework\TestCase;
use Rector\Exception\Reflection\MissingPrivatePropertyException;
use Rector\Util\Reflection\PrivatesAccessor;

class PrivatesAccessorParentFixture
{
    private string $secret = 'from-parent';
}

final class PrivatesAccessorChildFixture extends PrivatesAccessorParentFixture
{
}

final class PrivatesAccessorTest extends TestCase
{
    private PrivatesAccessor $accessor;

    protected function setUp(): void
    {
        $this->accessor = new PrivatesAccessor();
    }

    public function testReadsOwnPrivate(): void
    {
        $obj = new class () {
            private string $secret = 'own';
        };
        $this->assertSame('own', $this->accessor->getPrivateProperty($obj, 'secret'));
    }

    public function testReadsParentPrivate(): void
    {
        $obj = new PrivatesAccessorChildFixture();
        $this->assertSame('from-parent', $this->accessor->getPrivateProperty($obj, 'secret'));
    }

    public function testMissingPropertyOnSubclassThrowsDedicatedException(): void
    {
        $obj = new PrivatesAccessorChildFixture();
        $this->expectException(MissingPrivatePropertyException::class);
        $this->accessor->getPrivateProperty($obj, 'nope');
    }

    public function testSetAndClosure(): void
    {
        $obj = new class () {
            private int $counter = 1;
        };
        $this->accessor->setPrivateProperty($obj, 'counter', 1);
        $this->accessor->propertyClosure($obj, 'counter', static function ($value) {
            return $value + 1;
        });
        $this->assertSame(2, $this->accessor->getPrivateProperty($obj, 'counter'));
    }

    public function testCallPrivateMethod(): void
    {
        $obj = new class () {
            private function add(int $a, int $b): int
            {
                return $a + $b;
            }
        };
        $this->assertSame(5, $this->accessor->callPrivateMethod($obj, 'add', [2, 3]));
    }
}
