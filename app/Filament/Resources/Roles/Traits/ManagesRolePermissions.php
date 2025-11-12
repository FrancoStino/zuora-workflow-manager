<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Traits;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait ManagesRolePermissions
{
    public Collection $permissions;

    /**
     * Extract permissions from form data, filtering out metadata keys.
     */
    protected function extractPermissions(array $data): void
    {
        $excludedKeys = ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()];

        $this->permissions = collect($data)
            ->filter(fn (mixed $value, string $key): bool => ! in_array($key, $excludedKeys, true))
            ->values()
            ->flatten()
            ->unique();
    }

    /**
     * Filter form data to retain only role configuration fields.
     */
    protected function filterFormData(array $data): array
    {
        $fields = ['name', 'guard_name'];

        if ($this->shouldIncludeTenantKey($data)) {
            $fields[] = Utils::getTenantModelForeignKey();
        }

        return Arr::only($data, $fields);
    }

    /**
     * Determine if tenant model foreign key should be included.
     */
    private function shouldIncludeTenantKey(array $data): bool
    {
        $tenantKey = Utils::getTenantModelForeignKey();

        return Utils::isTenancyEnabled()
            && Arr::has($data, $tenantKey)
            && filled($data[$tenantKey]);
    }

    /**
     * Sync extracted permissions to the role record.
     */
    protected function syncRolePermissions(): void
    {
        $permissionModels = $this->permissions
            ->map(fn (string $permission) => Utils::getPermissionModel()::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->data['guard_name'],
            ]))
            ->all();

        // @phpstan-ignore-next-line
        $this->record->syncPermissions($permissionModels);
    }
}
