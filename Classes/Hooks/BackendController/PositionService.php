<?php

namespace Netlogix\Nxcondensedbelayout\Hooks\BackendController;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Just add the required JS file for scrollpos handling
 */
class PositionService implements SingletonInterface
{
    public function includeJavaScript()
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        assert($pageRenderer instanceof PageRenderer);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Nxcondensedbelayout/PositionService');
    }
}
