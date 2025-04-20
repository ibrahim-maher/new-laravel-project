namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\RegistrationField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RegistrationFieldController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,event_manager');
    }

    /**
     * Display a listing of registration fields for an event
     */
    public function index(Event $event)
    {
        $this->authorize('update', $event);
        
        $fields = RegistrationField::where('event_id', $event->id)
            ->orderBy('order')
            ->get();
        
        return view('registration.fields.index', compact('event', 'fields'));
    }

    /**
     * Show the form for creating a new registration field
     */
    public function create(Event $event)
    {
        $this->authorize('update', $event);
        
        return view('registration.fields.create', compact('event'));
    }

    /**
     * Store a newly created registration field
     */
    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        
        $validated = $request->validate([
            'field_name' => [
                'required', 
                'string', 
                'max:50',
                Rule::unique('registration_fields')->where(function ($query) use ($event) {
                    return $query->where('event_id', $event->id);
                })
            ],
            'field_type' => 'required|in:text,email,number,dropdown,checkbox',
            'is_required' => 'boolean',
            'options' => 'nullable|required_if:field_type,dropdown|string',
        ]);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Get the highest order value
            $maxOrder = RegistrationField::where('event_id', $event->id)->max('order');
            
            // Create the field
            $field = new RegistrationField([
                'event_id' => $event->id,
                'field_name' => $validated['field_name'],
                'field_type' => $validated['field_type'],
                'is_required' => $validated['is_required'] ?? false,
                'options' => $validated['options'] ?? null,
                'order' => $maxOrder + 1,
            ]);
            
            $field->save();
            
            DB::commit();
            
            return redirect()->route('registration.fields.index', $event)
                ->with('success', 'Registration field created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error creating field: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a registration field
     */
    public function edit(Event $event, RegistrationField $field)
    {
        $this->authorize('update', $event);
        
        // Ensure the field belongs to the event
        if ($field->event_id != $event->id) {
            abort(404);
        }
        
        return view('registration.fields.edit', compact('event', 'field'));
    }

    /**
     * Update a registration field
     */
    public function update(Request $request, Event $event, RegistrationField $field)
    {
        $this->authorize('update', $event);
        
        // Ensure the field belongs to the event
        if ($field->event_id != $event->id) {
            abort(404);
        }
        
        $validated = $request->validate([
            'field_name' => [
                'required', 
                'string', 
                'max:50',
                Rule::unique('registration_fields')->where(function ($query) use ($event, $field) {
                    return $query->where('event_id', $event->id)
                                ->where('id', '!=', $field->id);
                })
            ],
            'field_type' => 'required|in:text,email,number,dropdown,checkbox',
            'is_required' => 'boolean',
            'options' => 'nullable|required_if:field_type,dropdown|string',
        ]);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Update the field
            $field->field_name = $validated['field_name'];
            $field->field_type = $validated['field_type'];
            $field->is_required = $validated['is_required'] ?? false;
            $field->options = $validated['options'] ?? null;
            
            $field->save();
            
            DB::commit();
            
            return redirect()->route('registration.fields.index', $event)
                ->with('success', 'Registration field updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating field: ' . $e->getMessage());
        }
    }

    /**
     * Remove a registration field
     */
    public function destroy(Event $event, RegistrationField $field)
    {
        $this->authorize('update', $event);
        
        // Ensure the field belongs to the event
        if ($field->event_id != $event->id) {
            abort(404);
        }
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Delete the field
            $field->delete();
            
            // Reorder remaining fields
            $remainingFields = RegistrationField::where('event_id', $event->id)
                ->orderBy('order')
                ->get();
            
            foreach ($remainingFields as $index => $remainingField) {
                $remainingField->order = $index;
                $remainingField->save();
            }
            
            DB::commit();
            
            return redirect()->route('registration.fields.index', $event)
                ->with('success', 'Registration field deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting field: ' . $e->getMessage());
        }
    }

    /**
     * Reorder registration fields
     */
    public function reorder(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        
        $request->validate([
            'field_order' => 'required|array',
            'field_order.*' => 'required|exists:registration_fields,id',
        ]);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Update order of each field
            foreach ($request->field_order as $index => $fieldId) {
                $field = RegistrationField::findOrFail($fieldId);
                
                // Ensure the field belongs to the event
                if ($field->event_id != $event->id) {
                    continue;
                }
                
                $field->order = $index;
                $field->save();
            }
            
            DB::commit();
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
    
    /**
     * Get field form for AJAX requests
     */
    public function getFieldForm(Event $event, RegistrationField $field = null)
    {
        $this->authorize('update', $event);
        
        // If field is provided, ensure it belongs to the event
        if ($field && $field->event_id != $event->id) {
            abort(404);
        }
        
        // Render the field form
        $formHtml = view('registration.fields.partials.field_form', compact('event', 'field'))->render();
        
        return response()->json([
            'status' => 'success',
            'form_html' => $formHtml,
        ]);
    }
}
