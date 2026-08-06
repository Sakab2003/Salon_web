@if($data->status != 'completed')
<select name="branch_for" class="select2 change-select" data-token="{{csrf_token()}}" data-url="{{route('backend.bookings.updateStatus', ['id' => $data->id, 'action_type' => 'update-status'])}}" style="width: 100%;">
  @foreach ($booking_status as $key => $value )
    @php
    $color = optional($booking_colors->where('sub_type', $data->status)->first())->name ?? '#3ABA61';
    @endphp
    <option value="{{$value->name}}" {{$data->status == $value->name ? 'selected' : ''}} data-color="{{ $color }}">{{$value->value}}</option>
  @endforeach
</select>
@else
@php
$color = optional($booking_colors->where('sub_type', $data->status)->first())->name ?? '#3ABA61';
$statusText = [
    'pending' => 'En attente',
    'confirmed' => 'Confirmé',
    'check_in' => 'Enregistré',
    'checkout' => 'Paiement / Encaissé',
    'cancelled' => 'Annulé',
    'completed' => 'Terminé',
][$data->status] ?? $data->status;
@endphp
<span class="text-capitalize"><i class="fa-solid fa-circle" style="color: {{ $color }}"></i> {{ $statusText }}</span>
@endif
