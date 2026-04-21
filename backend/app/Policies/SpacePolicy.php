<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;

// Las policies son clases que definen la autorización
// por recurso — equivale a @PreAuthorize en Spring Security
// pero más expresivo
class SpacePolicy
{
    // Antes de cualquier método de la policy se llama before()
    // Si devuelve true, se salta el resto de la policy
    public function before(User $user): ?bool
    {
        // Los admins pueden hacer todo
        if ($user->isAdmin()) {
            return true;
        }
        // null → continúa evaluando los demás métodos
        return null;
    }

    // ¿Puede este usuario actualizar este espacio?
    public function update(User $user, Space $space): bool
    {
        // Solo el host que creó el espacio puede editarlo
        return $space->belongsToHost($user->id);
    }

    public function delete(User $user, Space $space): bool
    {
        return $space->belongsToHost($user->id);
    }
}
