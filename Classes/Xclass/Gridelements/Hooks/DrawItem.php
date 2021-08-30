<?php

namespace Netlogix\Nxcondensedbelayout\Xclass\Gridelements\Hooks;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GridElementsTeam\Gridelements\Hooks as Gridelements;
use Netlogix\Nxcondensedbelayout\Xclass\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutView as CorePageLayoutView;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DrawItem extends Gridelements\DrawItem
{
    protected function renderSingleGridColumn(
        CorePageLayoutView $parentObject,
        &$items,
        &$colPos,
        $values,
        &$gridContent,
        $row,
        &$editUidList
    ) {
        parent::renderSingleGridColumn(
            $parentObject,
            $items,
            $colPos,
            $values,
            $gridContent,
            $row,
            $editUidList
        );
        /**
         * This prevents the JS part of gridelements from changing content element
         * languages when moving between drop zones.
         * Would be even better if content elements could not be dropped into a drop
         * zone of an element that is not language all or does not match the given
         * language but that would require internal changes to the gridelements js
         * file.
         */
        $gridContent[$colPos] = \preg_replace(
            '%data-language-uid="-?\d+"%im',
            'data-language-uid="-1"',
            $gridContent[$colPos]
        );
    }

    /**
     * @inheritdoc
     */
    protected function collectItemsForColumns(CorePageLayoutView $parentObject, &$colPosValues, &$row)
    {
        if (!($parentObject instanceof PageLayoutView) || !$parentObject->enableCondensedMode()) {
            return parent::collectItemsForColumns(
                $parentObject,
                $colPosValues,
                $row
            );
        }

        $colPosList = array_keys($colPosValues);
        $specificIds = $this->helper->getSpecificIds($row);

        $query = $this->getQueryBuilder();
        $expr = $query->expr();

        $constraints = [
            $expr->in(
                'pid',
                $query->createNamedParameter(
                    [(int)$row['pid'], $specificIds['pid']],
                    Connection::PARAM_INT_ARRAY
                )
            ),
            $expr->eq('colPos', $query->createNamedParameter(-1, \PDO::PARAM_INT)),
            $expr->notIn(
                'uid',
                $query->createNamedParameter(
                    [(int)$row['uid'], $specificIds['uid']],
                    Connection::PARAM_INT_ARRAY
                )
            ),
            $expr->in(
                'tx_gridelements_container',
                $query->createNamedParameter(
                    [(int)$row['uid'], $specificIds['uid']],
                    Connection::PARAM_INT_ARRAY
                )
            ),
            $expr->in(
                'tx_gridelements_columns',
                $query->createNamedParameter($colPosList, Connection::PARAM_INT_ARRAY)
            ),
            $expr->eq('l18n_parent', 0)
        ];

        $language = (int)$parentObject->tt_contentConfig['sys_language_uid'];
        if ($language) {
            $constraints[] = $expr->in(
                'sys_language_uid',
                $query->createNamedParameter(
                    [-1, 0, $language],
                    Connection::PARAM_INT_ARRAY
                )
            );
        }

        if ($this->helper->getBackendUser()->workspace > 0 && $row['t3ver_wsid'] > 0) {
            $constraints[] = $expr->eq(
                't3ver_wsid',
                $query->createNamedParameter(
                    (int)$row['t3ver_wsid'],
                    \PDO::PARAM_INT
                )
            );
        }

        $query
            ->select('*')
            ->from('tt_content')
            ->where(
                ...$constraints
            )
            ->orderBy('sorting');

        $restrictions = $query->getRestrictions();
        $restrictions
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class)
            ->add(GeneralUtility::makeInstance(
                WorkspaceRestriction::class,
                (int)$this->helper->getBackendUser()->workspace)
            );
        if ($this->showHidden) {
            $restrictions->removeByType(HiddenRestriction::class);
        }

        return $query
            ->execute()
            ->fetchAllAssociative();
    }
}
