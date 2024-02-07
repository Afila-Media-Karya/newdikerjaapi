<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use DB;
use Auth;
use App\Http\Requests\AktivitasRequest;
use App\Models\Aktivitas;
use App\Traits\Kehadiran;
use App\Traits\Kinerja;
use App\Traits\Pegawai;
class AktivitasController extends BaseController
{
    use Kehadiran;
    use Kinerja;
        use Pegawai;
    public function option_master_aktivitas(){
      $data = array();
        try {
            // $jabatan = DB::table("tb_jabatan")->select('tb_master_jabatan.id_kelompok_jabatan')->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')->where("tb_jabatan.id_pegawai",Auth::user()->id_pegawai)->first();

            $jabatan = $this->findJabatan();

            $data = DB::table('tb_master_aktivitas')
            ->select('id','aktivitas as value','satuan','waktu')
            ->union(
                DB::table('tb_master_aktivitas')
                ->select('uuid','aktivitas as value','satuan','waktu')
                ->where('jenis','umum')
            )
            ->where('id_kelompok_jabatan',$jabatan->id_kelompok_jabatan)
            ->where('jenis','khusus')
            ->get();

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Option master aktivitas Success');
    }

    public function list(){
        $data = array();
        $tanggal = request('tanggal');
        try {
            $data = DB::table('tb_aktivitas')->select('id','uuid','aktivitas','waktu')->where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal',$tanggal)->get();
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'List aktivitas fetched Success');
    }

    public function store(AktivitasRequest $request){
        $data = array();
        try {

              if (DB::table('tb_libur')
                ->whereDate('tanggal_mulai', '<=', $request->tanggal)
                ->whereDate('tanggal_selesai', '>=', $request->tanggal)
                ->exists()) {
                    return $this->sendError('Aktivitas tidak dapat ditambahkan pada hari libur', 'Aktivitas tidak dapat ditambahkan pada hari libur', 422);
                }
       
            $waktu = 0;
            $jumlah_kinerja = $this->checkMenitKinerja($request->tanggal)->getData();
            $ax = $request->waktu + $jumlah_kinerja->data->count;

            if ($ax > 420) {
                $n_ = (420 - $jumlah_kinerja->data->count) - $request->waktu;
                $waktu = $ax + $n_;
                $waktu = $waktu - $jumlah_kinerja->data->count;  

                if ($waktu <= 0 ) {
                    return $this->sendError('Jumlah waktu sudah cukup 420 menit', 'Jumlah waktu sudah cukup 420 menit', 422);
                }
            }else{
                $waktu = $request->waktu;
            }

            if ($this->checkAbsenByTanggal(Auth::user()->id_pegawai,$request->tanggal) == null) {
            
                return $this->sendError('Maaf Anda belum Absen', 'Maaf Anda belum Absen', 422);
            }else{
    
                if ($this->checkAbsenByTanggal(Auth::user()->id_pegawai,$request->tanggal)->status == 'izin' || $this->checkAbsenByTanggal(Auth::user()->id_pegawai,$request->tanggal)->status == 'sakit'  || $this->checkAbsenByTanggal(Auth::user()->id_pegawai,$request->tanggal)->status == 'cuti') {
                    return $this->sendError('Anda sedang '.$this->checkAbsenByTanggal(Auth::user()->id_pegawai,$request->tanggal)->status, 'Anda sedang '.$this->checkAbsenByTanggal(Auth::user()->id_pegawai,$request->tanggal)->status, 422);
                }
            }

            // if ($this->checkValidasiLimaHari($request->tanggal)) {
            //     return $this->sendError('Tanggal aktivitas sudah lewat 5 hari', 'Tanggal aktivitas sudah lewat 5 hari', 422);
            // }

            $checkAktivitasDuplicate = Aktivitas::where('aktivitas',$request->aktivitas)->where('keterangan',$request->keterangan)->whereDate('tanggal',$request->tanggal)->where('id_pegawai',Auth::user()->id_pegawai)->first();

            if (is_null($checkAktivitasDuplicate)) {
                $data = new Aktivitas();
                $data->tanggal = $request->tanggal;
                $data->id_sasaran = $request->id_sasaran;
                $data->id_pegawai = Auth::user()->id_pegawai;
                $data->aktivitas = $request->aktivitas;
                $data->satuan = $request->satuan;
                $data->waktu = $waktu;
                $data->volume = $request->hasil;
                $data->keterangan = $request->keterangan;
                $data->save();  
            }else {
                $data = Aktivitas::where('uuid',$checkAktivitasDuplicate->uuid)->first();
                $data->waktu = $data->waktu + $waktu;
                $data->save();
            }

            
            
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Aktivitas Added Success');    
    }

    public function checkMenitKinerja($params){
        $data = DB::table('tb_aktivitas')->select(DB::raw("SUM(waktu) as count"))->where('id_pegawai',Auth::user()->id_pegawai)->where('tanggal',$params)->first();
        if($data->count == null){
            $data->count = 0;
        }
        return $this->sendResponse($data, 'Aktivitas Added Success');
    }

    public function show($params){
        $data = array();
        try {
            // $data = Aktivitas::where('uuid',$params)->first();
            $data = DB::table('tb_aktivitas')->select('tb_aktivitas.id','tb_aktivitas.uuid','tb_aktivitas.aktivitas','tb_aktivitas.keterangan','tb_aktivitas.volume','tb_aktivitas.satuan','tb_aktivitas.waktu','tb_aktivitas.tanggal','tb_aktivitas.validation','tb_skp.rencana as sasaran_kinerja','tb_skp.id as id_sasaran_kinerja')->leftJoin('tb_skp','tb_aktivitas.id_sasaran','=','tb_skp.id')->where('tb_aktivitas.uuid',$params)->first();
        } catch (\Exception $th) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }

        return $this->sendResponse($data, 'Aktivitas show Success');    
    }

    public function update(AktivitasRequest $request,$params){
        $data = array();
        try {
            $data = Aktivitas::where('uuid',$params)->first();
            $data->tanggal = $request->tanggal;
            $data->id_sasaran = $request->id_sasaran;
            $data->id_pegawai = Auth::user()->id_pegawai;
            $data->aktivitas = $request->aktivitas;
            $data->satuan = $request->satuan;
            $data->waktu = $request->waktu;
            $data->volume = $request->hasil;
            $data->keterangan = $request->keterangan;
            $data->save();
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Aktivitas Updated Success');   
    }

    public function delete(Request $request,$params){
        try {
            Aktivitas::where('uuid',$params)->delete();
            return $this->sendResponse([], 'Aktivitas Deleted Success');   
        } catch (\Exception $th) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
    }
}
