<?php

namespace App\Policies;

use App\Models\Flow;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Flow Policy
 *
 * Controla los permisos de acceso al módulo Flow Builder (Diseño).
 * Solo PM/Administradores pueden modificar la estructura de flujos.
 */
class FlowPolicy
{
    use HandlesAuthorization;

    /**
     * Determinar si el usuario puede ver cualquier flujo
     */
    public function viewAny(User $user): bool
    {
        // Todos pueden ver la lista de flujos
        return true;
    }

    /**
     * Determinar si el usuario puede ver un flujo específico
     */
    public function view(User $user, Flow $flow): bool
    {
        // Todos pueden ver un flujo
        // (pero no necesariamente editarlo)
        return true;
    }

    /**
     * Determinar si el usuario puede crear flujos
     *
     * FLOW BUILDER: Solo PM/Admin
     */
    public function create(User $user): bool
    {
        return $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede actualizar un flujo
     *
     * FLOW BUILDER: Solo PM/Admin
     */
    public function update(User $user, Flow $flow): bool
    {
        return $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede eliminar un flujo
     *
     * FLOW BUILDER: Solo PM/Admin
     */
    public function delete(User $user, Flow $flow): bool
    {
        return $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede restaurar un flujo eliminado
     */
    public function restore(User $user, Flow $flow): bool
    {
        return $this->isFlowBuilder($user);
    }

    /**
     * Determinar si el usuario puede eliminar permanentemente
     */
    public function forceDelete(User $user, Flow $flow): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Verificar si el usuario tiene rol de Flow Builder
     *
     * @param User $user
     * @return bool
     */
    private function isFlowBuilder(User $user): bool
    {
        return in_array($user->role, ['admin', 'project_manager', 'pm']);
    }
}
