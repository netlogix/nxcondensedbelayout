<?php

namespace Netlogix\Nxcondensedbelayout\Hooks\BackendController;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Just add the required JS file for scrollpos handling
 */
class PositionService implements \TYPO3\CMS\Core\SingletonInterface
{
	/**
	 * @param array $hookConfiguration
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendController
	 */
	public function includeJavaScript($hookConfiguration, $backendController)
	{
		$backendController->addJavascriptFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nxcondensedbelayout') . 'Resources/Public/Scripts/PositionService.min.js');
	}

}