<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CaptiveController extends Controller
{
    // Android check → HARUS 204
    public function androidCheck(Request $request)
    {
        return response('', 204);
    }

    // iOS check → cukup 200 (jangan redirect)
    public function appleCheck(Request $request)
    {
        return response('<html><body>Success</body></html>', 200)
            ->header('Content-Type', 'text/html');
    }
}