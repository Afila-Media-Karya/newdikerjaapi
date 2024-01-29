<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Auth;
trait Pegawai
{
    public function findJabatanUser(){
        return DB::table('tb_pegawai')->select("tb_jabatan.id as jabatan")->join('tb_jabatan','tb_jabatan.id_pegawai','tb_pegawai.id')->join("tb_master_jabatan",'tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')->join('tb_satuan_kerja','tb_pegawai.id_satuan_kerja','=','tb_satuan_kerja.id')->where('tb_pegawai.id',Auth::user()->id_pegawai)->first();
    }

    public function findJabatan(){
        return DB::table('users')->where('users.id', Auth::user()->id)
            ->select('users.id','tb_jabatan.status as status_jabatan','tb_jabatan.id_satuan_kerja as satuan_kerja','tb_master_jabatan.id_kelompok_jabatan','tb_pegawai.id as id_pegawai')
            ->join('tb_pegawai','users.id_pegawai','=','tb_pegawai.id')
            ->join('tb_jabatan','tb_jabatan.id_pegawai','=','tb_pegawai.id')
            ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
            ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
            ->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
            ->join('tb_lokasi','tb_jabatan.id_lokasi_kerja','tb_lokasi.id')
            ->join('tb_lokasi as tb_lokasi_apel', 'tb_jabatan.id_lokasi_apel', '=', 'tb_lokasi_apel.id')
            ->orderBy(DB::raw("FIELD(status_jabatan, 'pj', 'definitif', 'plt')"))
            ->first();
    }
}