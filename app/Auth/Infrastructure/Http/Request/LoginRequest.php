<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Request;

use Hyperf\Validation\Request\FormRequest;

final class LoginRequest extends FormRequest
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
            'document' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
