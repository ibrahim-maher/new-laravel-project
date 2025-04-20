<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Badge;
use App\Models\BadgeTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class BadgeService
{
    protected $qrCodeService;
    
    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }
    
    /**
     * Create a badge for a registration
     *
     * @param Registration $registration
     * @param BadgeTemplate|null $template
     * @return Badge
     */
    public function createBadge(Registration $registration, ?BadgeTemplate $template = null)
    {
        // If no template provided, get default
        if (!$template) {
            $template = BadgeTemplate::where('is_default', true)->firstOrFail();
        }
        
        // Create badge content
        $content = [
            'name' => [
                'enabled' => true,
                'value' => $registration->name,
                'alignment' => 'center',
                'size' => 'large'
            ],
            'company' => [
                'enabled' => !empty($registration->company),
                'value' => $registration->company,
                'alignment' => 'center'
            ],
            'event' => [
                'enabled' => true,
                'value' => $registration->event->name,
                'alignment' => 'center'
            ],
            'qr_code' => [
                'enabled' => true,
                'data' => $this->qrCodeService->generateBadgeQR($registration->id),
                'position' => 'bottom',
                'size' => 'medium'
            ]
        ];
        
        // Create badge
        $badge = new Badge([
            'registration_id' => $registration->id,
            'badge_template_id' => $template->id,
            'content' => $content,
            'qr_code' => $this->qrCodeService->generateBadgeQR($registration->id),
            'status' => 'generated'
        ]);
        
        $badge->save();
        
        return $badge;
    }
    
    /**
     * Generate PDF for a badge
     *
     * @param Badge $badge
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePDF(Badge $badge)
    {
        $badge->load(['registration', 'template']);
        
        $pdf = PDF::loadView('badges.print', [
            'badge' => $badge,
            'template' => $badge->template,
            'registration' => $badge->registration
        ]);
        
        // Use template dimensions
        $pdf->setPaper([0, 0, $badge->template->width, $badge->template->height], 'portrait');
        
        return $pdf;
    }
    
    /**
     * Generate PDF for multiple badges
     *
     * @param Collection $badges Collection of Badge models
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateBulkPDF(Collection $badges)
    {
        $badges->load(['registration', 'template']);
        
        $pdf = PDF::loadView('badges.print-bulk', [
            'badges' => $badges
        ]);
        
        return $pdf;
    }
}