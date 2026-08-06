@if(isset($data->payment))

@if($data->payment->payment_status != 1)
    <select name="branch_for" class="select2 change-select" data-token="{{csrf_token()}}"
        data-url="{{route('backend.bookings.updatePaymentStatus', ['id' => $data->id, 'action_type' => 'update-payment-status'])}}"
        style="width: 100%;">
        @foreach ($payment_status as $key => $value )
        @php
          $payName = $value->value == 1 ? 'Payé' : 'En attente';
        @endphp
        <option value="{{$value->value}}" {{$data->payment->payment_status  == $value->value ? 'selected' : ''}}>
            {{ $payName }}</option>
        @endforeach
    </select>

@else

@foreach ($payment_status as $key => $value )

@if(isset($data->payment))
    @if($data->payment->payment_status==$value->value)
    @php
      $payName = $value->value == 1 ? 'Payé' : 'En attente';
    @endphp
    <span class="text-capitalize badge bg-soft-info p-3">{{ $payName }}</span>
    @endif
@endif

@endforeach

@endif 

@else

<select name="branch_for" class="select2 change-select" data-token="{{csrf_token()}}"
        data-url="{{route('backend.bookings.updatePaymentStatus', ['id' => $data->id, 'action_type' => 'update-payment-status'])}}"
        style="width: 100%;">
        @foreach ($payment_status as $key => $value )
        @php
          $payName = $value->value == 1 ? 'Payé' : 'En attente';
        @endphp
        <option value="{{$value->value}}">
            {{ $payName }}</option>
        @endforeach
    </select>

@endif




