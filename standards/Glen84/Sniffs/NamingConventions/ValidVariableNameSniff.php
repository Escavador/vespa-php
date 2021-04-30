<?php

declare(strict_types=1);

namespace Glen84\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Common;

/**
 * Glen84_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * Checks the naming of variables.
 */
class ValidVariableNameSniff implements Sniff
{
    /** @var string[] */
    private static $phpReservedVars = [
        '_SERVER',
        '_GET',
        '_POST',
        '_REQUEST',
        '_SESSION',
        '_ENV',
        '_COOKIE',
        '_FILES',
        'GLOBALS',
        'http_response_header',
        'HTTP_RAW_POST_DATA',
        'php_errormsg'
    ];

    public function register(): array
    {
        return [T_VARIABLE];
    }

    /**
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];
        $varName = substr($content, 1);

        // Allow $_GET, $_POST, etc.
        if (in_array($varName, self::$phpReservedVars)) {
            return;
        }

        // Ensure that variables are in camel caps format.
        if (!Common::isCamelCaps($varName, false, true, true)) {
            $error = 'Variable "%s" is not in valid camel caps format';
            $phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', [$content]);
        }
    }
}
