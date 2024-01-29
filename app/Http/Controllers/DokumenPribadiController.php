<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use DB;
use Auth;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

class DokumenPribadiController extends BaseController
{
        public function dokumen_pribadi(){
            $data = array();
         
            try {
                
                
            $pegawai = DB::table('tb_pegawai')->where('id',Auth::user()->id_pegawai)->first();
            $riwayat_pendidikan_formal = DB::table('tb_profil_riwayat_pendidikan')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_pendidikan_non_formal = DB::table('tb_profil_riwayat_pendidikan_non_formal')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_kepangkatan =  DB::table('tb_profil_riwayat_kepangkatan')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_jabatan =  DB::table('tb_profil_riwayat_jabatan')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $catatan_hukuman_dinas =  DB::table('tb_profil_catatan_hukuman_dinas')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $diklat_struktral = DB::table('tb_profil_riwayat_diklat_struktural')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $diklat_fungsional = DB::table('tb_profil_riwayat_diklat_fungsional')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $diklat_teknis = DB::table('tb_profil_riwayat_diklat_teknis')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_penghargaan = DB::table('tb_profil_riwayat_penghargaan')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_istri = DB::table('tb_profil_riwayat_istri')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_anak = DB::table('tb_profil_riwayat_anak')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_orang_tua = DB::table('tb_profil_riwayat_orang_tua')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_saudara = DB::table('tb_profil_riwayat_saudara')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_keahlian = DB::table('tb_profil_keahlian')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $riwayat_bahasa = DB::table('tb_profil_bahasa')->where('id_pegawai',Auth::user()->id_pegawai)->get();
            $file_pegawai = DB::table('tb_profil_file_pegawai')->where('id_pegawai',Auth::user()->id_pegawai)->get();

            $data = [
                'pegawai' => $pegawai,
                'riwayat_pendidikan_formal' => $riwayat_pendidikan_formal,
                'riwayat_pendidikan_non_formal' => $riwayat_pendidikan_non_formal,
                'riwayat_kepangkatan' => $riwayat_kepangkatan,
                'riwayat_jabatan' => $riwayat_jabatan,
                'catatan_hukuman_dinas' => $catatan_hukuman_dinas,
                'diklat_struktral' => $diklat_struktral,
                'diklat_fungsional' => $diklat_fungsional,
                'diklat_teknis' => $diklat_teknis,
                'riwayat_istri' => $riwayat_istri,
                'riwayat_anak' => $riwayat_anak,
                'riwayat_orang_tua' => $riwayat_orang_tua,
                'riwayat_saudara' => $riwayat_saudara,
                'riwayat_keahlian' => $riwayat_keahlian,
                'riwayat_bahasa' => $riwayat_bahasa,
                'riwayat_penghargaan' => $riwayat_penghargaan,
                'file_pegawai' => $file_pegawai,
            ];

            } catch (\Exception $e) {
                return $this->sendError($e->getMessage(), $e->getMessage(), 200);
            }
            return $this->sendResponse($data, 'Dokumen pribadi fetch Success');
        }

        public function file_dokumen_pribadi(){
            $path = request('path');
            
            $url = Storage::disk('sftp')->get('/'.$path);

            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if ($extension === 'pdf') {
                $contentType = 'application/pdf';
            } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'gif'])) {
                $contentType = 'image/' . $extension;
                if ($extension == 'jpg') {
                    $contentType = 'image/jpeg';
                }
                
            } else {
                $contentType = 'application/octet-stream'; // Default content type for other file types
            }
            
            $response = new Response($url, 200, [
                'Content-Type' => $contentType,
            ]);
            ob_end_clean();
            return $response;
        }
}
