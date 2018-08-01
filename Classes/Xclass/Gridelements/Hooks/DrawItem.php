<?php

namespace Netlogix\Nxcondensedbelayout\Xclass\Gridelements\Hooks;

use GridElementsTeam\Gridelements\Hooks as Gridelements;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;

class DrawItem extends Gridelements\DrawItem
{
	/**
	 * @inheritdoc
	 */
	protected function collectItemsForColumns(PageLayoutView $parentObject, &$colPosValues, &$row)
	{
		if (!$parentObject instanceof \Netlogix\Nxcondensedbelayout\Xclass\CMS\Backend\View\PageLayoutView || !$parentObject->validModuleConfig()) {
			return parent::collectItemsForColumns($parentObject, $colPosValues, $row);
		}

		$colPosList = array_keys($colPosValues);
		$specificIds = $this->helper->getSpecificIds($row);

		$query = $this->getQueryBuilder();
		$expr = $query->expr();

		$constraints = [
			$expr->eq('pid', $query->createNamedParameter($row['pid'], \PDO::PARAM_INT)),
			$expr->eq('colPos', $query->createNamedParameter(-1, \PDO::PARAM_INT)),
			$expr->in('tx_gridelements_container',
				$query->createNamedParameter([(int)$row['uid'], $specificIds['uid']], Connection::PARAM_INT_ARRAY)),
			$expr->in('tx_gridelements_columns',
				$query->createNamedParameter($colPosList, Connection::PARAM_INT_ARRAY)),
			$expr->eq('l18n_parent', 0)
		];

		$language = (int)$parentObject->tt_contentConfig['sys_language_uid'];
		if ($language) {
			$constraints[] = $expr->in('sys_language_uid',
				$query->createNamedParameter([-1, 0, $language], Connection::PARAM_INT_ARRAY));
		}

		if ($this->helper->getBackendUser()->workspace > 0 && $row['t3ver_wsid'] > 0) {
			$constraints[] = $expr->eq('t3ver_wsid',
				$query->createNamedParameter((int)$row['t3ver_wsid'], \PDO::PARAM_INT));
		}

		$query
			->select('*')
			->from('tt_content')
			->where(
				...$constraints
			)
			->orderBy('sorting');

		$restrictions = $query->getRestrictions();
		if ($this->showHidden) {
			$restrictions->removeByType(HiddenRestriction::class);
		}
		$restrictions->removeByType(StartTimeRestriction::class);
		$restrictions->removeByType(EndTimeRestriction::class);
		$query->setRestrictions($restrictions);

		return $query->execute()->fetchAll();
	}
}