<?php

namespace App\Actions;

use Codesmiths\LaravelOcrSpace\OcrSpaceOptions;
use Codesmiths\LaravelOcrSpace\Facades\OcrSpace;
use Codesmiths\LaravelOcrSpace\Enums\Language;
use Codesmiths\LaravelOcrSpace\Enums\OcrSpaceEngine;

class VerifyProofOfPayment
{
    public function handle(string $imagePath): array
    {
        $options = OcrSpaceOptions::make()
            ->language(Language::English)
            ->overlayRequired(false)
            ->detectOrientation(true)
            ->OCREngine(OcrSpaceEngine::Engine2);

        $result = OcrSpace::parseImageFile($imagePath, $options);

        $exitCode = intval($result->getOCRExitCode());

        if ($exitCode !== 1) {
            return [
                'success' => false,
                'error' => 'image_only',
                'exit_code' => $exitCode,
            ];
        }

        // Extract parsed text
        $parsedText = $result->getParsedResults()->first()->getParsedText() ?? '';

        // Extract Amount
        preg_match('/Amount\s+([\d,.]+)/i', $parsedText, $amount1);
        preg_match('/Total Amount Sent\s*[₱P]?([\d,.]+)/i', $parsedText, $amount2);

        $amount = $amount1[1] ?? $amount2[1] ?? null;
        if ($amount) {
            $amount = floatval(str_replace(',', '', $amount));
        }

        // Extract Reference Number (Ref No. or Ref #)
        preg_match('/Ref(?:erence)?\s*No\.?\s*[:\-]?\s*(\d+)/i', $parsedText, $ref);
        $reference = $ref[1] ?? null;

        // Extract GCash Number
        $numberPatterns = [
            '/\+63[\s\-]*9(?:[\s\-]*\d){9}/', // +63 951 458 5608
            '/09[\s\-]*\d(?:[\s\-]*\d){8}/',  // 09 514 585 608
            '/9[\s\-]*\d(?:[\s\-]*\d){8}/',   // 9 514 585 608
        ];
        
        $foundNumber = null;

        foreach ($numberPatterns as $pattern) {
            if (preg_match($pattern, $parsedText, $match)) {
                $foundNumber = $match[0];
                break;
            }
        }

        // Normalize number to 09XXXXXXXXX format
        if ($foundNumber) {
            $foundNumber = preg_replace('/\D/', '', $foundNumber); // remove spaces + symbols

            if (str_starts_with($foundNumber, '63')) {
                $foundNumber = '0' . substr($foundNumber, 2);
            }

            if (str_starts_with($foundNumber, '9')) {
                $foundNumber = '0' . $foundNumber;
            }
        }

        return [
            'success' => true,
            'exit_code' => $exitCode,
            'parsed_text' => $parsedText,
            'amount' => $amount,
            'reference_number' => $reference,
            'number' => $foundNumber,
        ];
    }
}
