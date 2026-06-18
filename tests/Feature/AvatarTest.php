<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('returns empty string for avatar_url when no avatar is uploaded', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = getJson('/api/v1/me');

    $response->assertOk()
        ->assertJsonPath('data.avatar_url', '');
});

it('stores avatar and returns url via /me', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $user->addMedia($file)->toMediaCollection('avatar');

    Sanctum::actingAs($user);

    $response = getJson('/api/v1/me');

    $response->assertOk();
    expect($response->json('data.avatar_url'))->not->toBeEmpty();
});

it('replaces previous avatar on new upload because collection is singleFile', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $user->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('avatar');
    $user->addMedia(UploadedFile::fake()->image('second.jpg'))->toMediaCollection('avatar');

    expect($user->fresh()->getMedia('avatar'))->toHaveCount(1);
});
