<?php

namespace Netlogix\Nxcondensedbelayout\Hooks\DataHandler;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Incoming DataHandler calls to save both, TYPO3 core column positioning,
 * gridelements positioning  and languages of tt_content records is adjusted
 * to not touch the language setting.
 */
class SkipLanguageChangingOnMove implements SingletonInterface
{
	protected $requiredUpdateFields = [
		'colPos',
		'tx_gridelements_container',
		'tx_gridelements_columns',
		'sys_language_uid'
	];

	public function guardLanguageRequestParameterForTtContent()
	{
		if (!is_array(GeneralUtility::_GET('data'))) {
			return;
		}
		$data = GeneralUtility::_GET('data');
		if (count($data) !== 1) {
			return;
		}
		if (!array_key_exists('tt_content', $data) || !is_array($data['tt_content'])) {
			return;
		}
		if (count($data['tt_content']) !== 1) {
			return;
		}
		$recordId = key($data['tt_content']);
		$keys = array_intersect_key($data['tt_content'][$recordId], array_flip($this->requiredUpdateFields));
		if (count($keys) !== count($this->requiredUpdateFields)) {
			return;
		}
		unset($data['tt_content'][$recordId]['sys_language_uid']);
		GeneralUtility::_GETset($data, 'data');
	}
}