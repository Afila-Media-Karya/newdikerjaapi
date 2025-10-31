<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use DB;
use Auth;
use App\Traits\Kehadiran;
use App\Traits\Kinerja;
use App\Traits\Pegawai;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Traits\Option;

class HomeController extends BaseController
{

    use Kehadiran;
    use Kinerja;
    use Pegawai;
            use Option;

    // public function pegawai(){
    //     $data = array();
    //     try {

    //         $findJabatan = $this->findJabatan();

    //         $data = DB::table('tb_pegawai')
    //         ->select(
    //             "tb_pegawai.nama",
    //             'tb_pegawai.nip',
    //             "tb_pegawai.golongan",
    //             DB::raw('
    //                 CASE 
    //                     WHEN tb_jabatan.status = "definitif" THEN tb_master_jabatan.nama_jabatan
    //                     ELSE CONCAT(UPPER(tb_jabatan.status), " ", tb_master_jabatan.nama_jabatan)
    //                 END as nama_jabatan
    //             '),
    //             'tb_satuan_kerja.nama_satuan_kerja',
    //             'tb_unit_kerja.nama_unit_kerja'
    //         )
    //         ->join('tb_jabatan','tb_jabatan.id_pegawai','tb_pegawai.id')
    //         ->join("tb_master_jabatan",'tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
    //         ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
    //         ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
    //         ->where('tb_jabatan.id_satuan_kerja',$findJabatan->satuan_kerja)
    //         ->where('tb_pegawai.id',$findJabatan->id_pegawai)
    //         ->orderBy(DB::raw("FIELD(tb_jabatan.status, 'pj', 'definitif', 'plt')"))
    //         ->first();
    //     } catch (\Exception $e) {
    //         return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Pegawai fetch Success');
    // }

    public function pegawai(){
        $data = array();
        try {
            $findJabatan = $this->findJabatan(); 

            $idPegawai = $findJabatan->id_pegawai;
            $cacheKey = 'pegawai_data_' . $idPegawai;
            $baseTtlSeconds = 60; 
            $ttlSeconds =  $this->addJitter($baseTtlSeconds, 5);

            // Pastikan kita memiliki ID Pegawai yang valid sebelum mencoba cache
            if (!$idPegawai) {
                return $this->sendError('Pegawai tidak ditemukan (findJabatan)', 'Gagal', 404);
            }

            // 2. Gunakan Cache::remember()
            $data = Cache::remember($cacheKey, $ttlSeconds, function () use ($findJabatan) {
                
                // Query Database yang Berat (Hanya dijalankan saat Cache Miss)
                return DB::table('tb_pegawai')
                    ->select(
                        "tb_pegawai.nama",
                        'tb_pegawai.nip',
                        "tb_pegawai.golongan",
                        DB::raw('
                            CASE 
                                WHEN tb_jabatan.status = "definitif" THEN tb_master_jabatan.nama_jabatan
                                ELSE CONCAT(UPPER(tb_jabatan.status), " ", tb_master_jabatan.nama_jabatan)
                            END as nama_jabatan
                        '),
                        'tb_satuan_kerja.nama_satuan_kerja',
                        'tb_unit_kerja.nama_unit_kerja'
                    )
                    ->join('tb_jabatan','tb_jabatan.id_pegawai','tb_pegawai.id')
                    ->join("tb_master_jabatan",'tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
                    ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
                    ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
                    ->where('tb_jabatan.id_satuan_kerja', $findJabatan->satuan_kerja)
                    // Memfilter berdasarkan tb_pegawai.id yang didapatkan dari findJabatan
                    ->where('tb_pegawai.id', $findJabatan->id_pegawai) 
                    ->orderBy(DB::raw("FIELD(tb_jabatan.status, 'pj', 'definitif', 'plt')"))
                    ->first();
            });

            if (!$data) {
                return $this->sendError('Data pegawai tidak ditemukan', 'Gagal', 404);
            }

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Pegawai fetch Success');
    }

    // public function atasan(){
    //     $data = array();
    //     try {
    
    //     $jabatan = DB::table('tb_jabatan')->select('tb_jabatan.id_parent')->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')->where('id_pegawai',Auth::user()->id_pegawai)->first();

    //     $data = DB::table('tb_pegawai')
    //     ->select("tb_pegawai.nama",'tb_pegawai.nip',"tb_pegawai.golongan",'tb_master_jabatan.nama_jabatan','tb_satuan_kerja.nama_satuan_kerja','tb_jabatan.id as id_jabatan','tb_jabatan.status as status_jabatan','tb_unit_kerja.nama_unit_kerja')
    //     ->join('tb_jabatan','tb_jabatan.id_pegawai','tb_pegawai.id')
    //     ->join("tb_master_jabatan",'tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
    //     ->join('tb_satuan_kerja','tb_pegawai.id_satuan_kerja','=','tb_satuan_kerja.id')
    //     ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
    //     ->where('tb_jabatan.id',$jabatan->id_parent)->first();

    //     } catch (\Exception $e) {
    //         return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Atasan fetch Success');
    // }
    public function atasan(){
        $data = array();
        
        // 1. Konfigurasi Caching
        $idPegawai = Auth::user()->id_pegawai;
        // Key cache harus unik berdasarkan ID Pegawai yang sedang login
        $cacheKey = 'atasan_data_' . $idPegawai;
        $baseTtlSeconds = 60; 
        $ttlSeconds =  $this->addJitter($baseTtlSeconds, 5);

        try {
            // 2. Gunakan Cache::remember() untuk membungkus seluruh logika akses DB
            $data = Cache::remember($cacheKey, $ttlSeconds, function () use ($idPegawai) {

                // Bagian A: Mencari ID Atasan (id_parent)
                $jabatan = DB::table('tb_jabatan')
                    ->select('tb_jabatan.id_parent')
                    ->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
                    ->where('id_pegawai', $idPegawai)
                    ->first();

                // Cek jika id_parent tidak ditemukan
                if (is_null($jabatan) || is_null($jabatan->id_parent)) {
                    return null; // Kembalikan null agar cache menyimpan data kosong/tidak ditemukan
                }

                // Bagian B: Mengambil Data Atasan
                $atasanData = DB::table('tb_pegawai')
                    ->select("tb_pegawai.nama",'tb_pegawai.nip',"tb_pegawai.golongan",'tb_master_jabatan.nama_jabatan','tb_satuan_kerja.nama_satuan_kerja','tb_jabatan.id as id_jabatan','tb_jabatan.status as status_jabatan','tb_unit_kerja.nama_unit_kerja')
                    ->join('tb_jabatan','tb_jabatan.id_pegawai','tb_pegawai.id')
                    ->join("tb_master_jabatan",'tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
                    ->join('tb_satuan_kerja','tb_pegawai.id_satuan_kerja','=','tb_satuan_kerja.id') // Koreksi join tb_satuan_kerja
                    ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
                    ->where('tb_jabatan.id', $jabatan->id_parent)
                    ->first();
                
                return $atasanData;
            });

            if (is_null($data)) {
                // Jika data null dari cache atau DB
                $data = (object)[]; // Kembalikan objek kosong/default jika atasan tidak ditemukan
            }

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Atasan fetch Success');
    }

    // public function kinerja(){
    //     $data = array();
    //     $bulan = request('bulan');
    //     try {
    //         $data = $this->kinerja_pegawai($bulan);
    //     } catch (\Exception $e) {
    //        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Atasan fetch Success');
    // }

    public function kinerja(){
        $data = array();
        $bulan = request('bulan');
        
        // Pastikan bulan tersedia dari request
        if (!$bulan) {
            return $this->sendError('Parameter bulan diperlukan.', 'Error Validasi', 400);
        }
        
        try {
            // 1. Konfigurasi Caching
            $pegawaiId = Auth::user()->id_pegawai;
            // Key cache unik berdasarkan ID Pegawai dan Bulan/Tahun
            $tahun = date('Y');
            $cacheKey = 'rekap_kinerja_' . $pegawaiId . '_' . $tahun . '_' . $bulan;
            $baseTtlSeconds = 60; 
            $ttlSeconds =  $this->addJitter($baseTtlSeconds, 5);

            // 2. Gunakan Cache::remember() untuk membungkus helper method yang berat
            $data = Cache::remember($cacheKey, $ttlSeconds, function () use ($bulan) {
                
                // Asumsi: $this->kinerja_pegawai($bulan) memanggil DB
                $kinerjaData = $this->kinerja_pegawai($bulan);
                
                return $kinerjaData;
            });

        } catch (\Exception $e) {
        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        
        return $this->sendResponse($data, 'Rekap Kinerja fetch Success');
    }

    // public function kehadiran(){
    //     $data = array();
    //     try {
    //         $bulan = request('bulan');
    //         $data = $this->rekapDataKehadiran($bulan);
    //     } catch (\Exception $e) {
    //        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($data, 'Atasan fetch Success');
    // }

    public function kehadiran(){
        $data = array();
        $bulan = request('bulan');
        
        // Pastikan bulan tersedia dari request
        if (!$bulan) {
            return $this->sendError('Parameter bulan diperlukan.', 'Error Validasi', 400);
        }
        
        try {
            // 1. Konfigurasi Caching
            $pegawaiId = Auth::user()->id_pegawai;
            // Key cache unik berdasarkan ID Pegawai dan Bulan/Tahun
            $tahun = date('Y');
            $cacheKey = 'rekap_kehadiran_' . $pegawaiId . '_' . $tahun . '_' . $bulan;
            $baseTtlSeconds = 60; 
            $ttlSeconds =  $this->addJitter($baseTtlSeconds, 5);

            // 2. Gunakan Cache::remember() untuk membungkus helper method yang berat
            $data = Cache::remember($cacheKey, $ttlSeconds, function () use ($bulan) {
                
                // Asumsi: $this->rekapDataKehadiran($bulan) memanggil DB
                $rekapData = $this->rekapDataKehadiran($bulan);
                
                return $rekapData;
            });

        } catch (\Exception $e) {
        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        
        return $this->sendResponse($data, 'Rekap Kehadiran fetch Success');
    }

    function isTanggalLibur($tanggal,$tipe_pegawai)
    {

        if ($tipe_pegawai == 'pegawai_administratif' || $tipe_pegawai == 'tenaga_kesehatan') {
            $tipe_pegawai = 'pegawai_administratif';
        }else {
            $tipe_pegawai = 'tenaga_pendidik';
        }

        $libur = DB::table('tb_libur')
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->first();
        return !empty($libur);
    }

    public function data_kehadiran_pegawai($pegawai,$tanggal_awal, $tanggal_akhir, $waktu_tetap_masuk, $waktu_tetap_keluar, $tipe_pegawai){
        $result = array();
        $daftar_tanggal = [];
        $current_date = new Carbon($tanggal_awal);
        $jml_alfa = 0;

        $kmk_30 = 0;
        $kmk_60 = 0;
        $kmk_90 = 0;
        $kmk_90_keatas = 0;

        $cpk_30 = 0;
        $cpk_60 = 0;
        $cpk_90 = 0;
        $cpk_90_keatas = 0;

        $count_hadir = 0;
        $count_sakit = 0;
        $count_cuti = 0;
        $count_izin_cuti = 0;
        $count_dinas_luar = 0;
        $count_apel = 0;
        $jml_tidak_apel = 0;
        $jml_tidak_apel_hari_senin = 0;
        $jml_tidak_hadir_berturut_turut = 0;
        
        while ($current_date->lte(Carbon::parse($tanggal_akhir))) {
            if ($tipe_pegawai == 'pegawai_administratif') {
                if ($current_date->dayOfWeek !== 6 && $current_date->dayOfWeek !== 0) {
                    if (!$this->isTanggalLibur($current_date->toDateString(),$tipe_pegawai)) {
                        $daftar_tanggal[] = $current_date->toDateString();
                    }
                }
            }elseif($tipe_pegawai == 'tenaga_pendidik' || $tipe_pegawai == 'tenaga_pendidik_non_guru'){
                if ($current_date->dayOfWeek !== 0) {
                    if (!$this->isTanggalLibur($current_date->toDateString(),$tipe_pegawai)) {
                        $daftar_tanggal[] = $current_date->toDateString();
                    }
                }
            }else{
                $daftar_tanggal[] = $current_date->toDateString();
            }
            $current_date->addDay();
        }

        // Query untuk mengambil data absen
        $data = DB::table('tb_absen')
            ->select('tanggal_absen', 'status', 'waktu_masuk', 'waktu_keluar','shift')
            ->where('id_pegawai', $pegawai)
            ->where('validation', 1)
            ->whereBetween('tanggal_absen', [$tanggal_awal, $tanggal_akhir])
            ->get();

        // Ubah hasil query menjadi array asosiatif dengan tanggal sebagai kunci
        $absen_per_tanggal = [];

        foreach ($data as $row) {
            $absen_per_tanggal[$row->tanggal_absen] = [
                'status' => $row->status,
                'waktu_masuk' => $row->waktu_masuk,
                'waktu_keluar' => $row->waktu_keluar,
                'shift' => $row->shift,
            ];
        }

        // Buat hasil akhir dengan semua tanggal dalam rentang
        $hasil_akhir = [];
        $hari_tidak_hadir_nakes = [];
        $jml_menit_terlambat_masuk_kerja = 0;
        $jml_menit_terlambat_pulang_kerja = 0;
        foreach ($daftar_tanggal as $tanggal) {
            if (isset($absen_per_tanggal[$tanggal])) {
                $tanggalCarbon = Carbon::createFromFormat('Y-m-d', $tanggal);
                if ($tanggalCarbon->isMonday()) {
                    // Periksa jika status_absen bukan 'apel'
                    if (!in_array($tanggal, $this->getDateRange())) {
                            if ($absen_per_tanggal[$tanggal]['status'] !== 'apel' && $absen_per_tanggal[$tanggal]['status'] !== 'dinas luar' && $absen_per_tanggal[$tanggal]['status'] !== 'cuti' && $absen_per_tanggal[$tanggal]['status'] !== 'dinas luar' && $absen_per_tanggal[$tanggal]['status'] !== 'sakit') {
                                if ($tipe_pegawai == 'pegawai_administratif' && !$this->isRhamadan($tanggalCarbon->toDateString())) {
                                    $jml_tidak_apel += 1;
                                }elseif ($tipe_pegawai == 'tenaga_kesehatan') {
                                    if ($absen_per_tanggal[$tanggal]['shift'] == 'pagi' && !$this->isTanggalLibur($tanggalCarbon->toDateString(),$tipe_pegawai) && !$this->isRhamadan($tanggalCarbon->toDateString())) {
                                        $jml_tidak_apel += 1;
                                    }
                                }
                            }
                    } 
                }

        
                if ($absen_per_tanggal[$tanggal]['status'] == 'hadir' || $absen_per_tanggal[$tanggal]['status'] == 'apel') {
                    $count_hadir += 1;
                }elseif ($absen_per_tanggal[$tanggal]['status'] == 'sakit') {
                    $count_sakit += 1;
                }elseif ($absen_per_tanggal[$tanggal]['status'] == 'izin' || $absen_per_tanggal[$tanggal]['status'] == 'cuti') {
                    $count_izin_cuti += 1;
                }

                if($absen_per_tanggal[$tanggal]['status'] == 'dinas luar'){
                    $count_dinas_luar += 1;
                }

                if($absen_per_tanggal[$tanggal]['status'] == 'apel'){
                    $count_apel += 1;
                }

                if ($tipe_pegawai == 'pegawai_administratif') {
                    $selisih_waktu_masuk = $this->konvertWaktu('masuk', $absen_per_tanggal[$tanggal]['waktu_masuk'],$tanggal,$waktu_tetap_masuk,$tipe_pegawai);
                    $selisih_waktu_pulang = $this->konvertWaktu('keluar', $absen_per_tanggal[$tanggal]['waktu_keluar'],$tanggal,$waktu_tetap_keluar,$tipe_pegawai);
                }else{
                    $selisih_waktu_masuk = $this->konvertWaktuNakes('masuk',$absen_per_tanggal[$tanggal]['waktu_masuk'],$tanggal,$absen_per_tanggal[$tanggal]['shift'],$waktu_tetap_masuk,$tipe_pegawai);
                    $selisih_waktu_pulang = $this->konvertWaktuNakes('keluar',$absen_per_tanggal[$tanggal]['waktu_keluar'],$tanggal,$absen_per_tanggal[$tanggal]['shift'],$waktu_tetap_keluar,$tipe_pegawai);
                }

                if ($absen_per_tanggal[$tanggal]['waktu_masuk'] !== null) {
                    $jml_menit_terlambat_masuk_kerja += $selisih_waktu_masuk;
                }

                if ($tanggal !== date('Y-m-d')) {
                    $jml_menit_terlambat_pulang_kerja += $selisih_waktu_pulang;
                }
            
                if ($absen_per_tanggal[$tanggal]['status'] !== 'cuti' && $absen_per_tanggal[$tanggal]['status'] !== 'dinas luar' && $absen_per_tanggal[$tanggal]['status'] !== 'sakit') {

                    if ($selisih_waktu_masuk >= 1 && $selisih_waktu_masuk <= 30) {
                        $kmk_30 += 1;
                    } elseif ($selisih_waktu_masuk >= 31 && $selisih_waktu_masuk <= 60) {
                        $kmk_60 += 1;
                    } elseif ($selisih_waktu_masuk >= 61 && $selisih_waktu_masuk <= 90) {
                        $kmk_90 += 1;
                    } elseif ($selisih_waktu_masuk >= 91) {
                        $kmk_90_keatas += 1;
                    }

                    if ($selisih_waktu_pulang >= 1 && $selisih_waktu_pulang <= 30) {
                        $cpk_30 += 1;
                    } elseif ($selisih_waktu_pulang >= 31 && $selisih_waktu_pulang <= 60) {
                        $cpk_60 += 1;
                    } elseif ($selisih_waktu_pulang >= 61 && $selisih_waktu_pulang <= 90) {
                        $cpk_90 += 1;
                    } elseif ($selisih_waktu_pulang >= 91) {
                        $cpk_90_keatas += 1;
                    }
                }

                

                $waktu_pulang = $absen_per_tanggal[$tanggal]['waktu_keluar'];

                $waktu_pulang ? $waktu_pulang = $waktu_pulang : $waktu_pulang = '14:00:00';
                $hasil_akhir[] = [
                    'tanggal_absen' => $tanggal, // Ganti nilai 'tanggal_absen' dengan tanggal yang sesuai
                    'status' => $absen_per_tanggal[$tanggal]['status'],
                    'waktu_masuk' => $absen_per_tanggal[$tanggal]['waktu_masuk'],
                    'waktu_keluar' => $waktu_pulang,
                    'keterangan_masuk' => $selisih_waktu_masuk > 0 ?  'Telat ' . $selisih_waktu_masuk . ' menit' : 'Tepat waktu',
                    'keterangan_pulang' =>  $selisih_waktu_pulang > 0 ?  'Cepat ' . $selisih_waktu_pulang . ' menit' : 'Tepat waktu',
                    'shift' => $absen_per_tanggal[$tanggal]['shift']
                ];
            } else {
                 $status_ = 'Tanpa Keterangan';
                $tanggalCarbon = Carbon::createFromFormat('Y-m-d', $tanggal);
                if (strtotime($tanggal) > strtotime(date('Y-m-d'))) {
                    $status_ = 'Belum absen';
                }else{
                    if ($tipe_pegawai == 'pegawai_administratif' || $tipe_pegawai == 'tenaga_pendidik') {
                        $jml_alfa += 1;
                    }else{
                         $mingguKe = $tanggalCarbon->weekOfMonth;
                         $hari_tidak_hadir_nakes[] = ['tanggal'=>$tanggal,'minggu'=>$mingguKe];
                        $tanggalSebelumnya = date('Y-m-d', strtotime($tanggal . ' -1 day'));
                        $check_last_day = DB::table('tb_absen')->where('tanggal_absen',$tanggalSebelumnya)->where('id_pegawai',$pegawai)->first();
                        if (is_null($check_last_day) || $check_last_day->shift !== 'malam') {
                            $status_ = '-';
                        }else{
                            $status_ = 'Lepas Jaga / Lepas Piket';
                        }
                    }
                }
                // Jika tidak ada data absen untuk tanggal ini, berikan nilai null
                $hasil_akhir[] = [
                    'tanggal_absen' => $tanggal,
                    'status' => $status_,
                    'waktu_masuk' => '-',
                    'waktu_keluar' => '-',
                    'keterangan_masuk' => '-',
                    'keterangan_pulang' => '-',
                    'shift' => '-',
                ];
            }
        }

        $jumlah_alfa_nakes = 0;
        if ($tipe_pegawai == 'tenaga_kesehatan') {
            $jumlahHariMingguSama = array_count_values(array_column($hari_tidak_hadir_nakes, 'minggu'));
            foreach ($jumlahHariMingguSama as $minggu => $jumlah) {
                if ($jumlah > 1) {
                    $jumlah_alfa_nakes += $jumlah - 1;
                }
            }
            $jml_alfa = $jumlah_alfa_nakes;
        }

        $potongan_cuti_izin = 0;

        if ($count_izin_cuti > 2) {
            $potongan_cuti_izin = ($count_izin_cuti - 2) * 1;
        } else {
            $potongan_cuti_izin = 0;
        }


        $potongan_sakit = 0;
        if ($count_sakit > 3) {
            $potongan_sakit = ($count_sakit - 3) * 1;
        } else {
            $potongan_sakit = 0;
        }

        $potongan_masuk_kerja = ($kmk_30 * 0.5) + ($kmk_60 * 1) + ($kmk_90 * 1.25) + ($kmk_90_keatas * 1.5); 
        $potongan_pulang_kerja = ($cpk_30 * 0.5) + ($cpk_60 * 1) + ($cpk_90 * 1.25) + ($cpk_90_keatas * 1.5); 
        $potongan_tanpa_keterangan = $jml_alfa * 3;
        $potongan_apel = $jml_tidak_apel * 2;
        $jml_potongan_kehadiran_kerja = $potongan_tanpa_keterangan + $potongan_masuk_kerja + $potongan_pulang_kerja + $potongan_apel;

        return [
            'data' => $hasil_akhir,
            'jml_hari_kerja' => count($hasil_akhir),
            'kehadiran_kerja' => count($data),
            'tanpa_keterangan' => $jml_alfa,
            'potongan_tanpa_keterangan' => $potongan_tanpa_keterangan,
            'potongan_masuk_kerja' => $potongan_masuk_kerja,
            'potongan_pulang_kerja' => $potongan_pulang_kerja,
            'potongan_apel' => $potongan_apel,
            'jml_potongan_kehadiran_kerja' => $jml_potongan_kehadiran_kerja,
            'jml_hadir' => $count_hadir,
            'jml_sakit' => $count_sakit,
            'jml_cuti' => $count_izin_cuti,
            'jml_dinas_luar' => $count_dinas_luar,
            'jml_izin_cuti' => $count_izin_cuti,
            'kmk_30' => $kmk_30,
            'kmk_60' => $kmk_60,
            'kmk_90' => $kmk_90,
            'kmk_90_keatas' => $kmk_90_keatas,
            'cpk_30' => $cpk_30,
            'cpk_60' => $cpk_60,
            'cpk_90' => $cpk_90,
            'cpk_90_keatas' => $cpk_90_keatas,
            'jml_tidak_apel' => $jml_tidak_apel,
            'jml_apel' => $count_apel,
            'jml_menit_terlambat_masuk_kerja' => $jml_menit_terlambat_masuk_kerja,
            'jml_menit_terlambat_pulang_kerja' => $jml_menit_terlambat_pulang_kerja
        ];
    }

    // public function tpp(){
    //     $data = array();
    //     $bulan = request('bulan');
    //     $result = array();
    //     try {
          
    //     $tahun = date('Y'); 
    //     $tanggal_awal = date("$tahun-$bulan-01");
    //     $tanggal_akhir = date("Y-m-t", strtotime($tanggal_awal));
    //     $pegawai = Auth::user()->id_pegawai;

    //     $findJabatan = $this->findJabatan();
        

    //     $data = DB::table('tb_pegawai')
    //         ->selectRaw('
    //             tb_pegawai.id,
    //             tb_pegawai.nama,
    //             tb_pegawai.nip,
    //             tb_pegawai.golongan,
    //             tb_pegawai.tipe_pegawai,
    //             tb_master_jabatan.nama_jabatan,
    //             tb_jabatan.target_waktu,
    //             tb_master_jabatan.kelas_jabatan,
    //             tb_jabatan.pagu_tpp,
    //             tb_master_jabatan.jenis_jabatan,
    //             tb_master_jabatan.level_jabatan,
    //             tb_jabatan.pembayaran,
    //             tb_unit_kerja.waktu_masuk,
    //             tb_unit_kerja.waktu_keluar,
    //             (SELECT SUM(waktu) 
    //             FROM tb_aktivitas 
    //             WHERE tb_aktivitas.id_pegawai = tb_pegawai.id 
    //             AND validation = 1 
    //             AND tahun = ? 
    //             AND MONTH(tanggal) = ?) as capaian_waktu',
    //             [$tahun, $bulan] // Parameter posisi untuk menggantikan :tahun dan :bulan
    //         )
    //         ->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')
    //         ->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
    //         ->join('tb_satuan_kerja', 'tb_pegawai.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
    //         ->join('tb_unit_kerja', 'tb_jabatan.id_unit_kerja', '=', 'tb_unit_kerja.id')
    //         ->where('tb_pegawai.id', $findJabatan->id_pegawai)
    //         ->where('tb_jabatan.id_satuan_kerja', $findJabatan->satuan_kerja)
    //         ->groupBy(
    //             'tb_pegawai.id', 
    //             'tb_pegawai.nama', 
    //             'tb_pegawai.nip', 
    //             'tb_pegawai.golongan', 
    //             'tb_master_jabatan.nama_jabatan', 
    //             'tb_jabatan.target_waktu',
    //             'tb_master_jabatan.kelas_jabatan',
    //             'tb_jabatan.pagu_tpp',
    //             'tb_master_jabatan.jenis_jabatan',
    //             'tb_master_jabatan.level_jabatan',
    //             'tb_jabatan.pembayaran',
    //             'tb_unit_kerja.waktu_masuk',
    //             'tb_unit_kerja.waktu_keluar'
    //         )
    //         ->first();



    //     $child = $this->data_kehadiran_pegawai($data->id,$tanggal_awal,$tanggal_akhir,$data->waktu_masuk,$data->waktu_keluar,$data->tipe_pegawai);
    //     $data->jml_potongan_kehadiran_kerja = $child['jml_potongan_kehadiran_kerja'];
    //     $data->tanpa_keterangan = $child['tanpa_keterangan'];
    //     $data->jml_hari_kerja = $child['jml_hari_kerja'];
    //     $data->jml_hadir = $child['jml_hadir'];
    //     $data->jml_sakit = $child['jml_sakit'];
    //     $data->jml_cuti = $child['jml_cuti'];
    //     $data->jml_apel = $child['jml_apel'];
    //     $data->jml_dinas_luar = $child['jml_dinas_luar'];
    //     $data->jml_tidak_apel = $child['jml_tidak_apel'];
    //     $data->jml_menit_terlambat_masuk_kerja = $child['jml_menit_terlambat_masuk_kerja'];
    //     $data->jml_menit_terlambat_pulang_kerja = $child['jml_menit_terlambat_pulang_kerja'];

    //     $jmlPaguTpp = 0;
    //     $jmlNilaiKinerja = 0;
    //     $jmlNilaiKehadiran = 0;
    //     $jmlBpjs = 0;
    //     $jmlTppBruto = 0;
    //     $jmlPphPsl = 0;
    //     $jmlTppNetto = 0;
    //     $jmlBrutoSpm = 0;
    //     $jmlIuran = 0;
    //     $nilai_kinerja = 0;
    //     $target_nilai = 0;

    //     $capaian_prod = 0;
    //     $target_prod = 0;
    //     $nilaiKinerja = 0;
    //     $nilai_kinerja = 0;
    //     $keterangan = '';
    //     $kelas_jabatan = '';
    //     $golongan = '';

    //         $golongan = '-';
    //         if ($data->golongan !== null && str_contains($data->golongan, '/')) {
    //             $golonganParts = explode("/", $data->golongan);
    //             $golongan = isset($golonganParts[1]) ? $golonganParts[1] : '-';
    //         }
    //         $data->target_waktu !== null ? $target_nilai = $data->target_waktu : $target_nilai = 0;

    //         $target_nilai > 0 ? $nilai_kinerja = ( intval($data->capaian_waktu) / $target_nilai ) * 100 : $nilai_kinerja = 0;
    //         if ($nilai_kinerja > 100) {
    //             $nilai_kinerja = 100;
    //         }


    //         $nilaiPaguTpp = $data->pagu_tpp * $data->pembayaran / 100;
    //         $nilai_kinerja_rp = $nilaiPaguTpp* 60/100; 
    //         $nilaiKinerja = $nilai_kinerja * $nilai_kinerja_rp / 100; 
    //         $persentaseKehadiran = 40 * $nilaiPaguTpp / 100;
    //         $nilaiKehadiran = $persentaseKehadiran * $data->jml_potongan_kehadiran_kerja / 100;
    //         $jumlahKehadiran = $persentaseKehadiran - $nilaiKehadiran;
    //         $bpjs = 1 * $nilaiPaguTpp / 100;
    //         $data->tanpa_keterangan > 3  ? $keterangan = 'TMS'  : $keterangan = 'MS';
    //         $tppBruto = 0;
    //         $iuran = 4 * $nilaiPaguTpp / 100;
    //         if ($keterangan === 'TMS') {
    //             $tppBruto = 0;
    //             $bpjs=0;
    //             $iuran=0;
    //             $brutoSpm=0;
    //         }else{
    //             $tppBruto = $nilaiKinerja + $jumlahKehadiran - $bpjs;
    //             $brutoSpm = $nilaiKinerja + $jumlahKehadiran + $iuran;
    //         }

    //         if (strstr( $golongan, 'IV' )) {
    //             $pphPsl = 15 * $tppBruto / 100;
    //         }elseif (strstr( $golongan, 'III' )) {
    //                 $pphPsl = 5 * $tppBruto / 100;
    //         }else{
    //             $pphPsl = 0;
    //         }
    //         $tppNetto = $tppBruto - $pphPsl;
            

    //     $result = [
    //         'kinerja_maks' => $nilai_kinerja_rp,
    //         'persen_kinerja_maks' => round($nilai_kinerja,2),
    //         'kehadiran_maks' => $persentaseKehadiran,
    //         'persen_kehadiran_maks' => $data->jml_potongan_kehadiran_kerja,
    //         'potongan_kinerja' => $nilaiPaguTpp * (100 - $nilai_kinerja) / 100,
    //         'persen_potongan_kinerja' => round((100 - $nilai_kinerja),2),
    //         'potongan_kehadiran' => $nilaiKehadiran,
    //         'persentase_potongan_kehadiran' => $data->jml_potongan_kehadiran_kerja,
    //         'bpjs' => $bpjs,
    //         'pphPsl' => $pphPsl,
    //         'potongan_jkn_pph' => $pphPsl + $bpjs,
    //         'nilai_bruto' => $tppBruto,
    //         'tpp_bulan_ini' => $tppNetto,
    //         'tppNetto' => $tppNetto,
    //         'brutoSpm' => $brutoSpm,
    //         'nilaiPaguTpp' => $nilaiPaguTpp,
    //         'iuran' => $nilaiPaguTpp * 4 / 100,
    //         'jml_hari_kerja' => $data->jml_hari_kerja,
    //         'jml_hadir' => $data->jml_hadir,
    //         'jml_sakit' => $data->jml_sakit,
    //         'jml_cuti' => $data->jml_cuti,
    //         'jml_dinas_luar' => $data->jml_dinas_luar,
    //         'jml_tidak_apel' => $data->jml_tidak_apel,
    //         'jml_apel' => $data->jml_apel,
    //         'tanpa_keterangan' => $data->tanpa_keterangan,
    //         'persen_pagu_kinerja' => 60,
    //         'persen_pagu_kehadiran' => 40,
    //         'capaian_kinerja' => $nilaiKinerja,
    //     ];
            
    //     } catch (\Exception $e) {
    //        return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    //     }
    //     return $this->sendResponse($result, 'TPP fetch Success');
    // }

    public function tpp(){
    $result = array();
    $bulan = request('bulan'); // Ambil dari request
    $tahun = date('Y'); 
    
    // Pastikan bulan tersedia
    if (!$bulan) {
        return $this->sendError('Parameter bulan diperlukan.', 'Error Validasi', 400);
    }
    
    try {
        // --- Caching Block ---
        $pegawaiId = Auth::user()->id_pegawai;
        $tanggal_awal = date("$tahun-$bulan-01");
        $tanggal_akhir = date("Y-m-t", strtotime($tanggal_awal));
        $findJabatan = $this->findJabatan();
        
        // 1. Definisikan Cache Key Unik (Berdasarkan Pegawai, Bulan, dan Tahun)
        $cacheKey = 'tpp_data_' . $pegawaiId . '_' . $tahun . '_' . $bulan;
        $baseTtlSeconds = 60; 
        $ttlSeconds =  $this->addJitter($baseTtlSeconds, 5);
        
        // 2. Gunakan Cache::remember() untuk Query Database Berat
        $data = Cache::remember($cacheKey, $ttlSeconds, function () use ($tahun, $bulan, $findJabatan) {
            
            // Subquery (capaian_waktu) dan Main Query digabung
            $tppData = DB::table('tb_pegawai')
                ->selectRaw('
                    tb_pegawai.id,
                    tb_pegawai.nama,
                    tb_pegawai.nip,
                    tb_pegawai.golongan,
                    tb_pegawai.tipe_pegawai,
                    tb_master_jabatan.nama_jabatan,
                    tb_jabatan.target_waktu,
                    tb_master_jabatan.kelas_jabatan,
                    tb_jabatan.pagu_tpp,
                    tb_master_jabatan.jenis_jabatan,
                    tb_master_jabatan.level_jabatan,
                    tb_jabatan.pembayaran,
                    tb_unit_kerja.waktu_masuk,
                    tb_unit_kerja.waktu_keluar,
                    (SELECT COALESCE(SUM(waktu), 0) 
                    FROM tb_aktivitas 
                    WHERE tb_aktivitas.id_pegawai = tb_pegawai.id 
                    AND validation = 1 
                    AND YEAR(tanggal) = ? 
                    AND MONTH(tanggal) = ?) as capaian_waktu',
                    [$tahun, $bulan] 
                )
                ->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')
                ->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
                ->join('tb_satuan_kerja', 'tb_pegawai.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
                ->join('tb_unit_kerja', 'tb_jabatan.id_unit_kerja', '=', 'tb_unit_kerja.id')
                ->where('tb_pegawai.id', $findJabatan->id_pegawai)
                ->where('tb_jabatan.id_satuan_kerja', $findJabatan->satuan_kerja)
                ->first();
                
            return $tppData;
        });
        
        // Cek jika data pegawai tidak ditemukan di cache/db
        if (is_null($data)) {
            return $this->sendError('Data pegawai TPP tidak ditemukan.', 'Gagal', 404);
        }

               $child = $this->data_kehadiran_pegawai($data->id,$tanggal_awal,$tanggal_akhir,$data->waktu_masuk,$data->waktu_keluar,$data->tipe_pegawai);
        $data->jml_potongan_kehadiran_kerja = $child['jml_potongan_kehadiran_kerja'];
        $data->tanpa_keterangan = $child['tanpa_keterangan'];
        $data->jml_hari_kerja = $child['jml_hari_kerja'];
        $data->jml_hadir = $child['jml_hadir'];
        $data->jml_sakit = $child['jml_sakit'];
        $data->jml_cuti = $child['jml_cuti'];
        $data->jml_apel = $child['jml_apel'];
        $data->jml_dinas_luar = $child['jml_dinas_luar'];
        $data->jml_tidak_apel = $child['jml_tidak_apel'];
        $data->jml_menit_terlambat_masuk_kerja = $child['jml_menit_terlambat_masuk_kerja'];
        $data->jml_menit_terlambat_pulang_kerja = $child['jml_menit_terlambat_pulang_kerja'];

        $jmlPaguTpp = 0;
        $jmlNilaiKinerja = 0;
        $jmlNilaiKehadiran = 0;
        $jmlBpjs = 0;
        $jmlTppBruto = 0;
        $jmlPphPsl = 0;
        $jmlTppNetto = 0;
        $jmlBrutoSpm = 0;
        $jmlIuran = 0;
        $nilai_kinerja = 0;
        $target_nilai = 0;

        $capaian_prod = 0;
        $target_prod = 0;
        $nilaiKinerja = 0;
        $nilai_kinerja = 0;
        $keterangan = '';
        $kelas_jabatan = '';
        $golongan = '';

            $golongan = '-';
            if ($data->golongan !== null && str_contains($data->golongan, '/')) {
                $golonganParts = explode("/", $data->golongan);
                $golongan = isset($golonganParts[1]) ? $golonganParts[1] : '-';
            }
            $data->target_waktu !== null ? $target_nilai = $data->target_waktu : $target_nilai = 0;

            $target_nilai > 0 ? $nilai_kinerja = ( intval($data->capaian_waktu) / $target_nilai ) * 100 : $nilai_kinerja = 0;
            if ($nilai_kinerja > 100) {
                $nilai_kinerja = 100;
            }


            $nilaiPaguTpp = $data->pagu_tpp * $data->pembayaran / 100;
            $nilai_kinerja_rp = $nilaiPaguTpp* 60/100; 
            $nilaiKinerja = $nilai_kinerja * $nilai_kinerja_rp / 100; 
            $persentaseKehadiran = 40 * $nilaiPaguTpp / 100;
            $nilaiKehadiran = $persentaseKehadiran * $data->jml_potongan_kehadiran_kerja / 100;
            $jumlahKehadiran = $persentaseKehadiran - $nilaiKehadiran;
            $bpjs = 1 * $nilaiPaguTpp / 100;
            $data->tanpa_keterangan > 3  ? $keterangan = 'TMS'  : $keterangan = 'MS';
            $tppBruto = 0;
            $iuran = 4 * $nilaiPaguTpp / 100;
            if ($keterangan === 'TMS') {
                $tppBruto = 0;
                $bpjs=0;
                $iuran=0;
                $brutoSpm=0;
            }else{
                $tppBruto = $nilaiKinerja + $jumlahKehadiran - $bpjs;
                $brutoSpm = $nilaiKinerja + $jumlahKehadiran + $iuran;
            }

            if (strstr( $golongan, 'IV' )) {
                $pphPsl = 15 * $tppBruto / 100;
            }elseif (strstr( $golongan, 'III' )) {
                    $pphPsl = 5 * $tppBruto / 100;
            }else{
                $pphPsl = 0;
            }
            $tppNetto = $tppBruto - $pphPsl;
            

        $result = [
            'kinerja_maks' => $nilai_kinerja_rp,
            'persen_kinerja_maks' => round($nilai_kinerja,2),
            'kehadiran_maks' => $persentaseKehadiran,
            'persen_kehadiran_maks' => $data->jml_potongan_kehadiran_kerja,
            'potongan_kinerja' => $nilaiPaguTpp * (100 - $nilai_kinerja) / 100,
            'persen_potongan_kinerja' => round((100 - $nilai_kinerja),2),
            'potongan_kehadiran' => $nilaiKehadiran,
            'persentase_potongan_kehadiran' => $data->jml_potongan_kehadiran_kerja,
            'bpjs' => $bpjs,
            'pphPsl' => $pphPsl,
            'potongan_jkn_pph' => $pphPsl + $bpjs,
            'nilai_bruto' => $tppBruto,
            'tpp_bulan_ini' => $tppNetto,
            'tppNetto' => $tppNetto,
            'brutoSpm' => $brutoSpm,
            'nilaiPaguTpp' => $nilaiPaguTpp,
            'iuran' => $nilaiPaguTpp * 4 / 100,
            'jml_hari_kerja' => $data->jml_hari_kerja,
            'jml_hadir' => $data->jml_hadir,
            'jml_sakit' => $data->jml_sakit,
            'jml_cuti' => $data->jml_cuti,
            'jml_dinas_luar' => $data->jml_dinas_luar,
            'jml_tidak_apel' => $data->jml_tidak_apel,
            'jml_apel' => $data->jml_apel,
            'tanpa_keterangan' => $data->tanpa_keterangan,
            'persen_pagu_kinerja' => 60,
            'persen_pagu_kehadiran' => 40,
            'capaian_kinerja' => $nilaiKinerja,
        ];
            
    } catch (\Exception $e) {
       return $this->sendError($e->getMessage(), $e->getMessage(), 200);
    }
    return $this->sendResponse($result, 'TPP fetch Success');
}

    public function waktu_server(){
        $data = array();
        try {
            $currentTime = now();
            $data = [
                'date' => $currentTime->toDateString(), 
                'time' => $currentTime->toTimeString(),
            ];
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Waktu Server Success');
    }

    public function sasaran_kinerja(){
        $data = array();
        $pegawai_dinilai = 0;
        try {
            if ($this->findJabatanUser()) {
                $data = DB::table('tb_skp')
                ->join('tb_aspek_skp','tb_aspek_skp.id_skp','=','tb_skp.id')
                ->where('tb_skp.id_jabatan',$this->findJabatanUser()->jabatan)
                ->selectRaw('COALESCE(SUM(tb_aspek_skp.target), 0) as target_sasaran')
                ->selectRaw('COALESCE(SUM(tb_aspek_skp.realisasi), 0) as target_pencapaian')
                ->first();

                $pegawai_dinilai = DB::table('tb_jabatan') 
                ->where('id_parent', '=', $this->findJabatanUser()->jabatan) 
                ->count();
            }



            $sasaran =  0;
            $realisasi = 0;

            if (is_array($data) && count($data) > 0) {
                // $data adalah array dan memiliki elemen
                $sasaran = isset($data['target_sasaran']) ? $data['target_sasaran'] : 0;
                $realisasi = isset($data['target_pencapaian']) ? $data['target_pencapaian'] : 0;
            } elseif (is_object($data)) {
                // $data adalah objek
                $sasaran = isset($data->target_sasaran) ? $data->target_sasaran : 0;
                $realisasi = isset($data->target_pencapaian) ? $data->target_pencapaian : 0;
            } else {
                // $data bukan array atau objek
                $sasaran = 0;
                $realisasi = 0;
            }

            $data = [
                'sasaran' => $sasaran,
                'realisasi' => $realisasi,
                'kinerja' => $sasaran > 0 ? round(($realisasi / $sasaran) * 100,2) : 0,
                'pegawai_dinilai' => $pegawai_dinilai
            ];
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        } 

        return $this->sendResponse($data, 'Atasan fetch Success');
    }

    public function pengumuman(){
        $data = array();
        try {
            $data = DB::table('tb_pengumuman')->select('id','judul','deskripsi')->get();
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'List Pengumuman fetched Success');
    }
   
}
