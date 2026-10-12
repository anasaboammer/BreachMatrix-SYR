<?php

namespace App\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Services\PayloadService;

class EncryptionApps extends Page
{
    protected static ?string $title = 'Encryption Apps';

    protected ?string $subheading = 'Configure a local profile for a personal encryption workspace.';

    protected static ?string $navigationLabel = 'Encryption Apps';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.encryption-apps';

    public ?array $profilePreview = null;

    public static function getRoutePath(): string
    {
        return '/';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newApp')
                ->label('New app')
                ->icon('heroicon-m-plus')
                ->color('warning')
                ->modalHeading('Create a local profile')
                ->modalDescription('Preview only. This form does not build an app, access files, or save data.')
                ->modalSubmitActionLabel('Preview profile')
                ->modalWidth('3xl')
                ->form([
                    Section::make('App identity')
                        ->description('Choose a name and a visual mark for your profile.')
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
                        ->description('Choose the target platform and directories to encrypt.')
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
                        ->description('Select a cryptographic profile for this local workspace.')
                        ->schema([
                            ToggleButtons::make('algorithm')
                                ->label('Encryption type')
                                ->options([
                                    'aes' => 'AES-256-GCM',
                                    'rsa' => 'RSA-3072',
                                    'hybrid' => 'AES + RSA',
                                ])
                                ->inline()
                                ->required()
                                ->default('hybrid'),
                        ]),
                ])
                ->action(function (array $data, PayloadService $payloadService): void {
                        $payloadService->createPayloadProfile($data);
                        Notification::make()
                        ->title('Payload profile created successfully')
                        ->success()
                        ->send();
    $this->profilePreview = $data;
})
        ];
    }
}