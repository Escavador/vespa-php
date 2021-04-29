<?php

declare(strict_types=1);

namespace Glen84\Sniffs\Strings;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Glen84_Sniffs_Strings_DoubleQuoteUsageSniff.
 *
 * Makes sure that any use of Double Quotes ("") are warranted.
 */
class DoubleQuoteUsageSniff implements Sniff
{
    public function register(): array
    {
        return [T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // If tabs are being converted to spaces by the tokeniser, the
        // original content should be used instead of the converted content.
        if (isset($tokens[$stackPtr]['orig_content'])) {
            $workingString = $tokens[$stackPtr]['orig_content'];
        } else {
            /**
             * @psalm-suppress PossiblyUndefinedArrayOffset
             * @todo $tokens should have a more specific type.
             */
            $workingString = $tokens[$stackPtr]['content'];
        }

        $lastStringToken = $stackPtr;

        $i = $stackPtr + 1;
        if (isset($tokens[$i])) {
            while (
                $i < $phpcsFile->numTokens &&
                $tokens[$i]['code'] === $tokens[$stackPtr]['code']
            ) {
                if (isset($tokens[$i]['orig_content'])) {
                    $workingString .= $tokens[$i]['orig_content'];
                } else {
                    /**
                     * @psalm-suppress PossiblyUndefinedArrayOffset
                     * @todo $tokens should have a more specific type.
                     */
                    $workingString .= $tokens[$i]['content'];
                }

                $lastStringToken = $i;
                $i++;
            }
        }

        $skipTo = $lastStringToken + 1;

        // Check if it's a double quoted string.
        if ($workingString[0] !== '"' || substr($workingString, -1) !== '"') {
            return $skipTo;
        }

        // The use of complex-syntax variable interpolation is not allowed.
        if ($tokens[$stackPtr]['code'] === T_DOUBLE_QUOTED_STRING) {
            $stringTokens = token_get_all('<?php ' . $workingString);
            foreach ($stringTokens as $token) {
                if (
                    is_array($token) &&
                    in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])
                ) {
                    $error =
                        'The use of complex-syntax variable interpolation is not allowed; use sprintf() or ' .
                        'concatenation instead';
                    $phpcsFile->addError($error, $stackPtr, 'ContainsComplexSyntax');
                }
            }

            return $skipTo;
        }

        $allowedChars = [
            '\0',
            '\1',
            '\2',
            '\3',
            '\4',
            '\5',
            '\6',
            '\7',
            '\n',
            '\r',
            '\f',
            '\t',
            '\v',
            '\x',
            '\b',
            '\e',
            '\u',
            '\''
        ];

        foreach ($allowedChars as $testChar) {
            if (strpos($workingString, $testChar) !== false) {
                return $skipTo;
            }
        }

        $error = 'String %s does not require double quotes; use single quotes instead';
        $data = [str_replace(["\r", "\n"], ['\r', '\n'], $workingString)];
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NotRequired', $data);

        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            $innerContent = substr($workingString, 1, -1);
            $innerContent = str_replace('\"', '"', $innerContent);
            $innerContent = str_replace('\\$', '$', $innerContent);
            $phpcsFile->fixer->replaceToken($stackPtr, "'$innerContent'");
            while ($lastStringToken !== $stackPtr) {
                $phpcsFile->fixer->replaceToken($lastStringToken, '');
                $lastStringToken--;
            }

            $phpcsFile->fixer->endChangeset();
        }

        return $skipTo;
    }
}
