<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SatuanKerja;
class SatuanKerjaSeeders extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = new SatuanKerja;
        $data->nama_satuan_kerja = 'Dinas Pendidikan';
        $data->inisial_satuan_kerja = 'DISDIK';
        $data->kode_satuan_kerja = '001';
        $data->tahun = date('Y');
        $data->save();
    }
}
