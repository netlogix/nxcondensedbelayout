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