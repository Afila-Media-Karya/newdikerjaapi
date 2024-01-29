<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Auth;
trait Kehadiran
{
    public function jumlahHariKerja($bulan){
       $tahun = date('Y');
        $tanggalAwal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $tanggalAkhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        if ($tanggalAwal->isCurrentMonth()) {
            $tanggalAkhir = Carbon::now();
        }


        $jumlahHari = 0;

        while ($tanggalAwal <= $tanggalAkhir) {
            // Cek apakah hari merupakan hari Sabtu atau Minggu
            if ($tanggalAwal->isWeekday() && !$tanggalAwal->isSaturday() && !$tanggalAwal->isSunday()) {
                // Cek apakah tanggal merupakan hari libur
                $tanggal = $tanggalAwal->format('Y-m-d');
                $libur = DB::table('tb_libur')
                    ->whereDate('tanggal_mulai', '<=', $tanggal)
                    ->whereDate('tanggal_selesai', '>=', $tanggal)
                    ->exists();

                if (!$libur) {
                    $jumlahHari++;
                }
            }

            $tanggalAwal->addDay();
        }

        return $jumlahHari;
    }

    public function jmlAlfa($bulan){
        $jumlahHariKerja = $this->jumlahHariKerja($bulan);
          $jumlahAlfa = DB::table('tb_absen')
            ->whereMonth('tanggal_absen', $bulan)
            ->count();

            return $jumlahHariKerja - $jumlahAlfa;
    }

    public function hitungTelat($bulan){
        $absen = DB::table('tb_absen')->where('id_pegawai', Auth::user()->id_pegawai)->whereMonth('tanggal_absen',$bulan)->get();
        // return $absen;
        $kmk_30 = 0;
        $kmk_60 = 0;
        $kmk_90 = 0;
        $kmk_91 = 0;

        $cpk_30 = 0;
        $cpk_60 = 0;
        $cpk_90 = 0;
        $cpk_91 = 0;
        $jml_tidak_apel = 0;

        foreach ($absen as $data) {
            if ($data->waktu_keluar !== null) {
                $waktuMasukDefault = Carbon::createFromTime(8, 0, 0); // Waktu masuk default
                $waktuPulangDefault = Carbon::createFromTime(16, 0, 0); // Waktu pulang default

                // Ubah format waktu masuk dan waktu pulang menjadi objek Carbon
                $waktuMasuk = Carbon::createFromFormat('H:i:s', $data->waktu_masuk);
                $waktuPulang = Carbon::createFromFormat('H:i:s', $data->waktu_keluar);

                // Hitung selisih telat masuk dalam menit
                $telatMasuk = $waktuMasuk->diffInMinutes($waktuMasukDefault);

                // Hitung selisih cepat pulang dalam menit
                $cepatPulang = $waktuPulangDefault->diffInMinutes($waktuPulang);
                $tanggalAbsen = Carbon::parse($data->tanggal_absen);

                // Periksa apakah hari adalah Senin
                if ($tanggalAbsen->isMonday()) {
                    // Periksa jika status_absen bukan 'apel'
                    if ($data->status !== 'apel') {
                        $jml_tidak_apel++;
                    }
                }

                // Cek apakah telat masuk atau cepat pulang lebih dari atau sama dengan 30, 60, atau 90 menit
                if ($telatMasuk >= 1 && $telatMasuk <= 30) {
                    $kmk_30++;
                }
                
                if ($telatMasuk >= 31 && $telatMasuk <= 60) {
                    $kmk_60++;
                }

                if ($telatMasuk >= 61 && $telatMasuk <= 90) {
                    $kmk_90++;
                }

                if ($telatMasuk >= 91) {
                    $kmk_91++;
                }

                if ($cepatPulang >= 1 && $cepatPulang <= 30) {
                    $cpk_30++;
                }
                
                if ($cepatPulang >= 31 && $cepatPulang <= 60) {
                    $cpk_60++;
                }

                if ($cepatPulang >= 61 && $cepatPulang <= 90) {
                    $cpk_90++;
                }

                if ($cepatPulang >= 91) {
                    $cpk_91++;
                }
            }
        }

        return [
            'kmk_30' => $kmk_30,
            'kmk_60' => $kmk_60,
            'kmk_90' => $kmk_90,
            'kmk_91' => $kmk_91,
            'cpk_30' => $cpk_30,
            'cpk_60' => $cpk_60,
            'cpk_90' => $cpk_90,
            'cpk_91' => $cpk_91,
            'jumlah_tidak_apel' => $jml_tidak_apel
        ];
    }

    public function rekapDataKehadiran($bulan){
        $source = array();
        try {
            $res_jumlah_alfa = $this->jmlAlfa($bulan) * 3;
            $source = $this->hitungTelat($bulan);
             $res_jml_tidak_apel = $source['jumlah_tidak_apel'] * 2;
            $query = DB::table('tb_absen')
                ->select(
                    DB::raw('COUNT(CASE WHEN status = "hadir" THEN 1 END) AS jumlah_hadir'),
                    DB::raw('COUNT(CASE WHEN status = "apel" THEN 1 END) AS jumlah_apel'),
                    DB::raw('COUNT(CASE WHEN status = "dinas luar" THEN 1 END) AS jumlah_dinas_luar'),
                    DB::raw('COUNT(CASE WHEN status = "sakit" THEN 1 END) AS jumlah_sakit'),
                    DB::raw('COUNT(CASE WHEN status = "izin" THEN 1 END) AS jumlah_izin'),
                    DB::raw('COUNT(CASE WHEN status = "cuti" THEN 1 END) AS jumlah_cuti')
                )
                ->whereYear('tanggal_absen', date('Y'))
                ->whereMonth('tanggal_absen', $bulan)
                ->where('id_pegawai',Auth::user()->id_pegawai)
                ->where('validation',1)
                ->first();

            $potongan_masuk_kerja = ($source['kmk_30'] * 0.5) + ($source['kmk_60'] * 1) + ($source['kmk_90'] * 1.25) + ($source['kmk_91'] * 1.5);
            $potongan_pulang_kerja = ($source['cpk_30'] * 0.5) + ($source['cpk_60'] * 1) + ($source['cpk_90'] * 1.25) + ($source['cpk_91'] * 1.5);
            $potongan_kehadiran = $res_jumlah_alfa * $potongan_masuk_kerja * $potongan_pulang_kerja * $res_jml_tidak_apel;
            return [
                'hari_kerja' => $this->jumlahHariKerja($bulan),
                'jumlah_hadir' => $query->jumlah_hadir + $query->jumlah_apel,
                'jumlah_apel' => $query->jumlah_apel,
                'jumlah_dinas_luar' => $query->jumlah_dinas_luar,
                'jumlah_sakit' => $query->jumlah_sakit,
                'jumlah_izin' => $query->jumlah_izin,
                'jumlah_cuti' => $query->jumlah_cuti,
                'tanpa_keterangan' =>  $this->jmlAlfa($bulan),
                'potongan' => $potongan_kehadiran
            ];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function checkAbsenByTanggal($pegawai, $date){
         $data = array();
        // if (date('D', strtotime($date)) == 'Sun') {
        //     $data = null;
        // }else{
            
        // }
        $data = DB::table('tb_absen')->select('status')->where('id_pegawai',$pegawai)->where('tanggal_absen',$date)->first();
        return $data;
    }

    public function konvertWaktuNakes($params, $waktu, $tanggal,$shift,$waktu_tetap)
    {
        $diff = '';
        $selisih_waktu = '';
        $menit = 0;

        $waktu_absen_datang = '';
        $waktu_absen_pulang = '';

        if ($shift == 'pagi') {
            $waktu_absen_datang = '08:00:00';
            $waktu_absen_pulang = '14:00:00';
        }elseif ($shift == 'siang') {
            $waktu_absen_datang = '14:00:00';
            $waktu_absen_pulang = '21:00:00';
        }else {
            $waktu_absen_datang = '21:00:00';
            $waktu_absen_pulang = '08:00:00';
        }

        $tanggalCarbon = Carbon::createFromFormat('Y-m-d', $tanggal);

        if ($tanggalCarbon->isMonday()) {
            if ($params == 'masuk') {
                $waktu_absen_datang = $waktu_tetap;
            }
        }

        if ($waktu !== null) {
            if ($params == 'masuk') {
                $waktu_tetap_absen = strtotime($waktu_absen_datang);
                $waktu_absen = strtotime($waktu);
                $diff = $waktu_absen - $waktu_tetap_absen;
            } else {
                $waktu_checkout = $waktu_absen_pulang;
                $arr = $this->getDateRange();
                $key = array_search($waktu, $arr);

                if ($key !== false) {
                    $waktu_checkout = '15:00:00';
                }

                $waktu_tetap_absen = strtotime($waktu_checkout);
                $waktu_absen = strtotime($waktu);
                $diff = $waktu_tetap_absen - $waktu_absen;
            }

            if ($diff > 0) {
                $menit = floor($diff / 60);
            } else {
                $diff = 0;
            }
        }else{
             $menit = 90;
        }
        return $menit;
    }

    public function konvertWaktu($params, $waktu, $tanggal,$waktu_default_absen,$tipe_pegawai)
    {
   
        $diff = '';
        $selisih_waktu = '';
        $menit = 0;

        if ($waktu !== null) {
            if ($params == 'masuk') {
                $waktu_tetap_absen = strtotime($waktu_default_absen);
                $waktu_absen = strtotime($waktu);
                $diff = $waktu_absen - $waktu_tetap_absen;
            } else {
                $waktu_checkout = $waktu_default_absen;
                $arr = $this->getDateRange();
                $key = array_search($waktu, $arr);

                if ($key !== false) {
                    $tipe_pegawai == 'pegawai_administratif' ? $waktu_checkout = '15:00:00' : $waktu_checkout = '13:00:00';
                }

                $waktu_tetap_absen = strtotime($waktu_checkout);
                $waktu_absen = strtotime($waktu);
                $diff = $waktu_tetap_absen - $waktu_absen;
            }

            if ($diff > 0) {
                $menit = floor($diff / 60);
            } else {
                $diff = 0;
            }
        }else{
             $menit = 90;
        }

        

        return $menit;
    }

    function getDateRange()
    {
        $start_date = '2024-03-10';
        $end_date = '2024-04-09';

        $dates = [];
        for ($date = Carbon::parse($start_date); $date->lte(Carbon::parse($end_date)); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }
}