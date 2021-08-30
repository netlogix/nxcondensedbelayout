<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(function () {

    // Register wizard hook to manipulate gridelements default language
    $GLOBALS['TYPO3_CONF_VARS']['cms']['db_new_content_el']['wizardItemsHook'][] = \Netlogix\Nxcondensedbelayout\Hooks\WizardItems::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][] = \Netlogix\Nxcondensedbelayout\Hooks\WizardItems::class;

});
