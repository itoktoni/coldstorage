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
            'label' => 'Master Data',
            'items' => [
                ['route' => 'category.getTable', 'icon' => 'category', 'label' => 'Categories'],
                ['route' => 'tag.getTable', 'icon' => 'label', 'label' => 'Tags'],
                ['route' => 'wms-customer.getTable', 'icon' => 'groups', 'label' => 'Customer'],
                ['route' => 'wms-gudang.getTable', 'icon' => 'warehouse', 'label' => 'Gudang'],
                ['route' => 'wms-lokasi.getTable', 'icon' => 'place', 'label' => 'Lokasi'],
                ['route' => 'wms-product.getTable', 'icon' => 'inventory_2', 'label' => 'Product'],
                ['route' => 'wms-barcode.generate', 'icon' => 'qr_code', 'label' => 'Generate Barcode'],

            ],
        ],
        [
            'label' => 'Stock',
            'items' => [
                ['route' => 'wms-stock.getTable', 'icon' => 'store', 'label' => 'Stock'],
                ['route' => 'wms-stock-flow.index', 'icon' => 'swap_horiz', 'label' => 'Stock Flow'],
                ['route' => 'wms-stock-sales.index', 'icon' => 'storefront', 'label' => 'Stock Sales'],
                ['route' => 'wms-stock-card.index', 'icon' => 'history', 'label' => 'Kartu Stock'],
                ['route' => 'wms-staging-recap.index', 'icon' => 'inventory', 'label' => 'Staging Recap'],
            ],
        ],
        [
            'label' => 'Procurement',
            'items' => [
                ['route' => 'wms-po.getTable', 'icon' => 'shopping_cart', 'label' => 'Purchase Order'],
                ['route' => 'wms-po-detail.getTable', 'icon' => 'list_alt', 'label' => 'PO Detail'],
                ['route' => 'wms-supplier.getTable', 'icon' => 'business', 'label' => 'Supplier'],
            ],
        ],
        [
            'label' => 'Inbound',
            'items' => [
                ['route' => 'wms-masuk-detail.getTable', 'icon' => 'input', 'label' => 'Masuk Detail'],
                ['route' => 'wms-masuk-realisasi.getTable', 'icon' => 'check_circle', 'label' => 'Masuk Realisasi'],
                ['route' => 'wms-forklift.index', 'icon' => 'local_shipping', 'label' => 'Forklift'],
            ],
        ],
        [
            'label' => 'Sales',
            'items' => [
                ['route' => 'wms-so.getTable', 'icon' => 'point_of_sale', 'label' => 'Sales Order'],
                ['route' => 'wms-so-prepare.index', 'icon' => 'assignment', 'label' => 'Prepare SO'],
            ],
        ],
        [
            'label' => 'Outbound',
            'items' => [
                ['route' => 'wms-keluar.getTable', 'icon' => 'output', 'label' => 'Keluar'],
                ['route' => 'wms-keluar-detail.getTable', 'icon' => 'description', 'label' => 'Keluar Detail', 'match' => ['wms-keluar-realisasi-scan.*']],
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
