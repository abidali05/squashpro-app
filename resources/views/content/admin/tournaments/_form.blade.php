@php
    $tournament = $tournament ?? null;
@endphp

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label">Hosting Club <span class="text-danger">*</span></label>
        <select name="club_id" id="club_id" class="form-select @error('club_id') is-invalid @enderror">
            <option value="">Select Hosting Club</option>
            @foreach($clubs as $club)
                <option value="{{ $club->id }}" @selected(old('club_id', $tournament?->club_id ?? '') == $club->id)>
                    {{ $club->club_name ?? $club->name }} ({{ $club->city ?? 'No City' }})
                </option>
            @endforeach
        </select>
        @error('club_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Tournament Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $tournament?->name ?? '') }}" placeholder="e.g. National Squash Open">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Tournament Type <span class="text-danger">*</span></label>
        <select name="tournament_type" id="tournament_type" class="form-select @error('tournament_type') is-invalid @enderror">
            <option value="CLUB_MEMBERS_ONLY" @selected(old('tournament_type', $tournament?->tournament_type ?? '') === 'CLUB_MEMBERS_ONLY')>Club Members Only</option>
            <option value="CLUB_TO_CLUB" @selected(old('tournament_type', $tournament?->tournament_type ?? '') === 'CLUB_TO_CLUB')>Club to Club</option>
            <option value="OPEN" @selected(old('tournament_type', $tournament?->tournament_type ?? '') === 'OPEN')>Open to All</option>
        </select>
        @error('tournament_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6" id="opponent_club_container">
        <label class="form-label">Opponent Clubs <span class="text-danger" id="opponent_asterisk">*</span></label>
        <select name="opponent_club_id[]" id="opponent_club_id" class="form-select @error('opponent_club_id') is-invalid @enderror" multiple size="5">
            @foreach($clubs as $club)
                @php
                    $selected = false;
                    $oldOpponents = old('opponent_club_id', $tournament?->opponent_club_id ?? []);
                    if (is_array($oldOpponents)) {
                        $selected = in_array($club->id, array_map('intval', $oldOpponents), true);
                    } else {
                        $selected = (int)$oldOpponents == $club->id;
                    }
                @endphp
                <option value="{{ $club->id }}" @selected($selected)>
                    {{ $club->club_name ?? $club->name }} ({{ $club->city ?? 'No City' }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple clubs.</small>
        @error('opponent_club_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6" id="scorer_container">
        <label class="form-label">Tournament Scorers</label>
        <select name="scorer_ids[]" id="scorer_ids" class="form-select @error('scorer_ids') is-invalid @enderror" multiple size="5">
            @foreach($scorers as $scorer)
                @php
                    $selected = false;
                    $oldScorers = old('scorer_ids', isset($tournament) ? $tournament->scorers->pluck('id')->toArray() : []);
                    $selected = in_array($scorer->id, array_map('intval', $oldScorers), true);
                @endphp
                <option value="{{ $scorer->id }}" @selected($selected)>
                    {{ $scorer->name }} ({{ $scorer->email }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple scorers.</small>
        @error('scorer_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6" id="umpire_container">
        <label class="form-label">Tournament Umpires</label>
        <select name="umpire_ids[]" id="umpire_ids" class="form-select @error('umpire_ids') is-invalid @enderror" multiple size="5">
            @foreach($umpires as $umpire)
                @php
                    $selected = false;
                    $oldUmpires = old('umpire_ids', isset($tournament) ? $tournament->umpires->pluck('id')->toArray() : []);
                    $selected = in_array($umpire->id, array_map('intval', $oldUmpires), true);
                @endphp
                <option value="{{ $umpire->id }}" @selected($selected)>
                    {{ $umpire->name }} ({{ $umpire->email }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple umpires.</small>
        @error('umpire_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Format <span class="text-danger">*</span></label>
        <select name="format" class="form-select @error('format') is-invalid @enderror">
            <option value="">Select Format</option>
            @foreach(['knockout' => 'Knockout', 'league' => 'League', 'round_robin' => 'Round Robin', 'other' => 'Other'] as $val => $label)
                <option value="{{ $val }}" @selected(old('format', $tournament?->format ?? '') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        @error('format')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Gender Restriction <span class="text-danger">*</span></label>
        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
            @foreach(['OPEN' => 'Open to All', 'MALE' => 'Male Only', 'FEMALE' => 'Female Only', 'MIXED' => 'Mixed'] as $val => $label)
                <option value="{{ $val }}" @selected(old('gender', $tournament?->gender ?? 'OPEN') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Age Group (e.g. 15-45) <span class="text-danger">*</span></label>
        <input type="text" name="age_group" class="form-control @error('age_group') is-invalid @enderror"
            value="{{ old('age_group', $tournament?->age_group ?? '15-45') }}" placeholder="Min-Max age range">
        @error('age_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Maximum Players <span class="text-danger">*</span></label>
        <input type="number" name="maximum_players" min="1" class="form-control @error('maximum_players') is-invalid @enderror"
            value="{{ old('maximum_players', $tournament?->maximum_players ?? 16) }}">
        @error('maximum_players')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Start Date <span class="text-danger">*</span></label>
        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
            value="{{ old('start_date', $tournament?->start_date?->format('Y-m-d') ?? '') }}">
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">End Date <span class="text-danger">*</span></label>
        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
            value="{{ old('end_date', $tournament?->end_date?->format('Y-m-d') ?? '') }}">
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Registration Deadline <span class="text-danger">*</span></label>
        <input type="datetime-local" name="registration_deadline" class="form-control @error('registration_deadline') is-invalid @enderror"
            value="{{ old('registration_deadline', $tournament?->registration_deadline?->format('Y-m-d\TH:i') ?? '') }}">
        @error('registration_deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Entry Fees (PKR) <span class="text-danger">*</span></label>
        <input type="number" name="entry_fees" min="0" step="1" class="form-control @error('entry_fees') is-invalid @enderror"
            value="{{ old('entry_fees', $tournament?->entry_fees ?? 0) }}">
        @error('entry_fees')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Prize Pool (PKR) <span class="text-danger">*</span></label>
        <input type="number" name="prize_pool" min="0" step="1" class="form-control @error('prize_pool') is-invalid @enderror"
            value="{{ old('prize_pool', $tournament?->prize_pool ?? 0) }}">
        @error('prize_pool')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if($tournament)
        <div class="col-12 col-md-6">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach(['pending' => 'Pending', 'soft_accepted' => 'Soft Accepted', 'confirmed' => 'Confirmed', 'rejected' => 'Rejected', 'open' => 'Open', 'full' => 'Full', 'closed' => 'Closed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('status', $tournament?->status) === $val)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif

    <div class="col-12">
        <label class="form-label d-block">Eligible Player Levels <span class="text-danger">*</span></label>
        @php
            $oldLevels = old('player_level', $tournament?->player_level ?? []);
        @endphp
        <div class="d-flex flex-wrap gap-3 mt-1">
            @foreach(['BEGINNER' => 'Beginner', 'INTERMEDIATE' => 'Intermediate', 'ADVANCED' => 'Advanced', 'PROFESSIONAL' => 'Professional', 'OPEN' => 'Open Level'] as $level => $label)
                <div class="form-check">
                    <input type="checkbox" name="player_level[]" value="{{ $level }}" id="level_{{ $level }}" class="form-check-input"
                        @checked(in_array($level, $oldLevels, true))>
                    <label class="form-check-label" for="level_{{ $level }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
        @error('player_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Tournament Cover Image</label>
        <input type="file" name="tournament_image" class="form-control @error('tournament_image') is-invalid @enderror">
        @error('tournament_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Rules & Regulations</label>
        <textarea name="rules" rows="4" class="form-control @error('rules') is-invalid @enderror"
            placeholder="Describe any special rules for match reporting, referee decisions, or equipment...">{{ old('rules', $tournament?->rules ?? '') }}</textarea>
        @error('rules')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('my-styles')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 0.15rem 0.5rem;
            min-height: 38px;
            background-color: #fff;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #8592a3;
            outline: 0;
            box-shadow: 0 0 0.25rem 0.05rem rgba(133, 146, 163, 0.25);
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f0f2f4;
            border: 1px solid #d9dee3;
            border-radius: 0.25rem;
            color: #566a7f;
            font-size: 0.8125rem;
            padding: 2px 6px;
            margin-top: 4px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #ff3e1d;
            margin-right: 5px;
            border: none;
            background: none;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            background: none;
            color: #e6381a;
        }
        .select2-container--default .select2-search--inline .select2-search__field {
            margin-top: 6px;
        }
    </style>
@endpush

@push('my-script')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 on opponent_club_id, scorer_ids, and umpire_ids
            const $opponentSelect = $('#opponent_club_id').select2({
                placeholder: "Select Opponent Clubs",
                allowClear: true,
                width: '100%'
            });
            const $scorerSelect = $('#scorer_ids').select2({
                placeholder: "Select Scorers",
                allowClear: true,
                width: '100%'
            });
            const $umpireSelect = $('#umpire_ids').select2({
                placeholder: "Select Umpires",
                allowClear: true,
                width: '100%'
            });

            const typeSelect = document.getElementById('tournament_type');
            const opponentContainer = document.getElementById('opponent_club_container');
            const opponentSelectRaw = document.getElementById('opponent_club_id');
            const scorerContainer = document.getElementById('scorer_container');
            const umpireContainer = document.getElementById('umpire_container');

            function toggleOpponent() {
                if (typeSelect.value === 'CLUB_TO_CLUB') {
                    opponentContainer.style.display = 'block';
                    opponentSelectRaw.setAttribute('required', 'required');
                    scorerContainer.style.display = 'block';
                    umpireContainer.style.display = 'block';
                } else {
                    opponentContainer.style.display = 'none';
                    opponentSelectRaw.removeAttribute('required');
                    scorerContainer.style.display = 'none';
                    umpireContainer.style.display = 'none';
                    $opponentSelect.val(null).trigger('change');
                    $scorerSelect.val(null).trigger('change');
                    $umpireSelect.val(null).trigger('change');
                }
            }

            typeSelect.addEventListener('change', toggleOpponent);
            toggleOpponent();
        });
    </script>
@endpush
