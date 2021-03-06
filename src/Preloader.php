<?php

declare(strict_types=1);

namespace Phly\OpcachePreload;

use function array_flip;
use function array_merge;
use function closedir;
use function in_array;
use function is_dir;
use function opcache_compile_file;
use function opendir;
use function preg_match;
use function printf;
use function readdir;
use function realpath;
use function rtrim;
use function sprintf;
use function strpos;

use const PHP_EOL;

final class Preloader
{
    /** @var string[] */
    private array $ignoreClasses = [
        self::class,
    ];

    /** @var string[] */
    private array $ignorePaths = [];

    private int $count = 0;

    /** @var string[] */
    private array $paths = [];

    /** @var string[] */
    private array $classFileMap;

    public function __construct(?string $projectRoot = null, string ...$paths)
    {
        // We'll use composer's classmap to help us identify class files
        // for purposes of ignoring classes.
        $classMapFile = sprintf('%s/vendor/composer/autoload_classmap.php', $projectRoot ?: realpath(__DIR__));
        $classMap     = require $classMapFile;

        $this->classFileMap = array_flip($classMap);
        $this->paths        = $paths;
    }
    
    public function ignoreClasses(string ...$names): self
    {
        $this->ignoreClasses = array_merge($this->ignoreClasses, $names);
        return $this;
    }

    public function ignorePaths(string ...$paths): self
    {
        $this->ignorePaths = array_merge($this->ignorePaths, $paths);
        return $this;
    }
    
    public function paths(string ...$paths): self
    {
        $this->paths = array_merge($this->paths, $paths);
        return $this;
    }

    public function load(): void
    {
        $this->count = 0;

        // We'll loop over all registered paths and load them one by one
        foreach ($this->paths as $path) {
            $this->loadPath($path);
        }

        printf("[Preloader] Preloaded %d paths%s", $this->count, PHP_EOL);
    }

    private function loadPath(string $path): void
    {
        if (is_dir($path)) {
            $this->loadDir($path);
            return;
        }

        $this->loadFile($path);
    }

    private function loadDir(string $path): void
    {
        $path   = rtrim($path, '/\\');
        $handle = opendir($path);

        while ($file = readdir($handle)) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $this->loadPath(sprintf('%s/%s', $path, $file));
        }

        closedir($handle);
    }

    private function loadFile(string $path): void
    {
        if ($this->shouldIgnore($path)) {
            return;
        }

        // Load file
        opcache_compile_file($path);

        $this->count += 1;

        printf("[Preloader] Preloaded `%s`%s", $path, PHP_EOL);
    }

    private function shouldIgnore(string $path): bool
    {
        // Ignore non-PHP files
        if (! preg_match('#\.(?:ph(?:p|tml))$#', $path)) {
            return true;
        }

        // Ignore this class file?
        if ($this->shouldIgnoreClassFile($path)) {
            return true;
        }

        // Ignore this file in particular?
        if ($this->shouldIgnoreFile($path)) {
            return true;
        }

        return false;
    }

    private function shouldIgnoreClassFile(string $path): bool
    {
        $class = $this->classFileMap[$path] ?? null;

        if ($class === null) {
            return false;
        }

        foreach ($this->ignoreClasses as $ignore) {
            if (strpos($class, $ignore) === 0) {
                return true;
            }
        }

        return false;
    }

    private function shouldIgnoreFile(string $path): bool
    {
        if (in_array($path, $this->ignorePaths, true)) {
            return true;
        }

        foreach ($this->ignorePaths as $ignore) {
            if (strpos($path, $ignore) !== false) {
                return true;
            }
        }

        return false;
    }
}
