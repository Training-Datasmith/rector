<?php

declare(strict_types=1);

namespace Rector\Tests\Console\Output;

use PHPUnit\Framework\TestCase;
use Rector\ChangesReporting\Output\GitHubOutputFormatter;
use Rector\ChangesReporting\Output\JsonOutputFormatter;
use Rector\Console\Output\OutputFormatterCollector;
use Rector\Exception\Configuration\InvalidConfigurationException;

final class OutputFormatterCollectorTest extends TestCase
{
    public function testGetByName(): void
    {
        $json = new JsonOutputFormatter();
        $github = new GitHubOutputFormatter();
        $collector = new OutputFormatterCollector([$json, $github]);
        $this->assertSame($json, $collector->getByName('json'));
        $this->assertSame($github, $collector->getByName('github'));
    }

    public function testUnknownNameListsKnown(): void
    {
        $collector = new OutputFormatterCollector([
            new JsonOutputFormatter(),
            new GitHubOutputFormatter(),
        ]);
        try {
            $collector->getByName('nope');
            $this->fail('Expected InvalidConfigurationException');
        } catch (InvalidConfigurationException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('json', $message);
            $this->assertStringContainsString('github', $message);
        }
    }
}
