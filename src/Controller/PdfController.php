<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PdfParserService;
use ReflectionClass;
use setasign\Fpdi\Tcpdf\Fpdi;
use Smalot\PdfParser\Parser;

class PdfController extends AbstractController
{
    private PdfParserService $pdfParserService;

    public function __construct(PdfParserService $pdfParserService)
    {
        $this->pdfParserService = $pdfParserService;
    }

    #[Route('/pdf', name: 'pdf_list')]
    public function listPdfs(): Response
    {
        $pdfDirectory = $this->getParameter('pdf_directory');
        $pdfFiles = array_diff(scandir($pdfDirectory), ['..', '.']);

        return $this->render('pdf/list.html.twig', [
            'pdfFiles' => $pdfFiles,
            'pdfDirectory' => basename($pdfDirectory),
        ]);
    }

    #[Route('/pdf/view/{filename}', name: 'pdf_view')]
    public function viewPdf(string $filename): Response
    {
        $pdfDirectory = $this->getParameter('pdf_directory');
        $filePath = $pdfDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist');
        }
        // Utilisation de smalot/pdfparser pour analyser le fichier PDF
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);

        // Obtenez les détails du PDF pour déboguer
        $details = $pdf->getDetails();
        // Obtenir les textes pour déboguer
        $text = $pdf->getText();
dd($text, $details);

        return $this->render('pdf/view.html.twig', [
            'filename' => $filename,
        ]);
    }

    #[Route('/pdf/cut/{filename}', name: 'pdf_cuter')]
    public function cutePdf(string $filename): Response
    {
        $pdfDirectory = $this->getParameter('pdf_directory');
        $filePath = $pdfDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist');
        }
        
        // Découper le PDF en pages individuelles et les sauvegarder dans un répertoire spécifique
        $outputDirectory = $pdfDirectory;
        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0777, true);
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $pdf = new Fpdi();
            $pdf->setSourceFile($filePath); // Re-set the source file for each new instance
            $pdf->AddPage();
            $tplId = $pdf->importPage($i);
            $pdf->useTemplate($tplId);

            $outputPath = $outputDirectory . '/page_' . $i . '.pdf';
            $pdf->Output($outputPath, 'F');
        }
 // Débogage des détails et du texte du PDF
 dd('Pages découpées et sauvegardées dans ' . $outputDirectory);

        return $this->render('pdf/cut.html.twig', [
            'filename' => $filename,
        ]);
    }

    #[Route('/pdf/download/{filename}', name: 'pdf_download')]
    public function downloadPdf(string $filename): Response
    {
        $pdfDirectory = $this->getParameter('pdf_directory');
        $filePath = $pdfDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist');
        }

        return $this->file($filePath);
    }
}