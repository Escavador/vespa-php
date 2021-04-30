<?php

declare(strict_types=1);

namespace Glen84\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Glen84_Sniffs_Operators_ComparisonOperatorUsageSniff.
 *
 * A Sniff to enforce the use of IDENTICAL type operators rather than EQUAL operators.
 */
class ComparisonOperatorUsageSniff implements Sniff
{
    /**
     * A list of invalid operators with their alternatives.
     */
    private static $invalidOps = [
        T_IS_EQUAL => '===',
        T_IS_NOT_EQUAL => '!=='
    ];

    public function register(): array
    {
        return [T_IS_EQUAL, T_IS_NOT_EQUAL];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $token = $tokens[$stackPtr];

        $error = 'Operator %s prohibited; use %s instead';
        $data = [$token['content'], self::$invalidOps[$token['code']]];

        $phpcsFile->addError($error, $stackPtr, 'NotAllowed', $data);
    }
}
