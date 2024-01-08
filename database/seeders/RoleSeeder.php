<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // public function run(): void
    // {
    //     // Definir nombres de roles descriptivos
    //     $adminRoleName = 'admin';
    //     $userRoleName = 'user';
    //     $clientRoleName = 'client';
    //     $ownerRoleName = 'owner';
    //     $collaboratorRoleName = 'collaborator';
    //     $expertRoleName = 'expert';

    //     // Verificar si los roles ya existen antes de crearlos
    //     $userRole = Role::where('name', $userRoleName)->first();
    //     if (!$userRole) {
    //         Role::create(['name' => $userRoleName]);
    //     }

    //     $adminRole = Role::where('name', $adminRoleName)->first();
    //     if (!$adminRole) {
    //         Role::create(['name' => $adminRoleName]);
    //     }

    //     $clientRole = Role::where('name', $clientRoleName)->first();
    //     if (!$clientRole) {
    //         Role::create(['name' => $clientRoleName]);
    //     }

    //     $ownerRole = Role::where('name', $ownerRoleName)->first();
    //     if (!$ownerRole) {
    //         $ownerRole = Role::create(['name' => $ownerRoleName]);
    //     }

    //     $collaboratorRole = Role::where('name', $collaboratorRoleName)->first();
    //     if (!$collaboratorRole) {
    //         $collaboratorRole = Role::create(['name' => $collaboratorRoleName]);
    //     }

    //     $collaboratorInmobiliariaPermissionName = 'collaborator_inmobiliaria';
    //     $collaboratorDesarrolloPermissionName = 'collaborator_desarrollo';

    //     $collaboratorInmobiliariaPermission = Permission::where('name', $collaboratorInmobiliariaPermissionName)->first();

    //     if (!$collaboratorInmobiliariaPermission) {
    //         $collaboratorInmobiliariaPermission = Permission::create(['name' => $collaboratorInmobiliariaPermissionName]);
    //     }

    //     $collaboratorDesarrolloPermission = Permission::where('name', $collaboratorDesarrolloPermissionName)->first();
    //     if (!$collaboratorDesarrolloPermission) {
    //         $collaboratorDesarrolloPermission = Permission::create(['name' => $collaboratorDesarrolloPermissionName]);
    //     }

    //     // User Role guard name client
    //     Role::create(['name' => $userRoleName,  'guard_name' => 'client-api']);
    //     Role::create(['name' => $userRoleName, 'guard_name' => 'client-api']);

    //     Role::create(['name' => $userRoleName,  'guard_name' => 'admin-api']);
    //     Role::create(['name' => $adminRoleName, 'guard_name' => 'admin-api']);

    //     // MasterDash API Roles
    //     Role::create(['name' => $expertRoleName]);

    // }


    public function run(): void
    {
        // Definir nombres de roles descriptivos
        $roleNames = ['admin', 'user', 'client', 'owner', 'collaborator', 'expert'];

        // Crear roles si no existen
        foreach ($roleNames as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
        }

        // Definir nombres de permisos descriptivos
        $permissionNames = ['collaborator_inmobiliaria', 'collaborator_desarrollo'];

        // Crear permisos si no existen
        foreach ($permissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Crear roles con guard_name si no existen
        $roleGuardNames = [
            ['name' => 'user', 'guard_name' => 'client-api'],
            ['name' => 'admin', 'guard_name' => 'client-api'],
            ['name' => 'user', 'guard_name' => 'admin-api'],
            ['name' => 'admin', 'guard_name' => 'admin-api'],
            ['name' => 'user', 'guard_name' => 'dash-api'],
            ['name' => 'admin', 'guard_name' => 'dash-api'],
        ];

        foreach ($roleGuardNames as $roleGuardName) {
            $role = Role::firstOrCreate(['name' => $roleGuardName['name'], 'guard_name' => $roleGuardName['guard_name']]);
        }
    }

}
