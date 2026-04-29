@php
    $type = $type ?? 'view';
    $href = $href ?? null;
    $title = $title ?? null;
    $formAction = $formAction ?? null;
    $confirm = $confirm ?? 'Are you sure?';
    $method = $method ?? 'DELETE';

    $config = [
        'view' => ['class' => 'action-icon-view', 'icon' => 'mdi-eye-outline', 'title' => 'View'],
        'edit' => ['class' => 'action-icon-edit', 'icon' => 'mdi-pencil-outline', 'title' => 'Edit'],
        'delete' => ['class' => 'action-icon-delete', 'icon' => 'mdi-trash-can-outline', 'title' => 'Delete'],
        'permission' => ['class' => 'action-icon-permission', 'icon' => 'mdi-shield-key-outline', 'title' => 'Permissions'],
    ][$type] ?? ['class' => 'action-icon-view', 'icon' => 'mdi-eye-outline', 'title' => 'Action'];

    $resolvedTitle = $title ?? $config['title'];
@endphp

@if($formAction)
    <form action="{{ $formAction }}" method="POST" class="d-inline">
        @csrf
        @method($method)
        <button
            type="submit"
            class="action-icon-btn {{ $config['class'] }}"
            data-bs-toggle="tooltip"
            title="{{ $resolvedTitle }}"
            onclick="return confirm('{{ $confirm }}')"
        >
            <i class="mdi {{ $config['icon'] }}"></i>
        </button>
    </form>
@elseif($href)
    <a href="{{ $href }}" class="action-icon-btn {{ $config['class'] }}" data-bs-toggle="tooltip" title="{{ $resolvedTitle }}">
        <i class="mdi {{ $config['icon'] }}"></i>
    </a>
@endif
