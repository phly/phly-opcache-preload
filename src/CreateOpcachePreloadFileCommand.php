<?php

declare(strict_types=1);

namespace Phly\OpcachePreload;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateOpcachePreloadFileCommand extends Command
{
    public const DEFAULT_FILENAME = 'preload.php';

    private const HELP = <<< 'END'
Generate an opcache preload definition file for your project. By default,
it generates the file "preload.php" in the root of your project; you can
override this with the --filename option.

The command will try and auto-determine your project type, in order to determine
which vendor classes to preload; you can override this by specifying a type with
the --project-type option (using one of the values "laminas",
"laminas-api-tools", or "mezzio"), or skip vendor directories during initial
creation using --no-vendors.
END;

    private const TEMPLATE = <<< 'END'
%s
// Initialize and configure Preloader. Use one or more of:
// ->paths(...string $paths) to specify paths to look under
// ->ignorePaths(...string $paths) to specify paths or files to ignore
// ->ignoreClasses(...string $classes) to specify classes to never preload
$preloader = new Preloader(%s);
$preloader->paths(
%s
);

// Preload!
$preloader->load();

END;

    private const TYPE_API_TOOLS = 'laminas-api-tools';
    private const TYPE_LAMINAS   = 'laminas';
    private const TYPE_MEZZIO    = 'mezzio';
    private const TYPE_VALID     = [
        self::TYPE_API_TOOLS,
        self::TYPE_LAMINAS,
        self::TYPE_MEZZIO,
    ];

    protected function configure(): void
    {
        $this->setDescription('Generate an opcache preload definition file for your project.');
        $this->setHelp(self::HELP);

        $this->addOption(
            'filename',
            'f',
            InputOption::VALUE_REQUIRED,
            'Alternate filename to use for the opcache preload file.',
            self::DEFAULT_FILENAME
        );

        $this->addOption(
            'project-type',
            'p',
            InputOption::VALUE_REQUIRED,
            sprintf('Project type; one of: %s', implode(', ', self::TYPE_VALID))
        );

        $this->addOption(
            '--no-vendors',
            null,
            InputOption::VALUE_NONE,
            'Disable adding vendor sources to the configured paths during generation'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $type = $input->getOption('project-type');
        if (! in_array($type, array_merge([null], self::TYPE_VALID), true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid --project-type provided; must be one of [%s]',
                implode(', ', self::TYPE_VALID)
            ));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename    = $input->getOption('filename');
        $projectRoot = $this->discoverProjectRoot($filename, $output);
        if (null === $projectRoot) {
            return 1;
        }

        $paths = $this->createPaths($this->discoverProjectType($input), $input->getOption('no-vendors'));

        $contents = sprintf(
            self::TEMPLATE,
            file_get_contents(__DIR__ . '/Preloader.php'),
            $projectRoot,
            $paths
        );

        file_put_contents($filename, $contents);

        $output->writeln(sprintf('<info>Created opcache preload file "%s"</info>', $filename));
        $output->writeln('<info>Execute the following line to your php.ini to enable:</info>');
        $output->writeln(sprintf('    opcache.preload = %s', realpath($filename)));
        $output->writeln('You can repeat the above instructions using the opcache:preload-ini command');

        return 0;
    }

    private function discoverProjectRoot(string $filename, OutputInterface $output): ?string
    {
        if (
            $filename === self::DEFAULT_FILENAME
            || '.' === dirname($filename)
        ) {
            return '';
        }

        $currentDir = realpath(getcwd());
        $filePath   = realpath(dirname($filename));
        if (0 !== strpos($currentDir, $filePath)) {
            $output->writeln('<error>Specified --filename is not in the current working directory.</error>');
            $output->writeln('This tool can only generate a preload file for the current project.');
            return null;
        }

        return sprintf("__DIR__ . '%s/%s'", substr($filePath, strlen($currentDir)), basename($filename));
    }

    private function discoverProjectType(InputInterface $input): ?string
    {
        $type = $input->getOption('project-type');
        if (null !== $type) {
            return $type;
        }

        $composerFile = realpath(getcwd()) . '/composer.json';
        if (! file_exists($composerFile)) {
            return null;
        }

        $json = file_get_contents($composerFile);
        $composer = json_decode($json, true);
        if (! isset($composer['require'])) {
            return null;
        }

        foreach (array_keys($composer['require']) as $package) {
            if (0 === strpos($package, 'mezzio/')) {
                return self::TYPE_MEZZIO;
            }

            if (0 === strpos($package, 'laminas-api-tools/')) {
                return self::TYPE_API_TOOLS;
            }

            if (0 === strpos($package, 'laminas/laminas-mvc')) {
                // Only return TYPE_LAMINAS if no other types match
                $type = self::TYPE_LAMINAS;
            }
        }

        return $type;
    }

    private function createPaths(?string $projectType, ?bool $noVendors): string
    {
        $paths = [];
        switch ($projectType) {
            case self::TYPE_API_TOOLS:
                if (! $noVendors) {
                    $paths[] = 'vendor/laminas-api-tools/';
                }
                // fall-through

            case self::TYPE_LAMINAS:
                $paths = [
                    ...$paths,
                    'config/',
                    'module/',
                    'public/index.php',
                ];
                if (! $noVendors) {
                    $paths[] = 'vendor/laminas/';
                }
                break;

            case self::TYPE_MEZZIO:
                $paths = [
                    'config/',
                    'src/',
                    'public/index.php',
                ];
                if (! $noVendors) {
                    $paths[] = 'vendor/laminas/';
                    $paths[] = 'vendor/mezzio/';
                }
                break;

            default:
                break;
        }

        sort($paths, SORT_NATURAL);
        $paths = array_map(function ($path) {
            return sprintf('    \'%s\',', $path);
        }, $paths);

        return implode("\n", $paths);
    }
}
