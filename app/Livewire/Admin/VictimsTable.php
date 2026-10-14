<?php

namespace App\Livewire\Admin;

use App\Filament\Admin\Resources\VictimResource;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class VictimsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return VictimResource::table($table);
    }

    public function render(): View
    {
        return view('livewire.admin.victims-table');
    }
}
