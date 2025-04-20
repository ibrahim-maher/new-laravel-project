namespace App\Http\Controllers\Badges;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\BadgeTemplate;
use App\Models\Registration;
use App\Services\QrCodeService;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPdf\Facade\Pdf;

class BadgeController extends Controller
{
    protected $qrCodeService;
    protected $badgeService;

    public function __construct(QrCodeService $qrCodeService, BadgeService $badgeService)
    {
        $this->middleware('auth');
        $this->middleware('role:admin,event_manager')->except(['print']);
        $this->qrCodeService = $qrCodeService;
        $this->badgeService = $badgeService;
    }

    /**
     * Display a list of badge templates
     */
    public function templates()
    {
        $templates = BadgeTemplate::all();
        return view('badges.templates', compact('templates'));
    }

    /**
     * Show form to create a new badge template
     */
    public function createTemplate()
    {
        return view('badges.create-template');
    }

    /**
     * Store a new badge template
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'width' => 'required|integer|min:20|max:300',
            'height' => 'required|integer|min:20|max:300',
            'is_default' => 'boolean',
            'layout' => 'required|array'
        ]);
        
        // If setting as default, unset any current default
        if ($request->is_default) {
            BadgeTemplate::where('is_default', true)
                ->update(['is_default' => false]);
        }
        
        $template = new BadgeTemplate($validated);
        $template->save();
        
        return redirect()->route('badges.templates')
            ->with('success', 'Badge template created successfully!');
    }

    /**
     * Show form to add content to a badge template
     */
    public function addContent(BadgeTemplate $template)
    {
        return view('badges.add-content', compact('template'));
    }

    /**
     * Preview a badge with specified content
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:badge_templates,id',
            'content' => 'required|array'
        ]);
        
        $template = BadgeTemplate::findOrFail($request->template_id);
        $content = $request->content;
        
        // Generate sample QR code if needed
        if (isset($content['qr_code']) && $content['qr_code']['enabled']) {
            $content['qr_code']['data'] = $this->qrCodeService->generateSampleQR();
        }
        
        return view('badges.preview', compact('template', 'content'));
    }

    /**
     * Print a badge for a specific registration
     */
    public function print(Registration $registration)
    {
        // Check if user has permission to print badge
        if (!Auth::user()->can('manage', Badge::class) && Auth::id() != $registration->user_id) {
            abort(403);
        }
        
        // Get or create badge
        $badge = $registration->badge;
        
        if (!$badge) {
            $badge = $this->badgeService->createBadge($registration);
        }
        
        // Generate PDF
        $pdf = $this->badgeService->generatePDF($badge);
        
        // Update badge status
        $badge->status = 'printed';
        $badge->save();
        
        return $pdf->download('badge-' . $registration->id . '.pdf');
    }
    
    /**
     * Print multiple badges at once
     */
    public function printBulk(Request $request)
    {
        $registrationIds = $request->input('registration_ids', []);
        
        // Create and fetch badges
        $badges = collect();
        foreach ($registrationIds as $id) {
            $registration = Registration::findOrFail($id);
            
            // Check permissions
            if (!Auth::user()->can('manage', Badge::class) && Auth::id() != $registration->user_id) {
                continue; // Skip unauthorized registrations
            }
            
            // Get or create badge
            $badge = $registration->badge;
            if (!$badge) {
                $badge = $this->badgeService->createBadge($registration);
            }
            
            $badges->push($badge);
            
            // Update badge status
            $badge->status = 'printed';
            $badge->save();
        }
        
        // Generate bulk PDF
        $pdf = $this->badgeService->generateBulkPDF($badges);
        
        return $pdf->download('badges-bulk.pdf');
    }
    
    /**
     * Display a registration badge for viewing/printing
     */
    public function getRegistrationBadge(Registration $registration)
    {
        // Check permission
        if (!Auth::user()->can('manage', Badge::class) && Auth::id() != $registration->user_id) {
            abort(403, 'Unauthorized');
        }
        
        // Get badge template
        $badgeTemplate = BadgeTemplate::where('is_default', true)->first();
        
        if (!$badgeTemplate) {
            return back()->with('error', 'No default badge template found.');
        }
        
        // Prepare badge data
        $badgeData = $this->badgeService->prepareBadgeData($registration);
        
        return view('registration.registration_badge', [
            'registration' => $registration,
            'badge_template' => $badgeTemplate,
            'badge_data' => $badgeData,
        ]);
    }
}
