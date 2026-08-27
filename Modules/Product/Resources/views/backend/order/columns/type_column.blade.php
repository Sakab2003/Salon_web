@php
    $type = strtolower($data->orderGroup->type ?? '');
    $label = 'En ligne';
    $badgeClass = 'bg-soft-primary';

    if ($type === 'booking' || $type === 'reservation') {
        $label = 'Réservation';
        $badgeClass = 'bg-soft-danger';
    } elseif ($type === 'pos' || $type === 'direct') {
        $label = 'Vente directe (POS)';
        $badgeClass = 'bg-soft-success';
    } else {
        $label = 'En ligne';
        $badgeClass = 'bg-soft-primary';
    }
@endphp
<span class="badge {{ $badgeClass }} rounded-pill text-capitalize">
    {{ $label }}
</span>