<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestCanvasController extends Controller
{
    /**
     * Simple test method to verify routing
     */
    public function test()
    {
        return response()->json([
            "status" => "success",
            "message" => "TestCanvasController is working correctly",
            "timestamp" => now(),
        ]);
    }
}
