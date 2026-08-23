<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReview extends Model
{
    protected $fillable = ['branch_id', 'user_id', 'low_stock_count', 'empty_stock_count', 'notes'];
    public function branch() { return $this->belongsTo(Branch::class); }
    public function user() { return $this->belongsTo(User::class); }
}
