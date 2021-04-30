<?php

declare(strict_types=1);

namespace Glen84\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Glen84_Sniffs_Arrays_ArrayDeclarationSniff.
 *
 * Ensures that arrays are correctly formatted. Complements Squiz.Arrays.ArrayDeclaration.
 */
class ArrayDeclarationSniff implements Sniff
{
    public function register(): array
    {
        return [T_ARRAY, T_OPEN_SHORT_ARRAY];
    }

    /**
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_ARRAY) {
            $arrayStart = $tokens[$stackPtr]['parenthesis_opener'];

            if (!isset($tokens[$arrayStart]['parenthesis_closer'])) {
                return;
            }

            $arrayEnd = $tokens[$arrayStart]['parenthesis_closer'];
        } else {
            $arrayStart = $stackPtr;
            $arrayEnd = $tokens[$stackPtr]['bracket_closer'];
        }

        if ($tokens[$arrayStart]['line'] === $tokens[$arrayEnd]['line']) {
            $this->processSingleLineArray($phpcsFile, $arrayStart, $arrayEnd);
        } else {
            $this->processMultiLineArray($phpcsFile, $arrayStart, $arrayEnd);
        }
    }

    /**
     * Processes a single-line array definition.
     *
     * @param File $phpcsFile  The current file being checked.
     * @param int  $arrayStart The token that starts the array definition.
     * @param int  $arrayEnd   The token that ends the array definition.
     *
     * @return void
     */
    public function processSingleLineArray(File $phpcsFile, int $arrayStart, int $arrayEnd)
    {
        $tokens = $phpcsFile->getTokens();
        $commas = [];

        // Iterate over each token between the start and end of the array.
        for ($i = $arrayStart + 1; $i < $arrayEnd; $i++) {
            // Skip bracketed statements, like function calls.
            if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                /** @psalm-suppress LoopInvalidation -- Intentional (skipping). */
                $i = $tokens[$i]['parenthesis_closer'];

                continue;
            }

            if ($tokens[$i]['code'] === T_COMMA) {
                // Before counting this comma, make sure we are not at the end of the array.
                $next = $phpcsFile->findNext(T_WHITESPACE, $i + 1, $arrayEnd, true);

                if ($next !== false) {
                    $commas[] = $i;
                }
            }
        }

        // Below we repeat three of the rules found in Squiz.Arrays.ArrayDeclaration, because SingleLineNotAllowed stops
        // them from executing (it returns early).
        foreach ($commas as $comma) {
            if ($tokens[$comma + 1]['code'] !== T_WHITESPACE) {
                $content = $tokens[$comma + 1]['content'];
                $error = 'Expected 1 space between comma and "%s"; 0 found';
                $data = [$content];
                $fix = $phpcsFile->addFixableError($error, $comma, 'NoSpaceAfterComma', $data);

                if ($fix) {
                    $phpcsFile->fixer->addContent($comma, ' ');
                }
            } else {
                $spaceLength = $tokens[$comma + 1]['length'];

                if ($spaceLength !== 1) {
                    $content = $tokens[$comma + 2]['content'];
                    $error = 'Expected 1 space between comma and "%s"; %s found';
                    $data = [$content, $spaceLength];
                    $fix = $phpcsFile->addFixableError($error, $comma, 'SpaceAfterComma', $data);

                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($comma + 1, ' ');
                    }
                }
            }

            if ($tokens[$comma - 1]['code'] === T_WHITESPACE) {
                $content = $tokens[$comma - 2]['content'];
                $spaceLength = $tokens[$comma - 1]['length'];
                $error = 'Expected 0 spaces between "%s" and comma; %s found';
                $data = [$content, $spaceLength];
                $fix = $phpcsFile->addFixableError($error, $comma, 'SpaceBeforeComma', $data);

                if ($fix) {
                    $phpcsFile->fixer->replaceToken($comma - 1, '');
                }
            }
        }

        if (count($commas) > 0) {
            // There should be no whitespace after the opening bracket.
            if ($tokens[$arrayStart + 1]['code'] === T_WHITESPACE) {
                $spaceLength = $tokens[$arrayStart + 1]['length'];

                if ($spaceLength !== 0) {
                    $error = 'Expected 0 spaces after opening array bracket; %s found';
                    $data = [$spaceLength];
                    $fix = $phpcsFile->addFixableError(
                        $error,
                        $arrayStart,
                        'SpaceAfterArrayOpen',
                        $data
                    );

                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($arrayStart + 1, '');
                    }
                }
            }

            // There should be no whitespace before the closing bracket.
            if ($tokens[$arrayEnd - 1]['code'] === T_WHITESPACE) {
                $spaceLength = $tokens[$arrayEnd - 1]['length'];

                if ($spaceLength !== 0) {
                    $error = 'Expected 0 spaces before closing array bracket; %s found';
                    $data = [$spaceLength];
                    $fix = $phpcsFile->addFixableError(
                        $error,
                        $arrayEnd,
                        'SpaceBeforeArrayClose',
                        $data
                    );

                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($arrayEnd - 1, '');
                    }
                }
            }
        }
    }

    /**
     * Processes a multi-line array definition.
     *
     * @param File $phpcsFile  The current file being checked.
     * @param int  $arrayStart The token that starts the array definition.
     * @param int  $arrayEnd   The token that ends the array definition.
     *
     * @return void
     */
    public function processMultiLineArray(File $phpcsFile, int $arrayStart, int $arrayEnd)
    {
        $tokens = $phpcsFile->getTokens();
        $commas = [];

        // Iterate over each token between the start and end of the array.
        for ($i = $arrayStart + 1; $i < $arrayEnd; $i++) {
            // Skip bracketed statements, like function calls.
            if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                /** @psalm-suppress LoopInvalidation -- Intentional (skipping). */
                $i = $tokens[$i]['parenthesis_closer'];

                continue;
            }

            // Skip nested short arrays.
            if ($tokens[$i]['code'] === T_OPEN_SHORT_ARRAY) {
                /** @psalm-suppress LoopInvalidation -- Intentional (skipping). */
                $i = $tokens[$i]['bracket_closer'];

                continue;
            }

            if ($tokens[$i]['code'] === T_COMMA) {
                // Find the next non-whitespace token before the end of the array.
                $next = $phpcsFile->findNext(T_WHITESPACE, $i + 1, $arrayEnd, true);

                if ($next === false) {
                    // None found, which means that there is a trailing comma.
                    $error = 'Comma not allowed after last value in multi-line array declaration';
                    $fix = $phpcsFile->addFixableError($error, $i, 'CommaAfterLast');

                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                } else {
                    $commas[] = $i;
                }
            }
        }

        foreach ($commas as $comma) {
            // Below we repeat one of the rules found in Squiz.Arrays.ArrayDeclaration, because KeyNotAligned stops it
            // from executing (it returns early).
            // Makes Squiz.Arrays.ArrayDeclaration.SpaceBeforeComma unnecessary.
            // Check that there is no space before the comma.
            if ($tokens[$comma - 1]['code'] === T_WHITESPACE) {
                $content = $tokens[$comma - 2]['content'];
                $spaceLength = $tokens[$comma - 1]['length'];
                $error = 'Expected 0 spaces between "%s" and comma; %s found';
                $data = [$content, $spaceLength];
                $fix = $phpcsFile->addFixableError($error, $comma, 'SpaceBeforeComma', $data);

                if ($fix) {
                    $phpcsFile->fixer->replaceToken($comma - 1, '');
                }
            }

            // Makes Squiz.Arrays.ArrayDeclaration.IndexNoNewline and ValueNoNewline unnecessary.
            // Check that there is no code after the comma (on the same line).
            $nextCode = $phpcsFile->findNext([T_WHITESPACE, T_COMMENT], $comma + 1, null, true);

            if ($nextCode !== false && $tokens[$nextCode]['line'] === $tokens[$comma]['line']) {
                $error = 'Each element in a multi-line array must be on a new line';
                $fix = $phpcsFile->addFixableError($error, $comma, 'ElementNoNewline');

                if ($fix) {
                    $phpcsFile->fixer->beginChangeset();

                    // If the token after the comma is whitespace.
                    if ($tokens[$comma + 1]['code'] === T_WHITESPACE) {
                        // Replace it with an empty string.
                        $phpcsFile->fixer->replaceToken($comma + 1, '');
                    }

                    $phpcsFile->fixer->addNewline($comma);

                    $firstNonWsPos = $phpcsFile->findFirstOnLine(T_WHITESPACE, $comma, true);

                    $phpcsFile->fixer->addContent(
                        $comma,
                        str_repeat(' ', $tokens[$firstNonWsPos]['column'] - 1)
                    );

                    $phpcsFile->fixer->endChangeset();
                }
            }
        }
    }
}
