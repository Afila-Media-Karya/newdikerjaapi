<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid as Generator;
use Auth;

class Absen extends Model
{
    use HasFactory;
    protected $table = 'tb_absen';

    protected static function boot(){
        parent::boot();
        static::creating(function ($model) {
            try {
                $model->uuid = Generator::uuid4()->toString();
                $model->user_insert = Auth::user()->id;
                $model->user_update = Auth::user()->id;
            } catch (UnsatisfiedDependencyException $e) {
                abort(500, $e->getMessage());
            }
        });
    }
}
