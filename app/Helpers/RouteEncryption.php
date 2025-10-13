<?php
// app/Helpers/RouteEncryption.php
namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;

class RouteEncryption
{
    public static function encrypt($id)
    {
        return base64_encode(Crypt::encryptString($id));
    }
    
    public static function decrypt($encrypted)
    {
        try {
            return Crypt::decryptString(base64_decode($encrypted));
        } catch (\Exception $e) {
            abort(404);
        }
    }
}