<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function rentalContract(Request $request)
    {
        $pdf = Pdf::loadView('documents.rental-contract', ['user' => $request->user()]);

        return $pdf->stream('najomna-zmluva.pdf');
    }

    public function vop()
    {
        return Pdf::loadView('documents.vop')->stream('vop.pdf');
    }

    public function gdpr()
    {
        return Pdf::loadView('documents.gdpr')->stream('gdpr.pdf');
    }
}