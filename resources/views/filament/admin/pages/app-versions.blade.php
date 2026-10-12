<x-filament-panels::page>
	<div class="versions-dashboard space-y-5">
		<section class="overview-grid">
			<div class="overview-panel adoption-panel">
				<div class="panel-kicker">DEPLOYMENT OVERVIEW</div>
				<div class="adoption-content">
					<div>
						<div class="adoption-value">73<span>%</span></div>
						<p class="panel-copy">of 1,051 enrolled installations are on the latest release</p>
					</div>
					<div class="adoption-breakdown">
						<div class="progress-track" aria-label="73% current, 22% update available, 5% below minimum">
							<span class="progress-current" style="width: 73%"></span>
							<span class="progress-optional" style="width: 22%"></span>
							<span class="progress-required" style="width: 5%"></span>
						</div>
						<div class="legend-row">
							<span><i class="legend-dot dot-current"></i>Up to date <b>766</b> 73%</span>
							<span><i class="legend-dot dot-optional"></i>Update available <b>229</b> 22%</span>
							<span><i class="legend-dot dot-required"></i>Below minimum <b>56</b> 5%</span>
						</div>
					</div>
				</div>
				<div class="sample-note">Sample metrics for interface preview</div>
			</div>

			<div class="overview-panel attention-panel">
				<div class="attention-icon">
					<x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5" />
				</div>
				<div>
					<div class="attention-number">285</div>
					<div class="attention-title">installations need an update</div>
					<p class="attention-copy">56 are below the minimum supported version.</p>
				</div>
				<button type="button" class="review-button" wire:click="$set('statusFilter', 'required')">
					<x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4" />
					Review required updates
				</button>
			</div>
		</section>

		<section class="platform-grid" aria-label="Platform release status">
			@foreach ($this->getPlatformStats() as $platform)
				@php
					$currentShare = round(($platform['current'] / $platform['installs']) * 100);
					$optionalShare = round(($platform['optional'] / $platform['installs']) * 100);
					$requiredShare = 100 - $currentShare - $optionalShare;
				@endphp
				<article class="platform-panel">
					<div class="platform-heading">
						<div class="platform-mark">
							<x-filament::icon :icon="$platform['icon']" class="h-5 w-5" />
						</div>
						<div class="min-w-0">
							<h2>{{ $platform['name'] }}</h2>
							<p>Client app · {{ number_format($platform['installs']) }} installs</p>
						</div>
						<span class="release-state">Stable</span>
					</div>

					<div class="version-pair">
						<div>
							<span>Latest</span>
							<strong>{{ $platform['latest'] }}</strong>
						</div>
						<div>
							<span>Minimum allowed</span>
							<strong>{{ $platform['minimum'] }}</strong>
						</div>
					</div>

					<div class="platform-progress">
						<div class="progress-track">
							<span class="progress-current" style="width: {{ $currentShare }}%"></span>
							<span class="progress-optional" style="width: {{ $optionalShare }}%"></span>
							<span class="progress-required" style="width: {{ $requiredShare }}%"></span>
						</div>
						<div class="platform-stats">
							<span><i class="legend-dot dot-current"></i>{{ $platform['current'] }} current</span>
							<span><i class="legend-dot dot-optional"></i>{{ $platform['optional'] }} optional</span>
							<span><i class="legend-dot dot-required"></i>{{ $platform['required'] }} required</span>
						</div>
					</div>

					<div class="platform-footer">
						<span>Latest release</span>
						<time>{{ $platform['released'] }}</time>
					</div>
				</article>
			@endforeach
		</section>

		<section class="history-panel">
			<div class="history-heading">
				<div>
					<h2>Release history</h2>
					<p>Version adoption by platform</p>
				</div>
				<div class="history-controls">
					<div class="platform-tabs" role="group" aria-label="Filter by platform">
						@foreach (['all' => 'All platforms', 'windows' => 'Windows', 'linux' => 'Linux', 'android' => 'Android'] as $key => $label)
							<button
								type="button"
								wire:click="$set('platformFilter', '{{ $key }}')"
								@class(['platform-tab', 'is-active' => $platformFilter === $key])
							>
								{{ $label }}
							</button>
						@endforeach
					</div>
					<select wire:model.live="statusFilter" class="status-select" aria-label="Filter by update status">
						<option value="all">All statuses</option>
						<option value="latest">Latest</option>
						<option value="optional">Update available</option>
						<option value="required">Required</option>
					</select>
				</div>
			</div>

			<div class="table-scroll">
				<table class="release-table">
					<thead>
						<tr>
							<th>Version</th>
							<th>Platform</th>
							<th>Track</th>
							<th>Released</th>
							<th>Installations</th>
							<th>Adoption</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						@forelse ($this->getFilteredReleases() as $release)
							<tr>
								<td><strong>{{ $release['version'] }}</strong></td>
								<td>{{ $release['platform'] }}</td>
								<td>
									<span @class([
										'track-pill',
										'track-latest' => $release['track'] === 'Latest',
										'track-minimum' => $release['track'] === 'Minimum allowed',
										'track-old' => $release['track'] === 'Below minimum',
									])>{{ $release['track'] }}</span>
								</td>
								<td>{{ $release['released'] }}</td>
								<td>{{ $release['installs'] }}</td>
								<td>
									<div class="adoption-cell">
										<span class="mini-track"><i style="width: {{ $release['share'] }}%"></i></span>
										<span>{{ $release['share'] }}%</span>
									</div>
								</td>
								<td>
									<span @class([
										'status-label',
										'status-current' => $release['status'] === 'latest',
										'status-optional' => $release['status'] === 'optional',
										'status-required' => $release['status'] === 'required',
									])>
										<i></i>
										{{ match ($release['status']) {
											'latest' => 'Current',
											'optional' => 'Optional',
											default => 'Required',
										} }}
									</span>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="7" class="empty-state">No releases match these filters.</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>

			<div class="history-footer">
				<span>Showing {{ count($this->getFilteredReleases()) }} release records</span>
				<span>Aggregated preview data · no device identifiers or file details</span>
			</div>
		</section>
	</div>

	<style>
		.versions-dashboard {
			--surface: #17191b;
			--surface-raised: #1d2022;
			--line: #2b2e30;
			--muted: #9a9da0;
			--text: #f3f4f4;
			--green: #36c991;
			--amber: #e3a529;
			--red: #e46d70;
			color: var(--text);
		}

		.overview-grid { display: grid; grid-template-columns: minmax(0, 1.8fr) minmax(240px, .8fr); gap: 16px; }
		.overview-panel, .platform-panel, .history-panel { border: 1px solid var(--line); background: var(--surface); border-radius: 8px; }
		.overview-panel { min-height: 160px; padding: 20px; }
		.panel-kicker { color: #aeb1b2; font-size: 10px; font-weight: 700; }
		.adoption-content { display: grid; grid-template-columns: minmax(160px, .7fr) minmax(250px, 1.3fr); align-items: center; gap: 24px; margin-top: 9px; }
		.adoption-value { font-size: 42px; line-height: 1; font-weight: 700; }
		.adoption-value span { font-size: 22px; color: #c3c5c6; }
		.panel-copy, .attention-copy { margin-top: 6px; color: var(--muted); font-size: 12px; line-height: 1.5; }
		.progress-track { display: flex; height: 7px; overflow: hidden; background: #303436; border-radius: 999px; }
		.progress-current { background: var(--green); }
		.progress-optional { background: var(--amber); }
		.progress-required { background: var(--red); }
		.legend-row { display: flex; flex-wrap: wrap; gap: 8px 16px; margin-top: 14px; color: #b3b6b7; font-size: 10px; }
		.legend-row span, .platform-stats span { display: inline-flex; align-items: center; gap: 5px; }
		.legend-row b { color: var(--text); font-weight: 600; }
		.legend-dot { display: inline-block; width: 7px; height: 7px; flex: 0 0 7px; border-radius: 50%; }
		.dot-current { background: var(--green); }
		.dot-optional { background: var(--amber); }
		.dot-required { background: var(--red); }
		.sample-note { margin-top: 14px; color: #737779; font-size: 10px; }
		.attention-panel { display: grid; grid-template-columns: 34px minmax(0, 1fr); align-content: center; align-items: center; column-gap: 12px; row-gap: 12px; }
		.attention-icon, .platform-mark { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 8px; background: #302817; color: var(--amber); }
		.attention-number { font-size: 22px; font-weight: 700; line-height: 1.1; }
		.attention-title { margin-top: 2px; font-size: 12px; font-weight: 600; }
		.attention-copy { margin-top: 3px; }
		.review-button { grid-column: 1 / -1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; width: fit-content; border: 1px solid #3b3d3e; border-radius: 6px; padding: 8px 10px; color: #e6e7e7; font-size: 11px; transition: border-color .16s, background .16s; }
		.review-button:hover { border-color: #65686a; background: #242729; }
		.platform-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
		.platform-panel { padding: 16px; transition: transform .18s ease, border-color .18s ease; }
		.platform-panel:hover { transform: translateY(-2px); border-color: #484b4d; }
		.platform-heading { display: flex; align-items: center; gap: 10px; min-width: 0; }
		.platform-mark { width: 30px; height: 30px; flex: 0 0 30px; background: #28291e; color: #e5b638; }
		.platform-heading h2 { overflow: hidden; color: var(--text); font-size: 13px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
		.platform-heading p { margin-top: 2px; color: var(--muted); font-size: 10px; }
		.release-state { margin-left: auto; flex: 0 0 auto; border: 1px solid #343839; border-radius: 999px; padding: 3px 8px; color: #b7babb; font-size: 9px; }
		.version-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 18px; }
		.version-pair div { display: grid; gap: 4px; }
		.version-pair span, .platform-footer span { color: var(--muted); font-size: 9px; }
		.version-pair strong { font-size: 20px; line-height: 1.1; }
		.platform-progress { margin-top: 15px; }
		.platform-stats { display: flex; flex-wrap: wrap; gap: 7px 11px; margin-top: 9px; color: #aeb1b2; font-size: 9px; }
		.platform-stats .legend-dot { width: 6px; height: 6px; flex-basis: 6px; }
		.platform-footer { display: flex; justify-content: space-between; gap: 8px; margin-top: 15px; border-top: 1px solid var(--line); padding-top: 11px; }
		.platform-footer time { color: #d0d2d2; font-size: 10px; }
		.history-panel { overflow: hidden; }
		.history-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 15px 18px; border-bottom: 1px solid var(--line); }
		.history-heading h2 { font-size: 13px; font-weight: 600; }
		.history-heading p { margin-top: 3px; color: var(--muted); font-size: 10px; }
		.history-controls { display: flex; align-items: center; gap: 10px; }
		.platform-tabs { display: flex; gap: 2px; padding: 3px; border: 1px solid var(--line); border-radius: 7px; background: #141617; }
		.platform-tab { border-radius: 5px; padding: 6px 9px; color: #a7aaab; font-size: 10px; white-space: nowrap; }
		.platform-tab:hover { color: var(--text); }
		.platform-tab.is-active { background: #37301d; color: #f2bd45; }
		.status-select { min-height: 33px; border: 1px solid var(--line); border-radius: 6px; background: #191b1d; padding: 0 26px 0 9px; color: #d5d7d7; font-size: 10px; }
		.table-scroll { overflow-x: auto; }
		.release-table { width: 100%; min-width: 760px; border-collapse: collapse; text-align: left; }
		.release-table th { height: 34px; border-bottom: 1px solid var(--line); padding: 0 14px; color: #85898a; font-size: 9px; font-weight: 600; text-transform: uppercase; }
		.release-table td { height: 43px; border-bottom: 1px solid #25282a; padding: 0 14px; color: #c5c8c8; font-size: 10px; white-space: nowrap; }
		.release-table tbody tr:last-child td { border-bottom: 0; }
		.release-table tbody tr:hover { background: #1d2022; }
		.release-table td strong { color: var(--text); font-size: 11px; font-weight: 600; }
		.track-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 3px 7px; font-size: 9px; }
		.track-latest { background: #183329; color: #6bd6a9; }
		.track-minimum { background: #342b17; color: #edbd55; }
		.track-old { background: #382224; color: #ed9294; }
		.adoption-cell { display: flex; align-items: center; gap: 8px; }
		.mini-track { display: inline-block; width: 52px; height: 5px; overflow: hidden; border-radius: 99px; background: #343739; }
		.mini-track i { display: block; height: 100%; border-radius: inherit; background: var(--green); }
		.status-label { display: inline-flex; align-items: center; gap: 6px; }
		.status-label i { display: inline-block; width: 6px; height: 6px; border-radius: 50%; }
		.status-current { color: #70d8ad; }
		.status-current i { background: var(--green); }
		.status-optional { color: #e8bd5a; }
		.status-optional i { background: var(--amber); }
		.status-required { color: #ed9698; }
		.status-required i { background: var(--red); }
		.empty-state { height: 80px !important; text-align: center; color: var(--muted) !important; }
		.history-footer { display: flex; justify-content: space-between; gap: 12px; border-top: 1px solid var(--line); padding: 11px 18px; color: #7d8182; font-size: 9px; }

		@media (max-width: 1024px) {
			.overview-grid { grid-template-columns: 1fr; }
			.platform-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		}

		@media (max-width: 700px) {
			.adoption-content { grid-template-columns: 1fr; gap: 15px; }
			.platform-grid { grid-template-columns: 1fr; }
			.history-heading, .history-controls { align-items: stretch; flex-direction: column; }
			.platform-tabs { overflow-x: auto; }
			.history-footer { flex-direction: column; }
		}
	</style>
</x-filament-panels::page>
