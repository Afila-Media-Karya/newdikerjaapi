<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\User;
use Auth;
use DB;

class GpsController extends BaseController
{
    public function detectFakeGps(
        array $longitudesArray, 
        array $latitudesArray, 
        float $patokanLongitude, 
        float $patokanLatitude, 
        float $threshold = 0.09 // Pastikan default yang masuk akal
    ) {
        // Pastikan kedua array memiliki jumlah elemen yang sama (Logika bagus, biarkan)
        if (count($longitudesArray) !== count($latitudesArray)) {
            return true; 
        }
        
        // Iterasi setiap pasang koordinat
        for ($i = 0; $i < count($longitudesArray); $i++) {
            $longitude = (float) $longitudesArray[$i]; // Pastikan konversi ke float
            $latitude = (float) $latitudesArray[$i];   // Pastikan konversi ke float

            // Hitung selisih absolut untuk longitude dan latitude
            $diffLongitude = abs($longitude - $patokanLongitude);
            $diffLatitude = abs($latitude - $patokanLatitude);

            // Jika salah satu selisih melebihi ambang batas, terindikasi palsu (atau di luar area kerja)
            if ($diffLongitude > $threshold || $diffLatitude > $threshold) {
                return true; // Dinyatakan palsu/tidak valid
            }
        }

        // Jika semua titik dalam request berada dalam radius threshold 
        // dari titik patokan, maka tidak terindikasi palsu/valid.
        return false;
    }

    public function updateAbsen($indikasi_fake_gps){
        $data = DB::table('tb_absen')
                ->where('id_pegawai', Auth::user()->id_pegawai)
                ->where('tanggal_absen', date('Y-m-d'))
                ->first();
         
        if ($data) {
            DB::table('tb_absen')
                ->where('id_pegawai', Auth::user()->id_pegawai)
                ->where('tanggal_absen', date('Y-m-d'))
                ->update(['indikasi_fake' => $indikasi_fake_gps]);
        }
        return $data;

    }

    public function checkGps(Request $request)
    {
        // Pastikan Anda mendapatkan data longitude dan latitude dari request
        $longitudes = $request->input('longitudes');
        $latitudes = $request->input('latitudes');
        $threshold = 0.09;
        // dd($longitudes, $latitudes); // Hapus dd() setelah pengujian

        $data = User::where('users.id', Auth::user()->id)
            ->select('tb_lokasi.latitude as lat','tb_lokasi.longitude as long')
            ->join('tb_pegawai','users.id_pegawai','=','tb_pegawai.id')
            ->join('tb_jabatan','tb_jabatan.id_pegawai','=','tb_pegawai.id')
            ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
            ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
            ->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
            ->join('tb_lokasi','tb_jabatan.id_lokasi_kerja','tb_lokasi.id')
            ->join('tb_lokasi as tb_lokasi_apel', 'tb_jabatan.id_lokasi_apel', '=', 'tb_lokasi_apel.id')
            ->first();
        
        // Ambil nilai patokan dari hasil query
        $patokanLongitude = (float) $data->long;
        $patokanLatitude = (float) $data->lat;
        
        // Tambahkan validasi untuk latitudes
        if (!is_array($longitudes) || !is_array($latitudes) || !is_numeric($patokanLongitude) || !is_numeric($patokanLatitude)) {
            return $this->sendError(false, 'Input tidak Valid', 200);
        }
        
        // Panggil fungsi detectFakeGps dengan argumen yang baru
        $isFake = $this->detectFakeGps($longitudes, $latitudes, $patokanLongitude, $patokanLatitude, $threshold);

        if ($isFake) {
            $this->updateAbsen(1);
            return $this->sendResponse([], 'Indikasi GPS palsu terdeteksi.');
        }
        $this->updateAbsen(0);
        return $this->sendResponse([], 'Tidak ada indikasi GPS palsu.');
    }
}
