<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'action', 'auditable_type', 'auditable_id', 'old_values', 'new_values'];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array']; }
    public function user() { return $this->belongsTo(User::class); }
    public function auditable() { return $this->morphTo(); }

    public function actionLabel(): string
    {
        return [
            'product.created' => 'Producto creado', 'product.updated' => 'Producto actualizado', 'product.deleted' => 'Producto desactivado',
            'category.created' => 'Categoría creada', 'category.updated' => 'Categoría actualizada', 'category.deleted' => 'Categoría desactivada',
            'branch.created' => 'Sede creada', 'branch.updated' => 'Sede actualizada', 'branch.deleted' => 'Sede desactivada',
            'user.created' => 'Usuario creado', 'user.updated' => 'Usuario actualizado', 'user.deleted' => 'Usuario eliminado', 'user.status.updated' => 'Estado de usuario actualizado',
            'inventory.movement.created' => 'Movimiento de inventario', 'stock.review.completed' => 'Revisión de stock completada',
            'transfer.created' => 'Transferencia creada', 'transfer.completed' => 'Transferencia completada', 'transfer.cancelled' => 'Transferencia cancelada',
            'license.created' => 'Licencia creada',
        ][$this->action] ?? str($this->action)->replace('.', ' ')->headline()->toString();
    }

    public function subjectLabel(): string
    {
        $values = $this->new_values ?: $this->old_values ?: [];
        return (string) ($values['name'] ?? $values['code'] ?? $values['email'] ?? class_basename($this->auditable_type).' #'.$this->auditable_id);
    }

    public function readableChanges(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $ignored = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token', 'image_path'];
        $labels = [
            'name' => 'Nombre', 'email' => 'Correo', 'role' => 'Rol', 'is_active' => 'Estado', 'internal_code' => 'Código interno',
            'barcode' => 'Código de barras', 'sale_price' => 'Precio', 'purchase_price' => 'Costo', 'minimum_stock' => 'Stock mínimo',
            'quantity' => 'Cantidad', 'stock_before' => 'Stock anterior', 'stock_after' => 'Stock resultante', 'type' => 'Tipo',
            'reason' => 'Motivo', 'branch_id' => 'Sede', 'category_id' => 'Categoría', 'report_group' => 'Lista especial',
            'status' => 'Estado', 'permissions' => 'Permisos', 'notes' => 'Notas', 'movement_date' => 'Fecha del movimiento',
        ];

        return collect(array_unique(array_merge(array_keys($old), array_keys($new))))
            ->reject(fn ($field) => in_array($field, $ignored, true))
            ->filter(fn ($field) => ! array_key_exists($field, $old) || ! array_key_exists($field, $new) || $old[$field] != $new[$field])
            ->map(fn ($field) => [
                'label' => $labels[$field] ?? str($field)->replace('_', ' ')->headline()->toString(),
                'old' => $this->displayValue($old[$field] ?? null, $field),
                'new' => $this->displayValue($new[$field] ?? null, $field),
            ])->values()->all();
    }

    private function displayValue(mixed $value, string $field): string
    {
        if ($field === 'is_active') return $value ? 'Activo' : 'Inactivo';
        if ($field === 'report_group') return ['chemicals' => 'Químicos', 'quick_purchases' => 'Compras rápidas'][$value] ?? 'Sin lista';
        if (is_array($value)) return collect($value)->flatten()->implode(', ');
        if ($value === null || $value === '') return 'Sin dato';
        return (string) $value;
    }
}
