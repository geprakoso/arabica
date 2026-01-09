<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Breadcrumb;
use Filament\Support\Enums\BreadcrumbItem;

class CreatePenjadwalanService extends CreateRecord
{
    protected static string $resource = PenjadwalanServiceResource::class;
    protected static ?string $title = 'Tambah Penerimaan Service';

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        // Pindahkan tombol ke header agar footer lebih bersih.
        return [];
    }

    public function getBreadcrumb(): string
    {
        return static::$title ?? parent::getBreadcrumb();
    }

    protected array $pendingRelations = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $relations = ['crosschecks', 'listAplikasis', 'listGames', 'listOs'];
        
        foreach ($relations as $relation) {
            $ids = [];
            
            // Collect keys to remove to avoid modification during iteration issues
            $keysToRemove = [];

            foreach ($data as $key => $value) {
                // Check for Parent Checkbox
                if (preg_match('/^attr_' . $relation . '_(\d+)_parent$/', $key, $matches)) {
                    $parentId = $matches[1];
                    if ($value) { 
                        $ids[] = $parentId;
                    }
                    $keysToRemove[] = $key;
                }
                // Check for Children CheckboxList
                if (preg_match('/^attr_' . $relation . '_(\d+)_children$/', $key, $matches)) {
                    if (is_array($value)) {
                        $ids = array_merge($ids, $value);
                    }
                    $keysToRemove[] = $key;
                }
            }

            // Cleanup $data
            foreach ($keysToRemove as $key) {
                unset($data[$key]);
            }

            $this->pendingRelations[$relation] = array_unique($ids);
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->pendingRelations as $relation => $ids) {
            if (method_exists($this->record, $relation)) {
                $this->record->{$relation}()->sync($ids);
            }
        }
    }
}
