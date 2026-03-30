<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
  protected $table = 'offices';

  protected $fillable = [
    'office_name',
  ];

  // ── Relationships ─────────────────────────────────────────────────────────

  public function employees(): HasMany
  {
    return $this->hasMany(Employee::class, 'office_id');
  }

  public function users()
  {
    return $this->hasMany(User::class);
  }
}
