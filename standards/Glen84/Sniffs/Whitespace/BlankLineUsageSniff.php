<?php

declare(strict_types=1);

namespace Glen84\Sniffs\Whitespace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Glen84_Sniffs_Whitespace_BlankLineUsageSniff.
 *
 * Limits the usage of blank lines.
 */
class BlankLineUsageSniff implements Sniff
{
    /**
     * The maximum number of consecutive blank lines allowed.
     *
     * @var int
     */
    public $maxBlankLines = 1;

    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $start = $stackPtr + 1;

        // Find the position of each EOL sequence.
        while (
            // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Intentional.
            $pos = $phpcsFile->findNext(T_WHITESPACE, $start, null, false, $phpcsFile->eolChar)
        ) {
            // Count the number of EOL sequences directly following the current token.
            $innerStart = $pos + 1;
            $count = 0;

            while (
                $phpcsFile->findNext(
                    T_WHITESPACE,
                    $innerStart,
                    $innerStart + 1,
                    false,
                    $phpcsFile->eolChar
                )
            ) {
                ++$innerStart;
                ++$count;
            }

            // T_OPEN_TAG includes an EOL sequence for some reason, so add an extra one.
            if ($tokens[$pos - 1]['code'] === T_OPEN_TAG) {
                ++$count;
            }

            if ($count > $this->maxBlankLines) {
                $phpcsFile->addError(
                    'Expected no more than %d blank %s; %d found',
                    $innerStart - 1,
                    '',
                    [$this->maxBlankLines, $this->maxBlankLines === 1 ? 'line' : 'lines', $count]
                );
            }

            $start = $innerStart + 1;
        }
    }
}
