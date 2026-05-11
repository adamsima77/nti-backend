<?php

namespace Modules\IdentityAccess\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class TurnstileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()->post(config('services.turnstile.verify_url'), [
            'secret'   => config('services.turnstile.secret'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (! ($response->json('success'))) {
            $fail('Human verification failed. Please try again.');
        }
    }
}
