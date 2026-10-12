<?php

namespace App\Filament\Admin\Resources\VictimResource\Pages;

use App\Filament\Admin\Resources\VictimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVictim extends EditRecord
{
    protected static string $resource = VictimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
