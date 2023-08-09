<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0241e14e75622049bee129eef223f897
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'Nahid\\NfrSearchable\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Nahid\\NfrSearchable\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0241e14e75622049bee129eef223f897::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0241e14e75622049bee129eef223f897::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit0241e14e75622049bee129eef223f897::$classMap;

        }, null, ClassLoader::class);
    }
}
