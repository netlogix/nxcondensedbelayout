<?php
if ( ! defined('TYPO3_MODE')) {
	die('Access denied.');
}

call_user_func(function() {

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\View\PageLayoutView::class] = [
		'className' => \Netlogix\Nxcondensedbelayout\Xclass\CMS\Backend\View\PageLayoutView::class,
	];

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\GridElementsTeam\Gridelements\DataHandler\PreProcessFieldArray::class] = [
		'className' => \Netlogix\Nxcondensedbelayout\Xclass\Gridelements\DataHandler\PreProcessFieldArray::class,
	];

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['renderPreProcess'][] = sprintf('%s->includeJavaScript', \Netlogix\Nxcondensedbelayout\Hooks\BackendController\PositionService::class);
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Netlogix\Nxcondensedbelayout\Hooks\DataHandler\ProcessDatamapService::class;
});

call_user_func(function() {
    if (TYPO3_MODE != 'BE' || \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('ajaxID') !== '/ajax/record/process') {
        return;
    }
    $args = [];
    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction('Netlogix\\Nxcondensedbelayout\\Hooks\\DataHandler\\SkipLanguageChangingOnMove->guardLanguageRequestParameterForTtContent', $args, $args);
});
