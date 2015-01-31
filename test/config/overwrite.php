<?php
return [
    'dist-installer-params' => [
        'file' => __DIR__ . '/../fixture/overwrite.php',
    ],
    'confirmation' => true,
    'expected' => __DIR__ . '/../fixture/overwrite.php.expected',
    'old' => __DIR__ . '/../fixture/overwrite.php.old-expected',
];