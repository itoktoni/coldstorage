<?php

namespace Database\Seeders;

use App\Models\ContentType;
use App\Models\FieldGroup;
use App\Models\CustomField;
use Illuminate\Database\Seeder;

class UserFieldGroupSeeder extends Seeder
{
    public function run(): void
    {
        // Get the User content type
        $userContentType = ContentType::where('slug', 'user')->first();

        if (!$userContentType) {
            return;
        }

        // Create a FieldGroup for user custom fields
        $userFieldGroup = FieldGroup::create([
            'name' => 'User Profile Fields',
            'description' => 'Custom fields for user profiles',
            'content_type_id' => $userContentType->id,
            'sort_order' => 1,
            'is_active' => true,
            'field_ids' => []
        ]);

        // Create some sample user custom fields
        $phone = CustomField::create([
            'name' => 'phone',
            'label' => 'Phone Number',
            'type' => 'text',
            'is_required' => false,
            'sort_order' => 1
        ]);

        $address = CustomField::create([
            'name' => 'address',
            'label' => 'Address',
            'type' => 'textarea',
            'is_required' => false,
            'sort_order' => 2
        ]);

        $bio = CustomField::create([
            'name' => 'bio',
            'label' => 'Biography',
            'type' => 'textarea',
            'is_required' => false,
            'sort_order' => 3
        ]);

        // Attach fields to the field group
        $userFieldGroup->field_ids = [$phone->id, $address->id, $bio->id];
        $userFieldGroup->save();
    }
}