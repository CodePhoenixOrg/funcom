<?php

namespace FunCom\Components;

use FunCom\Registry\UseRegistry;

class Parser
{
    private $html;

    public function __construct(string $code)
    {
        $this->html = $code;
    }

    public function getHtml()
    {
        return $this->html;
    }

    public function doVariables(): bool
    {
        $result = '';

        $re = '/\{\{ ([a-z]*) \}\}/m';
        $su = '$\1';

        $this->html = preg_replace($re, $su, $this->html);

        $result = $this->html !== null;

        return $result;
    }

    public function doComponents(): bool
    {
        $result = '';

        $re = '/\<([A-Za-z0-9]*)([A-Za-z0-9 \{\}\(\)\>\'"=]*)((\s|[^\/\>].))?\/\>/m';

        preg_match_all($re, $this->html, $matches, PREG_SET_ORDER, 0);


        foreach ($matches as $match) {
            $component = $match[0];
            $componentName = $match[1];
            $componentArgs = isset($match[3]) ? $match[3] : '';

            // TO BE CONTINUED
            // $brackets  = ($componentArgs === '') ? '()' : '(' . $componentArgs . ')';

            if(trim($componentArgs) !== '') {
                $args = $this->doArguments($componentArgs);

                $args = ", '" . json_encode($args) . "'";
            }

            $componentRender = "<?php FunCom\Components\View::render('$componentName'$args); ?>";

            $this->html = str_replace($component, $componentRender, $this->html);
        }

        $result = $this->html !== null;

        return $result;
    }

    public function doArguments(string $componentArgs): ?array
    {
        $result = [];

        $re = '/([A-Za-z]*)=((")(.*)(")|(\')(.*)(\')|(\{)(.*)(\}))/m';

        preg_match_all($re, $componentArgs, $matches, PREG_SET_ORDER, 0);

        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[2];

            array_push($result, [$key = $value]);
        }

        $result = $matches;

        return $result;
    }


    public function doUses(): bool
    {
        $result = false;

        $re = '/use ([A-Za-z0-9\\\\ ]*)\\\\([A-Za-z0-9]*)([ ]*)?;/m';

        preg_match_all($re, $this->html, $matches, PREG_SET_ORDER, 0);
        // TO BE CONTINUED

        foreach ($matches as $match) {
            $componentNamespace = trim($match[1], '\\');
            $componentFunction = $match[2];

            UseRegistry::write($componentFunction, $componentNamespace . '\\' . $componentFunction);
        }
        return $result;
    }


    public function doUsesAs(): bool
    {
        $result = false;

        $re = '/use ([A-Za-z0-9\\\\ ]*)\\\\([A-Za-z0-9 ]*) as ([A-Za-z0-9 ]*)?;/m';

        preg_match_all($re, $this->html, $matches, PREG_SET_ORDER, 0);
        // TO BE CONTINUED

        foreach ($matches as $match) {
            $componentNamespace = trim($match[1], '\\');
            $componentFunction = $match[2];
            $componentAlias = $match[3];

            UseRegistry::write($componentAlias, $componentNamespace . '\\' . $componentFunction);
        }

        return $result;
    }


    public static function getFunctionDefinition(string $filename): array
    {
        $classText = file_get_contents($filename);

        if ($classText === false) {
            return [null, null, false];
        }

        $namespace = self::grabKeywordName('namespace', $classText, ';');
        $functionName = self::grabKeywordName('function', $classText, '(');

        return [$namespace, $functionName, $classText];
    }

    public static function getClassDefinition(string $filename): array
    {
        $classText = file_get_contents($filename);

        $namespace = self::grabKeywordName('namespace', $classText, ';');
        $className = self::grabKeywordName('class', $classText, ' ');

        return [$namespace, $className, $classText];
    }

    public static function grabKeywordName(string $keyword, string $classText, $delimiter): string
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
}
