<?php

return [
    /**
     * Control if the seeder should create a user per role while seeding the data.
     */
    'create_users' => true,

    /**
     * Control if all the laratrust tables should be truncated before running the seeder.
     */
    'truncate_tables' => true,

    'roles_structure' => [
        'superadmin' => [
            'acl' => 'c,r,u,d',
            'profile' => 'r,u',
            'users' => 'c,r,u,d',
            'company' => 'c,r,u,d',
            'scheduler' => 'c,r,u,d',
            'messaging-service' => 'c,r,u,d',
            'report'    => 'r',
            'orders' => 'c,r,u,d',
            'inventory' => 'c,r,u,d',
            'store' => 'c,r,u,d',
            'category' => 'c,r,u,d',
            'product' => 'c,r,u,d'
        ],
        'admin' => [
            'acl' => 'c,r,u,d',
            'profile' => 'r,u',
            'users' => 'c,r,u,d',
            'company' => 'r',
            'scheduler' => 'c,r,u,d',
            'messaging-service' => 'c,r,u,d',
            'report'    => 'r',
            'orders' => 'c,r,u,d',
            'inventory' => 'c,r,u,d',
            'category' => 'c,r,u,d',
            'product' => 'c,r,u,d'
        ],
        'kasir' => [
            'profile' => 'r,u',
            'report'    => 'r',
            'vehicle' => 'c,r,u,d',
            'vehicle_type' => 'r',
            'location_type' => 'r',
            'orders' => 'c,r,u',
            'maintenance' => 'c,r,u',
            'inventory' => 'c,r,u',
            'users' => 'c,r,u',
            'company_admin' => 'c,r,u,d',
            'category' => 'r',
            'product' => 'r'
        ]
    ],

    'permissions_map' => [
        'c' => 'create',
        'r' => 'read',
        'u' => 'update',
        'd' => 'delete'
    ]
];
