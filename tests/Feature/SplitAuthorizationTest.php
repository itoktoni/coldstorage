<?php

use App\Models\User;

test('admin can open the split list route', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)->get('/wms/split');

    $response->assertRedirect(route('wms-split.getTable'));
});
