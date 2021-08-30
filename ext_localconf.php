<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\View\PageLayoutView::class] = [
        'className' => \Netlogix\Nxcondensedbelayout\Xclass\CMS\Backend\View\PageLayoutView::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\GridElementsTeam\Gridelements\DataHandler\PreProcessFieldArray::class] = [
        'className' => \Netlogix\Nxcondensedbelayout\Xclass\Gridelements\DataHandler\PreProcessFieldArray::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\GridElementsTeam\Gridelements\Hooks\DrawItem::class] = [
        'className' => \Netlogix\Nxcondensedbelayout\Xclass\Gridelements\Hooks\DrawItem::class,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = sprintf('%s->includeJavaScript',
        \Netlogix\Nxcondensedbelayout\Hooks\BackendController\PositionService::class);
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Netlogix\Nxcondensedbelayout\Hooks\DataHandler\SkipIndividualShortcutsForDifferentLanguages::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getRecordOverlay'][] = \Netlogix\Nxcondensedbelayout\Hooks\PageRepository\KeepContentNontranslatlableValuesInSync::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\Netlogix\Nxcondensedbelayout\Install\Updates\SynchronizeContentL10nSourceWithL18nParent::IDENTIFIER] = \Netlogix\Nxcondensedbelayout\Install\Updates\SynchronizeContentL10nSourceWithL18nParent::class;
});
