<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorLog;

class SensorController extends Controller
{
    public function store(Request $request)
    {
        \Log::info("Incoming Sensor Data: ", $request->all());
        // Security check: API Key
        if ($request->api_key !== env('SENSOR_API_KEY')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid API Key'
            ], 401);
        }

        try {
            // Validate incoming data
            $request->validate([
                'temp'   => 'required|numeric',
                'hum'    => 'required|numeric',
                'smoke1' => 'nullable|integer',
                'smoke2' => 'nullable|integer',
                'smoke3' => 'nullable|integer',
                'flame1' => 'nullable|boolean',
                'flame2' => 'nullable|boolean',
                'smoke'  => 'nullable|integer',
                'fire'   => 'nullable|boolean',
            ]);

            // Calculate aggregate values if not provided
            $smoke1 = $request->input('smoke1', 0);
            $smoke2 = $request->input('smoke2', 0);
            $smoke3 = $request->input('smoke3', 0);
            $flame1 = $request->input('flame1', false);
            $flame2 = $request->input('flame2', false);

            $smoke = $request->input('smoke', max($smoke1, $smoke2, $smoke3));
            $fire  = $request->input('fire', ($flame1 || $flame2));

            $data = SensorLog::create([
                'temp'   => $request->temp,
                'hum'    => $request->hum,
                'smoke'  => $smoke,
                'fire'   => $fire,
                'smoke1' => $smoke1,
                'smoke2' => $smoke2,
                'smoke3' => $smoke3,
                'flame1' => $flame1,
                'flame2' => $flame2,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data saved successfully',
                'data' => $data
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
