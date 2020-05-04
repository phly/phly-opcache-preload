<?php

declare(strict_types=1);

namespace Phly\OpcachePreload;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function ltrim;
use function realpath;
use function rtrim;
use function sprintf;

use const DIRECTORY_SEPARATOR;

class GenerateIniDirectiveCommand extends Command
{
    private const HELP = <<<'END'
Generate the php.ini opcache.preload directive to add to your php.ini file.

Generally, this should be invoked as:

    laminas opcache:preload-ini >> path/to/php.ini

The opcache.preload directive requires an absolute path on the filesystem.
As such, you can provide a --path option to prefix to the --filename (which
should always be relative to the project root). If none is given, the realpath()
value of the --filename will be used.
END;

    protected function configure(): void
    {
        $this->setDescription('Generate the php.ini directive that will enable the opcache preload file');
        $this->setHelp(self::HELP);

        $this->addOption(
            'filename',
            'f',
            InputOption::VALUE_REQUIRED,
            'Alternate opcache preload filename to use',
            CreateOpcachePreloadFileCommand::DEFAULT_FILENAME
        );

        $this->addOption(
            'path',
            'p',
            InputOption::VALUE_REQUIRED,
            'Absolute path to prefix to opcache preload filename to use'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('opcache.preload=%s', $this->getPath($input)));
        return 0;
    }

    private function getPath(InputInterface $input): string
    {
        $filename = $input->getOption('filename');
        $path     = $input->getOption('path');
        if (null === $path) {
            return realpath($filename);
        }

        return sprintf('%s%s%s', rtrim($path, '/\\'), DIRECTORY_SEPARATOR, ltrim($filename, '/\\'));
    }
}
