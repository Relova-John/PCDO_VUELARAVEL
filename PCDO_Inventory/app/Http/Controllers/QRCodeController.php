<?php

namespace App\Http\Controllers;

use BaconQrCode\Writer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;

class QRCodeController extends Controller
{
    public function index()
    {
        // $url = url('/inventory');
        $url = "https://forms.gle/vyDQT2hhwuZ8sfAF7";
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        $qr = $writer->writeString($url);

        return response($qr)->header('Content-Type', 'image/svg+xml');
    }
}