<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenjadwalanService extends EditRecord
{
    protected static string $resource = PenjadwalanServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('print')
                ->label('Cetak Invoice')
                ->icon('heroicon-m-printer')
                ->color('success')
                ->url(fn ($record) => route('penjadwalan-service.print', $record))
                ->openUrlInNewTab(),
            Actions\Action::make('print_crosscheck')
                ->label('Cetak Checklist')
                ->icon('heroicon-m-clipboard-document-check')
                ->color('info')
                ->visible(fn ($record) => $record->has_crosscheck)
                ->url(fn ($record) => route('penjadwalan-service.print-crosscheck', $record))
                ->openUrlInNewTab(),
        ];
    }
    protected array $pendingRelations = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $relations = ['crosschecks', 'listAplikasis', 'listGames', 'listOs'];
        
        foreach ($relations as $relation) {
            $ids = [];
            $keysToRemove = [];

            foreach ($data as $key => $value) {
                if (preg_match('/^attr_' . $relation . '_(\d+)_parent$/', $key, $matches)) {
                    $parentId = $matches[1];
                    if ($value) $ids[] = $parentId;
                    $keysToRemove[] = $key;
                }
                if (preg_match('/^attr_' . $relation . '_(\d+)_children$/', $key, $matches)) {
                    if (is_array($value)) $ids = array_merge($ids, $value);
                    $keysToRemove[] = $key;
                }
            }

            foreach ($keysToRemove as $key) unset($data[$key]);
            $this->pendingRelations[$relation] = array_unique($ids);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        foreach ($this->pendingRelations as $relation => $ids) {
            if (method_exists($this->record, $relation)) {
                $this->record->{$relation}()->sync($ids);
            }
        }
    }
}
