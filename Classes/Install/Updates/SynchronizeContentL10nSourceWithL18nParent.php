<?php

namespace Netlogix\Nxcondensedbelayout\Install\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

class SynchronizeContentL10nSourceWithL18nParent extends AbstractUpdate
{
    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @var string
     */
    protected $title = 'Keep tt_content.l18n_parent in sync with tt_content.l10n_source';

    /**
     * {@inheritdoc}
     */
    public function checkForUpdate(&$description)
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

        $numberOfInvalidRecords = $result->fetchColumn(0);

        if ($numberOfInvalidRecords === 0) {
            return false;
        }

        $description = sprintf(
            'There are %d tt_content records where l18n_parent doesn\'t match l10n_source.',
            $numberOfInvalidRecords
        );
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function performUpdate(array &$dbQueries, &$customMessage)
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

        $dbQueries[] = $query->getSQL();

        return $query->execute();
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->getConnectionPool()
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
            ->createQueryBuilder();
    }

    protected function getConnectionPool(): ConnectionPool
    {
        if (!$this->connectionPool) {
            $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }
        return $this->connectionPool;
    }
}
