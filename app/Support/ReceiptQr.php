<?php

namespace App\Support;

use App\Models\Payment;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\URL;

/**
 * The QR code printed on each official receipt. It holds a signed link, so
 * nobody can guess other receipts' links, and scanning it reopens the receipt.
 */
class ReceiptQr
{
    public static function url(Payment $payment): string
    {
        return URL::signedRoute('receipts.verify', $payment);
    }

    /**
     * Inline SVG (no GD needed). Dark navy on white scans reliably on any phone.
     */
    public static function svg(Payment $payment): string
    {
        $options = new QROptions([
            'outputType' => 'svg',
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'drawLightModules' => false,
            'svgDefs' => '',
        ]);

        $svg = (new QRCode($options))->render(self::url($payment));

        // Paint the dark modules navy; light modules aren't drawn, so the white card shows through.
        return str_replace(['fill="#000"', '<svg '], ['fill="#0b1f44"', '<svg role="img" aria-label="QR code for this receipt" '], $svg);
    }
}
