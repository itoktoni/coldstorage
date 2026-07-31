<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Define menu items for desktop sidebar, mobile drawer, and bottom nav.
    | Each item: route (string), icon (string), label (string)
    | Sections: label (string), items (array)
    | Bottom nav: only 5 items max, uses short label
    |
    */

    'sidebar' => [
        [
            'label' => null,
            'items' => [
                ['route' => 'dashboard', 'icon' => 'dashboard', 'label' => 'Dashboard'],
            ],
        ],
        [
            'label' => 'Management',
            'items' => [
                ['route' => 'user.getTable', 'icon' => 'manage_accounts', 'label' => 'Users'],
            ],
        ],
        [
            'label' => 'CMS',
            'items' => [
                ['route' => 'cms-type.getTable', 'icon' => 'category', 'label' => 'Types'],
                // ['route' => 'content-type.getTable', 'icon' => 'article', 'label' => 'Content Types'],
                ['route' => 'field.getTable', 'icon' => 'account_tree', 'label' => 'Fields'],
                // ['route' => 'custom-field.getTable', 'icon' => 'text_fields', 'label' => 'Custom Fields'],
                // ['route' => 'field-group.getTable', 'icon' => 'view_agenda', 'label' => 'Field Groups'],
                ['route' => 'section.getTable', 'icon' => 'view_module', 'label' => 'Sections'],
                ['route' => 'content.getTable', 'icon' => 'library_books', 'label' => 'Content'],
                // ['route' => 'content-entry.getTable', 'icon' => 'edit_note', 'label' => 'Entries'],
                ['route' => 'category.getTable', 'icon' => 'category', 'label' => 'Categories'],
                ['route' => 'tag.getTable', 'icon' => 'label', 'label' => 'Tags'],
                ['route' => 'menu.getTable', 'icon' => 'menu', 'label' => 'Menus'],
            ],
        ],
        [
            'label' => 'Master Data',
            'items' => [
                ['route' => 'wms-gudang.getTable', 'icon' => 'warehouse', 'label' => 'Gudang'],
                ['route' => 'wms-lokasi.getTable', 'icon' => 'place', 'label' => 'Lokasi'],
                ['route' => 'wms-product.getTable', 'icon' => 'inventory_2', 'label' => 'Product'],
                ['route' => 'wms-stock.getTable', 'icon' => 'store', 'label' => 'Stock'],
            ],
        ],
        [
            'label' => 'Procurement',
            'items' => [
                ['route' => 'wms-po.getTable', 'icon' => 'shopping_cart', 'label' => 'Purchase Order'],
                ['route' => 'wms-po-detail.getTable', 'icon' => 'list_alt', 'label' => 'PO Detail'],
            ],
        ],
        [
            'label' => 'Inbound',
            'items' => [
                ['route' => 'wms-masuk-detail.getTable', 'icon' => 'input', 'label' => 'Masuk Detail'],
                ['route' => 'wms-masuk-realisasi.getTable', 'icon' => 'check_circle', 'label' => 'Masuk Realisasi'],
            ],
        ],
        [
            'label' => 'Outbound',
            'items' => [
                ['route' => 'wms-keluar.getTable', 'icon' => 'output', 'label' => 'Keluar'],
                ['route' => 'wms-keluar-detail.getTable', 'icon' => 'description', 'label' => 'Keluar Detail'],
                ['route' => 'wms-keluar-realisasi.getTable', 'icon' => 'task_alt', 'label' => 'Keluar Realisasi'],
            ],
        ],
        [
            'label' => 'Split',
            'items' => [
                ['route' => 'wms-split.getTable', 'icon' => 'call_split', 'label' => 'Split Stock'],
            ],
        ],
        [
            'label' => 'Settings',
            'items' => [
                ['route' => 'profile.edit', 'icon' => 'person', 'label' => 'My Profile'],
                ['route' => 'settings.env', 'icon' => 'settings', 'label' => 'Environment'],
            ],
        ],
    ],

    'bottom_nav' => [
        ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Home'],
        ['route' => 'profile.edit', 'icon' => 'person', 'label' => 'Profile'],
    ],

];
