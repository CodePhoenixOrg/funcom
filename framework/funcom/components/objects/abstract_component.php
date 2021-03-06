<?php

namespace FunCom\Components;

use BadFunctionCallException;
use FunCom\Registry\CacheRegistry;
use FunCom\Registry\ClassRegistry;
use FunCom\Registry\UseRegistry;
use tidy;

abstract class AbstractComponent implements ComponentInterface
{
    protected $namespace;
    protected $function;
    protected $code;
    protected $parentHTML;

    public function getParentHTML(): ?string
    {
        return $this->parentHTML;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getFullCleasName(): string
    {
        return $this->namespace  . '\\' . $this->function;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function analyse(): void
    {
        $parser = new Parser($this);
        $parser->doUses();
        $parser->doUsesAs();
    }

    public function parse(): void
    {
        $parser = new Parser($this);
        
        $parser->doUncache();
        
        $parser->doScalars();
        $parser->useVariables();
        $parser->doComponents();
        $parser->doOpenComponents();
        $html = $parser->getHtml();

        $parser->doCache();

        $this->code = $html;
    }

    public function getFunctionDefinition(): ?array
    {
        $contents = $this->code;

        if ($contents === null) {
            return null;
        }

        $namespace = $this->grabKeywordName('namespace', $contents, ';');
        $functionName = $this->grabKeywordName('function', $contents, '(');

        return [$namespace, $functionName];
    }

    public function getClassDefinition(): ?array
    {
        $contents = $this->code;

        if ($contents === null) {
            return null;
        }

        $namespace = $this->grabKeywordName('namespace', $contents, ';');
        $className = $this->grabKeywordName('class', $contents, ' ');

        return [$namespace, $className];
    }

    public function grabKeywordName(string $keyword, string $classText, string $delimiter): string
    {
        $result = '';

        $start = strpos($classText, $keyword);
        if ($start > -1) {
            $start += \strlen($keyword) + 1;
            $end = strpos($classText, $delimiter, $start);
            $result = substr($classText, $start, $end - $start);
        }

        return $result;
    }


    
    public static function checkCache(string $componentName): bool 
    {   
        list($functionName, $cacheFilename, $isCached) = static::findComponent($componentName);

        return $isCached;
    }

    public static function findComponent(string $componentName): array
    {
        UseRegistry::uncache();
        $uses = UseRegistry::items();

        $functionName = isset($uses[$componentName]) ? $uses[$componentName] : null;
        if ($functionName === null) {
            throw new BadFunctionCallException('The component ' . $componentName . ' does not exist.');
        }

        CacheRegistry::uncache();
        $cache = CacheRegistry::items();
        $filename = isset($cache[$functionName]) ? $cache[$functionName] : null;
        $isCached = $filename !== null;

        if(!$isCached) {
            ClassRegistry::uncache();
            $classes = ClassRegistry::items();
            $filename = isset($classes[$functionName]) ? $classes[$functionName] : null;
        }

        return [$functionName, $filename, $isCached];
    }


    public static function importComponent(string $componentName): ?string
    {
        list($functionName, $cacheFilename, $isCached) = static::findComponent($componentName);

        include_once ($isCached ? CACHE_DIR : SRC_ROOT) . $cacheFilename;

        return $functionName;
    }

    public static function renderHTML(string $functionName, ?array $functionArgs = null): string
    {

        $functionName = self::importComponent($functionName);

         $html = '';
        if ($functionArgs === null) {
            ob_start();
            $fn = call_user_func($functionName);
            $fn();
            $html = ob_get_clean();
        }

        if ($functionArgs !== null) {

            $props = [];
            foreach ($functionArgs as $key => $value) {
                $props[$key] = urldecode($value);
            }

            $props = (object) $props;

            ob_start();
            $fn = call_user_func($functionName, $props);
            $fn();
            $html = ob_get_clean();
        }

        $fqFunctionName = explode('\\', $functionName);
        $function = array_pop($fqFunctionName);
        if ($function === 'App') {
            $html = self::format($html);
        }

        return $html;
    }

    public static function format(string $html): string
    {
        $config = [
            'indent'         => true,
            'output-xhtml'   => true,
            'wrap'           => 200
        ];

        $tidy = new tidy;
        $tidy->parseString($html, $config, 'utf8');
        $tidy->cleanRepair();

        return $tidy->value;
    }
}
