<?php

namespace App\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AppVersions extends Page
{
    protected static ?string $title = 'App Versions';

    protected ?string $subheading = 'Release health and update adoption across your platforms.';

    protected static ?string $navigationLabel = 'App Versions';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.app-versions';

    public string $platformFilter = 'all';

    public string $statusFilter = 'all';

    public static function getRoutePath(): string
    {
        return '/';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newAppVersion')
                ->label('New app version')
                ->icon('heroicon-m-plus')
                ->color('warning')
                ->modalHeading('New app version')
                ->modalDescription('Preview form only. Submitting will not save or publish a release.')
                ->modalSubmitActionLabel('Preview release')
                ->form([
                    TextInput::make('version')
                        ->placeholder('1.8.1')
                        ->required()
                        ->maxLength(20),
                    Select::make('platform')
                        ->options([
                            'windows' => 'Windows',
                            'linux' => 'Linux',
                            'android' => 'Android',
                        ])
                        ->required(),
                    Select::make('channel')
                        ->options([
                            'stable' => 'Stable',
                            'beta' => 'Beta',
                        ])
                        ->default('stable')
                        ->required(),
                    TextInput::make('minimum_version')
                        ->label('Minimum supported version')
                        ->placeholder('1.7.4'),
                    DatePicker::make('released_at')
                        ->label('Release date')
                        ->default(now())
                        ->required(),
                    Textarea::make('release_notes')
                        ->label('Release notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->action(function (): void {
                    Notification::make()
                        ->title('Preview only')
                        ->body('No release was saved or published.')
                        ->warning()
                        ->send();
                }),
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function getPlatformStats(): array
    {
        return [
            [
                'key' => 'windows',
                'name' => 'Windows',
                'icon' => 'heroicon-o-computer-desktop',
                'installs' => 518,
                'current' => 402,
                'optional' => 81,
                'required' => 35,
                'latest' => '1.8.0',
                'minimum' => '1.7.4',
                'released' => 'Oct 1, 2026',
            ],
            [
                'key' => 'linux',
                'name' => 'Linux',
                'icon' => 'heroicon-o-command-line',
                'installs' => 284,
                'current' => 214,
                'optional' => 56,
                'required' => 14,
                'latest' => '1.8.0',
                'minimum' => '1.7.4',
                'released' => 'Oct 6, 2026',
            ],
            [
                'key' => 'android',
                'name' => 'Android',
                'icon' => 'heroicon-o-device-phone-mobile',
                'installs' => 249,
                'current' => 150,
                'optional' => 92,
                'required' => 7,
                'latest' => '1.8.0',
                'minimum' => '1.7.4',
                'released' => 'Oct 9, 2026',
            ],
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function getReleases(): array
    {
        return [
            ['version' => '1.8.0', 'platform' => 'Windows', 'platform_key' => 'windows', 'track' => 'Latest', 'status' => 'latest', 'released' => 'Oct 1, 2026', 'installs' => 402, 'share' => 78],
            ['version' => '1.8.0', 'platform' => 'Linux', 'platform_key' => 'linux', 'track' => 'Latest', 'status' => 'latest', 'released' => 'Oct 6, 2026', 'installs' => 214, 'share' => 75],
            ['version' => '1.8.0', 'platform' => 'Android', 'platform_key' => 'android', 'track' => 'Latest', 'status' => 'latest', 'released' => 'Oct 9, 2026', 'installs' => 150, 'share' => 60],
            ['version' => '1.7.4', 'platform' => 'Windows', 'platform_key' => 'windows', 'track' => 'Minimum allowed', 'status' => 'optional', 'released' => 'Apr 9, 2026', 'installs' => 81, 'share' => 16],
            ['version' => '1.7.4', 'platform' => 'Linux', 'platform_key' => 'linux', 'track' => 'Minimum allowed', 'status' => 'optional', 'released' => 'Apr 9, 2026', 'installs' => 56, 'share' => 20],
            ['version' => '1.7.4', 'platform' => 'Android', 'platform_key' => 'android', 'track' => 'Minimum allowed', 'status' => 'optional', 'released' => 'Apr 9, 2026', 'installs' => 92, 'share' => 37],
            ['version' => '1.7.0', 'platform' => 'Windows', 'platform_key' => 'windows', 'track' => 'Below minimum', 'status' => 'required', 'released' => 'Jan 7, 2026', 'installs' => 35, 'share' => 7],
            ['version' => '1.7.0', 'platform' => 'Linux', 'platform_key' => 'linux', 'track' => 'Below minimum', 'status' => 'required', 'released' => 'Jan 7, 2026', 'installs' => 14, 'share' => 5],
            ['version' => '1.7.0', 'platform' => 'Android', 'platform_key' => 'android', 'track' => 'Below minimum', 'status' => 'required', 'released' => 'Jan 7, 2026', 'installs' => 7, 'share' => 3],
        ];
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function getFilteredReleases(): array
    {
        return array_values(array_filter(
            $this->getReleases(),
            fn (array $release): bool => ($this->platformFilter === 'all' || $release['platform_key'] === $this->platformFilter) &&
                ($this->statusFilter === 'all' || $release['status'] === $this->statusFilter),
        ));
    }
}
