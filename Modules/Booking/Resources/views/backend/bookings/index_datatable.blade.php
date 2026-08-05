@extends('backend.layouts.app')

@section('title')
{{ __($module_action) }} {{ __($module_title) }}
@endsection
@section('banner-button')
<button type="button" class="btn btn-primary me-2 shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#shareBookingBladeModal">
  <i class="fa-solid fa-share-nodes me-1"></i> Partager le Salon
</button>
<a href="{{route("backend.$module_name.index")}}" class="btn btn-soft-dark"><i
        class="fa-solid fa-calendar-days me-2"></i>{{ __('messages.calender_view') }}</a>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <x-backend.section-header>
          <div class="d-flex flex-wrap gap-3">
              @if(auth()->user()->can('edit_booking') || auth()->user()->can('delete_booking'))
                <x-backend.quick-action url="{{route('backend.bookings.bulk_action')}}">
                    <div class="">
                        <select name="action_type" class="form-control select2 col-12" id="quick-action-type"
                            style="width:100%">
                            <option value="">{{ __('messages.no_action') }}</option>
                            @can('edit_booking')
                            <option value="change-status">{{ __('messages.status') }}</option>
                            @endcan
                            @can('delete_booking')
                            <option value="delete">{{ __('messages.delete') }}</option>
                            @endcan
                        </select>
                    </div>
                    <div class="select-status d-none quick-action-field" id="change-status-action">
                        <select name="status" class="form-control select2" id="status" style="width:100%">
                            @foreach ($booking_status as $key => $value)
                            <option value="{{ $value->name }}" {{$filter['status'] == $value->name ? "selected" : ''}}>
                                {{ $value->value }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-backend.quick-action>
                @endif
              <div>
                <button type="button" class="btn btn-secondary" data-modal="export">
                  <i class="fa-solid fa-download"></i> {{ __('messages.export') }}
                </button>
  {{--          <button type="button" class="btn btn-secondary" data-modal="import">--}}
  {{--            <i class="fa-solid fa-upload"></i> Import--}}
  {{--          </button>--}}
              </div>
            </div>
            <x-slot name="toolbar">
                <div>
                    <div class="datatable-filter">
                        <select name="column_status" id="column_status" class="select2 form-control p-10"
                            data-filter="select" style="width: 100%">
                            <option value="">{{ __('messages.all_status') }}</option>
                            @foreach ($booking_status as $key => $value)
                            <option value="{{ $value->name }}" {{$filter['status'] == $value->name ? "selected" : ''}}>
                                {{ $value->value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="input-group flex-nowrap">
                    <span class="input-group-text" id="addon-wrapping"><i
                            class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control dt-search" placeholder="{{ __('messages.search') }}..." aria-label="Search"
                        aria-describedby="addon-wrapping">
                </div>
                <button class="btn btn-outline-primary btn-group" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasExample" aria-controls="offcanvasExample"><i
                        class="fa-solid fa-filter"></i> {{__('messages.advance_filter')}}</button>
            </x-slot>
        </x-backend.section-header>
    </div>
    <div class="card-body" id="booking-datatable">
        <table id="datatable" class="table table-striped border table-responsive">
        </table>
    </div>
    <x-backend.advance-filter>
        <x-slot name="title">
            <h4> {{ __('booking.lbl_advanced_filter') }}</h4>
        </x-slot>
        <form action="javascript:void(0)" class="datatable-filter">
            <div class="form-group">
                <label for="form-label"> {{ __('booking.lbl_booking_date') }}</label>
                <input type="text" name="booking_date" id="booking_date" class="booking-date-range form-control"
                    readonly />
            </div>
            <div class="form-group">
                <label for="form-label"> {{ __('booking.lbl_customer_name') }} </label>
                <select name="filter_user_id" id="column_user_id" name="column_user_id" data-filter="select"
                    class="select2 form-control"
                    data-ajax--url="{{ route('backend.get_search_data', ['type' => 'customers']) }}"
                    data-ajax--cache="true">
                </select>
            </div>
            <div class="form-group">
                <label for="form-label"> {{ __('booking.lbl_staff_name') }} </label>
                <select name="filter_employee_id" id="column_employee_id" name="column_employee_id" data-filter="select"
                    class="select2 form-control"
                    data-ajax--url="{{ route('backend.get_search_data', ['type' => 'employees']) }}"
                    data-ajax--cache="true">
                </select>
            </div>
            <div class="form-group">
                <label for="form-label"> {{ __('booking.lbl_services') }} </label>
                <select name="filter_service_id" id="column_service_id" name="column_service_id[]" data-filter="select"
                    class="select2 form-control" multiple
                    data-ajax--url="{{ route('backend.get_search_data', ['type' => 'services']) }}"
                    data-ajax--cache="true">
                </select>
            </div>
            <button type="reset" class="btn btn-danger" id="reset-filter">{{ __('messages.reset') }}</button>
        </form>
    </x-backend.advance-filter>
</div>
@endsection

@push('after-styles')
<!-- DataTables Core and Extensions -->
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
<script src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
<script src="{{ mix('modules/booking/script.js') }}"></script>
<script src="{{ asset('js/form-modal/index.js') }}" defer></script>
<!-- DataTables Core and Extensions -->

<script type="text/javascript">
const range_flatpicker = document.querySelectorAll('.booking-date-range')
Array.from(range_flatpicker, (elem) => {
    if (typeof flatpickr !== typeof undefined) {
        flatpickr(elem, {
            mode: "range",
            dateFormat: "Y-m-d",
        })
    }
})
const columns = [{
        name: 'check',
        data: 'check',
        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
        width: '0%',
        exportable: false,
        orderable: false,
        searchable: false,
    },
    {
        data: 'start_date_time',
        name: 'start_date_time',
        title: "{{ __('booking.lbl_date') }}",
        orderable: true,
    },
    {
        data: 'user_id',
        name: 'user_id',
        title: "{{ __('booking.lbl_customer_name') }}"
    },
    {
        data: 'service_amount',
        name: 'service_amount',
        title: "{{ __('booking.lbl_amount') }}",
        orderable: true,
        searchable: false,
        // render: function(data, type, row) {

        //     return currencyFormat(data);

        // }
    },
    {
        data: 'service_duration',
        name: 'service_duration',
        title: "{{ __('booking.lbl_duration') }}",
        orderable: true,
        searchable: false,
    },
    {
        data: 'employee_id',
        name: 'employee_id',
        title: "{{ __('booking.lbl_staff_name') }}"
    },
    {
        data: 'services',
        name: 'services',
        title: "{{ __('booking.lbl_services') }}",
        orderable: false,
        searchable: true,
        width: '10%'
    },
    {
        data: 'updated_at',
        name: 'updated_at',
        title: "{{ __('booking.lbl_update_at') }}",
        orderable: true,
    },
    {
        data: 'status',
        name: 'status',
        orderable: true,
        searchable: true,
        title: "{{ __('booking.lbl_status') }}",
        width: '10%',
    },
    {
      data: 'payment_status',
      name: 'payment_status',
      orderable: false,
      searchable: false,
      title: "{{ __('booking.lbl_payment_status') }}",
      width: '10%',
    },
]

const actionColumn = [{
    data: 'action',
    name: 'action',
    orderable: false,
    searchable: false,
    title: "{{ __('booking.lbl_action') }}",
    width: '10%'
}]

let finalColumns = [
    ...columns,
    ...actionColumn
]

document.addEventListener('DOMContentLoaded', (event) => {
    initDatatable({
      url: '{{ route("backend.$module_name.index_data") }}',
      finalColumns,
      orderColumn: [[ 7, "desc" ]],
      advanceFilter: () => {
        return {
          booking_date: $('#booking_date').val(),
          user_id: $('#column_user_id').val(),
          emploee_id: $('#column_employee_id').val(),
          service_id: $('#column_service_id').val(),
        }
      }
    })
})
const offcanvasElem = document.querySelector('#offcanvasExample')
offcanvasElem.addEventListener('shown.bs.offcanvas', function() {
    $('form.datatable-filter .select2').select2({
        dropdownParent: $('#offcanvasExample')
    });
})

$('#reset-filter').on('click', function(e) {
    $('#column_status').val('')
    $('#booking_date').val('')
    $('#column_user_id').val('')
    $('#column_employee_id').val('')
    $('#column_service_id').val('')
    $('form.datatable-filter .select2').empty()
    $('form.datatable-filter .select2').select2()

    const range_flatpickers = document.querySelectorAll('.booking-date-range');
    Array.from(range_flatpickers, (elem) => {
        const flatpickrInstance = elem._flatpickr;
        if (flatpickrInstance) {
            flatpickrInstance.clear();
        }
    });

    window.renderedDataTable.ajax.reload(null, false)
})

$('#booking_date').on('change', function() {
    window.renderedDataTable.ajax.reload(null, false)
})



function resetQuickAction() {
    const actionValue = $('#quick-action-type').val();
    if (actionValue != '') {
        $('#quick-action-apply').removeAttr('disabled');

        if (actionValue == 'change-status') {
            $('.quick-action-field').addClass('d-none');
            $('#change-status-action').removeClass('d-none');
        } else {
            $('.quick-action-field').addClass('d-none');
        }
    } else {
        $('#quick-action-apply').attr('disabled', true);
        $('.quick-action-field').addClass('d-none');
    }
}

$('#quick-action-type').change(function() {
    resetQuickAction()
});
</script>

<!-- Modal de Partage Réseaux Sociaux Next-Gen -->
<div class="modal fade" id="shareBookingBladeModal" tabindex="-1" aria-labelledby="shareBookingBladeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: rgba(255,255,255,0.98); backdrop-filter: blur(10px);">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-2">
          <div class="p-2 rounded-3 text-white" style="background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);">
            <i class="fa-solid fa-share-nodes fs-5"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0 text-primary" id="shareBookingBladeModalLabel">Inviter des Clients au Salon</h5>
            <small class="text-muted">Partagez l'accès au salon sur les réseaux sociaux</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body py-4">
        @php
          $targetBranchId = auth()->user()->branch_id ?? 1;
          $publicShareUrl = url('/quick-booking?branch_id=' . $targetBranchId);
        @endphp
        <div class="p-3 mb-4 rounded-3 d-flex align-items-center justify-content-between" style="background: rgba(111, 66, 193, 0.05); border: 1px dashed rgba(111, 66, 193, 0.3);">
          <div>
            <small class="text-uppercase text-muted fw-bold d-block">Salon Sélectionné (Multi-Vendor)</small>
            <strong class="fs-6 text-dark"><i class="fa-solid fa-store me-1 text-primary"></i> {{ auth()->user()->branch->name ?? 'Salon Principal' }}</strong>
          </div>
          <a href="{{ $publicShareUrl }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ouvrir la Page Publique
          </a>
        </div>

        <p class="small text-muted fw-semibold mb-3">Partager directement sur :</p>
        <div class="row g-3 mb-4 text-center">
          <div class="col-3">
            <a href="https://api.whatsapp.com/send?text={{ urlencode('Bonjour ! Réservez directement vos prestations en suivant ce lien : ' . $publicShareUrl) }}" target="_blank" class="d-flex flex-column align-items-center p-2 rounded-3 text-decoration-none text-white shadow-sm" style="background: linear-gradient(135deg, #25D366, #128C7E);">
              <i class="fa-brands fa-whatsapp fs-3 mb-1"></i>
              <span class="small" style="font-size:11px;">WhatsApp</span>
            </a>
          </div>
          <div class="col-3">
            <a href="javascript:void(0)" onclick="navigator.clipboard.writeText('{{ $publicShareUrl }}'); alert('Lien copié ! Collez-le dans votre profil ou vidéo TikTok.');" class="d-flex flex-column align-items-center p-2 rounded-3 text-decoration-none text-white shadow-sm" style="background: linear-gradient(135deg, #000000, #25F4EE 50%, #FE2C55);">
              <i class="fa-brands fa-tiktok fs-3 mb-1"></i>
              <span class="small" style="font-size:11px;">TikTok</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($publicShareUrl) }}" target="_blank" class="d-flex flex-column align-items-center p-2 rounded-3 text-decoration-none text-white shadow-sm" style="background: linear-gradient(135deg, #1877F2, #0d52ab);">
              <i class="fa-brands fa-facebook-f fs-3 mb-1"></i>
              <span class="small" style="font-size:11px;">Facebook</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://twitter.com/intent/tweet?text={{ urlencode('Bonjour ! Réservez directement vos prestations en suivant ce lien : ' . $publicShareUrl) }}" target="_blank" class="d-flex flex-column align-items-center p-2 rounded-3 text-decoration-none text-white shadow-sm" style="background: linear-gradient(135deg, #14171A, #000000);">
              <i class="fa-brands fa-x-twitter fs-3 mb-1"></i>
              <span class="small" style="font-size:11px;">X / Twitter</span>
            </a>
          </div>
          <div class="col-3">
            <a href="javascript:void(0)" onclick="navigator.clipboard.writeText('{{ $publicShareUrl }}'); alert('Lien copié pour Instagram !');" class="d-flex flex-column align-items-center p-2 rounded-3 text-decoration-none text-white shadow-sm" style="background: linear-gradient(135deg, #f09433, #dc2743);">
              <i class="fa-brands fa-instagram fs-3 mb-1"></i>
              <span class="small" style="font-size:11px;">Instagram</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://www.threads.net/intent/post?text={{ urlencode('Bonjour ! Réservez vos prestations : ' . $publicShareUrl) }}" target="_blank" class="d-flex flex-column align-items-center p-2 rounded-3 text-decoration-none text-white shadow-sm" style="background: linear-gradient(135deg, #101010, #333333);">
              <i class="fa-brands fa-threads fs-3 mb-1"></i>
              <span class="small" style="font-size:11px;">Threads</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://www.snapchat.com/scan?attachmentUrl={{ urlencode($publicShareUrl) }}" target="_blank" class="d-flex flex-column align-items-center p-2 rounded-3 text-decoration-none text-dark shadow-sm" style="background: linear-gradient(135deg, #FFFC00, #E0DC00);">
              <i class="fa-brands fa-snapchat fs-3 mb-1"></i>
              <span class="small" style="font-size:11px;">Snapchat</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($publicShareUrl) }}" target="_blank" class="d-flex flex-column align-items-center p-2 rounded-3 text-decoration-none text-white shadow-sm" style="background: linear-gradient(135deg, #0A66C2, #004182);">
              <i class="fa-brands fa-linkedin-in fs-3 mb-1"></i>
              <span class="small" style="font-size:11px;">LinkedIn</span>
            </a>
          </div>
        </div>

        <div class="form-group mb-2">
          <label class="form-label small fw-bold">Lien de réservation directe du salon (Page Inscription Client) :</label>
          <div class="input-group">
            <input type="text" readonly class="form-control form-control-sm bg-light text-primary fw-bold" id="share-link-input-blade" value="{{ $publicShareUrl }}" />
            <a href="{{ $publicShareUrl }}" target="_blank" class="btn btn-outline-secondary btn-sm px-2" title="Tester le lien">
              <i class="fa-solid fa-external-link"></i>
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3" onclick="navigator.clipboard.writeText(document.getElementById('share-link-input-blade').value); alert('Lien copié !');">
              <i class="fa-regular fa-copy me-1"></i> Copier
            </button>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill w-100 fw-semibold" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
@endpush
