<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Client;
use App\Models\ClientProfile;
use App\Models\ClientProfileImage;
use App\Models\Quotation;
use App\Models\Role;
use Database\Factories\ClientFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var \App\Models\Client $client **/
        Client::factory(5)->create()->each( function ( $client ) {
            /** @var \App\Models\ClientProfile $clientProfile **/
            $clientProfile = ClientProfile::factory()->create([
                'client_id' => $client->id,
            ]);

            /** @var \App\Models\Attachment $attachment **/
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
                'path' => 'client-profile-images/',
                'disk' => 'public',
            ]);
            Storage::disk('public')->put("client-profile-images/{$attachment->name}.{$attachment->extension}", $fileContents);

            ClientProfileImage::factory()->create([
                'client_profile_id' => $clientProfile->id,
                'attachment_id' => $attachment->id,
            ]);

            $client->assignRole('user');

            Quotation::factory(5)->create([ 'client_id' => $client->id ]);
        });
    }
}
