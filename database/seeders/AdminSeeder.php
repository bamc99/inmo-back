<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\AdminProfile;
use App\Models\AdminProfileImage;
use App\Models\Attachment;
use App\Models\ClientProfile;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        $seedUser = Admin::where('email', 'bruno.mendoza@ideia.com.mx')->first();

        if (!$seedUser) {

            $adminRole = Role::where('name', 'admin')
                ->where('guard_name', 'admin-api')->first();

            if(!$adminRole) {
                $adminRole = Role::create([
                    'name' => 'user',
                    'guard_name' => 'admin-api'
                ]);
            }

            Admin::factory()->create([
                'name' => 'Bruno',
                'email' => 'bruno.mendoza@ideia.com.mx',
                'password' => Hash::make('141621'),
            ])->each(function ($admin) use ($adminRole) {

                $adminProfile = AdminProfile::factory()->create([
                    'admin_id' => $admin->id,
                ]);

                $imageNumber = rand(1, 23);
                $imageFilename = $imageNumber !== 23 ? "profile-picture-{$imageNumber}.png" : "default-profile.png";
                $imagePath = public_path("img/profile-images/{$imageFilename}");
                $fileContents = file_get_contents($imagePath);

                /** @var \App\Models\Attachment $attachment **/
                $attachment = Attachment::factory()->create([
                    'original_name' => $imageFilename,
                    'mime' => 'image/png',
                    'extension' => 'png',
                    'size' => strlen($fileContents),
                    'path' => 'admin-profile-images/',
                    'disk' => 'public',
                ]);
                Storage::disk('public')->put("admin-profile-images/{$attachment->name}.{$attachment->extension}", $fileContents);

                AdminProfileImage::factory()->create([
                    'admin_profile_id' => $adminProfile->id,
                    'attachment_id' => $attachment->id,
                ]);

                $admin->assignRole($adminRole);
            });
        }

        // Admin::factory(10)->create()->each(function ($admin) use ($adminRole) {

        //     $adminProfile = AdminProfile::factory()->create([
        //         'admin_id' => $admin->id,
        //     ]);

        //     $imageNumber = rand(1, 23);
        //     $imageFilename = $imageNumber !== 23 ? "profile-picture-{$imageNumber}.png" : "default-profile.png";
        //     $imagePath = public_path("img/profile-images/{$imageFilename}");
        //     $fileContents = file_get_contents($imagePath);

        //     /** @var \App\Models\Attachment $attachment **/
        //     $attachment = Attachment::factory()->create([
        //         'original_name' => $imageFilename,
        //         'mime' => 'image/png',
        //         'extension' => 'png',
        //         'size' => strlen($fileContents),
        //         'path' => 'admin-profile-images/',
        //         'disk' => 'public',
        //     ]);
        //     Storage::disk('public')->put("admin-profile-images/{$attachment->name}.{$attachment->extension}", $fileContents);

        //     AdminProfileImage::factory()->create([
        //         'admin_profile_id' => $adminProfile->id,
        //         'attachment_id' => $attachment->id,
        //     ]);

        //     $admin->assignRole($adminRole);
        // });
    }
}
