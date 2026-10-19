<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcmTokenController extends Controller
{
    /**
     * FCM token'ı kaydet (PWA push bildirimi için)
     */
    public function store(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string|max:500',
        ]);

        Auth::user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json(['success' => true, 'message' => 'FCM token kaydedildi.']);
    }

    /**
     * FCM token'ı sil (bildirim izni kaldırıldığında)
     */
    public function destroy()
    {
        Auth::user()->update(['fcm_token' => null]);

        return response()->json(['success' => true]);
    }
}
