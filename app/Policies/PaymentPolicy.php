<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $payment->payer_id === $user->id || $payment->receiver_id === $user->id;
    }

    /**
     * Determine whether the user can update the payment.
     */
    public function update(User $user, Payment $payment): bool
    {
        return $payment->payer_id === $user->id;
    }

    /**
     * Determine whether the user can delete the payment.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $payment->payer_id === $user->id;
    }

    /**
     * Determine whether the user can approve the payment.
     */
    public function approve(User $user, Payment $payment): bool
    {
        return $payment->receiver_id === $user->id;
    }

    /**
     * Determine whether the user can reject the payment.
     */
    public function reject(User $user, Payment $payment): bool
    {
        return $payment->receiver_id === $user->id;
    }
}
