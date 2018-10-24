<?php

namespace Webwings\Gettext\Extractor\Filters;


/**
 * Interface IFilter
 * @package Webwings\Gettext\Extractor\Filters
 */
interface IFilter
{

    /**
     * @param string $file
     * @return array
     */
    public function extract(string $file): array;
}