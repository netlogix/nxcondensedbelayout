<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

call_user_func(function () {

	global $TYPO3_CONF_VARS;

	foreach ([
				 'tx_gridelements_children',
				 'tx_gridelements_container',
				 'tx_gridelements_columns',
				 'tx_gridelements_backend_layout',
				 'colPos',
				 'sorting'
			 ] as $columnName) {

		if (TYPO3_MODE === 'FE') {
			$GLOBALS['TCA']['tt_content']['columns'][$columnName]['l10n_mode'] = 'exclude';
		}
		if (TYPO3_MODE === 'BE') {
			$GLOBALS['TCA']['tt_content']['columns'][$columnName]['l10n_display'] = 'defaultAsReadonly';
		}

	}

	if (TYPO3_MODE == 'BE') {
		// Register wizard hook to manipulate gridelements default language
		$TYPO3_CONF_VARS['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][] = \Netlogix\Nxcondensedbelayout\Hooks\WizardItems::class;
	}

});