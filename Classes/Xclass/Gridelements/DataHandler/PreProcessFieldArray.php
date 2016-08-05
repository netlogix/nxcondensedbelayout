<?php
namespace Netlogix\Nxcondensedbelayout\Xclass\Gridelements\DataHandler;

/*
 * This file is part of the Netlogix.Nxcondensedbelayout extension.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GridElementsTeam\Gridelements\DataHandler as GridElements;

/**
 * Even if gridelements tries to overrule a tt_contents langauge according
 * to the surrounding container, we don't let it to!
 */
class PreProcessFieldArray extends GridElements\PreProcessFieldArray
{
    const SYS_LANGUAGE_UID = 'sys_language_uid';

    /**
     * @param array $fieldArray
     * @return void
     */
    public function setFieldEntriesForGridContainers(array &$fieldArray)
    {
        $language = array_key_exists(self::SYS_LANGUAGE_UID, $fieldArray) ? $fieldArray[self::SYS_LANGUAGE_UID] : null;
        parent::setFieldEntriesForGridContainers($fieldArray);
        if (is_null($language)) {
            unset($fieldArray[self::SYS_LANGUAGE_UID]);
        } else {
            $fieldArray[self::SYS_LANGUAGE_UID] = $language;
        }
    }
}
