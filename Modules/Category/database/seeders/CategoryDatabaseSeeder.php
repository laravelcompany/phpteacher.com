<?php

namespace Modules\Category\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Category\Models\Category;

class CategoryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');


        $categories = [
            // Books, Courses, Podcasts, Conferences, Source Code.
            'Books',
            'Courses',
            'Podcasts',
            'Conferences',
            'Source Code',
        ];

        foreach ($categories as $category) {
            $category = Category::factory()->create([
                'name' => $category,
                'slug' => '',
                'description' => $this->faker->paragraph,
                'status' => $this->faker->randomElement(Category::getAllNames()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }



        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
