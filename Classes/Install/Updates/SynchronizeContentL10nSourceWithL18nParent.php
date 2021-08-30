<?php

namespace Netlogix\Nxcondensedbelayout\Install\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class SynchronizeContentL10nSourceWithL18nParent implements UpgradeWizardInterface, RepeatableInterface
{
    const IDENTIFIER = 'SynchronizeContentL10nSourceWithL18nParent';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getTitle(): string
    {
        return 'Keep tt_content.l18n_parent in sync with tt_content.l10n_source';
    }

    public function getDescription(): string
    {
        return join(' ', [
            'TYPO3 has two different modes of operation when it comes to translations.',
            'One is the "connected mode", which means a default language content has translations.',
            'The other one is the "free mode", which means foreign language content is not a translation but free standing.',
            'The difference is:',
            'In connected mode, when a content moves in terms of sorging or colPos, all translations move along.',
            'In free mode, when a content moves it moves without corresponding foreing language contents.',
            'The property l18n_parent connects a content to translation to its translation parent for connected mode behavior.',
            'The property l10n_source only indicates corresponding content but leaves both elements in free mode.' .
            'And of course there is the "mixed mode", which means there\'s both of them on one page.',
            'Since editors usually never want free mode at all this update syncs l18n_parent and l10n_source properties',
        ]);
    }

    public function executeUpdate(): bool
    {
        $query = $this->getQueryBuilder();
        $exp = $query->expr();

        $query
            ->update('tt_content')
            ->set('l10n_source', 'l18n_parent', false)
            ->where(
                $exp->gt('sys_language_uid', 0),
                $exp->eq('deleted', 0),
                $exp->neq(
                    'l18n_parent',
                    $query->getConnection()->quoteIdentifier('l10n_source')
                )
            );

        return !!$query->execute();
    }

    public function updateNecessary(): bool
    {
        $query = $this->getQueryBuilder();
        $exp = $query->expr();

        $result = $query
            ->count('*')
            ->from('tt_content')
            ->where(
                $exp->gt('sys_language_uid', 0),
                $exp->eq('deleted', 0),
                $exp->neq(
                    'l18n_parent',
                    $query->getConnection()->quoteIdentifier('l10n_source')
                )
            )
            ->execute();

        $numberOfInvalidRecords = $result->fetchOne(0);

        if ($numberOfInvalidRecords === 0) {
            return false;
        }

        return true;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->createQueryBuilder();
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
