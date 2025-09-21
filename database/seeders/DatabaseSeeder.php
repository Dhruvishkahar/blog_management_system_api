<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $blogs = \App\Models\Blog::factory()->count(1000)->create();
        foreach ($blogs as $blog) {
            \App\Models\BlogLike::create([
                'user_id' => \App\Models\User::first()->id,
                'likeable_id' => $blog->id,
                'likeable_type' => \App\Models\Blog::class,
            ]);
        }
    }
}
