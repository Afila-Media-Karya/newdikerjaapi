<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid as Generator;
use Auth;
class SasaranKinerja extends Model
{
    use HasFactory;
    protected $table = 'tb_skp';
    protected $fillable = ['id', 'uuid', 'jenis', 'rencana', 'id_jabatan', 'id_satuan_kerja', 'id_skp_atasan', 'validation', 'keterangan', 'id_reviewer', 'kesesuaian'];

    public function AspekSkp()
    {
        return $this->hasMany(AspekSkp::class, 'id_skp', 'id');
    }

    public function aktivitas()
    {
        return $this->hasMany('App\Models\Aktivitas', 'id_sasaran', 'id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            try {
                $model->uuid = Generator::uuid4()->toString();
                $model->tahun = date('Y');
                $model->user_insert = Auth::user()->id;
                $model->user_update = Auth::user()->id;
            } catch (UnsatisfiedDependencyException $e) {
                abort(500, $e->getMessage());
            }
        });
    }
}
