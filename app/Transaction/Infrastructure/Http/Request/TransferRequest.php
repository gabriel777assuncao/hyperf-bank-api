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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payer' => ['required', 'string', 'uuid'],
            'payee' => ['required', 'string', 'uuid', 'different:payer'],
            'value' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payer.required' => 'The payer field is required.',
            'payer.uuid' => 'The payer must be a valid UUID.',
            'payee.required' => 'The payee field is required.',
            'payee.uuid' => 'The payee must be a valid UUID.',
            'payee.different' => 'The payee must be different from the payer.',
            'value.required' => 'The value field is required.',
            'value.gt' => 'The value must be greater than zero.',
            'value.max' => 'The value must not exceed '.self::MAX_VALUE.'.',
        ];
    }
}
