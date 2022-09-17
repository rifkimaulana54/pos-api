<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $this->command->info('Truncating Company tables');
    	$this->truncateTable();

    	//1
        $datas = [
            [
                'name'              =>  'company1',
                'display_name'      =>  'Company1',
                'parent_id'         =>  null,
            ]
        ];

        foreach ($datas as $data) 
        {
            $this->command->info('Create company #'.$data['name']);
            $company = new Company;
            $company->name = $data['name'];
            $company->display_name = $data['display_name'];
            $company->save();
        }
    }

    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('user_tm_companies')->truncate();
    }
}
