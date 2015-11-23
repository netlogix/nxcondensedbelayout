<?php

if ( ! defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\View\\PageLayoutView'] = array(
	'className' => 'Netlogix\\Nxcondensedbelayout\\Xclass\\CMS\\Backend\\View\\PageLayoutView',
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['renderPreProcess'][] = 'Netlogix\\Nxcondensedbelayout\\Hooks\\BackendController\\PositionService->includeJavaScript';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'Netlogix\\Nxcondensedbelayout\\Hooks\\DataHandler\\ProcessDatamapService';
