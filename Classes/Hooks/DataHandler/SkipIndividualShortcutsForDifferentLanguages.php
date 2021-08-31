<?php

namespace Netlogix\Nxcondensedbelayout\Hooks\DataHandler;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * If a new shortcut record is created by the Gridelements "insert as reference"
 * feature, usually for every language of the source record one shortcut record
 * is created.
 *
 * The shortcut record for the default language source is forced to "All" language
 * since it is meant to work for every translation as well.
 *
 * All shortcut records for every translation of the source are skipped because the
 * default language shortcut handles those as well.
 */
class SkipIndividualShortcutsForDifferentLanguages
{
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        if (!array_key_exists('tt_content', $dataHandler->datamap)) {
            return;
        }
        foreach ($dataHandler->datamap['tt_content'] as $newUid => $record) {
            if (substr($newUid, 0, 3) === 'NEW' && $record['CType'] === 'shortcut' && !$record['sys_language_uid']) {
                if ($record['l18n_parent'] > 0) {
                    unset($dataHandler->datamap['tt_content'][$newUid]);
                } else {
                    $dataHandler->datamap['tt_content'][$newUid]['sys_language_uid'] = -1;
                }
            }
        }
    }
}
