<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\BaseController as BaseController;
use Auth;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Traits\Kehadiran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Traits\Option;

class LoginController extends BaseController
{
    use Kehadiran;
    use Option;
    public function signIn(LoginRequest $request)
    {
        // return $request;
        $path = explode('/', request()->path());

        if (!Auth::attempt($request->only('username', 'password'))) {
            return $this->sendError('Username atau password salah', 'Unauthorized', 401);
        }

        if ($path[1] !== 'v1' && $path[1] !== 'v2' && $path[1] !== 'v3') {
            return $this->sendError('Silahkan Update Aplikasi DIKERJA', 'Unauthorized', 401);
        }

        // $request->version !== '3.6.5' && $request->version !== '3.6.6' && $request->version !== '3.7.6'

        $validVersions = DB::table('tb_version_app')
            ->where('status', 1)
            ->pluck('version')
            ->toArray();

        if (!in_array($request->version, $validVersions)) {
            return $this->sendError('Mohon Update Aplikasi versi terbaru', 'Unauthorized', 401);
        }

        $user = User::where('username', $request->username)
            ->select('users.id', 'users.uuid', 'users.username', 'users.role', 'tb_pegawai.nama as nama_pegawai', 'tb_satuan_kerja.nama_satuan_kerja', 'tb_jabatan.status as status_jabatan')
            ->join('tb_pegawai', 'users.id_pegawai', '=', 'tb_pegawai.id')
            ->join('tb_satuan_kerja', 'tb_pegawai.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
            ->join('tb_jabatan', 'tb_jabatan.id_pegawai', '=', 'tb_pegawai.id')->first();

        if (!$user) {
            return $this->sendError('Jabatan tidak di temukan, Mohon hubungi admin opd', 'Unauthorized', 401);
        }

        // return $user;
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = [
            'nama' => $user->nama_pegawai,
            'satuan_kerja' => $user->nama_satuan_kerja,
            'uuid' => $user->uuid,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];

        return $this->sendResponse($response, 'Authencation successfull');
    }

    // public function current_user(){
    //     $data = array();
    //     try {
    //         $data = User::where('users.id', Auth::user()->id)
    //         ->select('users.id','users.uuid','users.username','tb_pegawai.uuid as pegawai_uuid','users.id_pegawai','tb_pegawai.nip','tb_pegawai.nama as nama_pegawai','tb_pegawai.face_character','tb_satuan_kerja.nama_satuan_kerja','tb_jabatan.status as status_jabatan','tb_master_jabatan.nama_jabatan','tb_pegawai.jenis_kelamin','tb_pegawai.agama','tb_pegawai.status_perkawinan','tb_pegawai.golongan','tb_pegawai.tmt_golongan','tb_pegawai.pendidikan','tb_pegawai.tahun','tb_pegawai.tmt_golongan','tb_pegawai.foto','tb_lokasi.latitude as lat','tb_lokasi.longitude as long','tb_lokasi_apel.latitude as apel_lat','tb_lokasi_apel.longitude as apel_long','tb_pegawai.status_rekam','tb_pegawai.status_kepegawaian','tb_pegawai.tipe_pegawai','tb_unit_kerja.waktu_masuk','tb_unit_kerja.waktu_keluar','tb_unit_kerja.waktu_apel','tb_lokasi.radius','tb_pegawai.tempat_lahir','tb_pegawai.tanggal_lahir','tb_pegawai.tanggal_lahir','tb_pegawai.alamat','tb_pegawai.email','tb_pegawai.tmt_pegawai','tb_pegawai.nama_pendidikan','tb_pegawai.no_hp','tb_master_jabatan.kelas_jabatan','tb_unit_kerja.nama_unit_kerja')
    //         ->join('tb_pegawai','users.id_pegawai','=','tb_pegawai.id')
    //         ->join('tb_jabatan','tb_jabatan.id_pegawai','=','tb_pegawai.id')
    //         ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
    //         ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
    //         ->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
    //         ->join('tb_lokasi','tb_jabatan.id_lokasi_kerja','tb_lokasi.id')
    //         ->join('tb_lokasi as tb_lokasi_apel', 'tb_jabatan.id_lokasi_apel', '=', 'tb_lokasi_apel.id')
    //         ->orderBy(DB::raw("FIELD(status_jabatan, 'pj', 'definitif', 'plt')"))
    //         ->first();

    //         $data->limitPenginputan = 5;
    //         $tanggal_hari_ini = date('Y-m-d');

    //         if ($this->isRhamadan($tanggal_hari_ini)) {
    //             if ($data->tipe_pegawai == 'pegawai_administratif') {
    //                 $data->waktu_masuk = '08:00:00';
    //                 $data->waktu_keluar = date('N') == 5 ? '15:30:00' : '15:00:00';
    //                 $data->waktu_apel = Carbon::parse($tanggal_hari_ini)->dayOfWeek === Carbon::MONDAY ? '07:15:00' : '07:40:00';
    //             }else{
    //                 $data->waktu_masuk = '08:00:00';
    //                 $data->waktu_keluar = '13:00:00';
    //                 $data->waktu_apel = '08:00:00';
    //             }                
    //         }

    //     } catch (\Exception $e) {
    //         return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Check absen Success');
    // }

    public function current_user()
    {
        $userId = Auth::user()->id;
        $cacheKey = 'user_data_' . $userId;
        $ttlSeconds = 46800;

        try {
            $data = Cache::remember($cacheKey, $ttlSeconds, function () use ($userId) {

                // Query Database yang Berat
                $userData = User::where('users.id', $userId)
                    ->select(
                        'users.id',
                        'users.uuid',
                        'users.username',
                        'tb_pegawai.uuid as pegawai_uuid',
                        'users.id_pegawai',
                        'tb_pegawai.nip',
                        'tb_pegawai.nama as nama_pegawai',
                        'tb_pegawai.face_character',
                        'tb_pegawai.jenis_kelamin',
                        'tb_pegawai.agama',
                        'tb_pegawai.status_perkawinan',
                        'tb_pegawai.golongan',
                        'tb_pegawai.tmt_golongan',
                        'tb_pegawai.pendidikan',
                        'tb_pegawai.tahun',
                        'tb_pegawai.foto',
                        'tb_pegawai.status_rekam',
                        'tb_pegawai.status_kepegawaian',
                        'tb_pegawai.tipe_pegawai',
                        'tb_pegawai.tempat_lahir',
                        'tb_pegawai.tanggal_lahir',
                        'tb_pegawai.alamat',
                        'tb_pegawai.email',
                        'tb_pegawai.tmt_pegawai',
                        'tb_pegawai.nama_pendidikan',
                        'tb_pegawai.no_hp',
                        'tb_satuan_kerja.nama_satuan_kerja',
                        'tb_jabatan.status as status_jabatan',
                        'tb_master_jabatan.nama_jabatan',
                        'tb_master_jabatan.kelas_jabatan',
                        'tb_lokasi.latitude as lat',
                        'tb_lokasi.longitude as long',
                        'tb_lokasi.radius',
                        'tb_lokasi_apel.latitude as apel_lat',
                        'tb_lokasi_apel.longitude as apel_long',
                        'tb_unit_kerja.waktu_masuk',
                        'tb_unit_kerja.waktu_keluar',
                        'tb_unit_kerja.waktu_apel',
                        'tb_unit_kerja.nama_unit_kerja'
                    )
                    ->join('tb_pegawai', 'users.id_pegawai', '=', 'tb_pegawai.id')
                    ->join('tb_jabatan', 'tb_jabatan.id_pegawai', '=', 'tb_pegawai.id')
                    ->join('tb_satuan_kerja', 'tb_jabatan.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
                    ->join('tb_unit_kerja', 'tb_jabatan.id_unit_kerja', '=', 'tb_unit_kerja.id')
                    ->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
                    ->join('tb_lokasi', 'tb_jabatan.id_lokasi_kerja', 'tb_lokasi.id')
                    ->join('tb_lokasi as tb_lokasi_apel', 'tb_jabatan.id_lokasi_apel', '=', 'tb_lokasi_apel.id')
                    ->orderBy(DB::raw("FIELD(status_jabatan, 'pj', 'definitif', 'plt')"))
                    ->first();

                // Kembalikan data dari database untuk disimpan di cache
                return $userData;
            });

            // Pastikan data tidak NULL (jika user tidak ditemukan)
            if (!$data) {
                return $this->sendError('Data pengguna tidak ditemukan', 'Gagal', 404);
            }

            // 3. Logika PHP Dinamis Dijalankan Setelah Cache Hit/Miss
            // Logika ini harus dilakukan di luar Cache::remember karena nilainya bisa berubah harian
            $data->limitPenginputan = 5;
            $tanggal_hari_ini = date('Y-m-d');

            // Asumsi: isRhamadan($date) adalah method yang tersedia
            if ($this->isRhamadan($tanggal_hari_ini)) {
                if ($data->tipe_pegawai == 'pegawai_administratif') {
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = date('N') == 5 ? '15:30:00' : '15:00:00';
                    // Carbon::parse() memerlukan 'use Carbon\Carbon;' di atas
                    $data->waktu_apel = Carbon::parse($tanggal_hari_ini)->dayOfWeek === Carbon::MONDAY ? '07:15:00' : '07:40:00';
                } else {
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = '13:00:00';
                    $data->waktu_apel = '08:00:00';
                }
            }

        } catch (\Exception $e) {
            // Ini akan menangani error koneksi database atau Redis
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }

        // Asumsi: sendResponse() adalah method yang tersedia
        return $this->sendResponse($data, 'Check absen Success');
    }

    // public function current_user2(){
    //     $data = array();
    //     try {

    //         $data = User::where('users.id', Auth::user()->id)
    //         ->select('users.id','users.uuid','users.username','tb_pegawai.uuid as pegawai_uuid','users.id_pegawai','tb_pegawai.nip','tb_pegawai.nama as nama_pegawai','tb_pegawai.face_character','tb_satuan_kerja.nama_satuan_kerja','tb_jabatan.status as status_jabatan','tb_master_jabatan.nama_jabatan','tb_pegawai.jenis_kelamin','tb_pegawai.agama','tb_pegawai.status_perkawinan','tb_pegawai.golongan','tb_pegawai.tmt_golongan','tb_pegawai.pendidikan','tb_pegawai.tahun','tb_pegawai.tmt_golongan','tb_pegawai.foto','tb_lokasi.latitude as lat','tb_lokasi.longitude as long','tb_lokasi_apel.latitude as apel_lat','tb_lokasi_apel.longitude as apel_long','tb_pegawai.status_rekam','tb_pegawai.status_kepegawaian','tb_pegawai.tipe_pegawai','tb_unit_kerja.waktu_masuk','tb_unit_kerja.waktu_keluar','tb_unit_kerja.waktu_apel','tb_lokasi.radius','tb_pegawai.tempat_lahir','tb_pegawai.tanggal_lahir','tb_pegawai.tanggal_lahir','tb_pegawai.alamat','tb_pegawai.email','tb_pegawai.tmt_pegawai','tb_pegawai.nama_pendidikan','tb_pegawai.no_hp','tb_master_jabatan.kelas_jabatan','tb_unit_kerja.nama_unit_kerja','tb_unit_kerja.jumlah_shift')
    //         ->join('tb_pegawai','users.id_pegawai','=','tb_pegawai.id')
    //         ->join('tb_jabatan','tb_jabatan.id_pegawai','=','tb_pegawai.id')
    //         ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
    //         ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
    //         ->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
    //         ->join('tb_lokasi','tb_jabatan.id_lokasi_kerja','tb_lokasi.id')
    //         ->join('tb_lokasi as tb_lokasi_apel', 'tb_jabatan.id_lokasi_apel', '=', 'tb_lokasi_apel.id')
    //         ->orderBy(DB::raw("FIELD(status_jabatan, 'pj', 'definitif', 'plt')"))
    //         ->first();

    //         $data->limitPenginputan = 5;
    //         $tanggal_hari_ini = date('Y-m-d');

    //         if (Carbon::parse($tanggal_hari_ini)->dayOfWeek !== Carbon::MONDAY) {
    //             $data->waktu_apel = '07:40:00';
    //         }

    //         $data->waktu_istirahat = '12:00:00';
    //         $data->waktu_masuk_istirahat = '13:00:00';

    //         if ($this->isRhamadan($tanggal_hari_ini)) {
    //             if ($data->tipe_pegawai == 'pegawai_administratif') {
    //                 $data->waktu_masuk = '08:00:00';
    //                 $data->waktu_keluar = date('N') == 5 ? '15:30:00' : '15:00:00';
    //                 $data->waktu_apel = Carbon::parse($tanggal_hari_ini)->dayOfWeek === Carbon::MONDAY ? '07:15:00' : '07:40:00';
    //             }elseif($data->tipe_pegawai == 'tenaga_kesehatan'){
    //                 $data->waktu_masuk = '08:00:00';
    //                 $data->waktu_keluar = '13:00:00';
    //                 $data->waktu_apel = '08:00:00';
    //             }             
    //         }

    //         if ($data->tipe_pegawai == 'tenaga_pendidik' || $data->tipe_pegawai == 'tenaga_pendidik_non_guru') {
    //             $data->waktu_masuk = '08:00:00';
    //             $data->waktu_keluar = '14:00:00';
    //             $data->waktu_apel = '07:30:00';
    //         }   

    //     } catch (\Exception $e) {
    //         return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Check absen Success');
    // }

    public function current_user2()
    {
        $userId = Auth::user()->id;
        $cacheKey = 'user_data_' . $userId;
        // TTL Dasar: 60 detik (1 menit)
        $baseTtlSeconds = 15;

        // Terapkan Jitter: Tambahkan acak 1 hingga 5 detik (cukup untuk TTL yang singkat)
        $ttlSeconds = $this->addJitter($baseTtlSeconds, 5);
        try {
            $data = Cache::remember($cacheKey, $ttlSeconds, function () use ($userId) {

                $userData = User::where('users.id', $userId)
                    ->select(
                        'users.id',
                        'users.uuid',
                        'users.username',
                        'tb_pegawai.uuid as pegawai_uuid',
                        'users.id_pegawai',
                        'tb_pegawai.nip',
                        'tb_pegawai.nama as nama_pegawai',
                        'tb_pegawai.face_character',
                        'tb_pegawai.jenis_kelamin',
                        'tb_pegawai.agama',
                        'tb_pegawai.status_perkawinan',
                        'tb_pegawai.golongan',
                        'tb_pegawai.tmt_golongan',
                        'tb_pegawai.pendidikan',
                        'tb_pegawai.tahun',
                        'tb_pegawai.foto',
                        'tb_pegawai.status_rekam',
                        'tb_pegawai.status_kepegawaian',
                        'tb_pegawai.tipe_pegawai',
                        'tb_pegawai.tempat_lahir',
                        'tb_pegawai.tanggal_lahir',
                        'tb_pegawai.alamat',
                        'tb_pegawai.email',
                        'tb_pegawai.tmt_pegawai',
                        'tb_pegawai.nama_pendidikan',
                        'tb_pegawai.no_hp',
                        'tb_satuan_kerja.nama_satuan_kerja',
                        'tb_jabatan.status as status_jabatan',
                        'tb_master_jabatan.nama_jabatan',
                        'tb_master_jabatan.kelas_jabatan',
                        'tb_lokasi.latitude as lat',
                        'tb_lokasi.longitude as long',
                        'tb_lokasi.radius',
                        'tb_lokasi_apel.latitude as apel_lat',
                        'tb_lokasi_apel.longitude as apel_long',
                        'tb_unit_kerja.waktu_masuk',
                        'tb_unit_kerja.waktu_keluar',
                        'tb_unit_kerja.waktu_apel',
                        'tb_unit_kerja.nama_unit_kerja',
                        'tb_unit_kerja.jumlah_shift'
                    )
                    ->join('tb_pegawai', 'users.id_pegawai', '=', 'tb_pegawai.id')
                    ->join('tb_jabatan', 'tb_jabatan.id_pegawai', '=', 'tb_pegawai.id')
                    ->join('tb_satuan_kerja', 'tb_jabatan.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
                    ->join('tb_unit_kerja', 'tb_jabatan.id_unit_kerja', '=', 'tb_unit_kerja.id')
                    ->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
                    ->join('tb_lokasi', 'tb_jabatan.id_lokasi_kerja', 'tb_lokasi.id')
                    ->join('tb_lokasi as tb_lokasi_apel', 'tb_jabatan.id_lokasi_apel', '=', 'tb_lokasi_apel.id')
                    ->orderBy(DB::raw("FIELD(status_jabatan, 'pj', 'definitif', 'plt')"))
                    ->first();

                return $userData;
            });

            if (!$data) {
                return $this->sendError('Data pengguna tidak ditemukan', 'Gagal', 404);
            }

            // 3. Logika PHP Dinamis Dijalankan Setelah Cache Hit/Miss
            $data->limitPenginputan = 5;
            $tanggal_hari_ini = date('Y-m-d');
            $data->waktu_istirahat = '12:00:00';
            if ($this->isRhamadan($tanggal_hari_ini)) {
                // Ramadan: Senin-Kamis 12:00-12:30, Jumat 12:00-13:00
                $data->waktu_masuk_istirahat = date('N') == 5 ? '13:00:00' : '12:30:00';
            } else {
                $data->waktu_masuk_istirahat = '13:00:00';
            }


            // Tentukan Waktu Apel Default/Hari Biasa
            if (Carbon::parse($tanggal_hari_ini)->dayOfWeek !== Carbon::MONDAY) {
                // Catatan: Jika hari Senin, waktu_apel akan tetap menggunakan nilai dari DB/Cache 
                // atau diubah oleh kondisi spesifik di bawah.
                $data->waktu_apel = '07:40:00';
            } else {
                // Hari Senin, gunakan waktu apel default dari DB/Cache (misal 07:15)
                // Jika ingin spesifik hari Senin: $data->waktu_apel = '07:15:00';
            }


            // Logika Penyesuaian Waktu Ramadan
            if ($this->isRhamadan($tanggal_hari_ini)) {
                if ($data->tipe_pegawai == 'pegawai_administratif') {
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = date('N') == 5 ? '15:30:00' : '15:00:00';
                    $data->waktu_apel = Carbon::parse($tanggal_hari_ini)->dayOfWeek === Carbon::MONDAY ? '07:15:00' : '07:40:00';
                } elseif ($data->tipe_pegawai == 'tenaga_kesehatan') {
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = date('N') == 5 ? '11:30:00' : '13:00:00';
                    $data->waktu_apel = '08:00:00';
                } elseif ($data->tipe_pegawai == 'tenaga_pendidik' || $data->tipe_pegawai == 'tenaga_pendidik_non_guru') {
                    $data->waktu_masuk = '07:00:00';
                    $data->waktu_keluar = date('N') == 5 ? '11:00:00' : '13:00:00';
                    $data->waktu_apel = '07:30:00';
                }
            }

            // Logika Penyesuaian Waktu Tenaga Pendidik (Override jika bukan Ramadan)
            // Logika ini akan menimpa pengaturan waktu default, tetapi akan di-override
            // oleh kondisi Ramadan di atas jika isRhamadan() bernilai TRUE.
            if (!($this->isRhamadan($tanggal_hari_ini))) {
                if ($data->tipe_pegawai == 'tenaga_pendidik' || $data->tipe_pegawai == 'tenaga_pendidik_non_guru') {
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = '14:00:00';
                    $data->waktu_apel = '07:30:00';
                }
            } else {
                // Jika Ramadan, logika di atas sudah mengatur.
            }

            // ========================================================
            // Ambil Batas Waktu Absen dari tabel (fallback ke default)
            // ========================================================
            $namaHari = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
            $hariIni = (int) date('N'); // 1=Senin, 7=Minggu
            $isRamadan = $this->isRhamadan($tanggal_hari_ini);
            $tipePegawai = $data->tipe_pegawai;
            $kategori = $isRamadan ? 'ramadan' : 'reguler';

            // -------------------------------------------------------
            // Cek tb_hari_kerja: apakah hari ini hari kerja?
            // -------------------------------------------------------
            // $cacheKeyHariKerja = "hari_kerja_{$tipePegawai}_{$hariIni}";
            // $hariKerja = Cache::remember($cacheKeyHariKerja, 3600, function () use ($tipePegawai, $hariIni) {
            //     return DB::table('tb_hari_kerja')
            //         ->where('tipe_pegawai', $tipePegawai)
            //         ->where('hari', $hariIni)
            //         ->first();
            // });
            $hariKerja = DB::table('tb_hari_kerja')
                ->where('tipe_pegawai', $tipePegawai)
                ->where('hari', $hariIni)
                ->first();
            $isHariKerja = $hariKerja ? (bool) $hariKerja->is_hari_kerja : false;

            // -------------------------------------------------------
            // Default values per tipe_pegawai (fallback jika DB kosong)
            // -------------------------------------------------------
            $defaultBatasAwalMasuk = '07:30:00';
            $defaultBatasAkhirMasuk = '17:00:00';
            $defaultBatasTepatWaktuMasuk = $data->waktu_masuk;
            $defaultBatasAwalPulang = $data->waktu_keluar;
            $defaultBatasAkhirPulang = '23:00:00';
            $defaultBatasTepatWaktuPulang = $data->waktu_keluar;
            $defaultBatasAwalApel = '07:30:00';
            $defaultBatasAkhirApel = $data->waktu_apel;
            $defaultBatasAwalApelHariBesar = '07:00:00';
            $defaultBatasAkhirApelHariBesar = '08:00:00';

            // Override default per tipe_pegawai
            if ($isRamadan) {
                if ($tipePegawai == 'pegawai_administratif') {
                    $defaultBatasTepatWaktuMasuk = '08:00:00';
                    $defaultBatasTepatWaktuPulang = $hariIni == 5 ? '15:30:00' : '15:00:00';
                    $defaultBatasAwalPulang = $defaultBatasTepatWaktuPulang;
                    $defaultBatasAkhirApel = $hariIni == 1 ? '07:15:00' : '07:40:00';
                } elseif ($tipePegawai == 'tenaga_kesehatan') {
                    $defaultBatasTepatWaktuMasuk = '08:00:00';
                    $defaultBatasTepatWaktuPulang = '13:00:00';
                    $defaultBatasAwalPulang = '13:00:00';
                    $defaultBatasAwalApel = '07:30:00';
                    $defaultBatasAkhirApel = '08:00:00';
                } elseif ($tipePegawai == 'tenaga_pendidik' || $tipePegawai == 'tenaga_pendidik_non_guru') {
                    $defaultBatasAwalMasuk = '07:00:00';
                    $defaultBatasTepatWaktuMasuk = '07:30:00';
                    $defaultBatasTepatWaktuPulang = '14:00:00';
                    $defaultBatasAwalPulang = '14:00:00';
                }
            } else {
                if ($tipePegawai == 'tenaga_pendidik' || $tipePegawai == 'tenaga_pendidik_non_guru') {
                    $defaultBatasTepatWaktuMasuk = '08:00:00';
                    $defaultBatasTepatWaktuPulang = '14:00:00';
                    $defaultBatasAwalPulang = '14:00:00';
                    $defaultBatasAkhirApel = '07:30:00';
                }
            }

            // -------------------------------------------------------
            // Query tb_jam_kerja berdasarkan kategori (ramadan/reguler)
            // Untuk tenaga_kesehatan: ambil semua shift, lainnya ambil first
            // -------------------------------------------------------
            $cacheKeyJamKerja = "jam_kerja_{$tipePegawai}_{$hariIni}_{$kategori}";

            if ($tipePegawai === 'tenaga_kesehatan') {
                // Ambil semua shift sekaligus
                $jamKerjaAll = Cache::remember($cacheKeyJamKerja, 3600, function () use ($tipePegawai, $hariIni, $kategori) {
                    return DB::table('tb_jam_kerja')
                        ->where('tipe_pegawai', $tipePegawai)
                        ->where('hari', $hariIni)
                        ->where('is_active', 1)
                        ->orderBy('jam_masuk')
                        ->get();
                });
                // jamKerja tetap diisi dengan shift pertama (sebagai fallback untuk field batas tunggal)
                $jamKerja = $jamKerjaAll->first();
            } else {
                $jamKerjaAll = null;
                $jamKerja = Cache::remember($cacheKeyJamKerja, 3600, function () use ($tipePegawai, $hariIni, $kategori) {
                    return DB::table('tb_jam_kerja')
                        ->where('tipe_pegawai', $tipePegawai)
                        ->where('hari', $hariIni)
                        //->where('kategori', $kategori)
                        ->where('is_active', 1)
                        ->first();
                });
            }

            // -------------------------------------------------------
            // Query tb_jam_apel berdasarkan jenis sesuai kategori
            // -------------------------------------------------------
            $jenisApelReguler = 'reguler';
            $jenisApelHariBesar = 'hari_besar';

            // $cacheKeyJamApel = "jam_apel_{$tipePegawai}_{$kategori}";
            // $jamApelAll = Cache::remember($cacheKeyJamApel, 3600, function () use ($tipePegawai, $jenisApelReguler, $jenisApelHariBesar) {
            //     return DB::table('tb_jam_apel')
            //         ->where('tipe_pegawai', $tipePegawai)
            //         ->whereIn('jenis', [$jenisApelReguler, $jenisApelHariBesar])
            //         ->where('is_active', 1)
            //         ->get()
            //         ->keyBy('jenis');
            // });
            $jamApelAll = DB::table('tb_jam_apel')
                ->where('tipe_pegawai', $tipePegawai)
                ->where('is_active', 1)
                ->where(function ($query) use ($hariIni, $jenisApelReguler, $jenisApelHariBesar) {
                    $query->where(function ($q) use ($hariIni, $jenisApelReguler) {
                        $q->where('jenis', $jenisApelReguler)
                            ->where('hari', $hariIni);
                    })->orWhere('jenis', $jenisApelHariBesar);
                })
                ->get()
                ->keyBy('jenis');

            $jamApelReguler = $jamApelAll->get($jenisApelReguler);
            $jamApelHariBesar = $jamApelAll->get($jenisApelHariBesar);

            // -------------------------------------------------------
            // Susun batas_waktu: DB value → fallback default
            // -------------------------------------------------------
            $batasWaktu = [
                'is_ramadan' => $isRamadan,
                'kategori' => $kategori,
                'hari' => $namaHari[$hariIni - 1],
                'hari_number' => $hariIni,
                'is_hari_kerja' => $isHariKerja,
                'is_apel' => $jamApelReguler ? 1 : 0,
                'batas_awal_masuk' => $jamKerja->batas_awal_masuk ?? $defaultBatasAwalMasuk,
                'batas_akhir_masuk' => $jamKerja->batas_akhir_masuk ?? $defaultBatasAkhirMasuk,
                'batas_tepat_waktu_masuk' => $jamKerja->jam_masuk ?? $defaultBatasTepatWaktuMasuk,
                'batas_awal_pulang' => $jamKerja->batas_awal_pulang ?? $defaultBatasAwalPulang,
                'batas_akhir_pulang' => $jamKerja->batas_akhir_pulang ?? $defaultBatasAkhirPulang,
                'batas_tepat_waktu_pulang' => $jamKerja->jam_keluar ?? $defaultBatasTepatWaktuPulang,
                'batas_awal_apel' => $jamApelReguler->batas_awal ?? $defaultBatasAwalApel,
                'batas_akhir_apel' => $jamApelReguler->batas_akhir ?? $defaultBatasAkhirApel,
                'batas_awal_apel_hari_besar' => $jamApelHariBesar->batas_awal ?? $defaultBatasAwalApelHariBesar,
                'batas_akhir_apel_hari_besar' => $jamApelHariBesar->batas_akhir ?? $defaultBatasAkhirApelHariBesar,
            ];

            // Untuk tenaga_kesehatan: sertakan semua shift hari ini
            if ($tipePegawai === 'tenaga_kesehatan' && $jamKerjaAll && $jamKerjaAll->isNotEmpty()) {
                $batasWaktu['jumlah_shift'] = $jamKerjaAll->first()->jumlah_shift ?? $jamKerjaAll->count();
                $batasWaktu['shifts'] = $jamKerjaAll->map(function ($shift) {
                    return [
                        'shift' => $shift->shift,
                        'jumlah_shift' => $shift->jumlah_shift,
                        'jam_masuk' => $shift->jam_masuk,
                        'jam_keluar' => $shift->jam_keluar,
                        'batas_awal_masuk' => $shift->batas_awal_masuk,
                        'batas_akhir_masuk' => $shift->batas_akhir_masuk,
                        'batas_awal_pulang' => $shift->batas_awal_pulang,
                        'batas_akhir_pulang' => $shift->batas_akhir_pulang,
                    ];
                })->values()->toArray();
            }

            $data->batas_waktu = (object) $batasWaktu;

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }

        return $this->sendResponse($data, 'Check absen Success');
    }

    public function revoke(Request $request)
    {
        try {
            $user = Auth::user();
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
                return $this->sendResponse('', 'You have been successfully logged out!');
            }
        } catch (\Exception $e) {
            return $this->sendError('No token to revoke', $e->getMessage(), 200);
        }
    }
}
