<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class QuotationDocumentController extends Controller
{
    public function show(Quotation $quotation): Response
    {
        $quotation->load(['items', 'attendant']);
        return Pdf::loadView('quotations.document', ['quotation' => $quotation, 'business' => config('quotation')])
            ->setPaper('a4')
            ->download($quotation->quotation_number.'.pdf');
    }
}
