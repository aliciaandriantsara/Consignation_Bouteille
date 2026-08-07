<?php
// app/helpers/qrcode.php

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

function genererImageQr(string $contenu, string $nomFichier): string {
    $writer = new PngWriter();

    $qrCode = new QrCode(
        data:                $contenu,
        encoding:            new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size:                300,
        margin:              10,
        foregroundColor:     new Color(0, 0, 0),
        backgroundColor:     new Color(255, 255, 255)
    );

    $result = $writer->write($qrCode);

    // $chemin = ROOT . '/public/qrcodes/' . $nomFichier . '.png';
    $sousDossier = (strpos($_SERVER['HTTP_HOST'] ?? '', 'infinityfreeapp.com') !== false) ? '' : '/public';//raha local
    $chemin = ROOT . $sousDossier . '/qrcodes/' . $nomFichier . '.png';
    $result->saveToFile($chemin);

    return '/qrcodes/' . $nomFichier . '.png';
}
