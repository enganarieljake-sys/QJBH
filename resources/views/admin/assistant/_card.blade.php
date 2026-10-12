@php
    $labels = [
        'pending' => 'Waiting for Confirm',
        'done' => 'Done',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'undone' => 'Undone',
        'expired' => 'Expired',
    ];
@endphp
<div class="action-card action-card--{{ $card['status'] }}" data-action-card="{{ $card['id'] }}">
    <div class="action-card__head">
        <span class="action-card__title">{{ $card['title'] }}</span>
        <span class="action-card__badge">{{ $labels[$card['status']] ?? $card['status'] }}</span>
    </div>
    <dl class="action-card__lines">
        @foreach ($card['lines'] as [$label, $value])
            <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>

    @if ($card['message'] && $card['status'] !== 'pending')
        <p class="action-card__message">{{ $card['message'] }}</p>
    @endif

    @if ($card['status'] === 'pending')
        <div class="action-card__buttons">
            <form method="POST" action="{{ route('admin.assistant.actions.confirm', $card['id']) }}" data-action-form>
                @csrf
                <button type="submit" class="action-card__confirm">Confirm</button>
            </form>
            <form method="POST" action="{{ route('admin.assistant.actions.cancel', $card['id']) }}" data-action-form>
                @csrf
                <button type="submit" class="action-card__cancel">Cancel</button>
            </form>
        </div>
    @elseif ($card['can_undo'])
        <div class="action-card__buttons">
            <form method="POST" action="{{ route('admin.assistant.actions.undo', $card['id']) }}" data-action-form>
                @csrf
                <button type="submit" class="action-card__cancel">Undo (10 min)</button>
            </form>
        </div>
    @elseif ($card['status'] === 'done' && $card['by'])
        <p class="action-card__by">Confirmed by {{ $card['by'] }} · {{ $card['at'] }}</p>
    @endif
</div>
