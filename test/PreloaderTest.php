<?php

declare(strict_types=1);

namespace PhlyTest\OpcachePreload;

use Phly\OpcachePreload\Preloader;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_merge;
use function array_pop;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_dir;
use function opendir;
use function preg_match;
use function readdir;
use function rtrim;
use function sprintf;
use function trim;

use const PHP_EOL;

class PreloaderTest extends TestCase
{
    private Preloader $preloader;

    protected function setUp(): void
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

        $this->setOutputCallback(static function (string $output) use ($expectedPaths) {
            $lines = explode(PHP_EOL, trim($output));
            $last  = array_pop($lines);
            $count = count($lines);
            self::assertSame(count($expectedPaths), $count);
            self::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                self::assertMatchesRegularExpression('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $this->preloader->load();
    }

    public function testCanIgnoreSpecificFiles(): void
    {
        $expectedPaths = $this->getFileList(__DIR__ . '/fixture/');
        $this->preloader->paths(__DIR__ . '/fixture/');
        $this->preloader->ignorePaths(__DIR__ . '/fixture/bin/console.php');

        $this->setOutputCallback(static function (string $output) use ($expectedPaths) {
            self::assertDoesNotMatchRegularExpression('#Preloaded \`.*?/bin/console.php\`#', $output);

            $lines = explode(PHP_EOL, trim($output));
            $last  = array_pop($lines);
            $count = count($lines);
            self::assertSame(count($expectedPaths) - 1, $count);
            self::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                self::assertMatchesRegularExpression('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $this->preloader->load();
    }

    public function testCanIgnoreSpecificClasses(): void
    {
        $expectedPaths = $this->getFileList(__DIR__ . '/fixture/');
        $this->preloader->paths(__DIR__ . '/fixture/');
        $this->preloader->ignoreClasses('Api\ApiHandler');

        $this->setOutputCallback(static function (string $output) use ($expectedPaths) {
            self::assertDoesNotMatchRegularExpression('#Preloaded \`.*?/src/Api/ApiHandler.php\`#', $output);

            $lines = explode(PHP_EOL, trim($output));
            $last  = array_pop($lines);
            $count = count($lines);
            self::assertSame(count($expectedPaths) - 1, $count);
            self::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                self::assertMatchesRegularExpression('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $this->preloader->load();
    }

    public function testCanIgnoreDirectory(): void
    {
        $expectedPaths = array_filter(
            $this->getFileList(__DIR__ . '/fixture/'),
            static function (string $filename): bool {
                return ! preg_match('#fixture/src/#', $filename);
            }
        );
        $this->preloader->paths(__DIR__ . '/fixture/');
        $this->preloader->ignorePaths(__DIR__ . '/fixture/src/');

        $this->setOutputCallback(static function (string $output) use ($expectedPaths) {
            self::assertDoesNotMatchRegularExpression('#Preloaded \`.*?/fixture/src#', $output);

            $lines = explode(PHP_EOL, trim($output));
            $last  = array_pop($lines);
            $count = count($lines);
            self::assertSame(count($expectedPaths), $count);
            self::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                self::assertMatchesRegularExpression('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
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

        $expectedPaths = array_filter($expectedPaths, static function (string $filename): bool {
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

        $this->setOutputCallback(static function (string $output) use ($expectedPaths) {
            $lines = explode(PHP_EOL, trim($output));
            $last  = array_pop($lines);
            $count = count($lines);
            self::assertSame(
                count($expectedPaths),
                $count,
                sprintf("Expected: %s\nReceived: %s", implode("\n", $expectedPaths), $output)
            );
            self::assertEquals(sprintf('[Preloader] Preloaded %d paths', $count), $last, $output);
            foreach ($lines as $line) {
                self::assertMatchesRegularExpression('#^\[Preloader\] Preloaded \`.*?\.(php|phtml)\`$#', $line);
            }
        });

        $preloader->load();
    }
}
