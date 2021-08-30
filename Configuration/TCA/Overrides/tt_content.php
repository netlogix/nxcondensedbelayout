<?php

use Netlogix\Nxcondensedbelayout\Hooks\PageRepository\KeepContentNontranslatlableValuesInSync;
use Netlogix\Nxcondensedbelayout\Xclass\Gridelements\Backend\ItemsProcFuncs\CTypeList;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

/*
 * The condensed backend layout is best used with grid elements containers
 * being set to "sys_language_uid:-1". So there is no need to adjust any
 * content language according to its container parent or preceding sibling
 * in list mode.
 *
 * Unfortunately gridelements relies on colPos to be spelled "colPos," with
 * an additional comma on the right side of the string. So we need to append
 * the comma to the properly processed data here as well, just in case "colPos"
 * is the either the only remaining element in list or the last one.
 */

(function () {

    $copyAfterDuplFields = $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'];
    $copyAfterDuplFields = GeneralUtility::rmFromList('sys_language_uid', $copyAfterDuplFields);
    $copyAfterDuplFields = ',' . $copyAfterDuplFields . ',';
    $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] = $copyAfterDuplFields;

    $itemsProcFunc = sprintf('%s->itemsProcFunc', CTypeList::class);
    $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemsProcFunc'] = $itemsProcFunc;

    foreach (KeepContentNontranslatlableValuesInSync::NON_TRANSLATABLE_PROPERTIES as $columnName) {
        $GLOBALS['TCA']['tt_content']['columns'][$columnName]['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['tt_content']['columns'][$columnName]['l10n_display'] = 'defaultAsReadonly';
    }
})();

