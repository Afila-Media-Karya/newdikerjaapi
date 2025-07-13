<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use Auth;
use DB;
use App\Models\Absen;
use App\Http\Requests\PresensiRequest;
use Carbon\Carbon;

class AbsenController extends BaseController
{
    
    public function checkAbsen(){
        $data = array();
        try {
            $query = DB::table('tb_absen')->select('status','waktu_masuk','waktu_keluar')->where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();

            if (is_null($query)) {
                $data['status'] = '-';
                $data['checkin'] = false;
                $data['checkout'] = false;
            }else{
                $data['status'] = $query->status;
                 if ($query->waktu_masuk && is_null($query->waktu_keluar)) {
                    $data['status'] = $query->status;
                    $data['checkin'] = true;
                    $data['checkout'] = false;
                 }else {
                    $data['status'] = $query->status;
                    $data['checkin'] = true;
                    $data['checkout'] = true;
                 }
            }

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Check absen Success');
    }

    public function checkAbsenNakes(){
        $data = array();
        $result = array();
        try {
            $data = DB::table('tb_absen')->select('status','waktu_masuk','waktu_keluar','shift')->where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();

            if (is_null($data)) {

                $waktuSekarang = strtotime(date('H:i:s'));
                // Mendapatkan waktu batas atas (jam 11 siang)
                // $waktuBatasAtas = strtotime('11:00:00');
                $waktuBatasAtas = Carbon::now()->setHour(11)->setMinute(0)->setSecond(0)->timestamp;
                $sehariSebelumnya = Carbon::now()->subDay()->toDateString();

                    $query = DB::table('tb_absen')
                        ->select('status', 'waktu_masuk', 'waktu_keluar', 'shift')
                        ->where('id_pegawai', Auth::user()->id_pegawai)
                        ->where('tanggal_absen', $sehariSebelumnya)
                        ->first();

                    if ($query) {
                        if ($query->shift == 'malam') {
                            if ($waktuSekarang <= $waktuBatasAtas) {
                                $data = $query;
                            }
                        } 
                    }    
                

            }

            if (is_null($data)) {
                $result['status'] = '-';
                $result['checkin'] = false;
                $result['checkout'] = false;
                $result['shift'] = '-';
            }else{
                $data->status = $data->status;
                 if ($data->waktu_masuk && is_null($data->waktu_keluar)) {
                    $result['status'] = $data->status;
                    $result['checkin'] = true;
                    $result['checkout'] = false;
                    $result['shift'] = !is_null($data->shift) ? $data->shift : '-';
                 }else {
                    $result['status'] = $data->status;
                    $result['checkin'] = true;
                    $result['checkout'] = true;
                    $result['shift'] = !is_null($data->shift) ? $data->shift : '-';
                 }
            }

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($result, 'Check absen Success');
    }

    public function hapusAbsen(){
        $tanggal = request('tanggal');
        return DB::table('tb_absen')->where('tanggal_absen', $tanggal)->where('id_pegawai',Auth::user()->id_pegawai)->delete();
    }

    public function checkAbsenByTanggal(){
         $data = array();
         $date = date('Y-m-d');
        if (date('D', strtotime($date)) == 'Sun') {
            $data = null;
        }else{
            $data = DB::table('tb_absen')->join('tb_pegawai','tb_absen.id_pegawai','=','tb_pegawai.id')->select('tb_absen.status','tb_pegawai.nip','tb_absen.tanggal_absen','waktu_masuk_istirahat','waktu_istirahat')->where('tb_absen.id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',$date)->first();
        }
        return $data;
    }   

    public function jml_shiftNakes(){
        $result = DB::table('tb_pegawai')
            ->select('tb_unit_kerja.jumlah_shift')
            ->join('tb_jabatan','tb_jabatan.id_pegawai','tb_pegawai.id')
            ->join("tb_master_jabatan",'tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
            ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
            ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
            ->where('tb_pegawai.id',Auth::user()->id_pegawai)->first();

        $result = $result ? $result->jumlah_shift : 3;
        return $result;
    }

    public function presensi(PresensiRequest $request){
        $data = array();
        try {
            $validation = 0;
            $status = strtolower($request->status);
            if ($request->jenis === 'datang') {

                $check_absen = $this->checkAbsenByTanggal();
                
                if (is_null($check_absen)) {
                    $data = new Absen;
                    $waktu_keluar = null;
                    if ($status == 'hadir' || $status == 'apel') {
                        $validation = 1;
                    }elseif ($status == 'dinas luar' || $status == 'izin' || $status == 'sakit') {
                        $validation = 0;
                    }                
                    
                    if ($status == 'cuti' || $status == 'dinas luar' || $status == 'sakit' || $status == 'izin') {
                        $waktu_keluar = '16:00:00';
                        
                        if ($request->tipe_pegawai == 'tenaga_kesehatan') {
                            $jumlah_shift = $this->jml_shiftNakes();
                            if ($request->shift == 'pagi') {
                                $waktu_keluar = $jumlah_shift == 3 ? '14:00:00' : '17:00:00';
                            }elseif ($request->shift == 'siang') {
                                $waktu_keluar = '21:00:00';
                            }else {
                               $waktu_keluar = $jumlah_shift == 3 ? '08:00:00' : '07:30:00';
                            }
                        }

                        if ($request->tipe_pegawai == 'tenaga_kesehatan_non_shift') {
                            $waktu_keluar = '15:15:00';
                        }
                    }

                    $data->id_pegawai = Auth::user()->id_pegawai;
                    $data->waktu_masuk = $request->waktu_masuk;
                    $data->waktu_keluar = $waktu_keluar;
                    $data->tanggal_absen = date('Y-m-d');
                    $data->status = $status;
                    $data->tahun = date('Y');
                    $data->validation = $validation;
                    $data->user_type = 0;

                    if ($request->tipe_pegawai == 'tenaga_kesehatan') {
                        $data->shift = $request->shift;
                    }

                    $data->save();
                }else{
                    return $this->sendError('Anda telah absen di tanggal '.$check_absen->tanggal_absen, 'Tidak bisa menambah absen!', 422);
                }
            }elseif($request->jenis === 'pulang'){
                $data = array();
                
                if ($request->tipe_pegawai == 'pegawai_administratif' || $request->tipe_pegawai == 'tenaga_kesehatan_non_shift') {
                    $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();
                }else {
                    if ($request->shift !== 'malam') {
                        $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();
                    }else{
                        $tanggalSebelumnya = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
                        $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',$tanggalSebelumnya)->first();
                    }
                }
                
                if ($data->waktu_keluar == null) {
                    $data->waktu_keluar = $request->waktu_keluar;
                    $data->save();
                }else{
                   return $this->sendError('Anda sudah absen pulang!', 422); 
                }   
            }            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Absen Added Success');
    }

    public function new_presensi(PresensiRequest $request){
        $data = array();
        try {
            $validation = 0;
            $status = strtolower($request->status);
            $check_absen = $this->checkAbsenByTanggal();
            if ($request->jenis === 'datang') {
                    $data = new Absen;
                    $waktu_keluar = null;
                    if ($status == 'hadir' || $status == 'apel') {
                        $validation = 1;
                    }elseif ($status == 'dinas luar' || $status == 'izin' || $status == 'sakit') {
                        $validation = 0;
                    }                
                    
                    if ($status == 'cuti' || $status == 'dinas luar' || $status == 'sakit' || $status == 'izin') {
                        $waktu_keluar = '16:00:00';
                        
                        if ($request->tipe_pegawai == 'tenaga_kesehatan') {
                            if ($request->shift == 'pagi') {
                                $waktu_keluar = '14:00:00';
                            }elseif ($request->shift == 'siang') {
                                $waktu_keluar = '21:00:00';
                            }else {
                               $waktu_keluar = '08:00:00';
                            }
                        }
                    }

                    if ($status !== 'waktu_masuk_istirahat') {
                        if (is_null($check_absen)) {
                            $data->id_pegawai = Auth::user()->id_pegawai;
                            $data->waktu_masuk = $request->waktu_masuk;
                            $data->waktu_keluar = $waktu_keluar;
                            $data->tanggal_absen = date('Y-m-d');
                            $data->status = $status;
                            $data->tahun = date('Y');
                            $data->validation = $validation;
                            $data->user_type = 0;

                            if ($request->tipe_pegawai == 'tenaga_kesehatan') {
                                $data->shift = $request->shift;
                            }
                            $data->save();

                        }else{
                            return $this->sendError('Anda telah absen di tanggal '.$check_absen->tanggal_absen, 'Tidak bisa menambah absen!', 422);
                        }
                    }else {
                      if ($check_absen) {
                        if (is_null($check_absen->waktu_masuk_istirahat)) {
                            $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();
                            $data->waktu_masuk_istirahat = $request->waktu_masuk_istirahat;
                            // $data->status = strtolower($request->status_masuk_istirahat);
                            $data->save();
                        }else {
                            return $this->sendError('Anda telah absen masuk istirahat di jam '.$check_absen->waktu_masuk_istirahat, 'Tidak bisa menambah absen!', 422);
                        }
                      }  
                        
                    }

                    
                
            }elseif($request->jenis === 'pulang'){
                $data = array();
                
                if ($request->tipe_pegawai == 'pegawai_administratif') {
                        $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();
                }else {
                    if ($request->shift !== 'malam') {
                        $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();
                    }else{
                        $tanggalSebelumnya = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
                        $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',$tanggalSebelumnya)->first();
                    }
                }
                
                // if ($data->waktu_keluar == null) {
                    if ($status == 'waktu_istirahat') {
                        if ($check_absen) {
                            if (is_null($check_absen->waktu_istirahat)) {
                                $data->waktu_istirahat = $request->waktu_istirahat;
                                $data->save();
                            }else {
                                return $this->sendError('Anda telah absen istirahat di jam '.$check_absen->waktu_istirahat, 'Tidak bisa menambah absen!', 422);
                            }
                        }
                        
                    }else {
                        $data->waktu_keluar = $request->waktu_keluar;
                        $data->save();
                    }
                
                // }else{
                //    return $this->sendError('Anda sudah absen pulang!', 422); 
                // }   
            }            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Absen Added Success');
    }

    public function new_presensi2(PresensiRequest $request){
        $data = array();
        try {
            $validation = 0;
            $status = strtolower($request->status);
            $check_absen = $this->checkAbsenByTanggal();
            if ($request->jenis === 'datang') {
                    $data = new Absen;
                    $waktu_keluar = null;
                    if ($status == 'hadir' || $status == 'apel') {
                        $validation = 1;
                    }elseif ($status == 'dinas luar' || $status == 'izin' || $status == 'sakit') {
                        $validation = 0;
                    }                
                    
                    if ($status == 'cuti' || $status == 'dinas luar' || $status == 'sakit' || $status == 'izin') {
                        $waktu_keluar = '16:00:00';
                        
                        if ($request->tipe_pegawai == 'tenaga_kesehatan') {
                            if ($request->shift == 'pagi') {
                                $waktu_keluar = '14:00:00';
                            }elseif ($request->shift == 'siang') {
                                $waktu_keluar = '21:00:00';
                            }else {
                               $waktu_keluar = '08:00:00';
                            }
                        }
                    }

                    if ($status !== 'waktu_masuk_istirahat') {
                        if (is_null($check_absen)) {
                            $data->id_pegawai = Auth::user()->id_pegawai;
                            $data->waktu_masuk = $request->waktu_masuk;
                            $data->waktu_keluar = $waktu_keluar;
                            $data->tanggal_absen = date('Y-m-d');
                            $data->status = $status;
                            $data->tahun = date('Y');
                            $data->validation = $validation;
                            $data->user_type = 0;

                            if ($request->tipe_pegawai == 'tenaga_kesehatan') {
                                $data->shift = $request->shift;
                            }
                            $data->save();

                        }else{
                            return $this->sendError('Anda telah absen di tanggal '.$check_absen->tanggal_absen, 'Tidak bisa menambah absen!', 422);
                        }
                    }else {
                      if ($check_absen) {
                        if (is_null($check_absen->waktu_masuk_istirahat)) {
                            $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();
                            $data->waktu_masuk_istirahat = $request->waktu_masuk_istirahat;
                            $data->status_masuk_istirahat = strtolower($request->status_masuk_istirahat);
                            $data->save();
                        }else {
                            return $this->sendError('Anda telah absen masuk istirahat di jam '.$check_absen->waktu_masuk_istirahat, 'Tidak bisa menambah absen!', 422);
                        }
                      }  
                        
                    }

                    
                
            }elseif($request->jenis === 'pulang'){
                $data = array();
                
                if ($request->tipe_pegawai == 'pegawai_administratif') {
                        $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();
                }else {
                    if ($request->shift !== 'malam') {
                        $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',date('Y-m-d'))->first();
                    }else{
                        $tanggalSebelumnya = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
                        $data = Absen::where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal_absen',$tanggalSebelumnya)->first();
                    }
                }
                
                // if ($data->waktu_keluar == null) {
                    if ($status == 'waktu_istirahat') {
                        if ($check_absen) {
                            if (is_null($check_absen->waktu_istirahat)) {
                                $data->waktu_istirahat = $request->waktu_istirahat;
                                $data->status_masuk_istirahat = $request->status_masuk_istirahat;
                                $data->save();
                            }else {
                                return $this->sendError('Anda telah absen istirahat di jam '.$check_absen->waktu_istirahat, 'Tidak bisa menambah absen!', 422);
                            }
                        }
                        
                    }else {
                        $data->waktu_keluar = $request->waktu_keluar;
                        $data->save();
                    }
                
                // }else{
                //    return $this->sendError('Anda sudah absen pulang!', 422); 
                // }   
            }            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Absen Added Success');
    }
}
