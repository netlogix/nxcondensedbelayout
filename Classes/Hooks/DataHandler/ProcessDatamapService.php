<?php
namespace Netlogix\Nxcondensedbelayout\Hooks\DataHandler;

/***************************************************************
*  Copyright notice
*
*  (c) 2015 Stephan Schuler <stephan.schuler@netlogix.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 */
class ProcessDatamapService implements \TYPO3\CMS\Core\SingletonInterface {


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
	 *
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
	 */
	public function processDatamap_beforeStart($dataHandler) {

		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('DDcopy')) {
			// Gridelements "insert as reference" is indicated by the "DDcopy" argument.
			return;
		}
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

	/**
	 * Make sure the gridelements layout field is in sync between
	 * translations and their corresponding language parents.
	 *
	 * @param string $status status
	 * @param string $table table name
	 * @param integer $recordUid id of the record
	 * @param array $fields fieldArray
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObject parent Object
	 *
	 * @return void
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $recordUid, array $fields, \TYPO3\CMS\Core\DataHandling\DataHandler $parentObject) {
		if ($table !== 'tt_content' || !array_key_exists('tx_gridelements_backend_layout', $fields) || substr($recordUid, 0, 3) === 'NEW') {
			return;
		}

		/** @var \TYPO3\CMS\Core\Database\DatabaseConnection $db */
		$db = $GLOBALS['TYPO3_DB'];
		$db->sql_query(sprintf('
			UPDATE
				tt_content AS defaultLanguage
				LEFT JOIN
					tt_content AS translationOverlay
					ON defaultLanguage.uid = translationOverlay.l18n_parent
			SET
				translationOverlay.tx_gridelements_backend_layout = defaultLanguage.tx_gridelements_backend_layout
			WHERE
				defaultLanguage.uid = %d
		', $recordUid));

	}

}