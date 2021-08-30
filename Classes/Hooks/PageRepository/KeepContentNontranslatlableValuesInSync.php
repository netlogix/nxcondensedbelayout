<?php

namespace Netlogix\Nxcondensedbelayout\Hooks\PageRepository;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetRecordOverlayHookInterface;
use TYPO3\CMS\Core\SingletonInterface;

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
