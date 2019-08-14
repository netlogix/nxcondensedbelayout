<?php

namespace Netlogix\Nxcondensedbelayout\Xclass\Gridelements\Backend\ItemsProcFuncs;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content CType.
 */
class CTypeList extends \GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\CTypeList
{

	/**
	 * Those column names influence the CType items contents but are known to
	 * be "l10n_mode = exclude".
	 *
	 * @var array<string>
	 */
	protected $excludeFromLocalizationColumnNames = array(
		'tx_gridelements_children',
		'tx_gridelements_container',
		'tx_gridelements_columns',
		'tx_gridelements_backend_layout',
		'colPos'
	);

	/**
	 * In contrast to the original manipulation mechanism, this one considers
	 * some columns being known as "l10n_mode = exclude" and uses values of the
	 * l10n parent record instead.
	 *
	 * @param array $params
	 */
	public function itemsProcFunc(array &$params)
	{

		$considerLanguageParentDataForL10NMode = $this->considerLanguageParentDataForL10NMode($params['table'],
			$params['row']);

		if ($considerLanguageParentDataForL10NMode) {
			$row = $params['row'];

			$languageParent = BackendUtility::getRecord('tt_content', $row['l18n_parent']);
			if ($languageParent) {
				foreach ($this->excludeFromLocalizationColumnNames as $columnName) {
					$params['row'][$columnName] = $languageParent[$columnName];
				}
			}
		}

		parent::itemsProcFunc($params);

		if ($considerLanguageParentDataForL10NMode) {
			$params['row'] = $row;
		}

	}

	/**
	 * Returns TRUE if the given record should fetch additional data
	 * from its l10n parent, otherwise FALSE.
	 *
	 * @param array $row
	 * @return bool
	 */
	protected function considerLanguageParentDataForL10NMode($tableName, $row)
	{
		if ($tableName !== 'tt_content') {
			return false;
		}

		if ($row['sys_language_uid'] <= 0) {
			return false;
		}

		if (!$row['l18n_parent']) {
			return false;
		}

		return true;
	}

}
