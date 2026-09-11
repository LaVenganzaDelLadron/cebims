<?php

use App\Models\BorrowRequest;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Transaction;
use App\Models\User;

function apiUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function adminRoutes(): array
{
    return [
        ['GET', '/api/admin/dashboard'],
        ['GET', '/api/admin/users'],
        ['GET', '/api/admin/users/1'],
        ['PUT', '/api/admin/users/1'],
        ['DELETE', '/api/admin/users/1'],
        ['POST', '/api/admin/categories'],
        ['PUT', '/api/admin/categories/1'],
        ['DELETE', '/api/admin/categories/1'],
        ['POST', '/api/admin/equipment'],
        ['PUT', '/api/admin/equipment/1'],
        ['DELETE', '/api/admin/equipment/1'],
        ['GET', '/api/admin/borrow-requests'],
        ['PUT', '/api/admin/borrow-requests/1/approve'],
        ['PUT', '/api/admin/borrow-requests/1/reject'],
        ['GET', '/api/admin/transactions'],
        ['PUT', '/api/admin/transactions/1/return'],
        ['GET', '/api/admin/history'],
        ['GET', '/api/admin/audit-logs'],
    ];
}

it('requires authentication for every admin route', function (string $method, string $uri) {
    $response = $this->call($method, $uri);

    $response->assertUnauthorized();
})->with(adminRoutes());

it('rejects regular users from every admin route', function (string $method, string $uri) {
    $user = apiUser();

    $response = $this->actingAs($user, 'sanctum')->call($method, $uri);

    $response->assertForbidden();
})->with(adminRoutes());

it('forces registrations to the user role', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => 'Normal',
        'last_name' => 'User',
        'address' => 'Address',
        'phone' => '09123456789',
        'email' => 'normal@example.test',
        'username' => 'normal-user',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ]);

    $response->assertCreated();
    expect(User::where('email', 'normal@example.test')->value('role'))->toBe('user');
});

it('prevents users from viewing another users borrow request', function () {
    $owner = apiUser();
    $other = apiUser();
    $borrowRequest = BorrowRequest::create([
        'user_id' => $owner->id,
        'barangay' => 'Barangay',
        'purpose' => 'Testing',
        'borrow_date' => now()->toDateString(),
        'expected_return_date' => now()->addDay()->toDateString(),
    ]);

    $this->actingAs($other, 'sanctum')
        ->getJson("/api/borrow-requests/{$borrowRequest->id}")
        ->assertForbidden();
});

it('prevents repeated approval from creating another transaction', function () {
    $admin = apiUser(['role' => 'admin']);
    $category = Category::create(['category_name' => 'Cameras', 'description' => '']);
    $equipment = Equipment::create([
        'category_id' => $category->id,
        'equipment_name' => 'Camera',
        'total_quantity' => 2,
        'available_quantity' => 2,
    ]);
    $borrowRequest = BorrowRequest::create([
        'user_id' => apiUser()->id,
        'barangay' => 'Barangay',
        'purpose' => 'Testing',
        'borrow_date' => now()->toDateString(),
        'expected_return_date' => now()->addDay()->toDateString(),
    ]);
    $borrowRequest->items()->create(['equipment_id' => $equipment->id, 'quantity' => 1]);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/borrow-requests/{$borrowRequest->id}/approve")
        ->assertOk();
    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/borrow-requests/{$borrowRequest->id}/approve")
        ->assertStatus(422);

    expect($borrowRequest->fresh()->transaction)->not->toBeNull();
    expect($borrowRequest->fresh()->transaction()->count())->toBe(1);
});

it('prevents demoting the last administrator', function () {
    $admin = apiUser(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/users/{$admin->id}", ['role' => 'user'])
        ->assertStatus(422);

    expect($admin->fresh()->role)->toBe('admin');
});

it('rejects a repeated return after the request is returned', function () {
    $admin = apiUser(['role' => 'admin']);
    $category = Category::create(['category_name' => 'Projectors', 'description' => '']);
    $equipment = Equipment::create([
        'category_id' => $category->id,
        'equipment_name' => 'Projector',
        'total_quantity' => 1,
        'available_quantity' => 0,
    ]);
    $borrowRequest = BorrowRequest::create([
        'user_id' => apiUser()->id,
        'barangay' => 'Barangay',
        'purpose' => 'Testing',
        'borrow_date' => now()->toDateString(),
        'expected_return_date' => now()->addDay()->toDateString(),
        'status' => 'Borrowed',
    ]);
    $borrowRequest->items()->create(['equipment_id' => $equipment->id, 'quantity' => 1]);
    $transaction = Transaction::create([
        'borrow_request_id' => $borrowRequest->id,
        'approved_by' => $admin->id,
        'approved_at' => now(),
        'released_at' => now(),
    ]);
    $payload = ['items' => [['equipment_id' => $equipment->id, 'quantity_returned' => 1, 'item_condition' => 'Excellent']]];

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/transactions/{$transaction->id}/return", $payload)
        ->assertOk();
    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/transactions/{$transaction->id}/return", $payload)
        ->assertStatus(422);

    expect($transaction->fresh()->returnLogs)->toHaveCount(1);
    expect($equipment->fresh()->available_quantity)->toBe(1);
});

it('rejects an over-return without changing inventory', function () {
    $admin = apiUser(['role' => 'admin']);
    $category = Category::create(['category_name' => 'Speakers', 'description' => '']);
    $equipment = Equipment::create([
        'category_id' => $category->id,
        'equipment_name' => 'Speaker',
        'total_quantity' => 2,
        'available_quantity' => 0,
    ]);
    $borrowRequest = BorrowRequest::create([
        'user_id' => apiUser()->id,
        'barangay' => 'Barangay',
        'purpose' => 'Testing',
        'borrow_date' => now()->toDateString(),
        'expected_return_date' => now()->addDay()->toDateString(),
        'status' => 'Borrowed',
    ]);
    $borrowRequest->items()->create(['equipment_id' => $equipment->id, 'quantity' => 2]);
    $transaction = Transaction::create([
        'borrow_request_id' => $borrowRequest->id,
        'approved_by' => $admin->id,
        'approved_at' => now(),
        'released_at' => now(),
    ]);
    $payload = ['items' => [['equipment_id' => $equipment->id, 'quantity_returned' => 1, 'item_condition' => 'Excellent']]];

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/transactions/{$transaction->id}/return", $payload)
        ->assertOk();
    $overReturn = ['items' => [['equipment_id' => $equipment->id, 'quantity_returned' => 2, 'item_condition' => 'Excellent']]];

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/admin/transactions/{$transaction->id}/return", $overReturn)
        ->assertStatus(422);

    expect($equipment->fresh()->available_quantity)->toBe(1);
    expect($transaction->fresh()->returnLogs)->toHaveCount(1);
});

it('does not serialize disabled_at or allow it during registration', function () {
    $user = apiUser();
    $user->forceFill(['disabled_at' => now()])->save();
    $user->refresh();

    expect($user->toArray())->not->toHaveKey('disabled_at');
    expect($user->fill(['disabled_at' => now()])->isDirty('disabled_at'))->toBeFalse();
});
