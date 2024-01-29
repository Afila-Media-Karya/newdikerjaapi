<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
trait Option
{
    public function status_kawin(){
        return DB::table("tb_status_kawin")->select('id','status_kawin as value')->get();
    }

    public function golongan_pangkat(){
        return DB::table("tb_golongan")->select('id','golongan as value')->get();
    }

    public function pendidikan(){
        return DB::table("tb_pendidikan")->select('id','pendidikan as value')->get();
    }
}