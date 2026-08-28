<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesBrotherInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrotherRequest extends FormRequest
{
    use ValidatesBrotherInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareBrotherInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->brotherRules((int) $this->route('brother'));
    }
}
