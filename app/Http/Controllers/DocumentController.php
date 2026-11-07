<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function rentalContract(Request $request): Response
    {
        $pdf = Pdf::loadView('documents.rental-contract', ['user' => $request->user()]);

        return $pdf->stream('najomna-zmluva.pdf');
    }

    public function vop(): Response
    {
        return Pdf::loadView('documents.vop')->stream('vop.pdf');
    }

    public function gdpr(): Response
    {
        return Pdf::loadView('documents.gdpr')->stream('gdpr.pdf');
    }
}
