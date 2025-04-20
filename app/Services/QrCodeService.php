<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Generate a QR code for a ticket
     *
     * @param int $registrationId
     * @return string
     */
    public function generateTicketQR($registrationId)
    {
        $code = 'TICKET-' . $registrationId . '-' . Str::random(10);
        
        // Generate QR code and return as base64 encoded data
        return base64_encode(QrCode::format('png')
            ->size(200)
            ->errorCorrection('H')
            ->generate($code));
    }
    
    /**
     * Generate a QR code for a badge
     *
     * @param int $registrationId
     * @return string
     */
    public function generateBadgeQR($registrationId)
    {
        $code = 'BADGE-' . $registrationId . '-' . Str::random(10);
        
        // Generate QR code and return as base64 encoded data
        return base64_encode(QrCode::format('png')
            ->size(200)
            ->errorCorrection('H')
            ->generate($code));
    }
    
    /**
     * Generate a sample QR code for preview
     *
     * @return string
     */
    public function generateSampleQR()
    {
        // Generate QR code and return as base64 encoded data
        return base64_encode(QrCode::format('png')
            ->size(200)
            ->errorCorrection('H')
            ->generate('SAMPLE-QR-CODE'));
    }
}