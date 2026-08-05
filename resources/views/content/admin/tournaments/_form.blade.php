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
        <label class="form-label">Opponent Club <span class="text-danger" id="opponent_asterisk">*</span></label>
        <select name="opponent_club_id" id="opponent_club_id" class="form-select @error('opponent_club_id') is-invalid @enderror">
            <option value="">Select Opponent Club</option>
            @foreach($clubs as $club)
                <option value="{{ $club->id }}" @selected(old('opponent_club_id', is_array($tournament?->opponent_club_id) ? ($tournament->opponent_club_id[0] ?? '') : ($tournament?->opponent_club_id ?? '')) == $club->id)>
                    {{ $club->club_name ?? $club->name }} ({{ $club->city ?? 'No City' }})
                </option>
            @endforeach
        </select>
        @error('opponent_club_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('tournament_type');
        const opponentContainer = document.getElementById('opponent_club_container');
        const opponentSelect = document.getElementById('opponent_club_id');

        function toggleOpponent() {
            if (typeSelect.value === 'CLUB_TO_CLUB') {
                opponentContainer.style.display = 'block';
                opponentSelect.setAttribute('required', 'required');
            } else {
                opponentContainer.style.display = 'none';
                opponentSelect.removeAttribute('required');
                opponentSelect.value = '';
            }
        }

        typeSelect.addEventListener('change', toggleOpponent);
        toggleOpponent();
    });
</script>
