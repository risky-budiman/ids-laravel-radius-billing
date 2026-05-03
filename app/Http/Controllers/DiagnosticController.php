<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Olt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosticController extends Controller
{
    public function index()
    {
        $olts = Olt::all();
        $customers = Customer::all();
        
        $results = [
            'total_customers' => $customers->count(),
            'customers_with_olt' => $customers->whereNotNull('olt_id')->count(),
            'customers_with_sn' => $customers->whereNotNull('onu_sn')->count(),
            'customers_with_index' => $customers->whereNotNull('onu_index')->count(),
            'active_olts' => $olts->where('is_active', true)->count(),
            'details' => []
        ];

        foreach ($customers as $c) {
            $results['details'][] = [
                'name' => $c->name,
                'olt_id' => $c->olt_id,
                'olt_name' => $c->olt ? $c->olt->name : 'NOT FOUND',
                'sn' => $c->onu_sn,
                'index' => $c->onu_index,
            ];
        }

        return response()->json($results);
    }
}
