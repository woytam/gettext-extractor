<?php declare(strict_types=1);

namespace Webwings\Gettext\Extractor\Filters;

use Webwings\Gettext\Extractor\Extractor;

class AnnotationFilter implements IFilter {

    /**
     * @param string $file
     * @return array
     */
	public function extract(string $file) : array
    {
		$pInfo = pathinfo($file);
		$data = array();
		foreach (file($file) as $line => $contents) {
			$line++;
			preg_match_all('#' .
					'\*.*@[\w]+\((.+)\)' . // annotations
					'#', $contents, $matches);
			if (empty($matches))
				continue;
			if (empty($matches[0]))
				continue;
			foreach ($matches[1] as $match) {
				if ($match == "")
					continue;
				$msgs = preg_split("/,/", $match);
				foreach ($msgs as $msg) {
					$msg = trim($msg);
					if (($start = strpos($msg, "= \"")) !== FALSE) {
						$msg = substr($msg, $start + 2);
					} elseif (($start = strpos($msg, "=\"")) !== FALSE) {
						$msg = substr($msg, $start + 1);
					}

					if ((substr($msg, 0, 1) == "\"") || (substr($msg, 0, 1) == "'")) {
						$msg = substr($msg, 1, -1);
					}

					$data[$msg][Extractor::SINGULAR] = $msg;
					$data[$msg][Extractor::FILE] = $pInfo['basename'];
					$data[$msg][Extractor::LINE] = $line;
				}
			}
		}
		return $data;
	}

}
