<?php

declare(strict_types=1);

namespace Phly\OpcachePreload;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'laminas-cli'  => $this->getCliConfig(),
        ];
    }

    public function getCliConfig(): array
    {
        return [
            'commands' => [
                'opcache:preload-generate' => CreateOpcachePreloadFileCommand::class,
                'opcache:preload-ini'      => GenerateIniDirectiveCommand::class,
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'invokables' => [
                CreateOpcachePreloadFileCommand::class => CreateOpcachePreloadFileCommand::class,
                GenerateIniDirectiveCommand::class     => GenerateIniDirectiveCommand::class,
            ],
        ];
    }
}
