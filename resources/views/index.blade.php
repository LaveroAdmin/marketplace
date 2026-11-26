<style>
    .market-card {
        border-radius: 8px;
        transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .market-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .market-icon-box {
        width: 52px;
        height: 52px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .console-terminal {
        background-color: #1e1e1e;
        color: #d4d4d4;
        font-family: Consolas, "Courier New", monospace;
        font-size: 13px;
        border-radius: 6px;
        padding: 12px;
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-wrap;
    }
    .cmd-item-box {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 10px 12px;
    }
</style>

<!-- Top Statistics Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card market-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="market-icon-box bg-primary bg-opacity-10 text-primary me-3">
                    <i class="icon-store"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">{{ trans('lavero-admin-marketplace::marketplace.all') }}</h6>
                    <h3 class="card-title mb-0 fw-bold">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card market-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="market-icon-box bg-success bg-opacity-10 text-success me-3">
                    <i class="icon-check"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">{{ trans('lavero-admin-marketplace::marketplace.installed') }}</h6>
                    <h3 class="card-title mb-0 fw-bold text-success">{{ $stats['installed'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card market-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="market-icon-box bg-info bg-opacity-10 text-info me-3">
                    <i class="icon-download"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">{{ trans('lavero-admin-marketplace::marketplace.not_installed') }}</h6>
                    <h3 class="card-title mb-0 fw-bold text-info">{{ $stats['not_installed'] }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="card mb-4">
    <div class="card-body py-2 px-3 d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-2">
        <ul class="nav nav-pills flex-nowrap overflow-auto py-1" id="marketplace-filter-pills" style="scrollbar-width: none; -webkit-overflow-scrolling: touch;">
            <li class="nav-item">
                <button type="button" class="nav-link active py-1 px-3 filter-btn text-nowrap" data-filter="all">
                    {{ trans('lavero-admin-marketplace::marketplace.all') }}
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link py-1 px-3 filter-btn text-nowrap" data-filter="installed">
                    {{ trans('lavero-admin-marketplace::marketplace.installed') }}
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link py-1 px-3 filter-btn text-nowrap" data-filter="not_installed">
                    {{ trans('lavero-admin-marketplace::marketplace.not_installed') }}
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link py-1 px-3 filter-btn text-nowrap" data-filter="tools">
                    {{ trans('lavero-admin-marketplace::marketplace.tools') }}
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link py-1 px-3 filter-btn text-nowrap" data-filter="system">
                    {{ trans('lavero-admin-marketplace::marketplace.system') }}
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link py-1 px-3 filter-btn text-nowrap" data-filter="content">
                    {{ trans('lavero-admin-marketplace::marketplace.content') }}
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link py-1 px-3 filter-btn text-nowrap" data-filter="security">
                    {{ trans('lavero-admin-marketplace::marketplace.security') }}
                </button>
            </li>
        </ul>

        <div class="d-flex align-items-center gap-2 flex-wrap flex-sm-nowrap">
            <button type="button" class="btn btn-sm btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#modal-upload-zip" title="{{ trans('lavero-admin-marketplace::marketplace.upload_zip_title') }}">
                <i class="icon-upload me-1"></i> <span class="d-none d-lg-inline">{{ trans('lavero-admin-marketplace::marketplace.upload_zip') }}</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="btn-refresh-repos" title="{{ trans('lavero-admin-marketplace::marketplace.refresh_repos') }}">
                <i class="icon-sync-alt me-1"></i> <span class="d-none d-lg-inline">{{ trans('lavero-admin-marketplace::marketplace.refresh_repos') }}</span>
            </button>
            <div class="input-group input-group-sm flex-grow-1" style="min-width: 130px; max-width: 260px;">
                <input type="text" id="marketplace-search" class="form-control" placeholder="{{ trans('lavero-admin-marketplace::marketplace.search') }}">
                <span class="input-group-text"><i class="icon-search"></i></span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" onclick="admin.ajax.reload();" title="{{ trans('admin.refresh') }}">
                <i class="icon-sync-alt"></i>
            </button>
        </div>
    </div>
</div>

<!-- Extension Cards Grid -->
<div class="row g-3" id="marketplace-grid">
    @foreach($extensions as $key => $ext)
    <div class="col-md-6 col-xl-4 ext-card-col"
         data-installed="{{ $ext['installed'] ? 'true' : 'false' }}"
         data-category="{{ $ext['category'] }}"
         data-name="{{ strtolower($ext['name']) }}"
         data-title="{{ strtolower($ext['title']) }}"
         data-desc="{{ strtolower($ext['description']) }}">
        <div class="card market-card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center flex-grow-1 text-truncate" style="min-width: 150px;">
                        <div class="market-icon-box bg-light text-primary border me-3 flex-shrink-0">
                            <i class="{{ $ext['icon'] }}"></i>
                        </div>
                        <div class="text-truncate">
                            <h5 class="card-title mb-0 fw-bold fs-6 text-truncate">{{ $ext['title'] }}</h5>
                            <span class="text-muted small font-monospace d-block text-truncate">{{ $ext['name'] }}</span>
                            @if(!empty($ext['author']['name']))
                                <div class="small text-muted mt-1 text-truncate">
                                    <i class="icon-user me-1 opacity-75"></i>
                                    @if(!empty($ext['author']['url']))
                                        <a href="{{ $ext['author']['url'] }}" target="_blank" class="text-decoration-none text-muted">{{ $ext['author']['name'] }}</a>
                                    @else
                                        <span>{{ $ext['author']['name'] }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex flex-wrap flex-sm-column align-items-start align-items-sm-end gap-1 ms-auto ms-sm-0">
                        @if($ext['installed'])
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2">
                                <i class="icon-check me-1"></i>{{ $ext['version'] ?: trans('lavero-admin-marketplace::marketplace.installed_status') }}
                            </span>
                        @else
                            <span class="badge bg-light text-secondary border py-1 px-2">
                                {{ ucfirst($ext['category']) }}
                            </span>
                        @endif

                        @if(isset($ext['compatibility']['compatible']))
                            @if($ext['compatibility']['compatible'])
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2 small" title="{{ $ext['compatibility']['summary'] }}">
                                    <i class="icon-check me-1"></i>{{ trans('lavero-admin-marketplace::marketplace.compatible') }}
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2 small" title="{{ implode('; ', $ext['compatibility']['reasons'] ?? []) }}">
                                    <i class="icon-exclamation-triangle me-1"></i>{{ trans('lavero-admin-marketplace::marketplace.incompatible') }}
                                </span>
                            @endif
                        @endif

                        @if(!empty($ext['repository_name']))
                            <span class="badge bg-light text-muted border py-1 px-2 text-truncate" style="font-size: 11px; max-width: 180px;" title="{{ $ext['repository_name'] }}">
                                <i class="icon-folder me-1 opacity-50"></i>{{ $ext['repository_name'] }}
                            </span>
                        @endif
                    </div>
                </div>

                <p class="card-text text-muted small flex-grow-1 mb-3">
                    {{ $ext['description'] }}
                </p>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-top pt-3 mt-auto">
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ $ext['github'] }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="{{ trans('lavero-admin-marketplace::marketplace.github') }}">
                            <i class="icon-external-link-alt"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-instructions" data-key="{{ $key }}" title="{{ trans('lavero-admin-marketplace::marketplace.instructions') }}">
                            <i class="icon-info-circle me-1"></i> {{ trans('lavero-admin-marketplace::marketplace.instructions_btn') }}
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-1 ms-auto ms-sm-0">
                        @if($ext['installed'])
                            @if(!empty($ext['has_update']))
                                <button type="button" class="btn btn-sm btn-warning btn-update text-dark fw-semibold" data-key="{{ $key }}" data-title="{{ $ext['title'] }}" title="{{ trans('lavero-admin-marketplace::marketplace.update_to', ['version' => $ext['latest_version'] ?? '']) }}">
                                    <i class="icon-arrow-up me-1"></i> {{ trans('lavero-admin-marketplace::marketplace.update_to', ['version' => $ext['latest_version'] ?? '']) }}
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-update" data-key="{{ $key }}" data-title="{{ $ext['title'] }}" title="{{ trans('lavero-admin-marketplace::marketplace.update') }}">
                                    <i class="icon-download"></i>
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-info btn-reimport" data-key="{{ $key }}" data-title="{{ $ext['title'] }}" title="{{ trans('lavero-admin-marketplace::marketplace.reimport_tooltip') }}">
                                <i class="icon-sync-alt me-1"></i> {{ trans('lavero-admin-marketplace::marketplace.reimport') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-key="{{ $key }}" data-title="{{ $ext['title'] }}" title="{{ trans('lavero-admin-marketplace::marketplace.remove') }}">
                                <i class="icon-trash"></i>
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-copy-cmd" data-package="{{ $ext['name'] }}" data-key="{{ $key }}" title="{{ trans('lavero-admin-marketplace::marketplace.copy_cmd') }}">
                                <i class="icon-terminal"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary btn-install" data-key="{{ $key }}" data-title="{{ $ext['title'] }}">
                                <i class="icon-download me-1"></i> {{ trans('lavero-admin-marketplace::marketplace.install') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Modal: Upload ZIP Extension -->
<div class="modal fade" id="modal-upload-zip" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-upload-zip" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="icon-upload me-2 text-success"></i>{{ trans('lavero-admin-marketplace::marketplace.upload_zip_title') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('admin.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">{{ trans('lavero-admin-marketplace::marketplace.upload_zip_desc') }}</p>
                    <input type="file" name="archive" id="zip-archive-input" class="form-control" accept=".zip" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('lavero-admin-marketplace::marketplace.btn_cancel') }}</button>
                    <button type="submit" class="btn btn-success text-white">
                        <i class="icon-upload me-1"></i>{{ trans('lavero-admin-marketplace::marketplace.upload_zip_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Setup Instructions -->
<div class="modal fade" id="modal-instructions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inst-modal-title">
                    <i class="icon-info-circle me-2 text-primary"></i>{{ trans('lavero-admin-marketplace::marketplace.instructions') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('admin.close') }}"></button>
            </div>
            <div class="modal-body">
                <!-- Author & Repository Header Card -->
                <div class="card bg-light border-0 mb-3" id="inst-section-author">
                    <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2" style="width: 34px; height: 34px;">
                                <i class="icon-user"></i>
                            </div>
                            <div>
                                <span class="small text-muted d-block lh-1">{{ trans('lavero-admin-marketplace::marketplace.author') }}</span>
                                <span class="fw-semibold small" id="inst-author-name"></span>
                                <a href="#" id="inst-author-email" class="text-muted small ms-1 text-decoration-none"></a>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2" id="inst-author-links">
                            <a href="#" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" id="inst-author-url">
                                <i class="icon-external-link-alt me-1"></i> Website
                            </a>
                            <a href="#" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" id="inst-github-url">
                                <i class="icon-external-link-alt me-1"></i> GitHub
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Compatibility Matrix Section -->
                <div class="mb-3" id="inst-section-compatibility" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold mb-0 small text-uppercase text-muted">
                            <i class="icon-shield-alt me-1 text-primary"></i>{{ trans('lavero-admin-marketplace::marketplace.compatibility_check') }}
                        </h6>
                        <span class="badge py-1 px-2" id="inst-compat-summary-badge"></span>
                    </div>
                    <div class="table-responsive border rounded-2">
                        <table class="table table-sm table-striped align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ trans('lavero-admin-marketplace::marketplace.component') }}</th>
                                    <th>{{ trans('lavero-admin-marketplace::marketplace.required_env') }}</th>
                                    <th>{{ trans('lavero-admin-marketplace::marketplace.current_env') }}</th>
                                    <th class="text-end pe-2">{{ trans('lavero-admin-marketplace::marketplace.status') }}</th>
                                </tr>
                            </thead>
                            <tbody id="inst-compat-table-body"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Requirements Section -->
                <div class="mb-3" id="inst-section-requirements" style="display: none;">
                    <h6 class="fw-bold mb-2 small text-uppercase text-muted">
                        <i class="icon-check-circle me-1 text-success"></i>{{ trans('lavero-admin-marketplace::marketplace.requirements') }}
                    </h6>
                    <ul class="list-group list-group-flush border rounded-2 small" id="inst-list-requirements"></ul>
                </div>

                <!-- Commands Section -->
                <div class="mb-3" id="inst-section-commands" style="display: none;">
                    <h6 class="fw-bold mb-2 small text-uppercase text-muted">
                        <i class="icon-terminal me-1 text-info"></i>{{ trans('lavero-admin-marketplace::marketplace.post_install_commands') }}
                    </h6>
                    <div class="d-flex flex-column gap-2" id="inst-commands-container"></div>
                </div>

                <!-- Config Section -->
                <div class="mb-3" id="inst-section-config" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h6 class="fw-bold mb-0 small text-uppercase text-muted">
                            <i class="icon-code me-1 text-warning"></i>{{ trans('lavero-admin-marketplace::marketplace.post_install_config') }}
                        </h6>
                        <span class="small font-monospace text-muted" id="inst-config-filename">config/admin.php</span>
                    </div>
                    <div class="position-relative">
                        <pre class="console-terminal mb-0" id="inst-code-config"></pre>
                        <button type="button" class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2 btn-copy-snippet" data-target="inst-code-config">
                            <i class="icon-copy me-1"></i> {{ trans('lavero-admin-marketplace::marketplace.copy') }}
                        </button>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="mb-2" id="inst-section-notes" style="display: none;">
                    <h6 class="fw-bold mb-2 small text-uppercase text-muted">
                        <i class="icon-sliders-h me-1 text-success"></i>{{ trans('lavero-admin-marketplace::marketplace.post_install_notes') }}
                    </h6>
                    <div class="alert alert-light border small text-muted mb-0" id="inst-text-notes"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('admin.close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Terminal Log -->
<div class="modal fade" id="modal-terminal-log" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="icon-terminal me-2 text-primary"></i>{{ trans('lavero-admin-marketplace::marketplace.console_output') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('admin.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-2" id="terminal-spinner-wrapper">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span class="small text-muted" id="terminal-action-status">{{ trans('lavero-admin-marketplace::marketplace.executing_command') }}</span>
                </div>
                <pre class="console-terminal mb-0" id="terminal-output-box">{{ trans('lavero-admin-marketplace::marketplace.waiting_output') }}</pre>

                <!-- Post-install instructions alert -->
                <div id="terminal-post-install-banner" class="alert alert-info d-flex align-items-center justify-content-between mt-3 mb-0" style="display: none;">
                    <span class="small"><i class="icon-info-circle me-2"></i>{{ trans('lavero-admin-marketplace::marketplace.post_install_steps') }}</span>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-terminal-open-instructions">
                        <i class="icon-info-circle me-1"></i> {{ trans('lavero-admin-marketplace::marketplace.show_instructions_now') }}
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('admin.close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Copy Command -->
<div class="modal fade" id="modal-copy-cli" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="icon-terminal me-2 text-info"></i>{{ trans('lavero-admin-marketplace::marketplace.cli_modal_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('admin.close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">{{ trans('lavero-admin-marketplace::marketplace.cli_run_instruction') }}</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control font-monospace small" id="cli-cmd-input" readonly>
                    <button class="btn btn-outline-primary" type="button" id="btn-copy-cli-exec">
                        <i class="icon-copy me-1"></i> {{ trans('lavero-admin-marketplace::marketplace.copy') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script data-exec-on-popstate>
(function () {
    const runUrl = '{{ route("marketplace-run") }}';
    const extensionsRegistry = @json($extensions);

    // Filter pills
    let currentFilter = 'all';
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            applyFilters();
        });
    });

    // Search input
    const searchInput = document.getElementById('marketplace-search');
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    function applyFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

        document.querySelectorAll('.ext-card-col').forEach(col => {
            let matchesCategory = true;
            if (currentFilter === 'installed') {
                matchesCategory = (col.dataset.installed === 'true');
            } else if (currentFilter === 'not_installed') {
                matchesCategory = (col.dataset.installed === 'false');
            } else if (currentFilter !== 'all') {
                matchesCategory = (col.dataset.category === currentFilter);
            }

            let matchesSearch = true;
            if (query) {
                const name = col.dataset.name || '';
                const title = col.dataset.title || '';
                const desc = col.dataset.desc || '';
                matchesSearch = name.includes(query) || title.includes(query) || desc.includes(query);
            }

            col.style.display = (matchesCategory && matchesSearch) ? '' : 'none';
        });
    }

    // Modal terminal helper
    const modalTerminalEl = document.getElementById('modal-terminal-log');
    const modalTerminal = modalTerminalEl ? new bootstrap.Modal(modalTerminalEl) : null;
    const terminalBox = document.getElementById('terminal-output-box');
    const terminalSpinner = document.getElementById('terminal-spinner-wrapper');
    const terminalStatus = document.getElementById('terminal-action-status');
    const terminalBanner = document.getElementById('terminal-post-install-banner');
    const btnTerminalOpenInst = document.getElementById('btn-terminal-open-instructions');

    let lastInstalledKey = null;

    function executeAction(action, key) {
        if (!modalTerminal) return;

        lastInstalledKey = (action === 'install') ? key : null;
        terminalBox.textContent = `Starting ${action} for ${key}...\nPlease wait, this may take a few moments.\n`;
        terminalSpinner.style.display = 'flex';
        terminalStatus.textContent = `Executing ${action}...`;
        if (terminalBanner) terminalBanner.style.display = 'none';
        modalTerminal.show();

        admin.ajax.post(runUrl, { action: action, key: key }, function (res) {
            terminalSpinner.style.display = 'none';
            if (res && res.data) {
                terminalBox.textContent = res.data.output || (res.data.status ? 'Command finished successfully.' : 'Command failed.');
                if (res.data.status) {
                    admin.toastr.success(res.data.message);
                    if (action === 'install' && terminalBanner) {
                        terminalBanner.style.display = 'flex';
                    }
                    modalTerminalEl.addEventListener('hidden.bs.modal', function onHide() {
                        modalTerminalEl.removeEventListener('hidden.bs.modal', onHide);
                        admin.ajax.reload();
                    });
                } else {
                    admin.toastr.error(res.data.message);
                }
            } else {
                terminalBox.textContent = 'Server response error.';
                admin.toastr.error('Action failed');
            }
        });
    }

    // Confirmation wrapper with SweetAlert2 fallback
    function askConfirmation(title, message, onConfirmed, isHtml = false, icon = 'question') {
        if (typeof Swal !== 'undefined') {
            const swalOptions = {
                title: title,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#12161f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ trans("lavero-admin-marketplace::marketplace.btn_confirm") }}',
                cancelButtonText: '{{ trans("lavero-admin-marketplace::marketplace.btn_cancel") }}'
            };
            if (isHtml) {
                swalOptions.html = message;
            } else {
                swalOptions.text = message;
            }
            Swal.fire(swalOptions).then((result) => {
                if (result.isConfirmed) {
                    onConfirmed();
                }
            });
        } else {
            if (window.confirm(message)) {
                onConfirmed();
            }
        }
    }

    // Refresh Repositories Button
    const btnRefreshRepos = document.getElementById('btn-refresh-repos');
    if (btnRefreshRepos) {
        btnRefreshRepos.addEventListener('click', function () {
            const icon = this.querySelector('i');
            if (icon) icon.classList.add('icon-spin');
            this.disabled = true;

            admin.ajax.post('{{ admin_url("marketplace/refresh") }}', {}, function (res) {
                if (res && res.data && res.data.status) {
                    admin.toastr.success(res.data.message);
                    setTimeout(() => admin.ajax.reload(), 500);
                } else {
                    admin.toastr.error(res && res.data ? res.data.message : 'Error refreshing repositories');
                    if (icon) icon.classList.remove('icon-spin');
                    btnRefreshRepos.disabled = false;
                }
            });
        });
    }

    // Install Button with confirmation & compatibility check
    document.querySelectorAll('.btn-install').forEach(btn => {
        btn.addEventListener('click', function () {
            const key = this.dataset.key;
            const title = this.dataset.title || key;
            const item = extensionsRegistry[key];
            const compat = item ? item.compatibility : null;

            let isHtml = false;
            let icon = 'question';
            let msg = '{{ trans("lavero-admin-marketplace::marketplace.confirm_install") }}'.replace(':name', title);

            if (compat && !compat.compatible && compat.reasons && compat.reasons.length > 0) {
                isHtml = true;
                icon = 'warning';
                const reasonsList = compat.reasons.map(r => `<li>${r}</li>`).join('');
                msg = `
                    <div class="text-start">
                        <p class="mb-2">{{ trans("lavero-admin-marketplace::marketplace.confirm_install") }}</p>
                        <div class="alert alert-warning small mb-2 py-2 px-3">
                            <strong><i class="icon-exclamation-triangle me-1"></i>{{ trans("lavero-admin-marketplace::marketplace.incompatible_warning") }}</strong>
                            <ul class="mb-1 mt-1 ps-3">${reasonsList}</ul>
                        </div>
                        <p class="mb-0 small text-muted">{{ trans("lavero-admin-marketplace::marketplace.incompatible_confirm") }}</p>
                    </div>
                `.replace(':name', '<b>' + title + '</b>');
            }

            askConfirmation('{{ trans("lavero-admin-marketplace::marketplace.confirm_title") }}', msg, function () {
                executeAction('install', key);
            }, isHtml, icon);
        });
    });

    // Update Button with confirmation
    document.querySelectorAll('.btn-update').forEach(btn => {
        btn.addEventListener('click', function () {
            const key = this.dataset.key;
            const title = this.dataset.title || key;
            const msg = '{{ trans("lavero-admin-marketplace::marketplace.confirm_update") }}'.replace(':name', title);
            askConfirmation('{{ trans("lavero-admin-marketplace::marketplace.confirm_title") }}', msg, function () {
                executeAction('update', key);
            });
        });
    });

    // Re-import Button with confirmation
    document.querySelectorAll('.btn-reimport').forEach(btn => {
        btn.addEventListener('click', function () {
            const key = this.dataset.key;
            const title = this.dataset.title || key;
            const msg = '{{ trans("lavero-admin-marketplace::marketplace.confirm_reimport") }}'.replace(':name', title);
            askConfirmation('{{ trans("lavero-admin-marketplace::marketplace.confirm_title") }}', msg, function () {
                executeAction('import', key);
            });
        });
    });

    // Remove Button with confirmation
    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', function () {
            const key = this.dataset.key;
            const title = this.dataset.title || key;
            const msg = '{{ trans("lavero-admin-marketplace::marketplace.confirm_remove") }}'.replace(':name', title);
            askConfirmation('{{ trans("lavero-admin-marketplace::marketplace.confirm_title") }}', msg, function () {
                executeAction('remove', key);
            });
        });
    });

    // Setup Instructions Modal
    const modalInstEl = document.getElementById('modal-instructions');
    const modalInst = modalInstEl ? new bootstrap.Modal(modalInstEl) : null;
    const instModalTitle = document.getElementById('inst-modal-title');
    const instAuthorName = document.getElementById('inst-author-name');
    const instAuthorEmail = document.getElementById('inst-author-email');
    const instAuthorUrl = document.getElementById('inst-author-url');
    const instGithubUrl = document.getElementById('inst-github-url');
    const instListRequirements = document.getElementById('inst-list-requirements');
    const instSecRequirements = document.getElementById('inst-section-requirements');
    const instCommandsContainer = document.getElementById('inst-commands-container');
    const instSecCommands = document.getElementById('inst-section-commands');
    const instConfigFilename = document.getElementById('inst-config-filename');
    const instCodeConfig = document.getElementById('inst-code-config');
    const instSecConfig = document.getElementById('inst-section-config');
    const instTextNotes = document.getElementById('inst-text-notes');
    const instSecNotes = document.getElementById('inst-section-notes');

    function showInstructions(key) {
        const item = extensionsRegistry[key];
        if (!item || !modalInst) return;

        const setup = item.setup || item.instructions || {};
        instModalTitle.innerHTML = `<i class="${item.icon} me-2 text-primary"></i>${item.title}: {{ trans('lavero-admin-marketplace::marketplace.instructions') }}`;

        // 1. Author info
        const author = item.author || {};
        instAuthorName.textContent = author.name || 'Aleksandr Kireev';
        if (author.email) {
            instAuthorEmail.textContent = `<${author.email}>`;
            instAuthorEmail.href = `mailto:${author.email}`;
            instAuthorEmail.style.display = 'inline';
        } else {
            instAuthorEmail.style.display = 'none';
        }
        if (author.url) {
            instAuthorUrl.href = author.url;
            instAuthorUrl.style.display = 'inline-flex';
        } else {
            instAuthorUrl.style.display = 'none';
        }
        if (item.github) {
            instGithubUrl.href = item.github;
            instGithubUrl.style.display = 'inline-flex';
        } else {
            instGithubUrl.style.display = 'none';
        }

        // 2. Compatibility Matrix
        const compat = item.compatibility || {};
        const instSecCompat = document.getElementById('inst-section-compatibility');
        const instCompatBadge = document.getElementById('inst-compat-summary-badge');
        const instCompatBody = document.getElementById('inst-compat-table-body');
        if (compat && compat.checks && compat.checks.length > 0) {
            instSecCompat.style.display = 'block';
            instCompatBadge.className = 'badge py-1 px-2 ' + (compat.compatible ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-danger bg-opacity-10 text-danger border border-danger');
            instCompatBadge.innerHTML = (compat.compatible ? '<i class="icon-check me-1"></i>' : '<i class="icon-exclamation-triangle me-1"></i>') + (compat.summary || '');

            instCompatBody.innerHTML = compat.checks.map(c => `
                <tr>
                    <td class="fw-semibold">${c.title}</td>
                    <td class="font-monospace">${c.required}</td>
                    <td class="font-monospace">${c.current}</td>
                    <td class="text-end pe-2">
                        ${c.passed
                            ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="icon-check me-1"></i>{{ trans("lavero-admin-marketplace::marketplace.status_passed") }}</span>'
                            : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="icon-times me-1"></i>{{ trans("lavero-admin-marketplace::marketplace.status_failed") }}</span>'}
                    </td>
                </tr>
            `).join('');
        } else {
            instSecCompat.style.display = 'none';
        }

        // 3. Requirements
        const reqs = setup.requirements || [];
        if (reqs.length > 0) {
            instListRequirements.innerHTML = '';
            reqs.forEach(r => {
                const li = document.createElement('li');
                li.className = 'list-group-item py-1 px-3 d-flex align-items-center';
                li.innerHTML = `<i class="icon-check text-success me-2"></i><span>${r}</span>`;
                instListRequirements.appendChild(li);
            });
            instSecRequirements.style.display = 'block';
        } else {
            instSecRequirements.style.display = 'none';
        }

        // 3. Commands
        const rawCommands = setup.commands || [];
        if (rawCommands.length > 0) {
            instCommandsContainer.innerHTML = '';
            rawCommands.forEach((c, idx) => {
                const cmdStr = (typeof c === 'string') ? c : (c.cmd || '');
                const descStr = (typeof c === 'object' && c.desc) ? c.desc : '';

                const wrap = document.createElement('div');
                wrap.className = 'cmd-item-box';
                wrap.innerHTML = `
                    ${descStr ? `<div class="small fw-semibold text-secondary mb-1">${descStr}</div>` : ''}
                    <div class="position-relative">
                        <pre class="console-terminal mb-0 py-2" id="cmd-pre-${idx}">${cmdStr}</pre>
                        <button type="button" class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-1 py-0 px-2 btn-copy-inline" data-text="${cmdStr}">
                            <i class="icon-copy me-1"></i> {{ trans('lavero-admin-marketplace::marketplace.copy') }}
                        </button>
                    </div>
                `;
                instCommandsContainer.appendChild(wrap);
            });

            // Bind inline copy buttons
            instCommandsContainer.querySelectorAll('.btn-copy-inline').forEach(b => {
                b.addEventListener('click', function () {
                    navigator.clipboard.writeText(this.dataset.text);
                    admin.toastr.success('{{ trans("lavero-admin-marketplace::marketplace.copied") }}');
                });
            });

            instSecCommands.style.display = 'block';
        } else {
            instSecCommands.style.display = 'none';
        }

        // 4. Config
        const cfg = setup.config || null;
        if (cfg) {
            const codeStr = (typeof cfg === 'string') ? cfg : (cfg.code || '');
            const fileStr = (typeof cfg === 'object' && cfg.file) ? cfg.file : 'config/admin.php';

            instConfigFilename.textContent = fileStr;
            instCodeConfig.textContent = codeStr;
            instSecConfig.style.display = 'block';
        } else {
            instSecConfig.style.display = 'none';
        }

        // 5. Notes
        const rawNotes = setup.notes || [];
        if (rawNotes) {
            if (Array.isArray(rawNotes) && rawNotes.length > 0) {
                instTextNotes.innerHTML = rawNotes.map(n => `<div class="mb-1">• ${n}</div>`).join('');
                instSecNotes.style.display = 'block';
            } else if (typeof rawNotes === 'string' && rawNotes.trim().length > 0) {
                instTextNotes.textContent = rawNotes;
                instSecNotes.style.display = 'block';
            } else {
                instSecNotes.style.display = 'none';
            }
        } else {
            instSecNotes.style.display = 'none';
        }

        modalInst.show();
    }

    document.querySelectorAll('.btn-instructions').forEach(btn => {
        btn.addEventListener('click', function () {
            showInstructions(this.dataset.key);
        });
    });

    if (btnTerminalOpenInst) {
        btnTerminalOpenInst.addEventListener('click', function () {
            if (lastInstalledKey) {
                if (modalTerminal) modalTerminal.hide();
                setTimeout(() => showInstructions(lastInstalledKey), 400);
            }
        });
    }

    // Copy snippet helper
    document.querySelectorAll('.btn-copy-snippet').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetEl = document.getElementById(this.dataset.target);
            if (targetEl) {
                navigator.clipboard.writeText(targetEl.textContent);
                admin.toastr.success('{{ trans("lavero-admin-marketplace::marketplace.copied") }}');
            }
        });
    });

    // Copy CLI Modal
    const modalCopyEl = document.getElementById('modal-copy-cli');
    const modalCopy = modalCopyEl ? new bootstrap.Modal(modalCopyEl) : null;
    const cmdInput = document.getElementById('cli-cmd-input');
    const btnCopyCli = document.getElementById('btn-copy-cli-exec');

    document.querySelectorAll('.btn-copy-cmd').forEach(btn => {
        btn.addEventListener('click', function () {
            const pkg = this.dataset.package;
            const key = this.dataset.key;
            cmdInput.value = `composer require ${pkg} && php artisan admin:import ${key}`;
            modalCopy.show();
        });
    });

    if (btnCopyCli && cmdInput) {
        btnCopyCli.addEventListener('click', function () {
            cmdInput.select();
            navigator.clipboard.writeText(cmdInput.value);
            admin.toastr.success('{{ trans("lavero-admin-marketplace::marketplace.copied") }}');
        });
    }

    const formUploadZip = document.getElementById('form-upload-zip');
    if (formUploadZip) {
        formUploadZip.addEventListener('submit', function (e) {
            e.preventDefault();
            const fileInput = document.getElementById('zip-archive-input');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                return;
            }

            const uploadModalEl = document.getElementById('modal-upload-zip');
            const uploadModal = uploadModalEl ? bootstrap.Modal.getInstance(uploadModalEl) : null;
            if (uploadModal) uploadModal.hide();

            const formData = new FormData();
            formData.append('archive', fileInput.files[0]);

            const modalTermEl = document.getElementById('modal-terminal');
            const modalTerm = modalTermEl ? bootstrap.Modal.getOrCreateInstance(modalTermEl) : null;
            const termOutput = document.getElementById('terminal-output');
            const termStatus = document.getElementById('terminal-status');
            if (termOutput) termOutput.textContent = '{{ trans("lavero-admin-marketplace::marketplace.waiting_output") }}';
            if (termStatus) termStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>{{ trans("lavero-admin-marketplace::marketplace.executing_command") }}';
            if (modalTerm) modalTerm.show();

            fetch('{{ route("marketplace-upload") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (termOutput) termOutput.textContent = data.output || data.message || '';
                if (data.status) {
                    if (termStatus) termStatus.innerHTML = '<span class="badge bg-success"><i class="icon-check me-1"></i>' + (data.message || 'OK') + '</span>';
                    admin.toastr.success(data.message || 'OK');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    if (termStatus) termStatus.innerHTML = '<span class="badge bg-danger"><i class="icon-times me-1"></i>' + (data.message || 'Error') + '</span>';
                    admin.toastr.error(data.message || 'Error');
                }
            })
            .catch(err => {
                if (termStatus) termStatus.innerHTML = '<span class="badge bg-danger">Error</span>';
                if (termOutput) termOutput.textContent = String(err);
            });
        });
    }
})();
</script>
