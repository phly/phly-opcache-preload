<?php

declare(strict_types=1);

namespace PhlyTest\OpcachePreload;

use Phly\OpcachePreload\Preloader;
use PHPUnit\Framework\TestCase;

class PreloaderTest extends TestCase
{
    private Preloader $preloader;

    public function setUp(): void
    {
        $this->preloader = new Preloader(__DIR__ . '/fixture/');
    }

    public function getFileList(string $path): array
    {
        $path   = rtrim($path, '/\\');
        $files  = [];
        $handle = opendir($path);

        while ($file = readdir($handle)) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $file = sprintf('%s/%s', $path, $file);
            if (is_dir($file)) {
                $files = [...$files, ...$this->getFileList($file)];
                continue;
            }

            if (preg_match('#\.ph(p|tml)$#', $file)) {
                $files[] = $file;
                continue;
            }
        }

        return $files;
    }

    public function testLoadsNothingIfNoPathsProvided(): void
    {
        $this->expectOutputString("[Preloader] Preloaded 0 paths" . PHP_EOL);
        $this->preloader->load();
    }

    public function testLoadsAllPhpFilesUnderPathByDefault(): void
    {
        $expectedPaths = $this->getFileList(__DIR__ . '/fixture/');
        $this->preloader->paths(__DIR__ . '/fixture/');

        $this->setOutputCallback(function (string $output) use ($expectedPaths) {
            $lines  = explode(PHP_EOL, trim($output));
            $last   = array_pop($lines);
            $count  = count($lines);
            TestCase::assertSame(count($expectedPaths), $count);
            TestCase::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                TestCase::assertRegExp('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $this->preloader->load();
    }

    public function testCanIgnoreSpecificFiles(): void
    {
        $expectedPaths = $this->getFileList(__DIR__ . '/fixture/');
        $this->preloader->paths(__DIR__ . '/fixture/');
        $this->preloader->ignorePaths(__DIR__ . '/fixture/bin/console.php');

        $this->setOutputCallback(function (string $output) use ($expectedPaths) {
            TestCase::assertNotRegExp('#Preloaded \`.*?/bin/console.php\`#', $output);

            $lines  = explode(PHP_EOL, trim($output));
            $last   = array_pop($lines);
            $count  = count($lines);
            TestCase::assertSame(count($expectedPaths) - 1, $count);
            TestCase::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                TestCase::assertRegExp('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $this->preloader->load();
    }

    public function testCanIgnoreSpecificClasses(): void
    {
        $expectedPaths = $this->getFileList(__DIR__ . '/fixture/');
        $this->preloader->paths(__DIR__ . '/fixture/');
        $this->preloader->ignoreClasses('Api\ApiHandler');

        $this->setOutputCallback(function (string $output) use ($expectedPaths) {
            TestCase::assertNotRegExp('#Preloaded \`.*?/src/Api/ApiHandler.php\`#', $output);

            $lines  = explode(PHP_EOL, trim($output));
            $last   = array_pop($lines);
            $count  = count($lines);
            TestCase::assertSame(count($expectedPaths) - 1, $count);
            TestCase::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                TestCase::assertRegExp('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $this->preloader->load();
    }

    public function testCanIgnoreDirectory(): void
    {
        $expectedPaths = array_filter(
            $this->getFileList(__DIR__ . '/fixture/'),
            function ($filename) {
                return ! preg_match('#fixture/src/#', $filename);
            }
        );
        $this->preloader->paths(__DIR__ . '/fixture/');
        $this->preloader->ignorePaths(__DIR__ . '/fixture/src/');

        $this->setOutputCallback(function (string $output) use ($expectedPaths) {
            TestCase::assertNotRegExp('#Preloaded \`.*?/fixture/src#', $output);

            $lines  = explode(PHP_EOL, trim($output));
            $last   = array_pop($lines);
            $count  = count($lines);
            TestCase::assertSame(count($expectedPaths), $count);
            TestCase::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                TestCase::assertRegExp('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $this->preloader->load();
    }

    public function testDemonstrateTypicalUsage(): void
    {
        $expectedPaths = array_merge(
            $this->getFileList(__DIR__ . '/fixture/src'),
            $this->getFileList(__DIR__ . '/fixture/config'),
            $this->getFileList(__DIR__ . '/fixture/vendor/laminas'),
            [__DIR__ . '/fixture/public/index.php'],
        );

        $expectedPaths = array_filter($expectedPaths, function ($filename) {
            static $ignore = [
                __DIR__ . '/fixture/config/autoload/local.php',
                __DIR__ . '/fixture/src/Api/ApiHandler.php',
                __DIR__ . '/fixture/vendor/laminas/laminas-foobar/src/FooBar.php',
            ];
            return ! in_array($filename, $ignore, true);
        });

        $preloader = new Preloader(
            __DIR__ . '/fixture/',
            __DIR__ . '/fixture/src',
            __DIR__ . '/fixture/config',
            __DIR__ . '/fixture/vendor/laminas',
            __DIR__ . '/fixture/public/index.php',
        );
        $preloader
            ->ignoreClasses(
                'Laminas\FooBar\FooBar',
                'Api\ApiHandler'
            )
            ->ignorePaths(
                'config/autoload/local.php',
            );

        $this->setOutputCallback(function (string $output) use ($expectedPaths) {
            $lines  = explode(PHP_EOL, trim($output));
            $last   = array_pop($lines);
            $count  = count($lines);
            TestCase::assertSame(count($expectedPaths), $count, sprintf("Expected: %s\nReceived: %s", implode("\n", $expectedPaths), $output));
            TestCase::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                TestCase::assertRegExp('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $preloader->load();
    }
}
