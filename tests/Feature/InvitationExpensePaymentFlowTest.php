<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationExpensePaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_flow_invitation_expense_payment_approval_and_rejection(): void
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);
        DB::table('group_members')->insert([
            'id' => (string) Str::uuid(),
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Invite new member
        $inviteEmail = 'flowuser@example.com';
        $this->actingAs($owner, 'sanctum');
        $inv = $this->postJson('/api/invitations', [
            'invitee_email' => $inviteEmail,
            'group_id' => $group->id,
        ])->assertStatus(201);
        $token    = $inv->json('invitation.token');
        $regToken = $inv->json('registration_token.token');

        // Register invited user
        $this->postJson('/api/auth/register', [
            'name' => 'Flow User',
            'email' => $inviteEmail,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'registration_token' => $regToken,
            'invitation_token' => $token,
        ])->assertStatus(201);
        $member = User::where('email', $inviteEmail)->first();

        // Owner creates expense
        $this->actingAs($owner, 'sanctum');
        $expense = $this->postJson('/api/expenses', [
            'description' => 'Cena',
            'total_amount' => 100,
            'group_id' => $group->id,
            'expense_date' => now()->toDateString(),
            'has_ticket' => false,
            'participants' => [
                ['user_id' => $member->id, 'amount_due' => 100],
            ],
        ])->assertStatus(201);
        $expenseId = $expense->json('expense.id');

        // Participant creates payment
        $this->actingAs($member, 'sanctum');
        $payment = $this->postJson('/api/payments', [
            'group_id' => $group->id,
            'from_user_id' => $member->id,
            'to_user_id' => $owner->id,
            'amount' => 100,
        ])->assertStatus(201);
        $paymentId = $payment->json('payment.id');

        // Owner approves payment
        $this->actingAs($owner, 'sanctum');
        $this->postJson("/api/payments/{$paymentId}/approve")
            ->assertStatus(200)
            ->assertJsonPath('payment.status', 'approved');

        $this->assertDatabaseHas('expense_participants', [
            'expense_id' => $expenseId,
            'user_id' => $member->id,
            'is_paid' => true,
        ]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expenseId,
            'status' => 'completed',
        ]);

        // Another payment to reject
        $this->actingAs($member, 'sanctum');
        $payment2 = $this->postJson('/api/payments', [
            'group_id' => $group->id,
            'from_user_id' => $member->id,
            'to_user_id' => $owner->id,
            'amount' => 50,
        ])->assertStatus(201);
        $payment2Id = $payment2->json('payment.id');

        $this->actingAs($owner, 'sanctum');
        $this->postJson("/api/payments/{$payment2Id}/reject")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Payment rejected');

        $this->assertDatabaseHas('payments', [
            'id' => $payment2Id,
            'status' => 'rejected',
        ]);
    }
}
