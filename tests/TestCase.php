<?php

namespace Tests;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        User::creating(function (User $user): void {
            if ($user->role === 'kasir' && ! $user->store_id) {
                $user->store_id = Store::query()->orderBy('id')->value('id');
            }
        });

        Product::creating(function (Product $product): void {
            if (! $product->store_id) {
                $product->store_id = Store::query()->orderBy('id')->value('id');
            }
        });
    }
}
