<?php

namespace App\Filament\Admin\Resources\VictimResource\Pages;

use App\Filament\Admin\Resources\VictimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVictims extends ListRecords
{
    protected static string $resource = VictimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
