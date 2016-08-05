<?php
namespace Netlogix\Nxcondensedbelayout\Xclass\CMS\Backend\View;

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
 * The backend page layout now displays all available languages in one
 * condensed mode.
 */
class PageLayoutView extends \TYPO3\CMS\Backend\View\PageLayoutView {

	const TABLE_TEMPLATE = '<table class="t3-table nxcondensedbelayout-languages">%s</table>';

	const ROW_TEMPLATE = '
		<tr class="bgColor%d %s">
			<td width="20px">%s</td>
			<td width="20px">%s</td>
			<td width="20px">%s</td>
			<td>%s</td>
		</tr>';

	const POSITION_RUNNER = '
		(function() {
			try {

				var namespace = window.parent.Ext.ns("TYPO3.Netlogix.Nxcondensedbelayout");
				var positionService = namespace.positionService;
				if (!positionService) {
					return;
				}

				Ext.onReady(function() {
					var body = Ext.fly("typo3-docbody"),
					currentUrl = window.location.href.split("#")[0];
					positionService.run(body, currentUrl);
				});

			} catch (e) {};
		})();
	';

	protected $skipTranslations = array();

	/**
	 * The "Columns" view now brings the "Make new translation of this
	 * page" feature. We need to switch languageMode on to enforce those
	 * UI elements but overrule the "languageCols" to empty to avoid
	 * additional language columns.
	 */
	public function __construct() {
		parent::__construct();

		/** @var \TYPO3\CMS\Backend\Controller\PageLayoutController $pageLayoutController */
		$pageLayoutController = $GLOBALS['SOBE'];

		$language = (int)$pageLayoutController->current_sys_language;
		if ($this->validModuleConfig() && $language <= 0) {
			$this->tt_contentConfig['languageMode'] = 1;
			$this->tt_contentConfig['languageCols'] = array();
		}

		$skipTranslations = (array)$GLOBALS['BE_USER']->getTSConfig('mod.web_layout.skipTranslations', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig((int)$pageLayoutController->id))['properties'];
		foreach ($skipTranslations as $skipTranslation) {
			foreach ($skipTranslation as $columnName => $options) {
				$skipTranslation[$columnName] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $options);
			}
			$this->skipTranslations[] = $skipTranslation;
		}

		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $this->getPageRenderer();
		$pageRenderer->addCssInlineBlock('nxcondensedbelayout-languages', '.t3-page-ce .t3-row-header .ce-icons, .t3-page-ce .t3-row-header .ce-icons-left {visibility: visible !important;}');
		$pageRenderer->addJsInlineCode(__CLASS__, self::POSITION_RUNNER);

	}

	/**
	 * Draws the preview content for a content element
	 *
	 * @param string $row Content element
	 * @param boolean $isRTE Set if the RTE link can be created.
	 * @return string HTML
	 * @throws \UnexpectedValueException
	 */
	public function tt_content_drawItem($row, $isRTE = FALSE) {

		if (!$this->validModuleConfig()) {
			return parent::tt_content_drawItem($row, $isRTE);
		}

		$result = parent::tt_content_drawItem($row, $isRTE);
		if ($this->allowLanguageNotificationLinesForRecord($row)) {
			$translations = array();
			$translationRows = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField('tt_content', 'l18n_parent', $row['uid'], '', '', 'uid ASC');
			if ($translationRows) {
				foreach ($translationRows as $translationRow) {
					// This "if" is an actual "limit 1 per language"
					if (!isset($translations[$translationRow['sys_language_uid']])) {
						$translations[$translationRow['sys_language_uid']] = $translationRow;
					}
				}
			}
			$languageLines = array();

			$lineCounter = 0;
			foreach ($this->getLanguagesForPage() as $languageId => $languageIconTitle) {
				$lineCounter = ($lineCounter + 1) % 2;

				$editVisibilityIcon = '';
				if ($translations[$languageId]) {
					$lineContent = $this->linkEditContent(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('tt_content', $translations[$languageId]), $translations[$languageId]);
					$languageIcon = $this->linkEditContent($this->languageFlag($languageId, FALSE), $translations[$languageId]);
					$buttonIcon = $this->linkEditContent(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open'), $translations[$languageId]);
					$editVisibilityIcon = $this->getHideUnhideContent($translations[$languageId]);
					$hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
					$class = 'edit-existing-record' . ($translations[$languageId][$hiddenField] ? ' t3-page-ce t3-page-ce-hidden' : '');
				} else {
					$lineContent = $this->linkLocalizeContent($this->getLanguageService()->sL('LLL:EXT:nxcondensedbelayout/Resources/Private/Language/Backend.xlf:tt_content.createTranslation'), $row, $languageId);
					$languageIcon = $this->linkLocalizeContent($this->languageFlag($languageId, FALSE), $row, $languageId);
					$buttonIcon = $this->linkLocalizeContent(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-page-new'), $row, $languageId);
					$class = 'create-new-translation';
				}

				$languageLines[] = sprintf(self::ROW_TEMPLATE, ($lineCounter + 3), $class, $languageIcon, $buttonIcon, $editVisibilityIcon, $lineContent);
			}

			$result .= sprintf(self::TABLE_TEMPLATE, join('', $languageLines));
		}

		return $result;
	}

	/**
	 * Now the query gets enhanced by the "l18n_parent" pointer. This results in fetching
	 * not only L=0 and L=-1 records but native foreign language records as well.
	 *
	 * This method mainly gets used by grid elements.
	 *
	 * @param string $table Table name
	 * @param integer $id Page id (NOT USED! $this->pidSelect is used instead)
	 * @param string $addWhere Additional part for where clause
	 * @param string $fieldList Field list to select, * for all (for "SELECT [fieldlist] FROM ...")
	 * @return array Returns query array
	 * @todo Define visibility
	 */
	public function makeQueryArray($table, $id, $addWhere = '', $fieldList = '*') {

		if (!$this->validModuleConfig()) {
			return parent::makeQueryArray($table, $id, $addWhere, $fieldList);
		}

		$pattern = '%^ AND colPos\\s*=\\s*-1 AND tx_gridelements_container IN \\(\\d+(,\\s*\\d+)*\\) AND tx_gridelements_columns\\s*=\\s*\\d+ AND tt_content.deleted\\s*=\\s*0%ims';
		if (preg_match($pattern, $addWhere, $matches)) {
			return parent::makeQueryArray($table, $id, $matches[0] . ' ' . $this->getLanguageRestrictionWhereClause(), $fieldList);
		}

		return parent::makeQueryArray($table, $id, $addWhere, $fieldList);
	}

	/**
	 * Now the query gets enhanced by the "l18n_parent" pointer. This results in fetching
	 * not only L=0 and L=-1 records but native foreign language records as well.
	 *
	 * This method mainly gets used by the PageLayout itself.
	 *
	 * @param string $table UNUSED (will always be queried from tt_content)
	 * @param integer $id Page Id to be used (not used at all, but part of the API, see $this->pidSelect)
	 * @param array $columns colPos values to be considered to be shown
	 * @param string $additionalWhereClause Additional where clause for database select
	 * @return array Associative array for each column (colPos)
	 */
	protected function getContentRecordsPerColumn($table, $id, array $columns, $additionalWhereClause = '') {

		if (!$this->validModuleConfig()) {
			return parent::getContentRecordsPerColumn($table, $id, $columns, $additionalWhereClause);
		}

		if ($table !== 'table' || $this->getSelectedLanguage() === FALSE || $additionalWhereClause !== sprintf(' AND sys_language_uid IN (%d,-1)', $this->getSelectedLanguage())) {
			return parent::getContentRecordsPerColumn($table, $id, $columns, $additionalWhereClause);
		}

		return parent::getContentRecordsPerColumn($table, $id, $columns, ' AND l18n_parent = 0' . $this->getLanguageRestrictionWhereClause());
	}

	/**
	 * Pretty much like the $this->linkEditContent() method, this one
	 * wraps the given string property in an A tag pointing to the "localize"
	 * mechanism.
	 *
	 * @param string $str
	 * @param array $row
	 * @param int $languageId
	 */
	protected function linkLocalizeContent($str, $row, $languageId) {
		$params = '&cmd[tt_content][' . $row['uid'] . '][localize]=' . $languageId;
		$onClick = 'window.location.href=\'' . $this->getLinkToDataHandlerAction($params) . '\'; return false;';
		return sprintf('<a href="#" onclick="%s">%s</a>', htmlspecialchars($onClick), $str);
	}

	/**
	 * Returns the list of sys_language records of those languages the current
	 * page already has translations in place.
	 *
	 * @return array
	 */
	protected function getLanguagesForPage() {

		static $languages = array();

		if (!$languages) {
			if ($this->getSelectedLanguage() === 0) {
				/** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $beUser */
				$beUser = $GLOBALS['BE_USER'];
				foreach ($this->pageOverlays as $languageId => $pageOverlayRecord) {
					if ($beUser->checkLanguageAccess($languageId)) {
						$languages[$languageId] = $this->languageIconTitles[$languageId];
					}
				}

			} else {
				$languageId = $this->getSelectedLanguage();
				$languages[$languageId] = $this->languageIconTitles[$languageId];

			}
		}

		return $languages;

	}

	/**
	 * Returns the additional where clause limiting tt_content to l18n_parent
	 * as well as a couple of langauge ids.
	 *
	 * @return string
	 */
	protected function getLanguageRestrictionWhereClause() {

		$allowedLanguages = array(0, -1);

		foreach ($this->getLanguagesForPage() as  $languageId => $languageRecord) {
			$allowedLanguages[] = (int)$languageId;
		}

		return sprintf(' AND l18n_parent = 0 AND sys_language_uid IN (%s) ', join(',', $allowedLanguages));
	}

	/**
	 * Currently the module configuration is valid as soon as the selected
	 * function is "1", meaning "Columns" layout. This might chance if we
	 * introduce a distinct "Condensed" mode.
	 *
	 * @return bool
	 */
	protected function validModuleConfig() {

		/** @var \TYPO3\CMS\Backend\Controller\PageLayoutController $pageLayoutController */
		$pageLayoutController = $GLOBALS['SOBE'];
		if ((int)$pageLayoutController->MOD_SETTINGS['function'] !== 1) {
			/* Only the former "Columns" view gets adjusted. "Languages" and "QuickEdit" stay the way they are. */
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Returns the selected language of the current page layout view
	 *
	 * @return bool|int
	 */
	protected function getSelectedLanguage() {
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->tt_contentConfig['sys_language_uid'])) {
			return FALSE;

		} else {
			return (int)$this->tt_contentConfig['sys_language_uid'];

		}
	}

	/**
	 * This method returns TRUE if the given tt_content record is meant to be
	 * translated, otherwise FALSE.
	 *
	 * @param $row
	 * @return bool
	 */
	protected function allowLanguageNotificationLinesForRecord($row) {
		if ($row['l18n_parent']) {
			return FALSE;
		}
		if ($row['sys_language_uid'] > 0) {
			return FALSE;
		}

		foreach ($this->skipTranslations as $skipTranslation) {
			$matches = TRUE;
			foreach ($skipTranslation as $columnName => $options) {
				if (!in_array($row[$columnName], $options)) {
					$matches = FALSE;
				}
			}
			if ($matches) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	protected function getHideUnhideContent($row) {
		$out = '';
		$hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];

		if (
			$hiddenField && $GLOBALS['TCA']['tt_content']['columns'][$hiddenField]
			&& (!$GLOBALS['TCA']['tt_content']['columns'][$hiddenField]['exclude']
				|| $this->getBackendUser()->check('non_exclude_fields', 'tt_content:' . $hiddenField))
		) {
			if ($row[$hiddenField]) {
				$value = 0;
				$label = 'unHide';
			} else {
				$value = 1;
				$label = 'hide';
			}
			$params = '&data[tt_content][' . $row['uid'] . '][' . $hiddenField . ']=' . $value;
			$out = '<a href="' . htmlspecialchars($this->getLinkToDataHandlerAction($params)) . '" title="' . $this->getLanguageService()->getLL($label, TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-' . strtolower($label)) . '</a>';
		}
		return $out;
	}

	/**
	 * @param $params
	 * @return string
	 */
	protected function getLinkToDataHandlerAction($params) {
		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) > 7000000) {
			return \TYPO3\CMS\Backend\Utility\BackendUtility::getLinkToDataHandlerAction($params);
		} else {
			return $this->getPageLayoutController()->doc->issueCommand($params);
		}
	}

	/**
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected function getPageRenderer() {
		if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) > 7000000) {
			return $this->getPageLayoutController()->getModuleTemplate()->getPageRenderer();
		} else {
			return $this->getPageLayoutController()->doc->getPageRenderer();
		}
	}

}