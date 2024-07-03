<?php

namespace App\Service;

use Spatie\PdfToText\Pdf;

class PdfParserService
{
    public function extractText(string $pdfPath): string
    {
        try {
            $text = Pdf::getText($pdfPath);
            return $text;
        } catch (\Exception $e) {
            // Gérer les exceptions si nécessaire
            throw new \RuntimeException("Impossible de lire le fichier PDF : " . $e->getMessage());
        }
    }
}