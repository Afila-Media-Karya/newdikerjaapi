<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\Pegawai;
use App\Models\User;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use Auth;
use Hash;
use App\Traits\Option;
use Illuminate\Support\Facades\Storage;
use DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ProfileController extends BaseController
{
    use Option;
    public function updateProfil(ProfileRequest $request){
        $data = array();
        try {

            $data = Pegawai::where('uuid',request('uuid'))->first();
            $data->nama = $request->nama;
            $data->nip = $request->nip;
            $data->jenis_kelamin = $request->jenis_kelamin;
            $data->agama = $request->agama;
            $data->status_perkawinan = $request->status_perkawinan;
            $data->golongan = $request->golongan;
            $data->tmt_golongan = $request->tmt_golongan;
            $data->pendidikan = $request->pendidikan;
            $data->tempat_lahir = $request->tempat_lahir;
            $data->tanggal_lahir = $request->tanggal_lahir;
            $data->no_hp = $request->no_hp;
            $data->alamat = $request->alamat;
            $data->email = $request->email;
            $data->tmt_pegawai = $request->tmt_pegawai;
            $data->nama_pendidikan = $request->nama_pendidikan;
            if (isset($request->foto)) {
                $file = $request->file('foto');
                $filePath = Storage::disk('sftp')->put('/sftpenrekang/foto_profil', $file);
                $data->foto = $filePath;
            }
            $data->tahun = $request->tahun;
            $data->save();
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Update profile Success');
        
    }

    // public function getImageProfil(){
    //     $pegawai =  Pegawai::where('id',Auth::user()->id_pegawai)->first();
    //     if ($pegawai->foto !== null || $pegawai->foto !== "") {
    //         $url = Storage::disk('sftp')->get('/'.$pegawai->foto);
    //         $response = new Response($url, 200, [
    //             'Content-Type' => 'image/png',
    //         ]);
    //         ob_end_clean();
    //         return $response;
    //     }
    // }

    public function getImageProfil(){
    
    // 1. Konfigurasi Caching
    $pegawaiId = Auth::user()->id_pegawai;
    $cacheKey = 'foto_profil_' . $pegawaiId;
    $ttlSeconds = 86400; // 24 Jam (Satu Hari)

    try {
        // 2. Gunakan Cache::remember() untuk mendapatkan Data Biner Gambar
        $url = Cache::remember($cacheKey, $ttlSeconds, function () use ($pegawaiId) {
            
            // Query DB untuk mendapatkan data path foto
            $pegawai = Pegawai::where('id', $pegawaiId)->first();

            // Cek Path Foto
            if (is_null($pegawai) || is_null($pegawai->foto) || $pegawai->foto === "") {
                // Jika tidak ada foto, kembalikan null agar cache menyimpan nilai null/kosong
                return null;
            }

            // Ambil data biner dari SFTP (Operasi I/O yang mahal)
            if (Storage::disk('sftp')->exists($pegawai->foto)) {
                return Storage::disk('sftp')->get($pegawai->foto);
            }
            
            return null; // Jika file tidak ditemukan
        });
        
        // 3. Menghasilkan Response
        if (!is_null($url)) {
            // Bersihkan output buffer sebelum mengirim response (untuk mencegah header error)
            // Ini meniru fungsi ob_end_clean() yang Anda gunakan sebelumnya
            // Namun, dalam Laravel/Lumen modern, ini jarang diperlukan.
            // Jika Anda mengalami error 'Headers already sent', gunakan ob_get_clean()
            
            // Pastikan tipe konten sesuai (misal: image/jpeg, image/png, etc.)
            // Jika tipe file bisa bervariasi, Anda perlu menyimpan tipe file di cache juga.
            
            return new Response($url, 200, [
                'Content-Type' => 'image/png', // Sesuaikan jika tipe file berbeda
                'Cache-Control' => 'max-age=' . $ttlSeconds . ', public', // Opsional: Beri tahu browser untuk cache juga
            ]);
        }
        
    } catch (\Exception $e) {
        // Jika terjadi error koneksi SFTP/Redis, Anda bisa logging
        // atau mencoba mengambil data tanpa cache sebagai fallback.
        // Untuk saat ini, kembalikan response kosong:
        return response()->json(['error' => $e->getMessage()], 404);
    }
    
    // Kembalikan 404 jika foto atau path tidak valid
    return response()->json(['error' => 'Foto profil tidak ditemukan.'], 404);
}

    public function ubahPassword(ChangePasswordRequest $request){
        // $user = array();

        try {
            $user = User::where('id',Auth::user()->id)->first();
            if (isset($user)) {    
                $password = Hash::check($request->password_lama, $user->password);
                // dd($password);    
                if ($password == true) {
                        $user->password = Hash::make($request->password_baru);
                        $user->save();
                }else{
                    return $this->sendError('Gagal', 'Kata sandi saat ini salah', 422);
                }
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }

        return $this->sendResponse($user, 'Change password Success');
    }

    public function verifikasiWajah(Request $request){
        $data = array();
        try {
            $data = Pegawai::where('id',Auth::user()->id_pegawai)->first();

            $cekDuplikasi = Pegawai::where('face_character', $request->face_character)
                        ->where('id', '!=', Auth::user()->id_pegawai) // Kecualikan data pegawai yang sedang di-update
                        ->exists();

            if ($cekDuplikasi) {
                return $this->sendError('Device sudah digunakan oleh pegawai lain.', 'Gagal.', 422);
            }

            $data->status_verifikasi = $request->status_verifikasi;
            $data->face_character = $request->face_character;
            $data->status_rekam = 1;
            $data->save();
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Verifikasi wajah Success');
    }

    // public function option_status_kawin(){
    //     $data = array();
    //     try {
    //         $data = $this->status_kawin();
    //     } catch (\Exception $e) {
    //        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Status kawin option success');
    // }

    public function option_status_kawin(){
    $data = array();
    
    try {
        // 1. Konfigurasi Caching
        $cacheKey = 'master_status_kawin_options';
        // 1 Minggu dalam detik (7 * 24 * 60 * 60)
        $ttlSeconds = 604800; 

        // 2. Gunakan Cache::remember()
        $data = Cache::remember($cacheKey, $ttlSeconds, function () {
            
            // Asumsi: $this->status_kawin() memanggil DB untuk data master
            $statusKawinOptions = $this->status_kawin(); 
            
            return $statusKawinOptions;
        });

    } catch (\Exception $e) {
       return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    }
    
    return $this->sendResponse($data, 'Status kawin option success');
}

    // public function option_golongan(){
    //     $data = array();
    //     try {
    //         $data = $this->golongan_pangkat();
    //     } catch (\Exception $e) {
    //        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Golongan option success');
    // }

    public function option_golongan(){
    $data = array();
    
    try {
        // 1. Konfigurasi Caching
        $cacheKey = 'master_golongan_options';
        // 1 Minggu dalam detik (7 * 24 * 60 * 60)
        $ttlSeconds = 604800; 

        // 2. Gunakan Cache::remember()
        $data = Cache::remember($cacheKey, $ttlSeconds, function () {
            
            // Asumsi: $this->golongan_pangkat() memanggil DB untuk data master
            $golonganOptions = $this->golongan_pangkat(); 
            
            return $golonganOptions;
        });

    } catch (\Exception $e) {
       return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    }
    
    return $this->sendResponse($data, 'Golongan option success');
}

    // public function option_pendidikan(){
    //     $data = array();
    //     try {
    //         $data = $this->pendidikan();
    //     } catch (\Exception $e) {
    //        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Pendidikan option success'); 
    // }

    public function option_pendidikan(){
    $data = array();
    
    try {
        // 1. Konfigurasi Caching
        $cacheKey = 'master_pendidikan_options';
        // 1 Minggu dalam detik (7 * 24 * 60 * 60)
        $ttlSeconds = 604800; 

        // 2. Gunakan Cache::remember()
        $data = Cache::remember($cacheKey, $ttlSeconds, function () {
            
            // Asumsi: $this->pendidikan() memanggil DB untuk data master
            $pendidikanOptions = $this->pendidikan(); 
            
            return $pendidikanOptions;
        });

    } catch (\Exception $e) {
       return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    }
    
    return $this->sendResponse($data, 'Pendidikan option success');
}

    public function hapus_wajah(){
        return DB::table('tb_pegawai')
        ->where('id', Auth::user()->id_pegawai)
        ->update(['face_character' => null]);
    }

    // public function option_skp(){
     
    //     $data = array();
    //     try {
    //         $data = DB::table("tb_skp")->select('tb_skp.id','tb_skp.rencana as value')->join('tb_jabatan','tb_skp.id_jabatan','=','tb_jabatan.id')->where('tb_jabatan.id_pegawai',Auth::user()->id_pegawai)->where('tahun',date('Y'))->get();
    //     } catch (\Exception $e) {
    //        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'SKP option success'); 
    // }

    public function option_skp(){
     
    $data = array();
    
    try {
        // 1. Konfigurasi Caching
        $pegawaiId = Auth::user()->id_pegawai;
        $tahunSaatIni = date('Y');
        
        // Key cache unik berdasarkan ID Pegawai dan Tahun
        $cacheKey = 'skp_options_' . $pegawaiId . '_' . $tahunSaatIni;
        $ttlSeconds = 86400; // 24 Jam (Satu Hari)

        // 2. Gunakan Cache::remember()
        $data = Cache::remember($cacheKey, $ttlSeconds, function () use ($pegawaiId, $tahunSaatIni) {
            
            // Query Database
            $skpOptions = DB::table("tb_skp")
                ->select('tb_skp.id','tb_skp.rencana as value')
                ->join('tb_jabatan','tb_skp.id_jabatan','=','tb_jabatan.id')
                ->where('tb_jabatan.id_pegawai', $pegawaiId)
                ->where('tahun', $tahunSaatIni)
                ->get();
            
            return $skpOptions;
        });

    } catch (\Exception $e) {
       return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    }
    
    return $this->sendResponse($data, 'SKP option success'); 
}
}
