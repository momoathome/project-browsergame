<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    public function show($filename)
    {
        // Prüfe, ob die Datei existiert
        if (!Storage::exists('public/' . $filename)) {
            abort(404);
        }

        // Hole den Inhalt der Datei
        $fileContents = Storage::get('public/' . $filename);

        // Setze den richtigen MIME-Typ
        $mimeType = Storage::mimeType('public/' . $filename);

        // Setze die Header
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400', // Cache für 1 Tag
        ];

        return response($fileContents, 200, $headers);
    }
}
