<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'category_id', 'internal_code', 'barcode', 'name', 'description', 'brand', 'purchase_price', 'sale_price', 'minimum_stock', 'report_group', 'image_path', 'is_active'];
    protected function casts(): array { return ['purchase_price' => 'decimal:2', 'sale_price' => 'decimal:2', 'minimum_stock' => 'integer', 'is_active' => 'boolean']; }
    public function category() { return $this->belongsTo(Category::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function inventories() { return $this->hasMany(Inventory::class); }
    public function movements() { return $this->hasMany(InventoryMovement::class); }
}
