<?php

namespace App\Actions\Webhooks;

use App\Models\User;

class ResolveUserByPhoneNumberAction
{
    public function handle(string $phoneNumber): User
    {
        $variants = [$phoneNumber];
        $alternative = $this->generateBrazilianPhoneVariant($phoneNumber);

        if ($alternative) {
            $variants[] = $alternative;
        }

        $user = User::query()
            ->whereIn('phone_number', $variants)
            ->orderByRaw('phone_number = ? DESC', [$phoneNumber])
            ->first();

        if (! $user) {
            abort(404, 'User not found for the given phone number.');
        }

        if (! $user->isActive()) {
            abort(403, 'User account is blocked.');
        }

        return $user;
    }

    /**
     * Generate an alternative Brazilian phone number variant by adding or removing the 9th digit.
     */
    private function generateBrazilianPhoneVariant(string $phoneNumber): ?string
    {
        if (! str_starts_with($phoneNumber, '55')) {
            return null;
        }

        $national = substr($phoneNumber, 2);

        // 13 digits total (11 national) = has the 9th digit → remove it
        if (strlen($phoneNumber) === 13 && $national[2] === '9') {
            return '55'.substr($national, 0, 2).substr($national, 3);
        }

        // 12 digits total (10 national) = missing the 9th digit → add it
        if (strlen($phoneNumber) === 12) {
            return '55'.substr($national, 0, 2).'9'.substr($national, 2);
        }

        return null;
    }
}
