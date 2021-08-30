<?php
declare(strict_types=1);

namespace Netlogix\Nxcondensedbelayout\Utilities;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class SkipTranslations
{
    /**
     * @var array<array<string, array<string>>>
     */
    protected $skipTranslationPatterns = [];

    public function __construct()
    {
        $config = BackendUtility::getPagesTSconfig(0);
        $config = (new TypoScriptService())->convertTypoScriptArrayToPlainArray($config);
        $skipTranslationPatterns = ObjectAccess::getPropertyPath($config, 'mod.web_layout.skipTranslations');
        foreach ($skipTranslationPatterns as $skipTranslationPattern) {
            $this->skipTranslationPatterns[] = array_map(
                function (string $options) {
                    return GeneralUtility::trimExplode(',', $options, true);
                },
                $skipTranslationPattern
            );
        }
    }

    public function showTranslationLines(array $row): bool
    {
        if (self::isTranslationRecord($row)) {
            return false;
        }
        if (!self::isDefaultLanguageRecord($row)) {
            return false;
        }

        foreach ($this->skipTranslationPatterns as $translationPattern) {
            if (self::matchesSkipTranslationPattern($row, $translationPattern)) {
                return false;
            }
        }

        return true;
    }

    protected static function isTranslationRecord(array $row): bool
    {
        return (bool)($row['l18n_parent'] ?? false);
    }

    protected static function isDefaultLanguageRecord(array $row): bool
    {
        return ($row['sys_language_uid'] ?? 0) === 0;
    }

    protected static function matchesSkipTranslationPattern(array $row, array $translationPattern): bool
    {
        foreach ($translationPattern as $columnName => $options) {
            if (!in_array($row[$columnName], $options)) {
                return false;
            }
        }
        return true;
    }
}
