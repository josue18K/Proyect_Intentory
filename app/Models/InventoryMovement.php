<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'branch_id', 'user_id', 'type', 'quantity', 'stock_before', 'stock_after', 'movement_date', 'reason', 'notes'];
    protected function casts(): array { return ['quantity' => 'integer', 'stock_before' => 'integer', 'stock_after' => 'integer', 'movement_date' => 'datetime']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function user() { return $this->belongsTo(User::class); }
}
