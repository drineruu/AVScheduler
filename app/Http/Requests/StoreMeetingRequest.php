<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesMeetingInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    use ValidatesMeetingInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareMeetingInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->meetingRules();
    }
}
