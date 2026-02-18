<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Auth;
trait General
{
    public function option_golongan()
    {
        return DB::table('tb_golongan')->select('golongan as value', 'golongan as text')->where('status', 'active')->get();
    }

    public function option_pendidikan()
    {
        return DB::table('tb_pendidikan')->select('pendidikan as value', 'pendidikan as text')->where('status', 'active')->get();
    }

    public function option_eselon()
    {
        return DB::table('tb_eselon')->select('eselon as value', 'eselon as text')->where('status', 'active')->get();
    }

    public function option_satuan_kerja()
    {
        return DB::table('tb_satuan_kerja')->select('id as value', 'nama_satuan_kerja as text')->get();
    }

    public function option_unit_kerja()
    {
        return DB::table('tb_unit_kerja')->select('id as value', 'nama_unit_kerja as text')->get();
    }

    public function option_by_unit_kerja($params)
    {
        return DB::table('tb_unit_kerja')->select('id', 'nama_unit_kerja as text')->where('id_satuan_kerja', $params)->get();
    }

    public function option_akan_pensiun($params)
    {
        $semuaPegawai = DB::table('tb_pegawai')->leftJoin('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')->leftJoin('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', 'tb_master_jabatan.id')->leftJoin('tb_satuan_kerja', 'tb_pegawai.id_satuan_kerja', '=', 'tb_satuan_kerja.id')->select('tb_pegawai.id', DB::raw('CONCAT(tb_pegawai.nama, " - ", tb_pegawai.nip) as text'), 'tb_pegawai.nip', 'tb_master_jabatan.jenis_jabatan')->where('tb_pegawai.id_satuan_kerja', $params)->get();
        // Array untuk menyimpan pegawai yang akan pensiun
        $pegawaiAkanPensiun = [];

        // Iterasi semua pegawai
        foreach ($semuaPegawai as $pegawai) {
            $nipPegawai = $pegawai->nip;

            // Mengurai NIP untuk mendapatkan tanggal lahir
            $tahun = substr($nipPegawai, 0, 4);
            $bulan = substr($nipPegawai, 4, 2);
            $tanggal = substr($nipPegawai, 6, 2);

            $tanggalLahir = "$tahun-$bulan-$tanggal";

            // Memeriksa apakah pegawai akan pensiun
            $usiaPensiun = 58; // Ganti dengan usia pensiun yang sesuai

            if ($pegawai->jenis_jabatan == 'Pimpinan Tinggi Pratama/IIa' || $pegawai->jenis_jabatan == 'Pimpinan Tinggi Pratama/IIb' || $pegawai->jenis_jabatan == 'Fungsional Keahlian Utama' || $pegawai->jenis_jabatan == 'Fungsional Keahlian Madya') {
                $usiaPensiun = 60;
            }

            $tanggalPensiun = date("Y-m-d", strtotime("+$usiaPensiun years", strtotime($tanggalLahir)));

            if (strtotime($tanggalPensiun) <= time()) {
                // Tambahkan pegawai ke array
                $pegawaiAkanPensiun[] = $pegawai;
            }
        }
        return $pegawaiAkanPensiun;
    }

    public function option_akan_pensiun_by_unit_kerja($params)
    {
        $semuaPegawai = DB::table('tb_pegawai')->leftJoin('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')->leftJoin('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', 'tb_master_jabatan.id')->leftJoin('tb_satuan_kerja', 'tb_pegawai.id_satuan_kerja', '=', 'tb_satuan_kerja.id')->select('tb_pegawai.id', DB::raw('CONCAT(tb_pegawai.nama, " - ", tb_pegawai.nip) as text'), 'tb_pegawai.nip', 'tb_master_jabatan.jenis_jabatan')->where('tb_jabatan.id_unit_kerja', $params)->get();
        // Array untuk menyimpan pegawai yang akan pensiun
        $pegawaiAkanPensiun = [];

        // Iterasi semua pegawai
        foreach ($semuaPegawai as $pegawai) {
            $nipPegawai = $pegawai->nip;

            // Mengurai NIP untuk mendapatkan tanggal lahir
            $tahun = substr($nipPegawai, 0, 4);
            $bulan = substr($nipPegawai, 4, 2);
            $tanggal = substr($nipPegawai, 6, 2);

            $tanggalLahir = "$tahun-$bulan-$tanggal";

            // Memeriksa apakah pegawai akan pensiun
            $usiaPensiun = 58; // Ganti dengan usia pensiun yang sesuai

            if ($pegawai->jenis_jabatan == 'Pimpinan Tinggi Pratama/IIa' || $pegawai->jenis_jabatan == 'Pimpinan Tinggi Pratama/IIb' || $pegawai->jenis_jabatan == 'Fungsional Keahlian Utama' || $pegawai->jenis_jabatan == 'Fungsional Keahlian Madya') {
                $usiaPensiun = 60;
            }

            $tanggalPensiun = date("Y-m-d", strtotime("+$usiaPensiun years", strtotime($tanggalLahir)));

            if (strtotime($tanggalPensiun) <= time()) {
                // Tambahkan pegawai ke array
                $pegawaiAkanPensiun[] = $pegawai;
            }
        }
        return $pegawaiAkanPensiun;
    }

    public function option_pegawaiBy_satuan_kerja($satuan_kerja, $kelas_jabatan)
    {
        $data = array();

        $query = DB::table('tb_pegawai')
            ->select(DB::raw('CONCAT(nama, " - ", nip) as text'), 'id', 'tipe_pegawai')
            ->where('status', '1');

        if ($kelas_jabatan < 12) {
            $query->where('id_satuan_kerja', $satuan_kerja);
        }

        $data = $query->get();
        return $data;
    }

    public function option_pegawaiBy_unit_kerja($satuan_kerja, $unit_kerja = null)
    {
        $data = array();

        $query = DB::table('tb_pegawai')->select('tb_pegawai.id', DB::raw('CONCAT(tb_pegawai.nama, " - ", tb_pegawai.nip) as text'), 'tipe_pegawai')
            ->join('tb_jabatan', 'tb_jabatan.id_pegawai', '=', 'tb_pegawai.id')
            ->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
            ->where('tb_pegawai.status', '1')
            ->groupBy('tb_pegawai.id');

        if (isset($satuan_kerja)) {
            $query->where('tb_pegawai.id_satuan_kerja', $satuan_kerja);
        }

        if ($unit_kerja !== 'all') {
            $query->where('tb_jabatan.id_unit_kerja', $unit_kerja);
        }

        $data = $query->get();
        return $data;
    }

    public function checkJabatan($pegawai)
    {
        return DB::table('tb_pegawai')->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', 'tb_master_jabatan.id')->select('tb_pegawai.id', 'tb_pegawai.uuid', 'tb_pegawai.nip', 'tb_pegawai.nama', 'tb_master_jabatan.nama_jabatan', 'tb_jabatan.id as id_jabatan', 'tb_jabatan.status')->where('tb_pegawai.uuid', $pegawai)->first();
    }

    public function option_agama()
    {
        return DB::table('tb_agama')->select('agama as value', 'agama as text')->where('status', 'active')->get();
    }

    public function option_satuan()
    {
        return DB::table('tb_satuan')->select('satuan as value', 'satuan as text')->where('status', 'active')->get();
    }

    public function infoSatuanKerja($pegawai)
    {
        return DB::table('tb_pegawai')
            ->join('tb_satuan_kerja', 'tb_pegawai.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
            ->leftJoin('tb_jabatan', 'tb_jabatan.id_pegawai', '=', 'tb_pegawai.id')
            ->leftJoin('tb_unit_kerja', 'tb_jabatan.id_unit_kerja', '=', 'tb_unit_kerja.id')
            ->select('tb_satuan_kerja.nama_satuan_kerja', 'tb_pegawai.id_satuan_kerja', 'tb_unit_kerja.id as id_unit_kerja', 'tb_unit_kerja.nama_unit_kerja')->where('tb_pegawai.id', $pegawai)->first();
    }

    public function option_jenis_jabatan()
    {
        return DB::table('tb_jenis_jabatan')->select('id', 'jenis_jabatan as value', 'jenis_jabatan as text', 'kelas_jabatan as kelas', 'level')->get();
    }

    public function option_jenis_jabatan_all()
    {
        return DB::table('tb_jenis_jabatan')->select('id', 'jenis_jabatan as text', 'kelas_jabatan')->get();
    }

    public function option_kelompok_jabatan($jenis_jabatan, $level)
    {

        return DB::table('tb_kelompok_jabatan')
            ->select('tb_kelompok_jabatan.id', 'tb_kelompok_jabatan.kelompok as text')
            ->join('tb_jenis_jabatan', 'tb_kelompok_jabatan.id_jenis_jabatan', '=', 'tb_jenis_jabatan.id')
            ->where('tb_kelompok_jabatan.id_jenis_jabatan', $jenis_jabatan)
            ->where('tb_jenis_jabatan.kelas_jabatan', $level)
            ->get();
    }

    public function option_kelompok_jabatan_all()
    {
        return DB::table('tb_kelompok_jabatan')->select('id as value', 'kelompok as text')->get();
    }

    public function option_atasan_langsung($level, $satuan_kerja)
    {
        $data = array();
        $query = DB::table('tb_master_jabatan')
            ->leftJoin('tb_jabatan', 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
            ->leftJoin('tb_pegawai', 'tb_jabatan.id_pegawai', '=', 'tb_pegawai.id')
            ->select('tb_jabatan.id', 'tb_master_jabatan.id as id_master_jabatan', 'tb_master_jabatan.nama_jabatan as text', 'tb_pegawai.nama')
            ->where('tb_master_jabatan.level_jabatan', '<=', 6);

        if (intval($level) > 2) {
            $query->where('tb_master_jabatan.id_satuan_kerja', $satuan_kerja);
        }

        $data = $query->get();

        return $data;
    }

    public function option_jabatan_all($satuan_kerja, $type)
    {
        $result = array();

        if ($type == 'update') {
            $result = DB::table('tb_master_jabatan')
                ->where('tb_master_jabatan.id_satuan_kerja', $satuan_kerja)
                ->distinct()
                ->union(
                    DB::table('tb_master_jabatan')
                        ->select(
                            'tb_master_jabatan.id as id',
                            'tb_master_jabatan.nama_jabatan as text',
                        )
                        ->where('tb_master_jabatan.id_satuan_kerja', 0)
                )
                ->select('tb_master_jabatan.id as id', 'tb_master_jabatan.nama_jabatan as text')
                ->get();
        } elseif ($type == 'add') {
            $result = DB::table('tb_master_jabatan')
                // ->where('tb_master_jabatan.id_satuan_kerja', 0)
                ->where('tb_master_jabatan.nama_jabatan', '!=', 'BUPATI')
                ->orWhereIn('tb_master_jabatan.level_jabatan', [5, 8])
                ->distinct()
                ->select('tb_master_jabatan.id as id', 'tb_master_jabatan.nama_jabatan as text')
                ->get();
        } elseif ($type == 'tenaga_pendidik') {
            $result = DB::table('tb_master_jabatan')
                ->where('tb_master_jabatan.id_satuan_kerja', $satuan_kerja)
                ->select('tb_master_jabatan.id as id', 'tb_master_jabatan.nama_jabatan as text')
                ->get();
        }

        return $result;
    }

    public function option_lokasi_satuan_kerja($params)
    {
        return DB::table('tb_lokasi')->select('id', 'nama_lokasi as text')->where('id_satuan_kerja', $params)->get();
    }

    public function checkAbsenByTanggal($pegawai, $date)
    {
        $data = array();
        if (date('D', strtotime($date)) == 'Sun') {
            $data = null;
        } else {
            $data = DB::table('tb_absen')->join('tb_pegawai', 'tb_absen.id_pegawai', '=', 'tb_pegawai.id')->select('tb_absen.status', 'tb_pegawai.nip', 'tb_absen.tanggal_absen')->where('tb_absen.id_pegawai', $pegawai)->where('tanggal_absen', $date)->first();
        }
        return $data;
    }

    public function ifPegawaiPlt($pegawai)
    {
        $query = DB::table('tb_pegawai')
            ->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')
            ->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', 'tb_master_jabatan.id')
            ->select('tb_pegawai.id', 'tb_pegawai.uuid', 'tb_pegawai.id_satuan_kerja', 'tb_pegawai.nip', 'tb_pegawai.nama', 'tb_master_jabatan.nama_jabatan', 'tb_master_jabatan.level_jabatan', 'tb_jabatan.id_parent', 'tb_jabatan.id as id_jabatan', 'tb_jabatan.status', 'tb_master_jabatan.id_kelompok_jabatan', 'tb_master_jabatan.id as id_master_jabatan', 'tb_jabatan.target_waktu', 'tb_master_jabatan.level_jabatan')
            ->where('tb_pegawai.id', $pegawai)
            ->first();

        return $query;
    }

    public function checkJabatanDefinitif($pegawai, $params = null, $type = null)
    {
        $status = '';
        $data = array();

        if ($params !== null && $params !== "") {
            $status = $params;
        } else {
            session('session_jabatan') ? $status = session('session_jabatan') : $status = 'definitif';
        }

        $path = explode('/', request()->path());

        if ($path[0] == 'review' && $path[1] == 'aktivitas' && $path[2] == 'review') {
            $status = 'definitif';
        }

        $query = DB::table('tb_pegawai')
            ->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')
            ->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', 'tb_master_jabatan.id')
            ->select('tb_pegawai.id', 'tb_pegawai.uuid', 'tb_pegawai.id_satuan_kerja', 'tb_pegawai.nip', 'tb_pegawai.nama', 'tb_master_jabatan.nama_jabatan', 'tb_master_jabatan.level_jabatan', 'tb_jabatan.id_parent', 'tb_jabatan.id as id_jabatan', 'tb_jabatan.status', 'tb_master_jabatan.id_kelompok_jabatan', 'tb_master_jabatan.id as id_master_jabatan', 'tb_jabatan.target_waktu', 'tb_master_jabatan.level_jabatan')
            ->where('tb_jabatan.status', $status)
            ->where('tb_pegawai.id', $pegawai);

        if ($type) {
            if ($type > 0) {
                $query->where('tb_jabatan.id', session('session_jabatan_kode'));
            }
        } else {
            if (session('session_jabatan_kode') && $path[0] !== 'review') {
                if (is_null($type) || $type > 0) {
                    $query->where('tb_jabatan.id', session('session_jabatan_kode'));
                }
            }
        }


        $data = $query->first();

        if (is_null($data)) {
            $data = $this->ifPegawaiPlt($pegawai);
        }

        return $data;

    }

    public function checkJabatanAll($pegawai)
    {
        return DB::table('tb_pegawai')->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', 'tb_master_jabatan.id')->select('tb_pegawai.id', 'tb_pegawai.uuid', 'tb_pegawai.id_satuan_kerja', 'tb_pegawai.nip', 'tb_pegawai.nama', 'tb_master_jabatan.nama_jabatan', 'tb_master_jabatan.level_jabatan', 'tb_jabatan.id_parent', 'tb_jabatan.id as id_jabatan', 'tb_jabatan.status', 'tb_master_jabatan.id_kelompok_jabatan', 'tb_master_jabatan.id as id_master_jabatan', 'tb_jabatan.target_waktu')->where('tb_pegawai.id', $pegawai)->get();
    }

    public function optionJabatanKosong($params)
    {
        return DB::table('tb_jabatan')
            ->join('tb_master_jabatan as jabatan1', 'tb_jabatan.id_master_jabatan', '=', 'jabatan1.id')
            ->select([
                'tb_jabatan.id',
                DB::raw('
                CASE 
                    WHEN tb_jabatan.status = "definitif" THEN jabatan1.nama_jabatan
                    ELSE CONCAT(UPPER(tb_jabatan.status), " ", jabatan1.nama_jabatan)
                END as text
            ')
            ])
            ->where('tb_jabatan.id_satuan_kerja', $params)
            ->whereNull('tb_jabatan.id_pegawai')
            ->get();
    }

    public function checkJabatanDuplicate($params)
    {
        return DB::table('tb_jabatan')->where('id_master_jabatan', $params)->first();
    }

    public function checkMasterJabatan($request)
    {
        return DB::table('tb_master_jabatan')->where('nama_struktur', $request->nama_struktur)->orWhere('nama_jabatan', $request->nama_jabatan)->exists();
    }

    public function optionSkp($pegawai)
    {
        $jabatan = $this->checkJabatanDefinitif($pegawai);

        $result = [];

        if ($jabatan) {
            $result = DB::table('tb_skp')->select('id', 'rencana as text')->where('id_jabatan', $jabatan->id_jabatan)->where('tahun', session('tahun_penganggaran'))->get();
        }

        return $result;
    }

    public function getMasterAktivitas($pegawai)
    {
        $jabatan = $this->checkJabatanDefinitif($pegawai);

        $kelompok_jabatan = 0;
        $jabatan->level_jabatan !== 1 ? $kelompok_jabatan = $jabatan->id_kelompok_jabatan : $kelompok_jabatan = 0;
        return DB::table('tb_master_aktivitas')
            ->select('uuid', 'aktivitas as text')
            ->union(
                DB::table('tb_master_aktivitas')
                    ->select('uuid', 'aktivitas as text')
                    ->where('jenis', 'umum')
            )
            ->where('id_kelompok_jabatan', $jabatan->id_kelompok_jabatan)
            ->Where('jenis', 'khusus')
            ->get();
    }

    public function findPegawai($params, $status_jabatan = null, $role_check = null)
    {

        $status = '';
        $data = array();
        if ($status_jabatan !== null) {
            $status = $status_jabatan;
        } else {
            if (session('session_jabatan')) {
                $status = session('session_jabatan');
            }
        }

        // $status = 'definitif';
        $query = DB::table('tb_pegawai')
            ->select("tb_pegawai.nama", 'tb_pegawai.nip', "tb_pegawai.golongan", 'tb_master_jabatan.nama_jabatan', 'tb_satuan_kerja.nama_satuan_kerja', 'tb_jabatan.target_waktu', 'tb_jabatan.status as status_jabatan', 'tb_unit_kerja.nama_unit_kerja', 'tb_unit_kerja.waktu_masuk', 'tb_unit_kerja.waktu_keluar', 'tb_pegawai.tipe_pegawai', 'tb_unit_kerja.jumlah_shift')
            ->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')
            ->join("tb_master_jabatan", 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
            ->join('tb_satuan_kerja', 'tb_jabatan.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
            ->join('tb_unit_kerja', 'tb_jabatan.id_unit_kerja', '=', 'tb_unit_kerja.id')
            ->where('tb_pegawai.id', $params);

        if ($status !== '') {
            $query->where('tb_jabatan.status', $status);
            if (!is_null(session('session_jabatan_kode'))) {
                if (is_null($role_check) || $role_check > 0) {
                    if (auth()->user()->role !== "1" && auth()->user()->role !== "3") {
                        $query->where('tb_jabatan.id', session('session_jabatan_kode'));
                    }
                }
            }
        }
        $data = $query->first();

        if (is_null($data)) {
            if ($status == 'definitif') {
                $status = 'plt';
                return $this->findPegawai($params, $status);
            } elseif ($status == 'plt') {
                $status = 'definitif';
                return $this->findPegawai($params, $status);
            }
        }

        return $data;
    }

    public function findAtasan($params)
    {

        $check_role = Auth::check();
        $role_check = 0;
        $role = array();
        if ($check_role) {
            $role = hasRole();
            if ($role['guard'] == 'web' && $role['role'] == '2') {
                $role_check = 1;
            }
        }

        $jabatan = array();

        $jabatan_query = DB::table('tb_jabatan')->select('tb_jabatan.id_parent')->join('tb_master_jabatan', 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')->where('id_pegawai', $params);

        if ($role_check > 0) {
            $jabatan_query->where('tb_jabatan.id', session('session_jabatan_kode'));
        } else {
            $jabatan_query->where('tb_jabatan.status', 'definitif');
        }

        $jabatan = $jabatan_query->first();

        $data = DB::table('tb_pegawai')
            ->select("tb_pegawai.nama", 'tb_pegawai.nip', "tb_pegawai.golongan", 'tb_master_jabatan.nama_jabatan', 'tb_satuan_kerja.nama_satuan_kerja', 'tb_jabatan.id as id_jabatan', 'tb_jabatan.status as status_jabatan', 'tb_unit_kerja.nama_unit_kerja')
            ->join('tb_jabatan', 'tb_jabatan.id_pegawai', 'tb_pegawai.id')
            ->join("tb_master_jabatan", 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')
            ->join('tb_satuan_kerja', 'tb_pegawai.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
            ->join('tb_unit_kerja', 'tb_jabatan.id_unit_kerja', '=', 'tb_unit_kerja.id')
            ->where('tb_jabatan.id', $jabatan->id_parent)
            ->first();

        return $data;
    }

    function isTanggalLibur($tanggal, $tipe_pegawai)
    {

        if ($tipe_pegawai == 'pegawai_administratif' || $tipe_pegawai == 'tenaga_kesehatan') {
            $tipe_pegawai = 'pegawai_administratif';
        } else if ($tipe_pegawai = 'tenaga_kesehatan_non_shift') {
            $tipe_pegawai = 'tenaga_kesehatan_non_shift';
        } else {
            $tipe_pegawai = 'tenaga_pendidik';
        }

        $libur = DB::table('tb_libur')
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->where('tipe', $tipe_pegawai)
            ->first();
        return !empty($libur);
    }

    function isRhamadan($tanggal)
    {
        $tahun = date('Y', strtotime($tanggal));
        $cacheKey = 'ramadan_' . $tahun;

        // Cache data Ramadan per tahun selama 24 jam
        $ramadan = \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($tahun) {
            return DB::table('tb_ramadan')
                ->where('tahun', $tahun)
                ->select('tanggal_mulai', 'tanggal_selesai')
                ->first();
        });

        if (!$ramadan) {
            return false;
        }

        return $tanggal >= $ramadan->tanggal_mulai && $tanggal <= $ramadan->tanggal_selesai;
    }

    public function data_kehadiran_pegawai($pegawai, $tanggal_awal, $tanggal_akhir, $waktu_tetap_masuk, $waktu_tetap_keluar, $tipe_pegawai, $jumlah_shift)
    {
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

        $count_apel = 0;
        $count_hadir = 0;
        $count_sakit = 0;
        $count_izin_cuti = 0;
        $count_dinas_luar = 0;
        $count_cuti = 0;

        $jml_tidak_apel = 0;
        $jml_tidak_apel_hari_senin = 0;
        $jml_tidak_hadir_berturut_turut = 0;


        while ($current_date->lte(Carbon::parse($tanggal_akhir))) {
            if ($tipe_pegawai == 'pegawai_administratif') {
                if ($current_date->dayOfWeek !== 6 && $current_date->dayOfWeek !== 0) {
                    if (!$this->isTanggalLibur($current_date->toDateString(), $tipe_pegawai)) {
                        $daftar_tanggal[] = $current_date->toDateString();
                    }
                }
            } elseif ($tipe_pegawai == 'tenaga_pendidik' || $tipe_pegawai == 'tenaga_pendidik_non_guru' || $tipe_pegawai == 'tenaga_kesehatan_non_shift') {
                if ($current_date->dayOfWeek !== 0) {
                    if (!$this->isTanggalLibur($current_date->toDateString(), $tipe_pegawai)) {
                        $daftar_tanggal[] = $current_date->toDateString();
                    }
                }
            } else {
                $daftar_tanggal[] = $current_date->toDateString();
            }
            $current_date->addDay();
        }

        // Query untuk mengambil data absen
        $data = DB::table('tb_absen')
            ->select('tanggal_absen', 'status', 'waktu_masuk', 'waktu_keluar', 'waktu_istirahat', 'waktu_masuk_istirahat', 'shift', 'status_masuk_istirahat')
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
                'waktu_istirahat' => $row->waktu_istirahat,
                'waktu_masuk_istirahat' => $row->waktu_masuk_istirahat,
                'shift' => $row->shift,
                'status_masuk_istirahat' => $row->status_masuk_istirahat
            ];
        }

        $hasil_akhir = [];
        $hari_tidak_hadir_nakes = [];

        $jml_menit_terlambat_masuk_kerja = 0;
        $jml_menit_terlambat_pulang_kerja = 0;
        $selisih_waktu_masuk = 0;
        $selisih_waktu_pulang = 0;
        $tes = [];
        foreach ($daftar_tanggal as $tanggal) {
            if (isset($absen_per_tanggal[$tanggal])) {
                $tanggalCarbon = Carbon::createFromFormat('Y-m-d', $tanggal);

                if ($tanggalCarbon->isMonday()) {
                    // if (!in_array($tanggal, $this->getDateRange())) {
                    if ($absen_per_tanggal[$tanggal]['status'] !== 'apel' && $absen_per_tanggal[$tanggal]['status'] !== 'dinas luar' && $absen_per_tanggal[$tanggal]['status'] !== 'cuti' && $absen_per_tanggal[$tanggal]['status'] !== 'dinas luar' && $absen_per_tanggal[$tanggal]['status'] !== 'sakit') {
                        if ($tipe_pegawai == 'pegawai_administratif' && !$this->isRhamadan($tanggalCarbon->toDateString())) {
                            $jml_tidak_apel += 1;
                        } elseif ($tipe_pegawai == 'tenaga_kesehatan') {

                            if ($absen_per_tanggal[$tanggal]['shift'] == 'pagi' && !$this->isTanggalLibur($tanggalCarbon->toDateString(), $tipe_pegawai) && !$this->isRhamadan($tanggalCarbon->toDateString())) {
                                $jml_tidak_apel += 1;
                            }
                        }
                    }

                    if ($absen_per_tanggal[$tanggal]['status'] == 'apel') {
                        $count_apel += 1;
                    }

                    // } 
                }

                if (in_array($tanggalCarbon->format('l'), ['Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
                    if ($absen_per_tanggal[$tanggal]['status'] !== 'apel' && $absen_per_tanggal[$tanggal]['status'] !== 'dinas luar' && $absen_per_tanggal[$tanggal]['status'] !== 'cuti' && $absen_per_tanggal[$tanggal]['status'] !== 'dinas luar' && $absen_per_tanggal[$tanggal]['status'] !== 'sakit') {
                        if ($tipe_pegawai == 'pegawai_administratif' && !$this->isRhamadan($tanggalCarbon->toDateString())) {
                            $jml_tidak_apel_hari_senin += 1;
                        }
                    }
                }



                if ($absen_per_tanggal[$tanggal]['status'] == 'hadir' || $absen_per_tanggal[$tanggal]['status'] == 'apel') {
                    $count_hadir += 1;
                } elseif ($absen_per_tanggal[$tanggal]['status'] == 'sakit') {
                    $count_sakit += 1;
                } elseif ($absen_per_tanggal[$tanggal]['status'] == 'izin' || $absen_per_tanggal[$tanggal]['status'] == 'cuti') {
                    $count_izin_cuti += 1;
                }

                if ($absen_per_tanggal[$tanggal]['status'] == 'dinas luar') {
                    $count_dinas_luar += 1;
                }

                if ($absen_per_tanggal[$tanggal]['status'] == 'cuti') {
                    $count_cuti += 1;
                }

                if ($tipe_pegawai == 'pegawai_administratif' || $tipe_pegawai == 'tenaga_pendidik' || $tipe_pegawai == 'tenaga_pendidik_non_guru') {
                    $selisih_waktu_masuk = $this->konvertWaktu('masuk', $absen_per_tanggal[$tanggal]['waktu_masuk'], $tanggal, $waktu_tetap_masuk, $tipe_pegawai);
                    $selisih_waktu_pulang = $this->konvertWaktu('keluar', $absen_per_tanggal[$tanggal]['waktu_keluar'], $tanggal, $waktu_tetap_keluar, $tipe_pegawai);
                    // dd($tanggal);

                } else {
                    $selisih_waktu_masuk = $this->konvertWaktuNakes('masuk', $absen_per_tanggal[$tanggal]['waktu_masuk'], $tanggal, $absen_per_tanggal[$tanggal]['shift'], $waktu_tetap_masuk, $jumlah_shift, $tipe_pegawai);
                    $selisih_waktu_pulang = $this->konvertWaktuNakes('keluar', $absen_per_tanggal[$tanggal]['waktu_keluar'], $tanggal, $absen_per_tanggal[$tanggal]['shift'], $waktu_tetap_keluar, $jumlah_shift, $tipe_pegawai);
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
                $keterangan_pulang = '';

                $waktuSekarang = Carbon::now();
                $jamSekarang = $waktuSekarang->format('H:i:s');

                if ($waktu_pulang) {
                    $keterangan_pulang = $selisih_waktu_pulang > 0 ? 'Cepat ' . $selisih_waktu_pulang . ' menit' : 'Tepat waktu';
                } else {
                    if ($waktuSekarang->greaterThan(Carbon::parse('22:00:00'))) {
                        $waktu_pulang = '14:00:00';
                        $keterangan_pulang = 'Cepat 90 menit';
                    } else {
                        $waktu_pulang = 'Belum Absen';
                        $keterangan_pulang = 'Belum Absen';
                    }
                }


                $hasil_akhir[] = [
                    'tanggal_absen' => $tanggal, // Ganti nilai 'tanggal_absen' dengan tanggal yang sesuai
                    'status' => $absen_per_tanggal[$tanggal]['status'],
                    'waktu_masuk' => $absen_per_tanggal[$tanggal]['waktu_masuk'],
                    'waktu_keluar' => $waktu_pulang,
                    'waktu_istirahat' => $absen_per_tanggal[$tanggal]['waktu_istirahat'],
                    'waktu_masuk_istirahat' => $absen_per_tanggal[$tanggal]['waktu_masuk_istirahat'],
                    'keterangan_masuk' => $selisih_waktu_masuk > 0 ? 'Telat ' . $selisih_waktu_masuk . ' menit' : 'Tepat waktu',
                    'keterangan_pulang' => $keterangan_pulang,
                    'shift' => $absen_per_tanggal[$tanggal]['shift'],
                    'status_masuk_istirahat' => $absen_per_tanggal[$tanggal]['status_masuk_istirahat']
                ];
            } else {
                // array_push($tes,$tanggal);
                $tanggalCarbon = Carbon::createFromFormat('Y-m-d', $tanggal);
                if ($tanggalCarbon->isWeekday() && !$tanggalCarbon->isTomorrow() && !$this->isTanggalLibur($tanggalCarbon->toDateString(), $tipe_pegawai)) {
                    $jml_tidak_hadir_berturut_turut += 1;
                } else {
                    $jml_tidak_hadir_berturut_turut = 0;
                }

                $status_ = 'Tanpa Keterangan';

                if (strtotime($tanggal) > strtotime(date('Y-m-d'))) {
                    $status_ = 'Belum absen';
                } else {
                    if ($tipe_pegawai == 'pegawai_administratif' || $tipe_pegawai == 'tenaga_pendidik') {
                        $jml_alfa += 1;
                    } else {
                        $mingguKe = $tanggalCarbon->weekOfMonth;

                        $tanggalSebelumnya = date('Y-m-d', strtotime($tanggal . ' -1 day'));
                        $tanggalSebelumnya2 = date('Y-m-d', strtotime($tanggal . ' -2 day'));

                        $check_last_day = DB::table('tb_absen')->where('tanggal_absen', $tanggalSebelumnya)->where('id_pegawai', $pegawai)->first();
                        $check_last_day2 = DB::table('tb_absen')->where('tanggal_absen', $tanggalSebelumnya2)->where('id_pegawai', $pegawai)->first();

                        if ($tipe_pegawai == 'tenaga_kesehatan') {
                            if (!is_null($check_last_day) && $check_last_day->shift == 'malam') {
                                $status_ = 'Lepas Jaga / Lepas Piket';
                            } elseif (!is_null($check_last_day2) && $check_last_day2->shift == 'malam') {
                                $status_ = 'Lepas Jaga / Lepas Piket';
                            } else {
                                $hari_tidak_hadir_nakes[] = ['tanggal' => $tanggal, 'minggu' => $mingguKe];
                                $status_ = '-';
                            }
                        }


                    }
                }


                // Jika tidak ada data absen untuk tanggal ini, berikan nilai null
                $hasil_akhir[] = [
                    'tanggal_absen' => $tanggal,
                    'status' => $status_,
                    'waktu_masuk' => '-',
                    'waktu_keluar' => '-',
                    'waktu_istirahat' => '-',
                    'waktu_masuk_istirahat' => '-',
                    'keterangan_masuk' => '-',
                    'keterangan_pulang' => '-',
                    'shift' => '-',
                    'status_masuk_istirahat' => '-'
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

        // if ($count_izin_cuti > 7) {
        //     $potongan_cuti_izin = ($count_izin_cuti - 7) * 3;
        //     $potongan_cuti_izin += ($count_izin_cuti - 2) * 2;
        // } elseif ($count_izin_cuti > 2) {
        //     $potongan_cuti_izin = ($count_izin_cuti - 2) * 2;
        // } else {
        //     $potongan_cuti_izin = 0;
        // }

        // if ($count_izin_cuti > 2) {
        //     $potongan_cuti_izin = ($count_izin_cuti - 2) * 1;
        // } else {
        //     $potongan_cuti_izin = 0;
        // }

        // $potongan_sakit = 0;
        // if ($count_sakit > 7) {
        //     $potongan_sakit = ($count_sakit - 7) * 3;
        // }elseif ($count_sakit > 3) {
        //     $potongan_sakit = ($count_sakit - 3) * 1;
        // } else {
        //     $potongan_sakit = 0;
        // }

        $potongan_masuk_kerja = ($kmk_30 * 0.5) + ($kmk_60 * 1) + ($kmk_90 * 1.25) + ($kmk_90_keatas * 1.5);
        $potongan_pulang_kerja = ($cpk_30 * 0.5) + ($cpk_60 * 1) + ($cpk_90 * 1.25) + ($cpk_90_keatas * 1.5);
        $potongan_tanpa_keterangan = $jml_alfa * 3;
        $potongan_apel = ($jml_tidak_apel * 2) + ($jml_tidak_apel_hari_senin * 0.25);
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
            'jml_apel' => $count_apel,
            'jml_hadir' => $count_hadir,
            'jml_sakit' => $count_sakit,
            'jml_cuti' => $count_cuti,
            'jml_izin_cuti' => $count_izin_cuti,
            'jml_dinas_luar' => $count_dinas_luar,
            'kmk_30' => $kmk_30,
            'kmk_60' => $kmk_60,
            'kmk_90' => $kmk_90,
            'kmk_90_keatas' => $kmk_90_keatas,
            'cpk_30' => $cpk_30,
            'cpk_60' => $cpk_60,
            'cpk_90' => $cpk_90,
            'cpk_90_keatas' => $cpk_90_keatas,
            'jml_tidak_apel' => $jml_tidak_apel,
            'jml_tidak_apel_hari_senin' => $jml_tidak_apel_hari_senin,
            'jml_tidak_hadir_berturut_turut' => $jml_tidak_hadir_berturut_turut,
            'jml_menit_terlambat_masuk_kerja' => $jml_menit_terlambat_masuk_kerja,
            'jml_menit_terlambat_pulang_kerja' => $jml_menit_terlambat_pulang_kerja
        ];
    }

    public function persentase_skp($jabatan)
    {

        $data = DB::table('tb_skp')
            ->join('tb_aspek_skp', 'tb_aspek_skp.id_skp', '=', 'tb_skp.id')
            ->where('tb_skp.id_jabatan', $jabatan)
            ->selectRaw('COALESCE(SUM(tb_aspek_skp.target), 0) as target_sasaran')
            ->selectRaw('COALESCE(SUM(tb_aspek_skp.realisasi), 0) as target_pencapaian')
            ->first();

        $sasaran = 0;
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

        return [
            'sasaran' => $sasaran,
            'realisasi' => intval($realisasi),
            'kinerja' => $sasaran > 0 ? round(($realisasi / $sasaran) * 100, 2) : 0,
        ];
    }

    public function persentase_kinerja($bulan, $pegawai)
    {
        $tahun = session("tahun_penganggaran");
        $persentase = 0;
        $jabatan = DB::table('tb_jabatan')->join("tb_master_jabatan", 'tb_jabatan.id_master_jabatan', '=', 'tb_master_jabatan.id')->select("tb_jabatan.target_waktu")->where('id_pegawai', $pegawai)->first();

        $aktivitas = DB::table('tb_aktivitas')
            ->select(
                DB::raw('SUM(waktu) as capaian'),
                DB::raw('COUNT(*) as total_aktivitas')
            )
            ->where('id_pegawai', $pegawai)
            ->whereMonth('tanggal', $bulan)
            ->where('validation', 1)
            ->where('tahun', $tahun)
            ->first();

        if ($jabatan->target_waktu > 0) {
            $persentase = ($aktivitas->capaian / $jabatan->target_waktu) * 100;
        }

        $data = [
            'target' => $jabatan->target_waktu,
            'capaian' => $aktivitas->capaian !== null ? $aktivitas->capaian : 0,
            'prestasi' => round($persentase, 2),
            'total_aktivitas' => $aktivitas->total_aktivitas
        ];

        return $data;
    }

    public function CheckOpd($unit_kerja)
    {
        $data = DB::table('tb_unit_kerja')
            ->select('tb_unit_kerja.nama_unit_kerja', 'tb_satuan_kerja.nama_satuan_kerja')
            ->join('tb_satuan_kerja', 'tb_unit_kerja.id_satuan_kerja', '=', 'tb_satuan_kerja.id')
            ->where('tb_unit_kerja.id', $unit_kerja)
            ->first();

        if (!$data) {
            return false; // Jika tidak ada data, langsung return false
        }

        $unitKerjaContains = stripos($data->nama_unit_kerja, 'dinas pendidikan') !== false;
        $satuanKerjaContains = stripos($data->nama_satuan_kerja, 'dinas pendidikan') !== false;

        // Jika nama_unit_kerja tidak ada "dinas pendidikan" dan nama_satuan_kerja ada "dinas pendidikan", maka true
        if (!$unitKerjaContains && $satuanKerjaContains) {
            return true;
        }

        // Jika nama_unit_kerja ada "dinas pendidikan" dan nama_satuan_kerja ada "dinas pendidikan", maka false
        // Jika nama_unit_kerja tidak ada "dinas pendidikan" dan nama_satuan_kerja tidak ada "dinas pendidikan", maka false
        return false;
    }

}