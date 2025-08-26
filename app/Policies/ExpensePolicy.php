<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;

class ExpensePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the expense.
     */
    public function view(User $user, Expense $expense): bool
    {
        if ($expense->payer_id === $user->id) {
            return true;
        }

        $isParticipant = DB::table('expense_participants')
            ->where('expense_id', $expense->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($isParticipant) {
            return true;
        }

        $inGroup = DB::table('group_members')
            ->where('group_id', $expense->group_id)
            ->where('user_id', $user->id)
            ->exists();

        return $inGroup;
    }

    /**
     * Determine whether the user can update the expense.
     */
    public function update(User $user, Expense $expense): bool
    {
        return $expense->payer_id === $user->id;
    }

    /**
     * Determine whether the user can delete the expense.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $expense->payer_id === $user->id;
    }

    /**
     * Determine whether the user can approve the expense.
     */
    public function approve(User $user, Expense $expense): bool
    {
        return $expense->payer_id === $user->id;
    }
}
