<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function audit(string $action, $model, ?array $old = null, ?array $new = null): void
    {
        \App\Models\AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'auditable_type' => get_class($model), 'auditable_id' => $model->getKey(), 'old_values' => $old, 'new_values' => $new]);
    }
}
