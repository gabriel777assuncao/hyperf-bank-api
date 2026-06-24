<?php

declare(strict_types=1);

namespace App\Transaction\Infrastructure\Http\Request;

use Hyperf\Validation\Request\FormRequest;

final class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'payee' => ['required', 'string', 'uuid'],
            'value' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payee.required' => 'The payee field is required.',
            'payee.uuid' => 'The payee must be a valid UUID.',
            'value.required' => 'The value field is required.',
            'value.gt' => 'The value must be greater than zero.',
        ];
    }
}
