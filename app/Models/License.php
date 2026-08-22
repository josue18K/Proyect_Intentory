<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'status', 'created_by', 'branch_id', 'activated_by', 'activated_at', 'expires_at'];
    protected function casts(): array { return ['activated_at' => 'datetime', 'expires_at' => 'datetime']; }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function activator() { return $this->belongsTo(User::class, 'activated_by'); }
    public function isAvailable(): bool { return $this->status === 'available' && (! $this->expires_at || $this->expires_at->isFuture()); }
}
