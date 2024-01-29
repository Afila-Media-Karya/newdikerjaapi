<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use DB;
use Auth;
use App\Http\Requests\CutiRequest;
use App\Http\Requests\CutiUpdateRequest;
use App\Models\LayananCuti;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

class LayananController extends BaseController
{
    public function list(){
        $data = array();
        try {
            $data = DB::table('tb_layanan')->select('id','uuid','nama','url','icon','keterangan')->where('status',1)->get();
        } catch (\Exception $th) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Layanan fetch Success');
    }

    public function getIcon(){
        $path = request('path');
            
        $url = Storage::disk('sftp')->get('/'.$path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $contentType = '';
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
                $contentType = 'image/' . $extension;
            } else {
                $contentType = 'application/octet-stream'; // Default content type for other file types
            }

            $response = new Response($url, 200, [
                'Content-Type' => $contentType,
            ]);
            ob_end_clean();
            return $response;
    }

    public function cuti_list(){
      
        $data = array();
        try {
            $data = DB::table('tb_layanan_cuti')->select('id','uuid','jenis_layanan','status','dokumen','dokumen_cuti','created_at','updated_at')->where('id_pegawai',Auth::user()->id_pegawai)->get();
        } catch (\Exception $th) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Layanan Cuti fetch Success');
    }

    public function dokumenByCuti($params){
        $data = DB::table('tb_layanan_cuti')->where('uuid',$params)->first();

        if ($data->dokumen) {
            $url = Storage::disk('sftp')->get('/'.$data->dokumen);
            $response = new Response($url, 200, [
                'Content-Type' => 'application/pdf',
            ]);
            ob_end_clean();
            return $response;
        }

        
    }

    public function dokumenCutiByCuti($params){
        $data = DB::table('tb_layanan_cuti')->where('uuid',$params)->first();
        if ($data->dokumen_cuti) {
            $url = Storage::disk('sftp')->get('/'.$data->dokumen_cuti);
            $response = new Response($url, 200, [
                'Content-Type' => 'application/pdf',
            ]);
            ob_end_clean();
            return $response;
        }
    }


    public function cuti_detail($params){
        $data = DB::table('tb_layanan_cuti')->where('uuid',$params)->first();
        return $this->sendResponse($data, 'Layanan Cuti Show Success');
    }

    public function store(CutiRequest $request){
        $data = array();
        try {
            $data = new LayananCuti();
            $data->jenis_layanan = $request->jenis_layanan;
            $data->alasan = $request->alasan;
            $data->tanggal_mulai = $request->tanggal_mulai;
            $data->tanggal_akhir = $request->tanggal_akhir;
            $data->alamat = $request->alamat;
            $data->status = $request->status;
            $data->id_pegawai = Auth::user()->id_pegawai;
            $data->keterangan = $request->keterangan;
            if (isset($request->dokumen)) {
                $file_konten = $request->file('dokumen');
                $filePath = Storage::disk('sftp')->put('/sftpenrekang/dokumen_cuti', $file_konten);
                $data->dokumen =  $filePath;
            }
            $data->save();
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Layanan Cuti fetch Success');
    }

    public function update(CutiRequest $request, $params){
        $data = array();
        try {
            $data = LayananCuti::where('uuid',$params)->first();
            $data->jenis_layanan = $request->jenis_layanan;
            $data->alasan = $request->alasan;
            $data->tanggal_mulai = $request->tanggal_mulai;
            $data->tanggal_akhir = $request->tanggal_akhir;
            $data->alamat = $request->alamat;
            $data->status = 1;
            $data->id_pegawai = Auth::user()->id_pegawai;
            $data->keterangan = $request->keterangan;
            if (isset($request->dokumen)) {
                $file_konten = $request->file('dokumen');
                $filePath = Storage::disk('sftp')->put('/sftpenrekang/dokumen_cuti', $file_konten);
                $data->dokumen =  $filePath;
            }
            $data->save();
        } catch (\Exception $th) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Layanan Cuti fetch Success');
    }

    public function option(){
        $array = ['Cuti Tahunan','Cuti Besar', 'Cuti Sakit','Cuti Melahirkan', 'Cuti Alasan Penting','Cuti di Luar Tanggungan Negara'];
        return $this->sendResponse($array, 'Layanan Cuti option Success');
    }

    public function layananGeneral(){
        $pegawai = request('pegawai');
        $jenis_layanan = request('jenis_layanan');
        $data = array();
        try {
            $data = DB::table('tb_layanan_general')
            ->join('tb_pegawai','tb_layanan_general.id_pegawai','=','tb_pegawai.id')
            ->join('tb_satuan_kerja','tb_pegawai.id_satuan_kerja','=','tb_satuan_kerja.id')
            ->join('tb_layanan','tb_layanan_general.id_jenis_layanan','=','tb_layanan.id')
            ->select('tb_layanan_general.id','tb_layanan_general.uuid','tb_layanan_general.id_jenis_layanan','tb_layanan_general.id_pegawai','tb_layanan_general.id_satuan_kerja','tb_layanan_general.keterangan','tb_pegawai.nama as nama_pegawai','tb_satuan_kerja.nama_satuan_kerja','tb_layanan_general.dokumen','tb_layanan_general.dokumen_pendukung','tb_layanan.nama as jenis_layanan','tb_layanan_general.created_at','tb_layanan_general.updated_at','tb_layanan_general.status')
            ->where('tb_layanan_general.id_jenis_layanan',$jenis_layanan)
            ->where('tb_layanan_general.id_pegawai',$pegawai)
            ->get();
        } catch (\Throwable $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Layanan General fetch Success');
    }
}
