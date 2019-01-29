<?php

namespace Netlogix\Nxcondensedbelayout\Hooks\PageRepository;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Page\PageRepositoryGetRecordOverlayHookInterface;

class KeepContentNontranslatlableValuesInSync implements PageRepositoryGetRecordOverlayHookInterface, SingletonInterface
{
    const NON_TRANSLATABLE_PROPERTIES = [
        'tx_gridelements_children',
        'tx_gridelements_container',
        'tx_gridelements_columns',
        'tx_gridelements_backend_layout',
        'colPos',
        'sorting'
    ];

    protected $values = [];

    public function getRecordOverlay_preProcess($table, &$row, &$sys_language_content, $OLmode, PageRepository $parent)
    {
        if ($table !== 'tt_content') {
            return;
        }

        $keys = self::NON_TRANSLATABLE_PROPERTIES;
        $values = array_map(function ($propertyName) use ($row) {
            return $row[$propertyName];
        }, $keys);

        $this->values[$row['uid']] = array_combine($keys, $values);
    }

    public function getRecordOverlay_postProcess($table, &$row, &$sys_language_content, $OLmode, PageRepository $parent)
    {
        if ($table !== 'tt_content' || !$this->values[$row['uid']]) {
            return;
        }

        $row = array_merge($row, $this->values[$row['uid']]);
        unset($this->values[$row['uid']]);
    }
}