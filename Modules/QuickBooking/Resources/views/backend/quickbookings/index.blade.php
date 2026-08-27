@extends('backend.layouts.quick-booking')

@section('title') Quick Booking @endsection

@push('after-styles')
    <link rel="stylesheet" href="/modules/quickbooking/style.css">
@endpush

@section('content')
  <div class="container-fluid px-2 px-md-3 py-2 py-md-4 quick-booking-page">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-11">
        <quick-booking></quick-booking>
      </div>
    </div>
  </div>
@endsection

@push ('after-scripts')
<script src="/modules/quickbooking/script.js"></script>
@endpush
