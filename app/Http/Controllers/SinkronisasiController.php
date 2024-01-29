<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jabatan;
use App\Models\MasterJabatan;
use App\Models\Absen;
use App\Models\Aktivitas;
use App\Models\Pegawai;
use DB;
use Str;
use App\Traits\Kehadiran;
use Carbon\Carbon;
use App\Models\User;
use Hash;

class SinkronisasiController extends Controller
{
    use Kehadiran;

    public function MasterJabatan(){

        try {
           DB::beginTransaction();

            $jabatan_dikerja_lama = DB::table('tb_jabatan_dikerja_lama')->get();
      
            foreach ($jabatan_dikerja_lama as $key => $value) {
                $jenis_jabatan_value = 0;
                $level_jabatan_value = 0;
                $jenis_jabatan = DB::table('tb_jenis_jabatan')->where('id',$value->id_jenis_jabatan)->first();
                $jenis_jabatan ? $jenis_jabatan_value = $jenis_jabatan->jenis_jabatan : $jenis_jabatan_value = null;
                $jenis_jabatan ? $level_jabatan_value = $jenis_jabatan->level : $level_jabatan_value = null;

                $MasterJabatan = new MasterJabatan();
                $MasterJabatan->id = $value->id;
                $MasterJabatan->uuid = $value->id;
                $MasterJabatan->id_satuan_kerja =  $value->id_satuan_kerja;
                $MasterJabatan->id_lokasi_kerja =  $value->id_lokasi;
                $MasterJabatan->id_kelompok_jabatan =  $value->id_kelompok_jabatan;
                $MasterJabatan->id_parent =  $value->parent_id;
                $MasterJabatan->kode_jabatan =  Str::random(3);
                $MasterJabatan->nama_struktur =  $value->nama_jabatan;
                $MasterJabatan->nama_jabatan =  $value->nama_jabatan;
                $MasterJabatan->jenis_jabatan =  $jenis_jabatan_value;
                $MasterJabatan->level_jabatan =  $level_jabatan_value;
                $MasterJabatan->kelas_jabatan =  $value->kelas_jabatan;
                $MasterJabatan->target_waktu = $value->target_waktu;
                $MasterJabatan->pagu_tpp = $value->nilai_jabatan;
                $MasterJabatan->id_lokasi_apel = $value->id_lokasi;
                $MasterJabatan->status = 1;
                $MasterJabatan->save();

                // if ($value->id_pegawai !== null && $value->id_pegawai !== 100000) {
                    $status = '';
                    if ($value->status_jabatan == 'Definitif') {
                        $status = 'definitif';
                    }else{
                        $status = 'plt';
                    }
                    $jabatan = new Jabatan();
                    $jabatan->id = $value->id;
                    $jabatan->uuid = $value->id;
                    $jabatan->id_pegawai = $value->id_pegawai;
                    $jabatan->id_master_jabatan = $MasterJabatan->id;
                    $jabatan->id_satuan_kerja = $MasterJabatan->id_satuan_kerja;
                    $jabatan->status = $status;
                    $jabatan->pembayaran = $value->pembayaran_tpp;
                    $jabatan->user_insert = $value->id_pegawai;
                    $jabatan->user_update = $value->id_pegawai;
                    $jabatan->save();
                // }
            }

           DB::commit();
        }  catch (\Throwable $e) {
           return $e;
        }    
        
        return true;
    }

    public function absen(){
        $bulan = request('bulan');
        $tahun = date('Y');

       $absenData = DB::table('tb_absen_by_dikerja_lama')
        ->select('id_pegawai', 'tanggal_absen', 'status','jenis','waktu_absen')
        ->groupBy('id_pegawai', 'tanggal_absen', 'status','jenis','waktu_absen')
        ->whereMonth('tanggal_absen',$bulan)
        ->whereYear('tanggal_absen',$tahun)
        ->get();

        $groupedAbsen = [];

        foreach ($absenData as $absen) {
            $key = $absen->tanggal_absen . '-' . $absen->id_pegawai;

            if (!isset($groupedAbsen[$key])) {
                $groupedAbsen[$key] = [];
            }

            $groupedAbsen[$key][] = [
                'tanggal_absen' => $absen->tanggal_absen,
                'id_pegawai' => $absen->id_pegawai,
                'status' => $absen->status,
                'jenis' => $absen->jenis,
                'waktu_absen' => $absen->waktu_absen
            ];
        }

        $groupedAbsen = array_values($groupedAbsen);

        // return $groupedAbsen;

        $new_absen = array();
        foreach ($groupedAbsen as $key => $value) {
            // return count($value);
            if (count($value) === 2) {
                $new_absen = new Absen();
                $new_absen->id_pegawai = $value[0]['id_pegawai'];
                $new_absen->tanggal_absen = $value[0]['tanggal_absen'];
                $new_absen->waktu_masuk = $value[0]['waktu_absen'];
                $new_absen->waktu_keluar = $value[1]['waktu_absen'];
                $new_absen->status = $value[0]['status'];
                $new_absen->validation = 1;
                $new_absen->tahun = $tahun;
                $new_absen->user_insert = $value[0]['id_pegawai'];
                $new_absen->user_update = $value[0]['id_pegawai'];
                $new_absen->save();
            }else{
                $new_absen = new Absen();
                $new_absen->id_pegawai = $value[0]['id_pegawai'];
                $new_absen->tanggal_absen = $value[0]['tanggal_absen'];
                $new_absen->waktu_masuk = $value[0]['waktu_absen'];
                $new_absen->waktu_keluar = null;
                $new_absen->status = $value[0]['status'];
                $new_absen->validation = 1;
                $new_absen->tahun = $tahun;
                $new_absen->user_insert = $value[0]['id_pegawai'];
                $new_absen->user_update = $value[0]['id_pegawai'];
                $new_absen->save();
            }
              
            
        }

        return $new_absen;
  
    }

    public function absen2(){
        $bulan = request('bulan');
        $tahun = date('Y');

       $absenData = DB::table('tb_absen_for_simasn_online')
        ->select('id_pegawai', 'tanggal_absen', 'status','jenis','waktu_masuk','waktu_keluar')
        ->whereMonth('tanggal_absen',$bulan)
        ->whereYear('tanggal_absen',$tahun)
        ->get();

        foreach ($absenData as $key => $value) {
            $new_absen = new Absen();
            $new_absen->id_pegawai = $value['id_pegawai'];
            $new_absen->tanggal_absen = $value['tanggal_absen'];
            $new_absen->waktu_masuk = $value['waktu_masuk'];
            $new_absen->waktu_keluar = $value['waktu_keluar'];
            $new_absen->status = $value['status'];
            $new_absen->validation = 1;
            $new_absen->tahun = $tahun;
            $new_absen->user_insert = $value['id_pegawai'];
            $new_absen->user_update = $value['id_pegawai'];
            $new_absen->save();
        }
    }

        function isTanggalLibur($tanggal)
    {
        $libur = DB::table('tb_libur')
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->first();
        return !empty($libur);
    }

    public function absenKajang(){
        $startDate = '2023-08-01';
        $endDate = '2023-08-31';

        // Ambil data pegawai dengan id_satuan_kerja = 34
        $pegawai = DB::table('tb_pegawai')->where('id_satuan_kerja', 34)->get();

        foreach ($pegawai as $pegawai) {
            for ($date = Carbon::parse($startDate); $date <= Carbon::parse($endDate); $date->addDay()) {
                if ($date->dayOfWeek !== Carbon::SATURDAY && $date->dayOfWeek !== Carbon::SUNDAY) {
                    if (!$this->isTanggalLibur($date)) {
                        DB::table('tb_absen')->insert([
                            'id_pegawai' => $pegawai->id,
                            'uuid' => 'te123',
                            'tanggal_absen' => $date->toDateString(),
                            'waktu_masuk' => '07:30:00',
                            'waktu_keluar' => '16:00:00',
                            'status' => 'hadir',
                            'validation' => 1,
                            'tahun' => '2023',
                            'user_type' => 1,
                            'user_insert' => $pegawai->id,
                            'user_update' => $pegawai->id,
                        ]);
                    }
                }
            }
        }
    }

    // public function aktivitas(){

    //     ini_set('max_execution_time', 700);
    //     set_time_limit(700);

    //     $bulan = request('bulan');
    //     $tahun = date('Y');

    //     $aktivitasData = DB::table('tb_aktivitas_by_percobaan2')
    //     ->select('id_pegawai', 'id_skp', 'nama_aktivitas','keterangan','satuan','tanggal','hasil','waktu','created_at','updated_at')
    //     ->groupBy('id_pegawai', 'id_skp', 'nama_aktivitas','keterangan','satuan','tanggal','hasil','waktu','created_at','updated_at')
    //     ->whereMonth('tanggal',$bulan)
    //     ->whereYear('tanggal',$tahun)
    //     ->get();

    //     $data = array();
    //     foreach ($aktivitasData as $key => $value) {
    //        $data = new Aktivitas;
    //        $data->id_pegawai = $value->id_pegawai;
    //        $data->id_sasaran = $value->id_skp;
    //        $data->aktivitas = $value->nama_aktivitas;
    //        $data->keterangan = $value->keterangan;
    //        $data->volume = $value->hasil;
    //        $data->satuan = $value->satuan;
    //        $data->waktu = $value->waktu;
    //        $data->tanggal = $value->tanggal;
    //        $data->validation = 1;
    //        $data->created_at = $value->created_at;
    //        $data->updated_at = $value->updated_at;
    //        $data->save();
    //     }

    //     return $data;
    // }

    public function aktivitas(){

        ini_set('max_execution_time', 820);
        set_time_limit(820);

        $bulan = request('bulan');
        $tahun = date('Y');

        $aktivitasData = DB::table('tb_aktivitas_dikerja')
        ->select('id_pegawai', 'id_skp', 'nama_aktivitas','keterangan','satuan','tanggal','hasil','waktu','created_at','updated_at')
        // ->groupBy('id_pegawai', 'id_skp', 'nama_aktivitas','keterangan','satuan','tanggal','hasil','waktu','created_at','updated_at')
        ->whereMonth('tanggal',$bulan)
        ->whereYear('tanggal',$tahun)
        ->get();

        $dataAktivitas = array(); // Ubah nama variabel
        foreach ($aktivitasData as $key => $value) { 
            $aktivitas = new Aktivitas; // Ubah nama variabel
            $aktivitas->id_pegawai = $value->id_pegawai;
            $aktivitas->id_sasaran = $value->id_skp;
            $aktivitas->aktivitas = $value->nama_aktivitas;
            $aktivitas->keterangan = $value->keterangan;
            $aktivitas->volume = $value->hasil;
            $aktivitas->satuan = $value->satuan;
            $aktivitas->waktu = $value->waktu;
            $aktivitas->tanggal = $value->tanggal;
            $aktivitas->validation = 1;
            $aktivitas->created_at = $value->created_at;
            $aktivitas->updated_at = $value->updated_at;
            $aktivitas->save();
        //    $dataAktivitas[] = $aktivitas; // Simpan ke array
        }

        // return $dataAktivitas; // Ubah hasil yang dikembalikan
    }


// public function aktivitas(){

//     ini_set('max_execution_time', 320);
//     set_time_limit(320);

//     $bulan = request('bulan');
//     $tahun = date('Y');

//     $aktivitasData = DB::table('tb_aktivitas_simasn')
//     ->select('id_pegawai', 'id_sasaran', 'aktivitas','keterangan','satuan','tanggal','volume','waktu','created_at','updated_at')
//     ->groupBy('id_pegawai', 'id_sasaran', 'aktivitas','keterangan','satuan','tanggal','volume','waktu','created_at','updated_at')
//     ->whereMonth('tanggal',$bulan)
//     ->whereYear('tanggal',$tahun)
//     ->get();

//     $dataAktivitas = array(); // Ubah nama variabel
//     foreach ($aktivitasData as $key => $value) {
//        $aktivitas = new Aktivitas; // Ubah nama variabel
//        $aktivitas->id_pegawai = $value->id_pegawai;
//        $aktivitas->id_sasaran = $value->id_sasaran;
//        $aktivitas->aktivitas = $value->aktivitas;
//        $aktivitas->keterangan = $value->keterangan;
//        $aktivitas->volume = $value->volume;
//        $aktivitas->satuan = $value->satuan;
//        $aktivitas->waktu = $value->waktu;
//        $aktivitas->tanggal = $value->tanggal;
//        $aktivitas->validation = 1;
//        $aktivitas->created_at = $value->created_at;
//        $aktivitas->updated_at = $value->updated_at;
//        $aktivitas->save();
//     //    $dataAktivitas[] = $aktivitas; // Simpan ke array
//     }

//     // return $dataAktivitas; // Ubah hasil yang dikembalikan
// }


    public function checkReviewer($id_jabatan){

       $jabatan = DB::table('tb_jabatan')->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','tb_master_jabatan.id')->select('tb_master_jabatan.id as id_master_jabatan')->where('tb_jabatan.id',$id_jabatan)->first();

        if ($jabatan) {
            $reviewer = DB::table('tb_master_jabatan')->join('tb_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')->join('tb_pegawai','tb_jabatan.id_pegawai','=','tb_pegawai.id')->select('tb_jabatan.id as jabatan_id_atasan')->where('tb_master_jabatan.id',$jabatan->id_master_jabatan)->first();


            // return $reviewer->jabatan_id_atasan;
            return $reviewer ?  $reviewer->jabatan_id_atasan : null;
        }

        
    }

    public function reviewer_insert(){
        $skp = DB::table('tb_skp')->whereNotNull('id_skp_atasan')->get();

        $tes = array();
        foreach ($skp as $key => $value) {
            $reviewer = $this->checkReviewer($value->id_jabatan);
          
            if ($reviewer !== null) {
                DB::table('tb_skp')
                ->where('id', $value->id)
                ->update([
                    'id_reviewer' => $reviewer
                ]);
            }
     
            
        }

        return "Pembaruan reviewer selesai.";
    }

    public function reset_face(){
        $data = DB::table('tb_pegawai')->select('id','uuid','face_character')->get();

        foreach ($data as $key => $value) {
            $karakter = substr($value->face_character, 0, 1);

            if ($karakter == '[') {
                $pegawai = Pegawai::where('uuid',$value->uuid)->first();
            $pegawai->status_rekam = 0;
            $pegawai->status_verifikasi = 0;
            $pegawai->face_character = null;
            $pegawai->save();
            }
            
        }
    }


    public function insert_user(){

        ini_set('max_execution_time', 820);
        set_time_limit(820);

        $bulan = request('bulan');
        $tahun = date('Y');

        $pegawai = DB::table('tb_pegawai')->get();

        $dataAktivitas = array(); // Ubah nama variabel
        foreach ($pegawai as $key => $value) { 
            $user = new User; // Ubah nama variabel
            $user->id = $value->id;
            $user->id_pegawai = $value->id;
            $user->username = $value->nip;
            $user->password = Hash::make('sitampan');
            $user->role = '2';
            $user->status = 1;
            $user->created_at = $value->created_at;
            $user->updated_at = $value->updated_at;
            $user->save();
        }
    }
}
