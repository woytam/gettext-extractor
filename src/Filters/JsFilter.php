<?php declare(strict_types=1);

namespace Webwings\Gettext\Extractor\Filters;

use Webwings\Gettext\Extractor\Extractor;

class JsFilter implements IFilter
{
    /**
     * @param string $file
     * @return array
     */
	public function extract(string $file) : array
	{
		$pInfo = pathinfo($file);
		$data = array();
		foreach (file($file) as $line => $contents) {
			// match all jsTranslate(..translated text.. ) tags

			preg_match_all('/'.
				'jsTranslate\(\s*(\'[^\']*\'|"[^"]*")\)'. // js text for translate
				'/', $contents, $matches);
			if (empty($matches)) continue;
			if (empty($matches[0])) continue;

			foreach ($matches[1] as $match) {
				if($match == "") continue;
				$result = array(
					Extractor::LINE => $line + 1
				);
				$result[Extractor::SINGULAR] = substr($match, 1, -1);
				$data[] = $result;
			}
		}
		return $data;
	}
}