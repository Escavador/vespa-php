<?php

declare(strict_types=1);

namespace Glen84\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Glen84_Sniffs_Namespaces_NamespaceDeclarationSniff.
 *
 * Ensures namespaces are declared correctly.
 */
class NamespaceDeclarationSniff implements Sniff
{
    public function register(): array
    {
        return [T_NAMESPACE];
    }

    /**
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // Find the position of the first non-whitespace token before the namespace declaration.
        $firstNonWsPos = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);

        $tokens = $phpcsFile->getTokens();

        $diff = $tokens[$stackPtr]['line'] - $tokens[$firstNonWsPos]['line'];

        if ($diff === 2) {
            return;
        }

        $error = 'There must be one blank line before the namespace declaration';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'BlankLineBefore');

        if ($fix) {
            if ($diff < 2) {
                // Add extra newlines.
                for ($i = 0; $i < 2 - $diff; $i++) {
                    $phpcsFile->fixer->addNewlineBefore($stackPtr);
                }
            } else {
                // Remove extra newlines.
                for ($i = $stackPtr - 1; $i > $firstNonWsPos; $i--) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }
        }
    }
}
