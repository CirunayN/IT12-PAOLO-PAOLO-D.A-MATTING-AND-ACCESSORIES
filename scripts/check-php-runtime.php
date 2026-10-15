<?php

if (PHP_VERSION_ID < 80300 || PHP_VERSION_ID >= 80600 || PHP_INT_SIZE !== 8) {
    fwrite(STDERR, "PHP 8.3, 8.4, or 8.5 (64-bit) is required.\n");
    exit(1);
}
foreach (['curl', 'fileinfo', 'gd', 'mbstring', 'openssl', 'zip', 'dom'] as $extension) {
    if (! extension_loaded($extension)) {
        fwrite(STDERR, 'Required PHP extension is missing: '.$extension."\n");
        exit(1);
    }
}
fwrite(STDOUT, "Private PHP runtime ready.\n");
