@php
    $status = strtolower($data->payment_status ?? '');
    $label = 'Payé';
    $badgeClass = 'bg-soft-success';

    if ($status === 'unpaid' || $status === 'non_paye' || $status === 'non payé') {
        $label = 'Non payé';
        $badgeClass = 'bg-soft-danger';
    } elseif ($status === 'pending' || $status === 'en attente') {
        $label = 'En attente';
        $badgeClass = 'bg-soft-warning text-dark';
    } elseif ($status === 'paid' || $status === 'paye' || $status === 'payé') {
        $label = 'Payé';
        $badgeClass = 'bg-soft-success';
    }
@endphp
<span class="badge {{ $badgeClass }} rounded-pill text-capitalize">
    {{ $label }}
</span>
