<?php
namespace Mrimann\XliffTranslator\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;

/**
 * A model representing data from one XLIFF file.
 *
 * Please note that plural forms for particular translation unit are accessed
 * with integer index (and not string like 'zero', 'one', 'many' etc). This is
 * because they are indexed such way in XLIFF files in order to not break tools'
 * support.
 *
 * There are very few XLIFF editors, but they are nice Gettext's .po editors
 * available. Gettext supports plural forms, but it indexes them using integer
 * numbers. Leaving it this way in .xlf files, makes it possible to easily convert
 * them to .po (e.g. using xliff2po from Translation Toolkit), edit with Poedit,
 * and convert back to .xlf without any information loss (using po2xliff).
 *
 * @see http://docs.oasis-open.org/xliff/v1.2/xliff-profile-po/xliff-profile-po-1.2-cd02.html#s.detailed_mapping.tu
 */
class XliffModel extends \TYPO3\Flow\I18n\Xliff\XliffModel {
	/**
	 * @param string $sourcePath
	 * @param \TYPO3\Flow\I18n\Locale $locale The locale represented by the file
	 */
	public function __construct($sourcePath, \TYPO3\Flow\I18n\Locale $locale) {
		parent::__construct($sourcePath, $locale);
		if (!file_exists($this->sourcePath)) {
			$this->createXliffFile();
		}
		$this->xml = simplexml_load_file($this->sourcePath);
	}

	public function createXliffFile() {
		Files::createDirectoryRecursively(dirname($this->sourcePath));
		file_put_contents($this->sourcePath, '<?xml version="1.0"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
	<file original="" source-language="en" target-language="en" datatype="plaintext">
		<body>
		</body>
	</file>
</xliff>');
	}

	public function add($labelId, $default = '') {
		$transUnit = $this->xml->file->body->addChild('trans-unit');
		$transUnit->addAttribute('id', $labelId);
		$transUnit->addChild('source', $default);
		$transUnit->addChild('target', $default);

		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($this->xml->asXML());
		file_put_contents($this->sourcePath, $dom->saveXML());
	}
}