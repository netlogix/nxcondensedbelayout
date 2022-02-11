<?php
declare(strict_types=1);

namespace Netlogix\Nxcondensedbelayout\Xclass\CMS\Backend\View;

use Netlogix\Nxcondensedbelayout\Utilities;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    /**
     * @var ?Utilities\SkipTranslations
     */
    protected $skipTranslations;

    /**
     * @var ?Utilities\Translations
     */
    protected $translations;

    /**
     * @var bool
     */
    protected $enableCondensedMode = false;

    public function getSelectedLanguage(): int
    {
        return current($this->getSelectedLanguages()) ?? 0;
    }

    public function enableCondensedMode(): bool
    {
        return $this->enableCondensedMode;
    }

    protected function initialize()
    {
        $this->enableCondensedMode =
            $this->tt_contentConfig['languageCols'] === 0
            && $this->tt_contentConfig['languageMode'] === 0
            && $this->tt_contentConfig['languageColsPointer'] === 0;

        $this->forceEnabledLanguageModeToColumnsAndResetSelectedColumns();

        parent::initialize();

        $this->initializeTranslations();
        $this->applyFrontend();
    }

    /**
     * Allow not only specific languages but every content which is not a translation of another content.
     *
     * @inheritDoc
     */
    protected function getContentRecordsPerColumn($table, $id, array $columns, $additionalWhereClause = '')
    {
        if ($this->enableCondensedMode) {
            $additionalWhereClause = \str_replace(
                '`sys_language_uid` IN (' . $this->getSelectedLanguage() . ', -1)',
                '`l18n_parent` = 0',
                $additionalWhereClause
            );
        }

        return parent::getContentRecordsPerColumn($table, $id, $columns, $additionalWhereClause);
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

        if ($this->enableCondensedMode && !$this->tt_contentConfig['languageMode']) {
            $language = $this->languageSelector($id);
            $content = $language . $content;
        }

        return $content;
    }

    /**
     * Draw additional language lines per content to either translate or show existing translations
     *
     * @inheritDoc
     */
    public function tt_content_drawItem($row)
    {
        $result = parent::tt_content_drawItem($row);;

        if (!$this->enableCondensedMode) {
            return $result;
        }

        $contentUid = (int)$row['uid'];
        $pageId = (int)$row['pid'];

        $siteLanguages = $this->translations->getAvailableTranslationsForPage($pageId);
        $siteLanguages = \array_filter($siteLanguages);
        if (!$siteLanguages) {
            return $result;
        }

        $translations = $this->translations->getAvailableTranslationsForContent($contentUid);
        if ($this->skipTranslations->showTranslationLines($row)) {
            $languageLines = [];

            $lineCounter = 0;
            foreach ($siteLanguages as $language) {
                assert($language instanceof SiteLanguage);
                $languageId = $language->getLanguageId();
                if ($languageId <= 0) {
                    continue;
                }
                $lineCounter = ($lineCounter + 1) % 2;

                if ($translations[$languageId]) {
                    $link = fn(string $content) => $this->linkEditContent($content, $translations[$languageId]);

                    $lineContent = BackendUtility::getRecordTitle('tt_content', $translations[$languageId]);
                    $lineContent = $link($lineContent);

                    $languageIcon = $this->languageFlag($languageId, false);
                    $languageIcon = $link($languageIcon);

                    $buttonIcon = $this->iconFactory
                        ->getIcon('actions-document-open', Icon::SIZE_SMALL)
                        ->render();
                    $buttonIcon = $link($buttonIcon);

                    $editVisibilityIcon = $this->getHideUnhideContent($translations[$languageId]);
                    $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
                    $class = 'edit-existing-record' . ($translations[$languageId][$hiddenField] ? ' t3-page-ce t3-page-ce-hidden' : '');
                } else {

                    $link = fn(string $content) => $this->linkLocalizeContent($content, $row, $languageId);

                    $lineContent = $this->getLanguageService()->sL('LLL:EXT:nxcondensedbelayout/Resources/Private/Language/Backend.xlf:tt_content.createTranslation');
                    $lineContent = $link($lineContent);

                    $languageIcon = $this->languageFlag($languageId, false);
                    $languageIcon = $link($languageIcon);

                    $buttonIcon = $this->iconFactory
                        ->getIcon('actions-page-new', Icon::SIZE_SMALL)
                        ->render();
                    $buttonIcon = $link($buttonIcon);

                    $editVisibilityIcon = '';
                    $class = 'create-new-translation';
                }

                $languageLines[] = vsprintf(
                    self::ROW_TEMPLATE,
                    [
                        ($lineCounter + 3),
                        $class,
                        $languageIcon,
                        $buttonIcon,
                        $editVisibilityIcon,
                        $lineContent
                    ]
                );
            }

            $result .= sprintf(self::TABLE_TEMPLATE, join('', $languageLines));
        } elseif (count($translations)) {
            // TODO: This element has a translation although it should not. Make visible.
        }

        return $result;
    }

    protected function forceEnabledLanguageModeToColumnsAndResetSelectedColumns(): void
    {
        if (!$this->enableCondensedMode) {
            return;
        }

        // Enabled language mode adds the "create translation for this page"
        $this->tt_contentConfig['languageMode'] = 0;
        // Only show default language column
        $this->tt_contentConfig['languageCols'] = [];
        $this->tt_contentConfig['languageColsPointer'] = 0;
    }

    protected function initializeTranslations(): void
    {
        $this->skipTranslations = GeneralUtility::makeInstance(
            Utilities\SkipTranslations::class
        );

        $this->translations = GeneralUtility::makeInstance(
            Utilities\Translations::class,
            ... \array_values($this->siteLanguages)
        );
    }

    protected function applyFrontend(): void
    {
        if (!$this->enableCondensedMode) {
            return;
        }

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssInlineBlock(
            'nxcondensedbelayout-languages',
            '.t3-page-ce .t3-row-header .ce-icons, .t3-page-ce .t3-row-header .ce-icons-left {visibility: visible !important;}'
        );
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Nxcondensedbelayout/ScrollService');
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

    protected function linkLocalizeContent($str, $row, $languageId)
    {
        $params = '&cmd[tt_content][' . $row['uid'] . '][localize]=' . $languageId;
        $onClick = 'window.location.href=\'' . BackendUtility::getLinkToDataHandlerAction($params) . '\'; return false;';
        return sprintf('<a href="#" onclick="%s">%s</a>', htmlspecialchars($onClick), $str);
    }

    protected function languageFlag(int $language): string
    {
        $language = $this->siteLanguages[$language] ?? null;
        $flagIdentifier = $language ? $language->getFlagIdentifier() : 'default-not-found';

        return $this
            ->iconFactory
            ->getIcon(
                $flagIdentifier,
                Icon::SIZE_SMALL
            )
            ->render();
    }
}
