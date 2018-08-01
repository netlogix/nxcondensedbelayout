<?php

namespace Netlogix\Nxcondensedbelayout\Hooks\BackendController;

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