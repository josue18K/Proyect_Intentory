<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = ['product_id', 'from_branch_id', 'to_branch_id', 'user_id', 'quantity', 'status', 'notes', 'completed_at', 'completed_by'];
    protected function casts(): array { return ['quantity' => 'integer', 'completed_at' => 'datetime']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function fromBranch() { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch() { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function completer() { return $this->belongsTo(User::class, 'completed_by'); }
}
