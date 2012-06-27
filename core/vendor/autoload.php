<?php

// autoload.php generated by Composer
if (!class_exists('Composer\\Autoload\\ClassLoader', false)) {
    require __DIR__ . '/composer' . '/ClassLoader.php';
}

return call_user_func(function() {
    $loader = new \Composer\Autoload\ClassLoader();
    $composerDir = __DIR__ . '/composer';

    $map = require $composerDir . '/autoload_namespaces.php';
    foreach ($map as $namespace => $path) {
        $loader->add($namespace, $path);
    }

    $classMap = require $composerDir . '/autoload_classmap.php';
    if ($classMap) {
        $loader->addClassMap($classMap);
    }

    $loader->register();

    return $loader;
});
