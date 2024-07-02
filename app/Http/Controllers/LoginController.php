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

class LoginController extends BaseController
{
    use Kehadiran;
    public function signIn(LoginRequest $request){
        // return $request;
        $path = explode('/', request()->path());

        if (!Auth::attempt($request->only('username', 'password'))) {
            return $this->sendError('Username atau password salah', 'Unauthorized',401);
        }

        if ($path[1] !== 'v1' && $path[1] !== 'v2') {
            return $this->sendError('Silahkan Update Aplikasi DANGKE', 'Unauthorized',401);
        }

        if ($request->version !== '3.4.1') {
            return $this->sendError('Mohon Update Aplikasi versi terbaru', 'Unauthorized',401);
        }
        
        $user = User::where('username', $request->username)
        ->select('users.id','users.uuid','users.username','users.role','tb_pegawai.nama as nama_pegawai','tb_satuan_kerja.nama_satuan_kerja','tb_jabatan.status as status_jabatan')
        ->join('tb_pegawai','users.id_pegawai','=','tb_pegawai.id')
        ->join('tb_satuan_kerja','tb_pegawai.id_satuan_kerja','=','tb_satuan_kerja.id')
        ->join('tb_jabatan','tb_jabatan.id_pegawai','=','tb_pegawai.id')->first();

        if (!$user) {
            return $this->sendError('Jabatan tidak di temukan, Mohon hubungi admin opd', 'Unauthorized',401);
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

        return $this->sendResponse($response,'Authencation successfull');
    }

    public function current_user(){
        $data = array();
        try {
            $data = User::where('users.id', Auth::user()->id)
            ->select('users.id','users.uuid','users.username','tb_pegawai.uuid as pegawai_uuid','users.id_pegawai','tb_pegawai.nip','tb_pegawai.nama as nama_pegawai','tb_pegawai.face_character','tb_satuan_kerja.nama_satuan_kerja','tb_jabatan.status as status_jabatan','tb_master_jabatan.nama_jabatan','tb_pegawai.jenis_kelamin','tb_pegawai.agama','tb_pegawai.status_perkawinan','tb_pegawai.golongan','tb_pegawai.tmt_golongan','tb_pegawai.pendidikan','tb_pegawai.tahun','tb_pegawai.tmt_golongan','tb_pegawai.foto','tb_lokasi.latitude as lat','tb_lokasi.longitude as long','tb_lokasi_apel.latitude as apel_lat','tb_lokasi_apel.longitude as apel_long','tb_pegawai.status_rekam','tb_pegawai.status_kepegawaian','tb_pegawai.tipe_pegawai','tb_unit_kerja.waktu_masuk','tb_unit_kerja.waktu_keluar','tb_unit_kerja.waktu_apel','tb_lokasi.radius','tb_pegawai.tempat_lahir','tb_pegawai.tanggal_lahir','tb_pegawai.tanggal_lahir','tb_pegawai.alamat','tb_pegawai.email','tb_pegawai.tmt_pegawai','tb_pegawai.nama_pendidikan','tb_pegawai.no_hp','tb_master_jabatan.kelas_jabatan','tb_unit_kerja.nama_unit_kerja')
            ->join('tb_pegawai','users.id_pegawai','=','tb_pegawai.id')
            ->join('tb_jabatan','tb_jabatan.id_pegawai','=','tb_pegawai.id')
            ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
            ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
            ->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
            ->join('tb_lokasi','tb_jabatan.id_lokasi_kerja','tb_lokasi.id')
            ->join('tb_lokasi as tb_lokasi_apel', 'tb_jabatan.id_lokasi_apel', '=', 'tb_lokasi_apel.id')
            ->orderBy(DB::raw("FIELD(status_jabatan, 'pj', 'definitif', 'plt')"))
            ->first();

            $data->limitPenginputan = 5;
            $tanggal_hari_ini = date('Y-m-d');

            if ($this->isRhamadan($tanggal_hari_ini)) {
                if ($data->tipe_pegawai == 'pegawai_administratif') {
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = date('N') == 5 ? '15:30:00' : '15:00:00';
                    $data->waktu_apel = Carbon::parse($tanggal_hari_ini)->dayOfWeek === Carbon::MONDAY ? '07:15:00' : '07:40:00';
                }else{
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = '13:00:00';
                    $data->waktu_apel = '08:00:00';
                }                
            }
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Check absen Success');
    }

    public function current_user2(){
        $data = array();
        try {

            $data = User::where('users.id', Auth::user()->id)
            ->select('users.id','users.uuid','users.username','tb_pegawai.uuid as pegawai_uuid','users.id_pegawai','tb_pegawai.nip','tb_pegawai.nama as nama_pegawai','tb_pegawai.face_character','tb_satuan_kerja.nama_satuan_kerja','tb_jabatan.status as status_jabatan','tb_master_jabatan.nama_jabatan','tb_pegawai.jenis_kelamin','tb_pegawai.agama','tb_pegawai.status_perkawinan','tb_pegawai.golongan','tb_pegawai.tmt_golongan','tb_pegawai.pendidikan','tb_pegawai.tahun','tb_pegawai.tmt_golongan','tb_pegawai.foto','tb_lokasi.latitude as lat','tb_lokasi.longitude as long','tb_lokasi_apel.latitude as apel_lat','tb_lokasi_apel.longitude as apel_long','tb_pegawai.status_rekam','tb_pegawai.status_kepegawaian','tb_pegawai.tipe_pegawai','tb_unit_kerja.waktu_masuk','tb_unit_kerja.waktu_keluar','tb_unit_kerja.waktu_apel','tb_lokasi.radius','tb_pegawai.tempat_lahir','tb_pegawai.tanggal_lahir','tb_pegawai.tanggal_lahir','tb_pegawai.alamat','tb_pegawai.email','tb_pegawai.tmt_pegawai','tb_pegawai.nama_pendidikan','tb_pegawai.no_hp','tb_master_jabatan.kelas_jabatan','tb_unit_kerja.nama_unit_kerja')
            ->join('tb_pegawai','users.id_pegawai','=','tb_pegawai.id')
            ->join('tb_jabatan','tb_jabatan.id_pegawai','=','tb_pegawai.id')
            ->join('tb_satuan_kerja','tb_jabatan.id_satuan_kerja','=','tb_satuan_kerja.id')
            ->join('tb_unit_kerja','tb_jabatan.id_unit_kerja','=','tb_unit_kerja.id')
            ->join('tb_master_jabatan','tb_jabatan.id_master_jabatan','=','tb_master_jabatan.id')
            ->join('tb_lokasi','tb_jabatan.id_lokasi_kerja','tb_lokasi.id')
            ->join('tb_lokasi as tb_lokasi_apel', 'tb_jabatan.id_lokasi_apel', '=', 'tb_lokasi_apel.id')
            ->orderBy(DB::raw("FIELD(status_jabatan, 'pj', 'definitif', 'plt')"))
            ->first();

            $data->limitPenginputan = 5;
            $tanggal_hari_ini = date('Y-m-d');

            if (Carbon::parse($tanggal_hari_ini)->dayOfWeek !== Carbon::MONDAY) {
                $data->waktu_apel = '07:40:00';
            }

            $data->waktu_istirahat = '12:00:00';
            $data->waktu_masuk_istirahat = '13:00:00';

            if ($this->isRhamadan($tanggal_hari_ini)) {
                if ($data->tipe_pegawai == 'pegawai_administratif') {
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = date('N') == 5 ? '15:30:00' : '15:00:00';
                    $data->waktu_apel = Carbon::parse($tanggal_hari_ini)->dayOfWeek === Carbon::MONDAY ? '07:15:00' : '07:40:00';
                }else{
                    $data->waktu_masuk = '08:00:00';
                    $data->waktu_keluar = '13:00:00';
                    $data->waktu_apel = '08:00:00';
                }                
            }
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), 200);
        }
        return $this->sendResponse($data, 'Check absen Success');
    }

    public function revoke (Request $request) {
        try {
            $user = Auth::user();
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
                return $this->sendResponse('','You have been successfully logged out!');
            }
        } catch (\Exception $e) {
            return $this->sendError('No token to revoke', $e->getMessage(), 200);
        }
    }
}
