<?php

declare(strict_types=1);

namespace Rector\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

final class SuiteManifestTest extends TestCase
{
    private const EXPECTED_FILES = [
        'ChangesReporting/Output/GitHubOutputFormatterTest.php',
        'ChangesReporting/Output/JsonOutputFormatterTest.php',
        'Config/RectorConfigTest.php',
        'Configuration/LevelRulesResolverTest.php',
        'Configuration/RectorConfigBuilderTest.php',
        'Configuration/SimpleParameterProviderTest.php',
        'Console/ExitCodeTest.php',
        'Console/Output/OutputFormatterCollectorTest.php',
        'Differ/DefaultDifferTest.php',
        'FileSystem/FileAndDirectoryFilterTest.php',
        'FileSystem/FilePathHelperTest.php',
        'FileSystem/JsonFileSystemTest.php',
        'Php/PhpVersionResolver/ComposerJsonPhpVersionResolverTest.php',
        'Skipper/FnmatcherTest.php',
        'Skipper/PathNormalizerTest.php',
        'Skipper/RealpathMatcherTest.php',
        'SuiteManifestTest.php',
        'Testing/FixtureSplitterTest.php',
        'Util/ArrayCheckerTest.php',
        'Util/ArrayParametersMergerTest.php',
        'Util/FileHasherTest.php',
        'Util/MemoryLimiterTest.php',
        'Util/NewLineSplitterTest.php',
        'Util/PhpVersionFactoryTest.php',
        'Util/RectorClassesSorterTest.php',
        'Util/Reflection/PrivatesAccessorTest.php',
        'Util/StringUtilsTest.php',
        'ValueObject/ConfigurationTest.php',
        'ValueObject/Error/SystemErrorTest.php',
        'ValueObject/PhpVersionTest.php',
        'ValueObject/ProcessResultTest.php',
        'ValueObject/Reporting/FileDiffTest.php',
    ];

    private const EXPECTED_TEST_COUNT = 110;

    public function testFileListAndTestCount(): void
    {
        $testsDir = __DIR__;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testsDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $files = [];
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if (substr_compare($name, 'Test.php', -strlen('Test.php')) !== 0) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($testsDir) + 1);
            $files[] = str_replace('\\', '/', $relative);
        }
        sort($files);
        $this->assertSame(self::EXPECTED_FILES, $files);

        $count = 0;
        foreach ($files as $relativePath) {
            $class = 'Rector\\Tests\\' . str_replace('/', '\\', substr($relativePath, 0, -4));
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getMethods() as $method) {
                if (strpos($method->getName(), 'test') === 0) {
                    ++$count;
                }
            }
        }
        $this->assertSame(self::EXPECTED_TEST_COUNT, $count);
    }
}
