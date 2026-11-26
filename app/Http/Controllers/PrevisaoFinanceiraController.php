<?php

namespace App\Http\Controllers;

use App\Services\CalcularPrevisaoFinanceira;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrevisaoFinanceiraController extends Controller
{
    public function __invoke(Request $request, CalcularPrevisaoFinanceira $previsao): Response
    {
        return Inertia::render('planejamento/index', $previsao->calcular($request->user()->id));
    }
}
