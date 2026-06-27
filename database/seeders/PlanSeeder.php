<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'lgu_free', 'name' => 'LGU (Government)', 'price' => 0,
                'billing_cycle' => 'custom', 'is_unlimited' => true, 'is_public' => false,
                'max_ambulances' => null, 'max_members' => null,
            ],
            [
                'code' => 'partner_basic', 'name' => 'Partner — Basic', 'price' => 0,
                'billing_cycle' => 'yearly', 'max_dispatchers' => 2, 'max_drivers' => 5,
                'max_ambulances' => 3, 'max_hospitals' => 1, 'max_members' => 10, 'max_roles_assignable' => 5,
            ],
            [
                'code' => 'partner_pro', 'name' => 'Partner — Pro', 'price' => 0,
                'billing_cycle' => 'yearly', 'max_dispatchers' => 5, 'max_drivers' => 20,
                'max_ambulances' => 10, 'max_hospitals' => 3, 'max_members' => 40, 'max_roles_assignable' => 15,
            ],
        ];

        foreach ($plans as $p) {
            Plan::updateOrCreate(['code' => $p['code']], $p);
        }
    }
}
