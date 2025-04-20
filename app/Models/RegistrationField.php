<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationField extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'field_name',
        'field_type',
        'is_required',
        'options',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
        'order' => 'integer',
    ];

    /**
     * Default registration fields.
     */
    const DEFAULT_FIELDS = [
        [
            'field_name' => 'First Name',
            'field_type' => 'text',
            'is_required' => true,
        ],
        [
            'field_name' => 'Last Name',
            'field_type' => 'text',
            'is_required' => true,
        ],
        [
            'field_name' => 'Email',
            'field_type' => 'email',
            'is_required' => true,
        ],
        [
            'field_name' => 'Phone',
            'field_type' => 'tel',
            'is_required' => true,
        ],
    ];

    /**
     * Available field types.
     */
    const FIELD_TYPES = [
        'text' => 'Text',
        'email' => 'Email',
        'tel' => 'Phone',
        'number' => 'Number',
        'select' => 'Dropdown',
        'checkbox' => 'Checkbox',
        'radio' => 'Radio Button',
        'textarea' => 'Text Area',
        'date' => 'Date',
    ];

    /**
     * Get the event that the registration field belongs to.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the registration responses for this field.
     */
    public function responses()
    {
        return $this->hasMany(\App\Models\RegistrationResponse::class);
    }

    /**
     * Generate the HTML input element for this field.
     */
    public function getInputHtml($value = null)
    {
        $required = $this->is_required ? 'required' : '';
        $id = 'field_' . $this->id;
        $label = '<label class="form-label" for="' . $id . '">' . $this->field_name . ($this->is_required ? ' *' : '') . '</label>';
        
        switch ($this->field_type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
            case 'date':
                return $label . '<input type="' . $this->field_type . '" class="form-control" id="' . $id . '" name="fields[' . $this->id . ']" ' . $required . ' value="' . htmlspecialchars($value ?? '') . '">';
                
            case 'textarea':
                return $label . '<textarea class="form-control" id="' . $id . '" name="fields[' . $this->id . ']" ' . $required . '>' . htmlspecialchars($value ?? '') . '</textarea>';
                
            case 'select':
                $options = '';
                foreach ($this->options ?? [] as $option) {
                    $selected = $value == $option ? 'selected' : '';
                    $options .= '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                }
                return $label . '<select class="form-select" id="' . $id . '" name="fields[' . $this->id . ']" ' . $required . '><option value="">Select...</option>' . $options . '</select>';
                
            case 'checkbox':
                $checkboxes = '';
                foreach ($this->options ?? [] as $option) {
                    $checked = $value == $option ? 'checked' : '';
                    $checkboxes .= '
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="' . htmlspecialchars($option) . '" id="' . $id . '_' . md5($option) . '" name="fields[' . $this->id . '][]" ' . $checked . '>
                            <label class="form-check-label" for="' . $id . '_' . md5($option) . '">' . htmlspecialchars($option) . '</label>
                        </div>';
                }
                return '<div class="mb-3">' . $label . $checkboxes . '</div>';
                
            case 'radio':
                $radios = '';
                foreach ($this->options ?? [] as $option) {
                    $checked = $value == $option ? 'checked' : '';
                    $radios .= '
                        <div class="form-check">
                            <input class="form-check-input" type="radio" value="' . htmlspecialchars($option) . '" id="' . $id . '_' . md5($option) . '" name="fields[' . $this->id . ']" ' . $checked . ' ' . $required . '>
                            <label class="form-check-label" for="' . $id . '_' . md5($option) . '">' . htmlspecialchars($option) . '</label>
                        </div>';
                }
                return '<div class="mb-3">' . $label . $radios . '</div>';
                
            default:
                return '';
        }
    }
}