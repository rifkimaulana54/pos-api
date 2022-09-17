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
            'locations' => 'c,r,u,d',
            'profile' => 'r,u',
            'users' => 'c,r,u,d',
            'user-device'   => 'c,r,u',
            'company' => 'c,r,u,d',
            'scheduler' => 'c,r,u,d',
            'messaging-service' => 'c,r,u,d',
            'report'    => 'r',
            'promotion' => 'c,r,u,d',
            'vehicles' => 'c,r,u,d',
            'orders' => 'c,r,u,d',
            'maintenance' => 'c,r,u,d',
            'inventory' => 'c,r,u,d',
            'content' => 'c,r,u,d',
            'vehicle_type' => 'c,r,u,d',
            'category' => 'c,r,u,d',
            'product' => 'c,r,u,d'
        ],
        'admin' => [
            'locations' => 'c,r,u,d',
            'profile' => 'r,u',
            'users' => 'c,r,u,d',
            'user-device'   => 'c,r,u',
            // 'queue-type' => 'c,r,u,d',
            // 'queue-subtype' => 'c,r,u,d',
            // 'queue-transaction' => 'c,r,u,d',
            // 'queue-subtransaction' => 'c,r,u,d',
            'company' => 'c,r,u,d',
            // 'queue-attribute' => 'c,r,u,d',
            'scheduler' => 'c,r,u,d',
            'messaging-service' => 'c,r,u,d',
            'report'    => 'r',
            'promotion' => 'c,r,u,d',
            'vehicles' => 'c,r,u,d',
            'orders' => 'c,r,u,d',
            'maintenance' => 'c,r,u,d',
            'inventory' => 'c,r,u,d',
            'content' => 'c,r,u,d',
            'vehicle_type' => 'c,r,u,d',
            'location_type' => 'c,r,u,d'
        ],
        'vendor' => [
            'profile' => 'r,u',
            'user-device'   => 'c,r,u',
            // 'desk-number' => 'c,r,u,d',
            'locations' => 'c,r,u',
            // 'queue-type' => 'r',
            // 'app-service' => 'r',
            // 'queue-transaction' => 'c,r,u',
            // 'queue-subtransaction' => 'c,r,u',
            'report'    => 'r',
            'vehicle' => 'c,r,u,d',
            'vehicle_type' => 'r',
            'location_type' => 'r',
            'orders' => 'c,r,u',
            'maintenance' => 'c,r,u',
            'inventory' => 'c,r,u',
            'users' => 'c,r,u',
            'company_admin' => 'c,r,u,d',
        ],
        'sales' => [
            'profile' => 'r,u',
            'user-device'   => 'c,r,u',
            // 'desk-number' => 'c,r,u,d',
            'locations' => 'r',
            // 'queue-type' => 'r',
            // 'app-service' => 'r',
            // 'queue-transaction' => 'c,r,u',
            // 'queue-subtransaction' => 'c,r,u',
            'report'    => 'r',
            'vehicle' => 'r',
            'vehicle_type' => 'r',
            'location_type' => 'r',
            'orders' => 'c,r,u',
            // 'maintenance' => 'c,r,u',
            // 'inventory' => 'c,r,u',
            'users' => 'c,r,u',
        ],
        'customer' => [
            'profile' => 'r,u',
            'user-device'   => 'c,r,u',
            // 'desk-number' => 'c,r,u,d',
            'locations' => 'r',
            // 'queue-type' => 'r',
            // 'app-service' => 'r',
            // 'queue-transaction' => 'c,r,u',
            // 'queue-subtransaction' => 'c,r,u',
            'report'    => 'r',
            // 'vehicle' => 'r',
            'vehicle_type' => 'r',
            'location_type' => 'r',
            'orders' => 'c,r',
            // 'maintenance' => 'c,r,u',
            // 'inventory' => 'c,r,u',
            // 'users' => 'c,r,u',
        ],
        // 'picker' => [
        //     'profile' => 'r,u',
        //     'user-device'   => 'c,r,u',
        //     'app-picker' => 'r',
        //     'queue-subtransaction' => 'r,u',
        //     'report'    => 'r',
        // ],
        // 'kiosk' => [
        //     'profile' => 'r,u',
        //     'user-device'   => 'c,r,u',
        //     'app-customer' => 'r',
        //     'queue-transaction' => 'c,r',
        //     'queue-subtransaction' => 'c,r',
        //     'report'    => 'r',
        // ],
        'customer-support' => [
            'profile' => 'r,u',
        ],
        // 'tv'    => [
        //     'profile' => 'r,u',
        //     'users' => 'r',
        //     'user-device'   => 'c,r,u',
        //     'queue-transaction' => 'r',
        //     'queue-subtransaction' => 'r',
        //     'app-tv'    => 'r'
        // ]
    ],

    'permissions_map' => [
        'c' => 'create',
        'r' => 'read',
        'u' => 'update',
        'd' => 'delete'
    ]
];
