@php
    $dStatus = strtolower($data->delivery_status ?? '');
    $label = 'Livré';
    $badgeClass = 'bg-soft-success';

    if ($dStatus === 'delivered' || $dStatus === 'livre' || $dStatus === 'livré') {
        $label = 'Livré';
        $badgeClass = 'bg-soft-success';
    } elseif ($dStatus === 'cancelled' || $dStatus === 'annule' || $dStatus === 'annulé') {
        $label = 'Annulé';
        $badgeClass = 'bg-soft-danger';
    } elseif ($dStatus === 'order_placed' || $dStatus === 'pending' || $dStatus === 'en_attente') {
        $label = 'Commande passée';
        $badgeClass = 'bg-soft-info';
    } elseif ($dStatus === 'processing' || $dStatus === 'en_cours') {
        $label = 'En cours';
        $badgeClass = 'bg-soft-warning text-dark';
    } else {
        $label = Str::title(Str::replace('_', ' ', $data->delivery_status));
        $badgeClass = 'bg-soft-info';
    }
@endphp
<span class="badge {{ $badgeClass }} rounded-pill text-capitalize">
    {{ $label }}
</span>
