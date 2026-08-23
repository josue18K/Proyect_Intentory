<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function inventories() { return $this->hasMany(Inventory::class); }
    public function movements() { return $this->hasMany(InventoryMovement::class); }
    public function stockReviews() { return $this->hasMany(StockReview::class); }
}
