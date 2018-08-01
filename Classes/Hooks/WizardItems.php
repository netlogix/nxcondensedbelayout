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
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $language = $this->getLanguage();

        foreach ($wizardItems as $key => $item) {
            if (
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
            } else {
                if ($wizardItems[$key]['tt_content_defValues']) {
                    $wizardItems[$key]['tt_content_defValues']['sys_language_uid'] = $language;
                }
                if ($wizardItems[$key]['params']) {
                    $wizardItems[$key]['params'] .= '&defVals[tt_content][sys_language_uid]=' . $language;
                }
            }
        }
    }

    protected function getLanguage()
    {
        /** @var PageLayoutController $ctrl */
        $pageLayoutController = GeneralUtility::makeInstance(PageLayoutController::class);
        $pageLayoutController->init();
        return (int)$pageLayoutController->current_sys_language;
    }
}