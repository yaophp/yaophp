<?php
namespace yaophp;

$pathRoot = \dirname(\getcwd());

return [
    'debug' => true,

    'path' => [
        'root' => $pathRoot,
        'app' => $pathRoot . DIRECTORY_SEPARATOR . 'app',
        'runtime' => $pathRoot . DIRECTORY_SEPARATOR . 'runtime',
    ],

    'redis' => [
        'host' => '127.0.0.1',
        'port' => 11211,
        'auth' => ''
    ],
    
    'session' => [
        'driver' => 'redis',
        'key' => ''
    ],

    'runtime' => [
        'driver' => 'file',
    ],

    'template' => [
        'suffix' => '.html',
        'path' => $pathRoot . DIRECTORY_SEPARATOR . 'app' .  DIRECTORY_SEPARATOR . 'view',
        'cache_type' => 'file',
        'cache_path' => $pathRoot . DIRECTORY_SEPARATOR . 'runtime' .  DIRECTORY_SEPARATOR . 'template',
    ],
];