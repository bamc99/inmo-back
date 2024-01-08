<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Client;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\UserProfileImage;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedUser = User::where('email', 'gian@gian.com')->first();
        if(!$seedUser) {

            /** @var \App\Models\User $user **/
            // Crear un usuario y asignarle el rol de administrador
            User::factory()->create([
                'name' => 'Gian',
                'user_name' => 'gian.zapata',
                'email' => 'gian@gian.com',
                'password' => Hash::make('Abc123456!'),
                'email_verified_at' => now(),
            ])->each(function ($user) {

                /** @var \App\Models\UserProfile $userProfile **/
                $userProfile = UserProfile::factory()->create(['user_id' => $user->id]);
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
                    'path' => 'user-profile-images/',
                    'disk' => 'public',
                ]);

                Storage::disk('public')->put("user-profile-images/{$attachment->name}.{$attachment->extension}", $fileContents);
                $userProfileImage = UserProfileImage::factory()->create([
                    'user_profile_id' => $userProfile->id,
                    'attachment_id' => $attachment->id,
                ]);
                $userProfile->profileImage()->save($userProfileImage);
                $user->assignRole('admin');

                $newOrganization = Organization::create([
                    'name' => 'Gian Dev',
                    'type' => 'inmobiliaria',
                    'owner_id' => $user->id,
                ]);

                $user->assignRole('owner');

                if( $newOrganization->type === 'inmobiliaria' ) {
                    $user->givePermissionTo('collaborator_inmobiliaria');
                } else if( $newOrganization->type === 'desarrollo' ) {
                    $user->givePermissionTo('collaborator_desarrollo');
                }

                Membership::create([
                    'user_id' => $user->id,
                    'organization_id' => $newOrganization->id,
                ]);

            });
        }

        /** @var \App\Models\User $user **/
        // Crear otros usuarios y asignarles el rol de usuario
        User::factory(10)->create()->each(function ($user) {
            /** @var \App\Models\UserProfile $userProfile **/
            $userProfile = UserProfile::factory()->create(['user_id' => $user->id]);

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
                'path' => 'user-profile-images/',
                'disk' => 'public',
            ]);
            Storage::disk('public')->put("user-profile-images/{$attachment->name}.{$attachment->extension}", $fileContents);

            /** @var \App\Models\UserProfileImage $userProfileImage **/
            $userProfileImage = UserProfileImage::factory()->create([
                'user_profile_id' => $userProfile->id,
                'attachment_id' => $attachment->id,
            ]);

            $userProfile->profileImage()->save($userProfileImage);
            $user->assignRole('user');
        });

    }
}
