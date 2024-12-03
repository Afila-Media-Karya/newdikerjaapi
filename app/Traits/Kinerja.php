<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Auth;
trait Kinerja
{
    public function kinerja_pegawai($bulan){
        $data = array();
        try {
            $persentase = 0;
            $jabatan = DB::table('tb_jabatan')->join("tb_master_jabatan",'tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')->select("tb_jabatan.target_waktu")->where('id_pegawai',Auth::user()->id_pegawai)->first();

            $aktivitas = DB::table('tb_aktivitas')
            ->select(
                DB::raw('SUM(waktu) as capaian'),
                DB::raw('COUNT(*) as total_aktivitas')
            )
            ->where('id_pegawai', Auth::user()->id_pegawai)
            ->where('validation',1)
            ->whereMonth('tanggal',$bulan)
            ->first();

            // return $aktivitas;

            if ($jabatan->target_waktu > 0) {
                $persentase = ($aktivitas->capaian / $jabatan->target_waktu) * 100;
            }

            $data = [
                'target' => $jabatan->target_waktu,
                'capaian' => $aktivitas->capaian,
                'prestasi' => round($persentase,2),
                'total_aktivitas' => $aktivitas->total_aktivitas 
            ];
        } catch (\Exception $e) {
           return $e->getMessage();
        }
        return $data;
    }

    public function checkValidasiLimaHari($params){
        $currentDate = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime('-6 days', strtotime($currentDate)));
        // return $params;

        if ($params <= $futureDate) {
            return true;
        }else{
            return false;
        }
    }
}