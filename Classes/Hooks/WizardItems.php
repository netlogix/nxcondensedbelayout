<?php
namespace Netlogix\Nxcondensedbelayout\Hooks;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class WizardItems implements NewContentElementWizardHookInterface
{

    /**
     * Modifies WizardItems array
     *
     * Every tt_content gets either created with the current pages language
     * selection or "all" in case of gridelements records.
     *
     * @param array $wizardItems
     * @param NewContentElementController $parentObject
     * @return void
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject)
    {
        $returnUrl = GeneralUtility::_GET('returnUrl');
        parse_str(parse_url($returnUrl)['query'], $queryString);

        if (isset($queryString['SET']['language']) && $queryString['SET']['language'] != 0) {
            $language = $queryString['SET']['language'];
        } else {
            // Load page TSconfig.
            $pageTSconfig = BackendUtility::getPagesTSconfig($queryString['id']);
            /** @var TypoScriptService $typoscriptService */
            $typoscriptService = GeneralUtility::makeInstance(TypoScriptService::class);

            $language = ObjectAccess::getPropertyPath(
                $typoscriptService->convertTypoScriptArrayToPlainArray($pageTSconfig),
                'TCAdefaults.tx_nxcondensedbelayout.defaultLanguageForNonContainers'
            );
            if (MathUtility::canBeInterpretedAsInteger($language)) {
                $language = (int) $language;
            } else {
                $language = null;
            }
        }

        foreach ($wizardItems as $key => $item) {
            if (
                strpos($key, 'gridelements_grid') !== false ||
                strpos($key, 'gridelements') !== false ||
                strpos($key, 'plugins') !== false ||
                in_array($key, ['special_menu', 'common_uploads'])
            ) {
                if ($wizardItems[$key]['tt_content_defValues']) {
                    $wizardItems[$key]['tt_content_defValues']['sys_language_uid'] = -1;
                }
                if ($wizardItems[$key]['params']) {
                    $wizardItems[$key]['params'] .= '&defVals[tt_content][sys_language_uid]=-1';
                }
            } else if ($language !== null) {

                if ($wizardItems[$key]['tt_content_defValues']) {
                    $wizardItems[$key]['tt_content_defValues']['sys_language_uid'] = $language;
                }
                if ($wizardItems[$key]['params']) {
                    $wizardItems[$key]['params'] .= '&defVals[tt_content][sys_language_uid]=' . $language;
                }
            }
        }
    }
}