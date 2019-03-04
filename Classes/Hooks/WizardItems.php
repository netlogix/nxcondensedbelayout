<?php

namespace Netlogix\Nxcondensedbelayout\Hooks;

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
     * @inheritdoc
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject)
    {
        $language = $this->getLanguage();

        foreach ($wizardItems as $key => $item) {
            switch (true) {
                case (!strpos($key, '_')):
                    break;
                case $key === 'special_menu':
                case $key === 'special_div':
                case $key === 'special_shortcut':
                case $key === 'common_uploads':
                case strpos($key, 'menu_') === 0:
                case strpos($key, 'plugins_') === 0:
                case (strpos($key, 'gridelements') !== false):
                    $wizardItems[$key] = $this->setLanguageForWizardItem($item, -1);
                    break;
                default:
                    $wizardItems[$key] = $this->setLanguageForWizardItem($item, (int)$language);
                    break;
            }
        }
    }

    protected function getLanguage(): int
    {
        $pageLayoutController = $this->getPageLayoutController();
        $pageLayoutController->init();
        return (int)$pageLayoutController->current_sys_language;
    }

    protected function getPageLayoutController(): PageLayoutController
    {
        return GeneralUtility::makeInstance(PageLayoutController::class);
    }

    protected function setLanguageForWizardItem(array $wizardItem, int $language): array
    {
        if ($wizardItem['tt_content_defValues']) {
            $wizardItem['tt_content_defValues']['sys_language_uid'] = $language;
        }
        if ($wizardItem['params']) {
            $wizardItem['params'] .= '&defVals[tt_content][sys_language_uid]=' . $language;
        }
        return $wizardItem;
    }
}