<?php

namespace Netlogix\Nxcondensedbelayout\Hooks\DataHandler;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 */
class ProcessDatamapService implements SingletonInterface
{
	protected $remember = [];

	/**
	 * @param DataHandler $dataHandler
	 */
	public function processDatamap_beforeStart($dataHandler)
	{
		$this->skipIndividualShortcutsForDifferentLanguages($dataHandler);
		$this->skipLanguageOverwriteForPastedRecords($dataHandler);
		$this->transformGridNestingToTranslationChildren($dataHandler);
	}

	/**
	 * Make sure the gridelements layout field is in sync between
	 * translations and their corresponding language parents.
	 *
	 * @param string $status status
	 * @param string $table table name
	 * @param integer $recordUid id of the record
	 * @param array $fields fieldArray
	 * @param DataHandler $parentObject parent Object
	 *
	 * @return void
	 */
	public function processDatamap_afterDatabaseOperations(
		$status,
		$table,
		$recordUid,
		array $fields,
		DataHandler $parentObject
	) {
		if ($table !== 'tt_content' || !array_key_exists('tx_gridelements_backend_layout',
				$fields) || substr($recordUid, 0, 3) === 'NEW') {
			return;
		}

		/** @var DatabaseConnection $db */
		$db = $GLOBALS['TYPO3_DB'];
		$db->sql_query(sprintf('
			UPDATE
				tt_content AS defaultLanguage
				LEFT JOIN
					tt_content AS translationOverlay
					ON defaultLanguage.uid = translationOverlay.l10n_source
			SET
				translationOverlay.tx_gridelements_backend_layout = defaultLanguage.tx_gridelements_backend_layout
			WHERE
				defaultLanguage.uid = %d
		', $recordUid));

	}

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
	 * @param DataHandler $dataHandler
	 */
	protected function skipIndividualShortcutsForDifferentLanguages($dataHandler)
	{
		if (!GeneralUtility::_GET('DDcopy')) {
			// Gridelements "insert as reference" is indicated by the "DDcopy" argument.
			return;
		}
		if (!array_key_exists('tt_content', $dataHandler->datamap)) {
			return;
		}
		foreach ($dataHandler->datamap['tt_content'] as $newUid => $record) {
			if (substr($newUid, 0, 3) === 'NEW' && $record['CType'] === 'shortcut' && !$record['sys_language_uid']) {
				if ($record['l10n_source'] > 0) {
					unset($dataHandler->datamap['tt_content'][$newUid]);
				} else {
					$dataHandler->datamap['tt_content'][$newUid]['sys_language_uid'] = -1;
				}
			}
		}
	}

	/**
	 * If an existing record is pasted into a specific position, Gridelements
	 * overrules the original records language by the one currently active in
	 * the backend page view.
	 * Although that might be an important feature when moving records around
	 * amongst containers of different languages that is the rare case in the
	 * real world. Pretty often containers are not of type "language > 0" but
	 * of type "language = -1", meaning it's not at ranslated container.
	 *
	 * Updating such a container from "langauge = -1" to "language = 0" while
	 * copying just doesn't make much sense.
	 *
	 * @param DataHandler $dataHandler
	 */
	protected function skipLanguageOverwriteForPastedRecords($dataHandler)
	{
		if (GeneralUtility::_GET('ajaxID') !== '/ajax/record/process') {
			return;
		}
		foreach ($dataHandler->cmdmap as $tablename => $commands) {
			if ($tablename !== 'tt_content') {
				continue;
			}
			foreach ($commands as $commandId => $action) {
				foreach ($action as $actionName => $actionArguments) {
					if ($actionName !== 'copy' || $actionArguments['action'] !== 'paste') {
						continue;
					}
					unset($dataHandler->cmdmap[$tablename][$commandId][$actionName]['update']['sys_language_uid']);
				}
			}
		}
	}

	/**
	 * In case we're moving tt_content and adjust container, columns and colPos all at the
	 * same time, chances are that's the chained operation of a "past into" action.
	 * Translated records are copied as well but don't point at the same container target
	 * as the translation source. Instead, they point to the original container.
	 *
	 * @param DataHandler $dataHandler
	 */
	protected function transformGridNestingToTranslationChildren($dataHandler)
	{
		if (!isset($dataHandler->datamap['tt_content'])) {
			return;
		}

		$requiredKeys = ['tx_gridelements_container', 'tx_gridelements_columns', 'colPos'];
		foreach ($dataHandler->datamap['tt_content'] as $recordUid => $record) {
			if (substr($recordUid, 0, 3) === 'NEW') {
				continue;
			}

			$copyFields = array_intersect_key($record, array_flip($requiredKeys));
			if (count($copyFields) !== count($requiredKeys)) {
				continue;
			}

			$translationRecords = BackendUtility::getRecordsByField('tt_content',
				$GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'], $recordUid);
			foreach (array_column($translationRecords, 'uid') as $translationUid) {
				$dataHandler->datamap['tt_content'][$translationUid] = array_merge(
					$dataHandler->datamap['tt_content'][$translationUid] ?? [],
					$copyFields
				);
			}
		}
	}

}