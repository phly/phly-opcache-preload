<?php

$vendorDir = dirname(dirname(__FILE__));
$baseDir   = dirname($vendorDir);

return [
    'Api\ApiHandler'        => $baseDir . '/src/Api/ApiHandler.php',
    'Api\ConfigProvider'    => $baseDir . '/src/Api/ConfigProvider.php',
    'App\ConfigProvider'    => $baseDir . '/src/App/src/ConfigProvider.php',
    'App\HomePageHandler'   => $baseDir . '/src/App/src/HomePageHandler.php',
    'Laminas\FooBar\FooBar' => $vendorDir . '/laminas/laminas-foobar/src/FooBar.php',
];
