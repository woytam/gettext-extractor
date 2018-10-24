<?php declare(strict_types=1);


namespace Webwings\Gettext\Extractor\Filters;


use Latte\MacroTokens;
use Webwings\Gettext\Extractor\Extractor;

class NetteLatteFilter extends Filter implements IFilter
{

    public function __construct()
    {
        $this->addFunction('_');
        $this->addFunction('__');
        $this->addFunction('!_');
        $this->addFunction('_n', 1, 2);
        $this->addFunction('!_n', 1, 2);
        $this->addFunction('_p', 2, null, 1);
        $this->addFunction('!_p', 2, null, 1);
        $this->addFunction('_np', 2, 3, 1);
        $this->addFunction('!_np', 2, 3, 1);
    }

    /**
     * Includes a prefix to match in { }
     * Alias for Filter::addFunction
     *
     * @param string $prefix
     * @param int $singular
     * @param int|null $plural
     * @param int|null $context
     * @return NetteLatteFilter
     */
    public function addPrefix(string $prefix, int $singular = 1, int $plural = null, int $context = null): self
    {
        parent::addFunction($prefix, $singular, $plural, $context);
        return $this;
    }

    /**
     * Excludes a prefix from { }
     * Alias for AFilter::removeFunction
     *
     * @param string $prefix
     * @return self
     */
    public function removePrefix(string $prefix): self
    {
        parent::removeFunction($prefix);
        return $this;
    }

    /**
     * @param string $file
     * @return array
     * @throws \Latte\CompileException
     */
    public function extract(string $file) : array
    {
        if (count($this->functions) === 0) {
            return [];
        }
        $data = [];

        $regexp = '/' . implode('|', array_keys($this->functions)) . '/';
        $latte = new \Latte\Parser();
        foreach ($latte->parse(file_get_contents($file)) as $token) {
            if ($token->name !== null) {
                if (preg_match($regexp, $token->name) || preg_match($regexp, $token->text)) {
                    $macroTokens = new MacroTokens($token->text);
                    foreach ($this->extractTokens($macroTokens) as $translation) {
                        $translation[Extractor::LINE] = $token->line;
                        $data[] = $translation;
                    }
                }
            }
        }
        return $data;
    }


    /**
     * @param MacroTokens $tokens
     * @return array
     */
    private function extractTokens(MacroTokens $tokens) : array
    {
        $data = [];
        $tokens->nextToken();
        $tokens->nextToken();
        $nthArguments = $this->getRequiredArguments($tokens->currentValue());
        $data = array_merge($data, $this->addNthArgument($tokens, $nthArguments));
        return $data;
    }

    /**
     * @param $function
     * @return array
     */
    private function getRequiredArguments($function) : array
    {
        $requiredArguments = [];
        if (isset($this->functions[$function])) {
            foreach ($this->functions[$function] as $definition) {
                $requiredArguments[Extractor::SINGULAR] = $definition[Extractor::SINGULAR];
                if (isset($definition[Extractor::PLURAL])) {
                    $requiredArguments[Extractor::PLURAL] = $definition[Extractor::PLURAL];
                }
            }
        }
        return $requiredArguments;
    }

    /**
     * @param MacroTokens $tokens
     * @param array $nthArguments
     * @return array
     */
    private function addNthArgument(MacroTokens $tokens, array $nthArguments) : array
    {
        $argumentPosition = 1;
        $level = 0;
        $levelArguments = [0 => []];
        $levelRequiredArguments = [0 => $nthArguments];
        $levelTernalOperator = [0 => 0];
        $foundedTranslations = [];
        while ($tokens->nextToken()) {
            if ($tokens->isCurrent($tokens::T_WHITESPACE)) {
                continue;
            }
            if ($tokens->isCurrent(':')) {
                if (!$levelTernalOperator[$level]) {
                    $argumentPosition++;
                } else {
                    $levelTernalOperator--;
                }
            } elseif ($tokens->isCurrent('?')) {
                $levelTernalOperator[$level]++;
            } elseif ($tokens->isCurrent('|')) {
                $tokens->nextToken();
                $levelRequiredArguments[$level] = $this->getRequiredArguments($tokens->currentValue());
            } elseif ($tokens->isCurrent(['(', '['])) {
                $level++;
                $levelArguments[$level] = [];
            } elseif ($tokens->isCurrent([')', ']']) || !$tokens->isNext()) {
                $finalTranslation = $this->getLevelTranslations($levelArguments[$level], $levelRequiredArguments[$level]);
                if (count($finalTranslation)) {
                    $foundedTranslations[] = $finalTranslation;
                }
                $level--;
            } elseif ($tokens->isCurrent(',')) {
                $argumentPosition++;
            } elseif ($tokens->isCurrent($tokens::T_STRING)) {
                $levelArguments[$level][$argumentPosition] = $tokens->currentValue();
            }
        }
        return $foundedTranslations;
    }

    /**
     * @param $levelArguments
     * @param $requiredArguments
     * @return array
     */
    private function getLevelTranslations($levelArguments, $requiredArguments) : array
    {
        $translation = [];
        if (count($levelArguments) && count($requiredArguments)) {
            foreach ($requiredArguments as $key => $requiredArgument) {
                $translation[$key] = $this->stripQuotes($this->fixEscaping($levelArguments[$requiredArgument]));
            }
        }
        return $translation;
    }

}