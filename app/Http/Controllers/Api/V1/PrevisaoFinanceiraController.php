<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CalcularPrevisaoFinanceira;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrevisaoFinanceiraController extends Controller
{
    public function index(Request $request, CalcularPrevisaoFinanceira $previsao): JsonResponse
    {
        return response()->json(
            $previsao->calcular((int) $request->user()->getAuthIdentifier())
        );
    }
}