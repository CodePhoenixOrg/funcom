<?php

namespace FunCom\Registry;

class ClassRegistry extends AbstractStaticRegistry
{
    protected static $instance = null;

    public static function getInstance(): StaticRegistryInterface
    {
        if (self::$instance === null) {
            self::$instance = new ClassRegistry();
        }

        return self::$instance;
    }
}
