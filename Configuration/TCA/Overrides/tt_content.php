<?php
if (!defined ('TYPO3_MODE')) {
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

$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] = ',' . \TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList('sys_language_uid', $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields']) . ',';
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemsProcFunc'] = 'Netlogix\\Nxcondensedbelayout\\Xclass\\Gridelements\\Backend\\ItemsProcFuncs\\CTypeList->itemsProcFunc';

/*
 * This only works because we have a composer patch file allowing the TCA
 * to be cached individually per TYPO3_MODE. Usually TCA gets cached for
 * BE and FE as the very same thing!
 *
 * Either make sure to allow patches from dependencies or copy the patch
 * statement to your root composer.json.
 */
foreach (\Netlogix\Nxcondensedbelayout\Hooks\PageRepository\KeepContentNontranslatlableValuesInSync::NON_TRANSLATABLE_PROPERTIES as $columnName) {

    if (TYPO3_MODE === 'FE') {
        $GLOBALS['TCA']['tt_content']['columns'][$columnName]['l10n_mode'] = 'exclude';
    }
    if (TYPO3_MODE === 'BE') {
        $GLOBALS['TCA']['tt_content']['columns'][$columnName]['l10n_display'] = 'defaultAsReadonly';
    }

}
