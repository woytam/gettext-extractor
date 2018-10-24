<?php declare(strict_types=1);


namespace Webwings\Gettext\Extractor\Filters;


use Webwings\Gettext\Extractor\Extractor;

abstract class Filter
{

    /** @var array */
    protected $functions = array();


    /**
     * @param string $functionName
     * @param int $singular
     * @param int|null $plural
     * @param int|null $context
     * @return Filter
     */
    public function addFunction(string $functionName, int $singular = 1, int $plural = null, int $context = null) : self {
        if (!is_int($singular) || $singular <= 0) {
            throw new \InvalidArgumentException('Invalid argument type or value given for paramater $singular.');
        }
        $function = array(
            Extractor::SINGULAR => $singular
        );
        if ($plural !== null) {
            if (!is_int($plural) || $plural <= 0) {
                throw new \InvalidArgumentException('Invalid argument type or value given for paramater $plural.');
            }
            $function[Extractor::PLURAL] = $plural;
        }
        if ($context !== null) {
            if (!is_int($context) || $context <= 0) {
                throw new \InvalidArgumentException('Invalid argument type or value given for paramater $context.');
            }
            $function[Extractor::CONTEXT] = $context;
        }
        $this->functions[$functionName][] = $function;
        return $this;
    }

    /**
     * @param string $functionName
     * @return Filter
     */
    public function removeFunction(string $functionName) : self {
        unset($this->functions[$functionName]);
        return $this;
    }

    /**
     * @return Filter
     */
    public function removeAllFunctions() : self {
        $this->functions = array();
        return $this;
    }

    /**
     * @param string $string
     * @return mixed|string
     */
    protected function fixEscaping(string $string) {
        $prime = substr($string, 0, 1);
        $string = str_replace('\\' . $prime, $prime, $string);
        return $string;
    }

    /**
     * @param string $string
     * @return bool|string
     */
    protected function stripQuotes(string $string) {
        $prime = substr($string, 0, 1);
        if ($prime === "'" || $prime === '"') {
            if (substr($string, -1, 1) === $prime) {
                $string = substr($string, 1, -1);
            }
        }
        return $string;
    }


}