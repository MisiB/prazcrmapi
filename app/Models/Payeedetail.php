<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payeedetail extends Model
{
   protected $fillable = [
       'name',
       'surname',
       'email',
       'phone'
   ];
    public function attempts()
    {
        return $this->hasMany(Payeeattempt::class, 'payeedetail_id');
    }
}
