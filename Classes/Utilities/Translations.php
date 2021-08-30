<?php
declare(strict_types=1);

namespace Netlogix\Nxcondensedbelayout\Utilities;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Translations
{
    /**
     * @var array<int, array<int, SiteLanguage>>
     */
    protected $cache = [];

    /**
     * @var array<int, SiteLanguage>
     */
    protected $siteLanguages = [];

    public function __construct(SiteLanguage ...$siteLanguages)
    {
        foreach ($siteLanguages as $siteLanguage) {
            $this->siteLanguages[$siteLanguage->getLanguageId()] = $siteLanguage;
        }
    }

    /**
     * @param int $pageId
     * @return SiteLanguage[]
     * @throws \Doctrine\DBAL\Driver\Exception
     * @see \TYPO3\CMS\Backend\View\PageLayoutView::languageSelector()
     */
    public function getAvailableTranslationsForPage(int $pageId): array
    {
        if (\array_key_exists($pageId, $this->cache)) {
            return $this->cache[$pageId];
        }

        $availableTranslations = [];
        $this->cache[$pageId] = &$availableTranslations;

        if (!$this->getBackendUser()->check('tables_modify', 'pages')) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $restrictions = $queryBuilder->getRestrictions();
        $restrictions
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));

        $queryBuilder
            ->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            );
        $statement = $queryBuilder->execute();

        foreach ($statement->fetchAllAssociative() as $row) {
            $languageUid = (int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
            $availableTranslations[$languageUid] = $this->siteLanguages[$languageUid];
        }

        return $availableTranslations;
    }

    /**
     * @param $contentUid
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableTranslationsForContent($contentUid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        $translationRows = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('l18n_parent',
                $queryBuilder->createNamedParameter($contentUid, \PDO::PARAM_INT)))
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
