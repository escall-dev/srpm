<?php

namespace App\Livewire\Forms\Tenant;

use App\Models\Lease;
use App\Models\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class RequestForm extends Form
{
    public const GENERAL_COMPLAINT_TOPICS = [
        'noise',
        'littering',
        'parking_obstruction',
        'vandalism',
        'pets',
        'other',
    ];

    public ?Lease $activeLease = null;
    public string $type = '';
    public string $complaint_type = '';
    public string $complaint_topic = '';
    public ?int $reported_tenant_id = null;
    public ?int $reported_unit_id = null;
    public string $description = '';
    public array $image_paths = [];

    protected function rules(): array
    {
        $descriptionMinimum = 10;

        if ($this->type === 'complaint' && $this->complaint_type === 'general') {
            $descriptionMinimum = 5;
        }

        if ($this->type === 'complaint' && $this->complaint_type === 'specific') {
            $descriptionMinimum = 20;
        }

        return [
            'type'        => ['required', 'in:maintenance,complaint,others'],
            'complaint_type' => ['nullable', 'required_if:type,complaint', 'in:general,specific'],
            'complaint_topic' => ['nullable', 'required_if:complaint_type,general', 'string', Rule::in(self::GENERAL_COMPLAINT_TOPICS)],
            'reported_tenant_id' => ['nullable', 'required_if:complaint_type,specific', 'integer', 'exists:tenants,id'],
            'reported_unit_id' => ['nullable', 'required_if:complaint_type,specific', 'integer', 'exists:units,id'],
            'description' => ['required', 'string', "min:{$descriptionMinimum}", 'max:1000'],
            'image_paths'      => ['nullable', 'array', 'max:3'],
            'image_paths.*'    => ['nullable', 'file', 'mimes:jpeg,png,jpg', 'max:5120'], // 5MB each
        ];
    }

    protected function messages(): array
    {
        return [
            'type.required' => 'The request type is required.',
            'type.in' => 'The selected request type is invalid.',
            'complaint_type.required_if' => 'Please select a complaint type before submitting.',
            'complaint_type.in' => 'The selected complaint type is invalid.',
            'complaint_topic.required_if' => 'A complaint topic is required for general complaints.',
            'complaint_topic.in' => 'The selected complaint topic is invalid.',
            'reported_tenant_id.required_if' => 'Please select the reported tenant for specific complaints.',
            'reported_tenant_id.exists' => 'The selected reported tenant is invalid.',
            'reported_unit_id.required_if' => 'Please select the reported unit for specific complaints.',
            'reported_unit_id.exists' => 'The selected reported unit is invalid.',
            'description.required' => 'The description is required.',
            'description.min' => 'The description must be at least :min characters.',
            'description.max' => 'The description may not be greater than :max characters.',
            'image_paths.array' => 'The images must be an array.',
            'image_paths.max' => 'You may not upload more than :max images.',
            'image_paths.*.mimes' => 'Each image must be a file of type: :values.',
            'image_paths.*.max' => 'Each image may not be greater than :max kilobytes.',
        ];
    }

    public function submit()
    {
        // Validate form data
        $this->validate();

        if (! $this->activeLease || ! $this->activeLease->unit) {
            throw ValidationException::withMessages([
                'type' => 'Unable to submit without an active lease.',
            ]);
        }

        if ($this->type !== 'complaint') {
            $this->complaint_type = '';
            $this->complaint_topic = '';
            $this->reported_tenant_id = null;
            $this->reported_unit_id = null;
        }

        if ($this->type === 'complaint' && $this->complaint_type === 'specific') {
            $this->validateSpecificComplaintScope();
        }

        $tenant = Auth::user()->tenant;

        if ($tenant?->isTerminated()) {
            throw ValidationException::withMessages([
                'type' => 'Your account is under termination status. You cannot create new requests.',
            ]);
        }

        $complaintPriority = null;
        $ownerDecision = null;

        if ($this->type === 'complaint') {
            $complaintPriority = $this->complaint_type === 'specific' ? 'high' : 'standard';
            $ownerDecision = 'pending_review';
        }

        // Prepare folder
        $folderPath = "requests/tenant_{$tenant->id}";

        $storedImages = [];

        // Store uploaded images
        if (!empty($this->image_paths)) {
            foreach ($this->image_paths as $image) {
                $fileName = $image->getClientOriginalName();
                $path = $image->storeAs($folderPath, $fileName, 'public');

                $storedImages[] = $path;
            }
        }

        // Create database record
        Request::create([
            'tenant_id'   => $tenant->id,
            'unit_id'     => $this->activeLease->unit_id,
            'type'        => $this->type,
            'complaint_type' => $this->type === 'complaint' ? $this->complaint_type : null,
            'complaint_topic' => $this->type === 'complaint' && $this->complaint_type === 'general' ? $this->complaint_topic : null,
            'complaint_priority' => $complaintPriority,
            'reported_tenant_id' => $this->type === 'complaint' && $this->complaint_type === 'specific' ? $this->reported_tenant_id : null,
            'reported_unit_id' => $this->type === 'complaint' && $this->complaint_type === 'specific' ? $this->reported_unit_id : null,
            'description' => $this->description,
            'image_path'  => $storedImages, // MUST be casted as array in model
            'owner_decision' => $ownerDecision,
            'owner_decision_at' => null,
        ]);
    }

    protected function validateSpecificComplaintScope(): void
    {
        $propertyId = $this->activeLease?->unit?->property_id;

        if (! $propertyId || ! $this->reported_tenant_id || ! $this->reported_unit_id) {
            throw ValidationException::withMessages([
                'reported_tenant_id' => 'Specific complaint details are incomplete.',
            ]);
        }

        $reportedPairIsValid = Lease::query()
            ->where('status', 'active')
            ->where('tenant_id', $this->reported_tenant_id)
            ->where('unit_id', $this->reported_unit_id)
            ->whereHas('unit', fn ($q) => $q->where('property_id', $propertyId))
            ->exists();

        if (! $reportedPairIsValid) {
            throw ValidationException::withMessages([
                'reported_tenant_id' => 'The reported tenant and unit must belong to the same active property.',
            ]);
        }
    }
}