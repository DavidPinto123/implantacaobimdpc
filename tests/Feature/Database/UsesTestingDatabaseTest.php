<?php

use App\Models\User;

it('consegue gravar no banco isolado de testes', function () {
    $user = User::factory()->create();

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
});
