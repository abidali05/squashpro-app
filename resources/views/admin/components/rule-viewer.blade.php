@php
    $data = $rules ?? null;
@endphp

@if(empty($data))
    <div class="text-muted small py-2">
        <i class="mdi mdi-information-outline me-1"></i> No rules configured for this section.
    </div>
@elseif(is_string($data))
    <div class="p-3 bg-light rounded text-dark small" style="white-space: pre-wrap; line-height: 1.6;">{{ $data }}</div>
@elseif(is_array($data))
    @if(array_is_list($data))
        <div class="list-group list-group-flush rounded border mb-0">
            @foreach($data as $item)
                <div class="list-group-item bg-transparent px-3 py-2 text-dark small d-flex align-items-center gap-2">
                    <i class="mdi mdi-circle-small text-primary fs-5"></i>
                    <span>{{ is_array($item) ? json_encode($item, JSON_UNESCAPED_SLASHES) : $item }}</span>
                </div>
            @endforeach
        </div>
    @else
        <div class="row g-3">
            @foreach($data as $key => $val)
                <div class="col-sm-6 col-md-4">
                    <div class="p-3 rounded border bg-light h-100 shadow-xs">
                        <small class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                            {{ Illuminate\Support\Str::headline($key) }}
                        </small>
                        <div class="fw-semibold text-dark fs-6">
                            @if(is_bool($val))
                                @if($val)
                                    <span class="badge bg-label-success px-2 py-1"><i class="mdi mdi-check-circle me-1"></i>Yes / Enabled</span>
                                @else
                                    <span class="badge bg-label-secondary px-2 py-1"><i class="mdi mdi-minus-circle me-1"></i>No / Disabled</span>
                                @endif
                            @elseif(is_null($val))
                                <span class="text-muted">—</span>
                            @elseif(is_array($val))
                                @if(array_is_list($val))
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($val as $v)
                                            <span class="badge bg-label-info">{{ is_array($v) ? json_encode($v) : $v }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="ps-2 border-start border-2 border-primary mt-1">
                                        @foreach($val as $subK => $subV)
                                            <div class="small mb-1">
                                                <span class="text-muted">{{ Illuminate\Support\Str::headline($subK) }}:</span>
                                                <span class="fw-semibold ms-1">
                                                    @if(is_bool($subV))
                                                        <span class="badge bg-label-{{ $subV ? 'success' : 'secondary' }} btn-xs">{{ $subV ? 'Yes' : 'No' }}</span>
                                                    @else
                                                        {{ is_array($subV) ? json_encode($subV) : $subV }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                {{ $val }}
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@else
    <div class="text-dark small">{{ (string) $data }}</div>
@endif
