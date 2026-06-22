<?php

declare(strict_types=1);

namespace App\Auth\Http\Request;

use Hyperf\Validation\Request\FormRequest;

final class RegisterRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'required_without:cnpj'],
            'cnpj' => ['nullable', 'string', 'required_without:cpf'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'type' => ['required', 'string', 'in:NORMAL,SHOPKEEPER'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cpf.required_without' => 'At least one document (CPF or CNPJ) is required.',
            'cnpj.required_without' => 'At least one document (CPF or CNPJ) is required.',
            'type.in' => 'The user type must be NORMAL or SHOPKEEPER.',
        ];
    }
}
