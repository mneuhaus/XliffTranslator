<?php
namespace Mrimann\XliffTranslator\Aop;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Mrimann\XliffTranslator\Core\XliffModel;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;

/**
 * The central security aspect, that invokes the security interceptors.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class TranslationAspect {

	/**
	 * @var \TYPO3\Flow\I18n\Service
	 * @Flow\Inject
	 */
	protected $localizationService;

	/**
	 * An absolute path to the directory where translation files reside.
	 *
	 * @var string
	 */
	protected $xliffBasePath = 'Private/Translations/';

	/**
	 * Returns a XliffModel instance representing desired XLIFF file.
	 *
	 * Will return existing instance if a model for given $sourceName was already
	 * requested before. Returns FALSE when $sourceName doesn't point to existing
	 * file.
	 *
	 * @param string $packageKey Key of the package containing the source file
	 * @param string $sourceName Relative path to existing CLDR file
	 * @param \TYPO3\Flow\I18n\Locale $locale Locale object
	 * @return \TYPO3\Flow\I18n\Xliff\XliffModel New or existing instance
	 * @throws \TYPO3\Flow\I18n\Exception
	 */
	protected function getXlfFileName($packageKey, $sourceName, \TYPO3\Flow\I18n\Locale $locale) {
		$sourcePath = \TYPO3\Flow\Utility\Files::concatenatePaths(array(
			'resource://' . $packageKey,
			$this->xliffBasePath
		));

		$possibleXliffFilename = Files::concatenatePaths(array($sourcePath, $locale->getLanguage(), $sourceName . '.xlf'));
		return $possibleXliffFilename;
	}

	/**
	 *
	 * @Flow\Around("setting(Mrimann.XliffTranslator.autoCreateTranslations) && method(TYPO3\Flow\I18n\Translator->translateById(*))")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function autoCreateIdTranslation(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$locale = $joinPoint->getMethodArgument('locale');
		if ($locale === NULL) {
			$locale = $this->localizationService->getConfiguration()->getCurrentLocale();
		}

		$packageKey = $joinPoint->getMethodArgument('packageKey');
		$sourceName = $joinPoint->getMethodArgument('sourceName');
		$labelId = $joinPoint->getMethodArgument('labelId');
		$default = $joinPoint->getMethodArgument('default');
		$fileName = $this->getXlfFileName($packageKey, $sourceName, $locale);

		try {
			$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		} catch (\TYPO3\Flow\I18n\Exception $exception) {
			switch ($exception->getCode()) {
				case 1334759591:
					// Missing xlf file
					$model = new XliffModel($fileName, $locale);
					$model->initializeObject();
					$model->add($labelId, $default);

				default:
					throw $exception;
					break;
			}
			return NULL;
		}

		if ($result === $labelId) {
			$model = new XliffModel($fileName, $locale);
			$model->initializeObject();
			$model->add($labelId, $default);
		}

		return $result;
	}

}

?>