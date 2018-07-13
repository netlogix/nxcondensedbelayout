<?php
namespace Netlogix\Nxcondensedbelayout\Xclass\Gridelements\Backend\ItemsProcFuncs;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content CType.
 */
class CTypeList extends \GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\CTypeList {

	/**
	 * Those column names influence the CType items contents but are known to
	 * be "l10n_mode = exclude".
	 *
	 * @var array<string>
	 */
	protected $excludeFromLocalizationColumnNames = array('tx_gridelements_children', 'tx_gridelements_container', 'tx_gridelements_columns', 'tx_gridelements_backend_layout', 'colPos');

	/**
	 * In contrast to the original manipulation mechanism, this one considers
	 * some columns being known as "l10n_mode = exclude" and uses values of the
	 * l10n parent record instead.
	 *
	 * @param array $params
	 */
	public function itemsProcFunc(array &$params) {

		$considerLanguageParentDataForL10NMode = $this->considerLanguageParentDataForL10NMode($params['table'], $params['row']);

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
	protected function considerLanguageParentDataForL10NMode($tableName, $row) {
		if ($tableName !== 'tt_content') {
			return FALSE;
		}

		if ($row['sys_language_uid'] <= 0) {
			return FALSE;
		}

		if (!$row['l18n_parent']) {
			return FALSE;
		}

		return TRUE;
	}

}
