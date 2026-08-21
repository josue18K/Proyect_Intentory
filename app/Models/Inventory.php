<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'branch_id', 'quantity', 'last_entry_at', 'exhausted_at'];
    protected function casts(): array { return ['quantity' => 'integer', 'last_entry_at' => 'datetime', 'exhausted_at' => 'datetime']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
}
