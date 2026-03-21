<?php

namespace Tests\Feature\Common;

use App\Livewire\Common\Notifications as NotificationsComponent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsReadStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_read_and_mark_all_as_read_work_for_authenticated_user(): void
    {
        $user = User::create([
            'first_name' => 'Read',
            'last_name' => 'State',
            'email' => 'read-state@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $first = Notification::create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_DEMERIT_WARNING,
            'message' => 'First unread',
            'is_read' => false,
        ]);

        $second = Notification::create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_RENT_DUE_REMINDER,
            'message' => 'Second unread',
            'is_read' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(NotificationsComponent::class)
            ->call('markAsRead', $first->id)
            ->assertHasNoErrors()
            ->call('markAllAsRead')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'id' => $first->id,
            'is_read' => true,
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $second->id,
            'is_read' => true,
        ]);
    }
}
