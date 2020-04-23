<?php

declare(strict_types=1);

namespace Phly\OpcachePreload;

class Module
{
    public function getConfig(): array
    {
        $provider = new ConfigProvider();
        return [
            'laminas-cli'     => $provider->getCliConfig(),
            'service_manager' => $provider->getDependencies(),
        ];
    }
}
