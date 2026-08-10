<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Contracts\PdfRenderer;
use Dompdf\Dompdf;
use Dompdf\Options;

final class DompdfRenderer implements PdfRenderer
{
    public function render(string $html): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
