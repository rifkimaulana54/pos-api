<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $this->command->info('Truncating Store tables');
    	$this->truncateTable();

        $stores = [
            [
                'name'              => 'Store1',
                'company_id'        => 1,
                'address'           => 'Jl. Syek Nurjati Desa Wanasaba Kidul Kec. Talun Kab. Cirebon',
                'telepone'          => '085314472422',
                'created_id'        => 1,
                'created_name'      => 'Superadmin',
                'updated_id'        => 1,
                'updated_name'      => 'Superadmin',
            ],

            [
                'name'              => 'Store2',
                'company_id'        => 1,
                'address'           => 'Jl. Test',
                'telepone'          => '0812345678',
                'created_id'        => 1,
                'created_name'      => 'Superadmin',
                'updated_id'        => 1,
                'updated_name'      => 'Superadmin',
            ]
        ];

        foreach ($stores as $data) 
        {
            $this->command->info('Create store #'.$data['name']);
            $store = new Store;
            $store->store_name = $data['name'];
            $store->store_address = $data['address'];
            $store->no_telepone = $data['telepone'];
            $store->company_id = $data['company_id'];
            $store->created_id = $data['created_id'];
            $store->created_name = $data['created_name'];
            $store->updated_id = $data['updated_id'];
            $store->updated_name = $data['updated_name'];
            $store->save();
        }
    }

    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('user_tm_stores')->truncate();
    }
}
