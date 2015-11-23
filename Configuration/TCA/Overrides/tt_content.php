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

