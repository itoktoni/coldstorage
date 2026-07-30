<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContentType;
use App\Models\CustomField;
use App\Models\FieldGroup;

class ContainerFieldSeeder extends Seeder
{
    public function run()
    {
        // Define section layouts for flexible content
        $sectionLayouts = [
            [
                'name' => 'hero',
                'label' => 'Hero Banner',
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea'],
                    ['name' => 'image', 'label' => 'Background Image', 'type' => 'image'],
                    ['name' => 'button_text', 'label' => 'Button Text', 'type' => 'text'],
                    ['name' => 'button_url', 'label' => 'Button URL', 'type' => 'url'],
                ],
            ],
            [
                'name' => 'slider',
                'label' => 'Slider',
                'fields' => [
                    ['name' => 'autoplay', 'label' => 'Autoplay', 'type' => 'toggle'],
                    ['name' => 'dots', 'label' => 'Show Dots', 'type' => 'toggle'],
                    ['name' => 'slides', 'label' => 'Slides', 'type' => 'container', 'mode' => 'multiple', 'fields' => [
                        ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                        ['name' => 'button_text', 'label' => 'Button Text', 'type' => 'text'],
                        ['name' => 'button_url', 'label' => 'Button URL', 'type' => 'url'],
                    ]],
                ],
            ],
            [
                'name' => 'cta',
                'label' => 'Call to Action',
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'button_text', 'label' => 'Button Text', 'type' => 'text'],
                    ['name' => 'button_url', 'label' => 'Button URL', 'type' => 'url'],
                    ['name' => 'background_color', 'label' => 'Background Color', 'type' => 'color'],
                ],
            ],
            [
                'name' => 'image_left_right',
                'label' => 'Image Left/Right',
                'fields' => [
                    ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'position', 'label' => 'Image Position', 'type' => 'select', 'config' => ['options' => ['left' => 'Left', 'right' => 'Right']]],
                    ['name' => 'button_text', 'label' => 'Button Text', 'type' => 'text'],
                    ['name' => 'button_url', 'label' => 'Button URL', 'type' => 'url'],
                ],
            ],
            [
                'name' => 'text_block',
                'label' => 'Text Block',
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'content', 'label' => 'Content', 'type' => 'wysiwyg'],
                ],
            ],
            [
                'name' => 'gallery',
                'label' => 'Gallery',
                'fields' => [
                    ['name' => 'images', 'label' => 'Images', 'type' => 'assets'],
                    ['name' => 'columns', 'label' => 'Columns', 'type' => 'select', 'config' => ['options' => ['2' => '2 Columns', '3' => '3 Columns', '4' => '4 Columns']]],
                    ['name' => 'caption', 'label' => 'Show Caption', 'type' => 'toggle'],
                ],
            ],
            [
                'name' => 'faq',
                'label' => 'FAQ',
                'fields' => [
                    ['name' => 'title', 'label' => 'Section Title', 'type' => 'text'],
                    ['name' => 'items', 'label' => 'FAQ Items', 'type' => 'container', 'mode' => 'multiple', 'fields' => [
                        ['name' => 'question', 'label' => 'Question', 'type' => 'text'],
                        ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea'],
                    ]],
                ],
            ],
            [
                'name' => 'pricing',
                'label' => 'Pricing',
                'fields' => [
                    ['name' => 'title', 'label' => 'Section Title', 'type' => 'text'],
                    ['name' => 'plans', 'label' => 'Plans', 'type' => 'container', 'mode' => 'multiple', 'fields' => [
                        ['name' => 'name', 'label' => 'Plan Name', 'type' => 'text'],
                        ['name' => 'price', 'label' => 'Price', 'type' => 'text'],
                        ['name' => 'features', 'label' => 'Features', 'type' => 'container', 'mode' => 'multiple', 'fields' => [
                            ['name' => 'title', 'label' => 'Feature', 'type' => 'text'],
                            ['name' => 'included', 'label' => 'Included', 'type' => 'toggle'],
                        ]],
                    ]],
                ],
            ],
            [
                'name' => 'footer',
                'label' => 'Footer',
                'fields' => [
                    ['name' => 'company_name', 'label' => 'Company Name', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea'],
                    ['name' => 'copyright', 'label' => 'Copyright Text', 'type' => 'text'],
                    ['name' => 'facebook', 'label' => 'Facebook URL', 'type' => 'url'],
                    ['name' => 'instagram', 'label' => 'Instagram URL', 'type' => 'url'],
                    ['name' => 'twitter', 'label' => 'Twitter URL', 'type' => 'url'],
                ],
            ],
        ];

        // Process each content type
        $contentTypes = ContentType::all();

        foreach ($contentTypes as $contentType) {
            // Convert existing field groups to containers
            $fieldGroups = FieldGroup::where('content_type_id', $contentType->id)->get();

            foreach ($fieldGroups as $fieldGroup) {
                // Create a container field for each field group
                $containerField = CustomField::create([
                    'name' => strtolower(str_replace(' ', '_', $fieldGroup->name)),
                    'label' => $fieldGroup->name,
                    'type' => 'container',
                    'mode' => 'multiple',
                    'is_required' => false,
                    'sort_order' => $fieldGroup->sort_order,
                    'content_type_id' => $contentType->id,
                ]);

                // Move each field from the field group to the container
                if (!empty($fieldGroup->field_ids)) {
                    foreach ($fieldGroup->field_ids as $fieldId) {
                        $field = CustomField::find($fieldId);
                        if ($field) {
                            $field->update([
                                'parent_id' => $containerField->id,
                                'sort_order' => $field->sort_order,
                            ]);
                        }
                    }
                }

                $fieldGroup->delete();
            }
        }

        // Update repeater/flexible_content types
        $customFields = CustomField::whereIn('type', ['repeater', 'flexible_content'])->get();

        foreach ($customFields as $field) {
            if ($field->type === 'repeater') {
                $field->update(['type' => 'container', 'mode' => 'multiple']);
            } elseif ($field->type === 'flexible_content') {
                $field->update([
                    'type' => 'container',
                    'mode' => 'flexible',
                    'layouts' => $field->config['layouts'] ?? [],
                    'min' => $field->config['min'] ?? 0,
                    'max' => $field->config['max'] ?? 0,
                ]);
            }
            if ($field->type === 'container') {
                $field->update(['config' => []]);
            }
        }

        // Create Page Builder container for Page content type
        $pageContentType = ContentType::where('slug', 'page')->first();
        if ($pageContentType) {
            CustomField::create([
                'name' => 'page_builder',
                'label' => 'Page Builder',
                'type' => 'container',
                'mode' => 'flexible',
                'is_required' => false,
                'sort_order' => 1,
                'content_type_id' => $pageContentType->id,
                'layouts' => $sectionLayouts,
            ]);
        }

        // Create Page Builder container for Post content type
        $postContentType = ContentType::where('slug', 'post')->first();
        if ($postContentType) {
            CustomField::create([
                'name' => 'content_builder',
                'label' => 'Content Builder',
                'type' => 'container',
                'mode' => 'flexible',
                'is_required' => false,
                'sort_order' => 1,
                'content_type_id' => $postContentType->id,
                'layouts' => $sectionLayouts,
            ]);
        }
    }
}