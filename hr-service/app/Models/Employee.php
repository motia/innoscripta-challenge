<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'salary',
        'country',
        'ssn',
        'address',
        'goal',
        'tax_id',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }
}
