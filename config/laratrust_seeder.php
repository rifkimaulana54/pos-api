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
            'report'    => 'r',
            'orders' => 'c,r,u,d',
            'store' => 'c,r,u,d',
            'category' => 'c,r,u,d',
            'product' => 'c,r,u,d',
            'cms-order' => 'r',
            'cms-user' => 'r',
            'cms-acl-role' => 'r',
            'cms-store' => 'r',
            'cms-company' => 'r',
            'cms-category' => 'r',
            'cms-product' => 'r'
        ],
        'admin' => [
            'acl' => 'c,r,u,d',
            'profile' => 'r,u',
            'users' => 'c,r,u,d',
            'company' => 'c,r,u,d',
            'report'    => 'r',
            'orders' => 'r',
            'store' => 'c,r,u,d',
            'category' => 'c,r,u,d',
            'product' => 'c,r,u,d',
            'cms-dashboard' => 'r',
            'cms-order' => 'r',
            'cms-user' => 'r',
            'cms-acl-role' => 'r',
            'cms-store' => 'r',
            'cms-company' => 'r',
            'cms-category' => 'r',
            'cms-product' => 'r'

        ],
        'kasir' => [
            'profile' => 'r,u',
            'report'    => 'r',
            'orders' => 'c,r,u',
            'category' => 'r',
            'product' => 'r',
            'cms-dashboard' => 'r',
            'cms-order' => 'r',
        ]
    ],

    'permissions_map' => [
        'c' => 'create',
        'r' => 'read',
        'u' => 'update',
        'd' => 'delete'
    ]
];
