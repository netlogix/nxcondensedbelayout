<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

foreach (array('tx_gridelements_children', 'tx_gridelements_container', 'tx_gridelements_columns', 'tx_gridelements_backend_layout', 'colPos', 'sorting') as $columnName) {

	if (TYPO3_MODE === 'FE') {
		$GLOBALS['TCA']['tt_content']['columns'][$columnName]['l10n_mode'] = 'exclude';
	}
	if (TYPO3_MODE === 'BE') {
		$GLOBALS['TCA']['tt_content']['columns'][$columnName]['displayCond'] = 'FIELD:l18n_parent:=:0';
	}

}

if (TYPO3_MODE == 'BE') {
    // Register wizard hook to manipulate gridelements default language
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][] = 'EXT:nxcondensedbelayout/Classes/Hooks/WizardItems.php:Netlogix\\Nxcondensedbelayout\\Hooks\\WizardItems';
}
