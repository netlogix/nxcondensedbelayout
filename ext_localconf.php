<?php

if ( ! defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\View\\PageLayoutView'] = [
	'className' => 'Netlogix\\Nxcondensedbelayout\\Xclass\\CMS\\Backend\\View\\PageLayoutView',
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['GridElementsTeam\\Gridelements\DataHandler\PreProcessFieldArray'] = [
    'className' => 'Netlogix\\Nxcondensedbelayout\\Xclass\\Gridelements\\DataHandler\\PreProcessFieldArray',
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['renderPreProcess'][] = 'Netlogix\\Nxcondensedbelayout\\Hooks\\BackendController\\PositionService->includeJavaScript';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'Netlogix\\Nxcondensedbelayout\\Hooks\\DataHandler\\ProcessDatamapService';

call_user_func(function() {
    if (TYPO3_MODE != 'BE' || \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('ajaxID') !== '/ajax/record/process') {
        return;
    }
    $args = [];
    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction('Netlogix\\Nxcondensedbelayout\\Hooks\\DataHandler\\SkipLanguageChangingOnMove->guardLanguageRequestParameterForTtContent', $args, $args);
});