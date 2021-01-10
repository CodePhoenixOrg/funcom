<?php

namespace Ephel\Registry;

use FunCom\StaticElement;
use Ephel\Core\Registry;

/**
 * Description of registry
 *
 * @author david
 */

class ClassInfo extends StaticElement
{
    private $_type = '';
    private $_alias = '';
    private $_path = '';
    private $_namespace = '';
    private $_hasTemplate = false;
    private $_canRender = false;
    private $_isAutoloaded = false;
    private $_details = [];
    private $_isValid = false;

    public function __construct(array $info) 
    {
        $this->_type = key($info);
        $this->_details = isset($info[$this->_type]) ? $info[$this->_type] : [];

        if(count($this->_details) < 5) {
            throw new \Exception("The class info is incomplete");
        }

        $this->_alias = isset($this->_details["alias"]) ? $this->_details["alias"] : '';
        $this->_path = isset($this->_details["path"]) ? $this->_details["path"] : '';

        if(!isset($this->_details["path"])) {
            throw new \Exception("The path detail is missing");
        }

        $this->_namespace = isset($this->_details["namespace"]) ? $this->_details["namespace"] : '';

        if(!isset($this->_details["namespace"])) {
            throw new \Exception("The namespace detail is missing");
        }

        $this->_hasTemplate = isset($this->_details["hasTemplate"]) ? $this->_details["hasTemplate"] : false;

        if(!isset($this->_details["hasTemplate"])) {
            throw new \Exception("The hasTemplate detail is missing");
        }

        $this->_canRender = isset($this->_details["canRender"]) ? $this->_details["canRender"] : false;

        if(!isset($this->_details["canRender"])) {
            throw new \Exception("The canRender detail is missing");
        }

        $this->_isAutoloaded = isset($this->_details["isAutoloaded"]) ? $this->_details["isAutoloaded"] : false;

        if(!isset($this->_details["isAutoloaded"])) {
            throw new \Exception("The isAutoloaded detail is missing");
        }

        $this->_isValid = true;

    }

    public static function builder(array $info) : void 
    {
        $ci = ClassInfo::create($info);
    }

    public function getType(): string
    {
        return $this->_type;
    }
    
    public function getAlias(): string
    {
        return $this->_alias;
    }

    public function getPath(): string
    {
        return $this->_path;
    }

    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    public function hasTemplate(): bool
    {
        return $this->_hasTemplate;
    }

    public function canRender(): bool
    {
        return $this->_canRender;
    }

    public function isAutoloaded(): bool
    {
        return $this->_isAutoloaded;
    }

    public function isValid() : bool 
    {
        return $this->_isValid;
    }

    public function register() {
        Registry::registerClass($this);
    }
}
