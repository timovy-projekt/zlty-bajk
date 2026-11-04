<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function rentalContract()
    {
        $pdf = Pdf::loadView('documents.rental-contract', ['user' => $request->user()]);

        return $pdf->stream('najomna-zmluva.pdf');
    }
}