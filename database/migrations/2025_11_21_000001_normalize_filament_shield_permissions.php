<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        $permissionPivotKey = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamColumn = $columnNames['team_foreign_key'] ?? 'team_id';
        $teams = (bool) config('permission.teams', false);

        $prefixes = (array) config('filament-shield.permission_prefixes.resource', []);
        $prefixes = array_values(array_filter($prefixes, fn ($prefix) => is_string($prefix) && $prefix !== ''));

        if ($prefixes === []) {
            return;
        }

        DB::transaction(function () use (
            $permissionsTable,
            $roleHasPermissionsTable,
            $modelHasPermissionsTable,
            $permissionPivotKey,
            $rolePivotKey,
            $modelMorphKey,
            $teamColumn,
            $teams,
            $prefixes
        ): void {
            $permissions = DB::table($permissionsTable)->select('id', 'name', 'guard_name')->get();

            foreach ($permissions as $permission) {
                $prefix = null;

                foreach ($prefixes as $candidate) {
                    if (Str::startsWith($permission->name, $candidate . '_')) {
                        $prefix = $candidate;
                        break;
                    }
                }

                if (! $prefix) {
                    continue;
                }

                $identifier = substr($permission->name, strlen($prefix) + 1);

                if (! str_contains($identifier, '::')) {
                    continue;
                }

                $segments = explode('::', $identifier);
                $normalizedSegments = [];

                foreach ($segments as $segment) {
                    if ($segment === '' || end($normalizedSegments) === $segment) {
                        continue;
                    }

                    $normalizedSegments[] = $segment;
                }

                $normalizedIdentifier = implode('::', $normalizedSegments);

                if ($normalizedIdentifier === $identifier) {
                    continue;
                }

                $newName = $prefix . '_' . $normalizedIdentifier;

                $existing = DB::table($permissionsTable)
                    ->where('name', $newName)
                    ->where('guard_name', $permission->guard_name)
                    ->first();

                if ($existing) {
                    $this->repointRolePermissions(
                        $roleHasPermissionsTable,
                        $permissionPivotKey,
                        $rolePivotKey,
                        (int) $permission->id,
                        (int) $existing->id
                    );

                    $this->repointModelPermissions(
                        $modelHasPermissionsTable,
                        $permissionPivotKey,
                        $modelMorphKey,
                        $teamColumn,
                        $teams,
                        (int) $permission->id,
                        (int) $existing->id
                    );

                    DB::table($permissionsTable)
                        ->where('id', $permission->id)
                        ->delete();

                    continue;
                }

                DB::table($permissionsTable)
                    ->where('id', $permission->id)
                    ->update(['name' => $newName]);
            }
        });

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        //
    }

    private function repointRolePermissions(
        string $table,
        string $permissionPivotKey,
        string $rolePivotKey,
        int $oldPermissionId,
        int $newPermissionId
    ): void {
        $roleIds = DB::table($table)
            ->where($permissionPivotKey, $oldPermissionId)
            ->pluck($rolePivotKey);

        foreach ($roleIds as $roleId) {
            $exists = DB::table($table)
                ->where($permissionPivotKey, $newPermissionId)
                ->where($rolePivotKey, $roleId)
                ->exists();

            if ($exists) {
                DB::table($table)
                    ->where($permissionPivotKey, $oldPermissionId)
                    ->where($rolePivotKey, $roleId)
                    ->delete();
            } else {
                DB::table($table)
                    ->where($permissionPivotKey, $oldPermissionId)
                    ->where($rolePivotKey, $roleId)
                    ->update([$permissionPivotKey => $newPermissionId]);
            }
        }
    }

    private function repointModelPermissions(
        string $table,
        string $permissionPivotKey,
        string $modelMorphKey,
        string $teamColumn,
        bool $teams,
        int $oldPermissionId,
        int $newPermissionId
    ): void {
        $columns = [$modelMorphKey, 'model_type'];

        if ($teams) {
            $columns[] = $teamColumn;
        }

        $rows = DB::table($table)
            ->where($permissionPivotKey, $oldPermissionId)
            ->get($columns);

        foreach ($rows as $row) {
            $existsQuery = DB::table($table)
                ->where($permissionPivotKey, $newPermissionId)
                ->where($modelMorphKey, $row->{$modelMorphKey})
                ->where('model_type', $row->model_type);

            if ($teams) {
                $existsQuery->where($teamColumn, $row->{$teamColumn});
            }

            $targetQuery = DB::table($table)
                ->where($permissionPivotKey, $oldPermissionId)
                ->where($modelMorphKey, $row->{$modelMorphKey})
                ->where('model_type', $row->model_type);

            if ($teams) {
                $targetQuery->where($teamColumn, $row->{$teamColumn});
            }

            if ($existsQuery->exists()) {
                $targetQuery->delete();
            } else {
                $targetQuery->update([$permissionPivotKey => $newPermissionId]);
            }
        }
    }
};
