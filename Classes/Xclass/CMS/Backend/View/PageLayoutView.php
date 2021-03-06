<?php

namespace Netlogix\Nxcondensedbelayout\Xclass\CMS\Backend\View;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * The backend page layout now displays all available languages in one
 * condensed mode.
 */
class PageLayoutView extends \TYPO3\CMS\Backend\View\PageLayoutView
{

	const TABLE_TEMPLATE = '<table class="t3-table table table-hover table-striped nxcondensedbelayout-languages">%s</table>';

	const ROW_TEMPLATE = '
		<tr class="bgColor%d %s">
			<td width="20px">%s</td>
			<td width="20px">%s</td>
			<td width="20px">%s</td>
			<td>%s</td>
		</tr>';

	const POSITION_RUNNER = /** @lang JavaScript */
<<<'JavaScript'
	(function() {
		window.addEventListener('DOMContentLoaded', function() {
			try {
				var Ext = window.parent.Ext;
				var namespace = Ext.ns("TYPO3.Netlogix.Nxcondensedbelayout");
				var positionService = namespace.positionService;
				if (!positionService) {
					return;
				}

				var run = function() {
					var body = Ext.fly(document.querySelector('body div.module-body'));
					var currentUrl = window.location.href.split("#")[0];
					positionService.run(body, currentUrl);
				};
				Ext.isReady ? run() : Ext.onReady(run);

			} catch (e) {
				console.error('Could not register Nxcondensedbelayout.positionService.', e);
			}
		});
	})();
JavaScript;

	protected $skipTranslations = [];

	/**
	 * The "Columns" view now brings the "Make new translation of this
	 * page" feature. We need to switch languageMode on to enforce those
	 * UI elements but overrule the "languageCols" to empty to avoid
	 * additional language columns.
	 */
	public function __construct()
	{
		parent::__construct();

		$pageLayoutController = $this->getPageLayoutController();

		if (!$pageLayoutController instanceof PageLayoutController) {
			return false;
		}

		$language = (int)$pageLayoutController->current_sys_language;
		if ($this->validModuleConfig() && $language <= 0) {
			$this->tt_contentConfig['languageMode'] = 0;
			$this->tt_contentConfig['languageCols'] = [];
		}

		$skipTranslations = (array)$this->getBackendUser()->getTSConfig('mod.web_layout.skipTranslations',
			BackendUtility::getPagesTSconfig((int)$pageLayoutController->id))['properties'];
		foreach ($skipTranslations as $skipTranslation) {
			foreach ($skipTranslation as $columnName => $options) {
				$skipTranslation[$columnName] = GeneralUtility::trimExplode(',', $options);
			}
			$this->skipTranslations[] = $skipTranslation;
		}

		$pageRenderer = $this->getPageRenderer();
		$pageRenderer->addCssInlineBlock('nxcondensedbelayout-languages',
			'.t3-page-ce .t3-row-header .ce-icons, .t3-page-ce .t3-row-header .ce-icons-left {visibility: visible !important;}');
		$pageRenderer->addJsInlineCode(__CLASS__, self::POSITION_RUNNER);

	}

	/**
	 * Renders Content Elements from the tt_content table from page id
	 *
	 * @param int $id Page id
	 * @return string HTML for the listing
	 */
	public function getTable_tt_content($id)
	{
		$content = parent::getTable_tt_content($id);
		$language = $this->languageSelector($id);
		return $language . $content;
	}

	/**
	 * Draws the preview content for a content element
	 *
	 * @param array $row Content element
	 * @return string HTML
	 * @throws \UnexpectedValueException
	 */
	public function tt_content_drawItem($row)
	{

		if (!$this->validModuleConfig()) {
			return parent::tt_content_drawItem($row);
		}

		$result = parent::tt_content_drawItem($row);
		$translations = $this->getTranslationRecords($row['uid']);
		if ($this->allowLanguageNotificationLinesForRecord($row)) {
			$languageLines = [];

			$lineCounter = 0;
			foreach ($this->getLanguagesForPage() as $languageId => $languageIconTitle) {
				$lineCounter = ($lineCounter + 1) % 2;

				$editVisibilityIcon = '';
				if ($translations[$languageId]) {
					$lineContent = $this->linkEditContent(BackendUtility::getRecordTitle('tt_content',
						$translations[$languageId]), $translations[$languageId]);
					$languageIcon = $this->linkEditContent($this->languageFlag($languageId, false),
						$translations[$languageId]);
					$buttonIcon = $this->linkEditContent($this->iconFactory->getIcon('actions-document-open', 'small'),
						$translations[$languageId]);
					$editVisibilityIcon = $this->getHideUnhideContent($translations[$languageId]);
					$hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
					$class = 'edit-existing-record' . ($translations[$languageId][$hiddenField] ? ' t3-page-ce t3-page-ce-hidden' : '');
				} else {
					$lineContent = $this->linkLocalizeContent($this->getLanguageService()->sL('LLL:EXT:nxcondensedbelayout/Resources/Private/Language/Backend.xlf:tt_content.createTranslation'),
						$row, $languageId);
					$languageIcon = $this->linkLocalizeContent($this->languageFlag($languageId, false), $row,
						$languageId);
					$buttonIcon = $this->linkLocalizeContent(GeneralUtility::makeInstance('TYPO3\CMS\Core\Imaging\IconFactory')->getIcon('actions-page-new',
						\TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL), $row, $languageId);
					$class = 'create-new-translation';
				}

				$languageLines[] = sprintf(self::ROW_TEMPLATE, ($lineCounter + 3), $class, $languageIcon, $buttonIcon,
					$editVisibilityIcon, $lineContent);
			}

			$result .= sprintf(self::TABLE_TEMPLATE, join('', $languageLines));
		} elseif (count($translations)) {
			// TODO: This element has a translation although it should not. Make visible.
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
	public function makeQueryArray($table, $id, $addWhere = '', $fieldList = '*')
	{

		if (!$this->validModuleConfig()) {
			return parent::makeQueryArray($table, $id, $addWhere, $fieldList);
		}

		$pattern = '%^ AND colPos\\s*=\\s*-1 AND tx_gridelements_container IN \\(\\d+(,\\s*\\d+)*\\) AND tx_gridelements_columns\\s*=\\s*\\d+ AND (.*)tt_content.deleted\\s*=\\s*0%ims';
		if (preg_match($pattern, $addWhere, $matches)) {
			return parent::makeQueryArray($table, $id, $matches[0] . ' ' . $this->getLanguageRestrictionWhereClause(),
				$fieldList);
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
	protected function getContentRecordsPerColumn($table, $id, array $columns, $additionalWhereClause = '')
	{

		if (!$this->validModuleConfig()) {
			return parent::getContentRecordsPerColumn($table, $id, $columns, $additionalWhereClause);
		}

		if ($table !== 'table' || $this->getSelectedLanguage() === null || $additionalWhereClause !== sprintf('`sys_language_uid` IN (%d, -1)',
				$this->getSelectedLanguage())) {
			return parent::getContentRecordsPerColumn($table, $id, $columns, $additionalWhereClause);
		}

		return parent::getContentRecordsPerColumn($table, $id, $columns,
			'l18n_parent = 0' . $this->getLanguageRestrictionWhereClause());
	}

	/**
	 * Pretty much like the $this->linkEditContent() method, this one
	 * wraps the given string property in an A tag pointing to the "localize"
	 * mechanism.
	 *
	 * @param string $str
	 * @param array $row
	 * @param int $languageId
	 * @return string
	 */
	protected function linkLocalizeContent($str, $row, $languageId)
	{
		$params = '&cmd[tt_content][' . $row['uid'] . '][localize]=' . $languageId;
		$onClick = 'window.location.href=\'' . BackendUtility::getLinkToDataHandlerAction($params) . '\'; return false;';
		return sprintf('<a href="#" onclick="%s">%s</a>', htmlspecialchars($onClick), $str);
	}

	/**
	 * Returns the list of sys_language records of those languages the current
	 * page already has translations in place.
	 *
	 * @return array
	 */
	protected function getLanguagesForPage()
	{

		static $languages = [];

		if (!$languages) {
			if ($this->getSelectedLanguage() === 0) {
				$beUser = $this->getBackendUser();
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
	protected function getLanguageRestrictionWhereClause()
	{

		$allowedLanguages = [0, -1];

		foreach ($this->getLanguagesForPage() as $languageId => $languageRecord) {
			$allowedLanguages[] = (int)$languageId;
		}

		return sprintf(' AND l18n_parent = 0 AND sys_language_uid IN (%s)', join(',', $allowedLanguages));
	}

	/**
	 * Currently the module configuration is valid as soon as the selected
	 * function is "1", meaning "Columns" layout. This might chance if we
	 * introduce a distinct "Condensed" mode.
	 *
	 * @return bool
	 */
	public function validModuleConfig()
	{
		$pageLayoutController = $this->getPageLayoutController();
		if ((int)$pageLayoutController->MOD_SETTINGS['function'] !== 1) {
			/* Only the former "Columns" view gets adjusted. "Languages" and "QuickEdit" stay the way they are. */
			return false;
		}

		return true;
	}

	/**
	 * Returns the selected language of the current page layout view
	 *
	 * @return bool|int
	 */
	public function getSelectedLanguage()
	{
		if (!MathUtility::canBeInterpretedAsInteger($this->tt_contentConfig['sys_language_uid'])) {
			return null;

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
	protected function allowLanguageNotificationLinesForRecord($row)
	{
		if ($row['l18n_parent']) {
			return false;
		}
		if ($row['sys_language_uid'] !== 0) {
			return false;
		}

		foreach ($this->skipTranslations as $skipTranslation) {
			$matches = true;
			foreach ($skipTranslation as $columnName => $options) {
				if (!in_array($row[$columnName], $options)) {
					$matches = false;
				}
			}
			if ($matches) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	protected function getHideUnhideContent($row)
	{
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
			$icon = $this->iconFactory->getIcon('actions-edit-' . strtolower($label), 'small');
			$title = $this->getLanguageService()->getLL($label);
			$out = '<a href="' . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction($params)) . '" title="' . $title . '">' . $icon . '</a>';
		}
		return $out;
	}

	/**
	 * @param int $sourceUid
	 * @return array
	 */
	protected function getTranslationRecords($sourceUid)
	{
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
		$queryBuilder->getRestrictions()
			->removeAll()
			->add(GeneralUtility::makeInstance(DeletedRestriction::class))
			->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

		$translationRows = $queryBuilder
			->select('*')
			->from('tt_content')
			->where($queryBuilder->expr()->eq('l18n_parent', $queryBuilder->createNamedParameter($sourceUid, \PDO::PARAM_INT)))
			->orderBy('uid', 'ASC')
			->execute()->fetchAll();

		$translations = [];
		if ($translationRows) {
			foreach ($translationRows as $translationRow) {
				// This "if" is an actual "limit 1 per language"
				if (!isset($translations[$translationRow['sys_language_uid']])) {
					$translations[$translationRow['sys_language_uid']] = $translationRow;
				}
			}
		}
		return $translations;
	}

	/**
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected function getPageRenderer()
	{
		return $this->getPageLayoutController()->getModuleTemplate()->getPageRenderer();
	}

}
