<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Hash;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = new User();
        $user->id_pegawai = 1;
        $user->username = 'tespegawai';
        $user->password = Hash::make('password');
        $user->role = '2';
        $user->status = true;
        $user->save();
    }
}
