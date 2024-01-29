<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pegawai;
class pegawaiSeeders extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = new Pegawai();
        $data->nip = '329463824234324';
        $data->nama = 'Herman';
        $data->tempat_lahir = 'Bulukumba';
        $data->tanggal_lahir = date('Y-m-d');
        $data->jenis_kelamin = 'L';
        $data->golongan = 'golongan A';
        $data->agama = 'Islam';
        $data->status_perkawinan = 'Kawin';
        $data->pendidikan = 'S1';
        $data->pendidikan_lulus = 'S1';
        $data->pendidikan_struktural = 'S1';
        $data->face_character = 'sdfnwejr2334324234';
        $data->id_satuan_kerja = 1;
        $data->status = 1;
        $data->status_verifikasi = true;
        $data->save();
    }
}
