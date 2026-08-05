@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection

@push('after-styles')
    {{-- <link rel="stylesheet" href='{{ mix("modules/booking/style.css") }}'> --}}

@endpush
@section('banner-button')
<button type="button" class="btn btn-primary me-2 shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#shareBookingBladeModal">
  <i class="fa-solid fa-share-nodes me-1"></i> Partager le Salon
</button>
@hasPermission('booking_booking_tableview')
<a href="{{route("backend.$module_name.datatable_view")}}" class="btn btn-soft-dark"><i class="fa-solid fa-table"></i> {{ __('messages.datatable_view') }}</a>
@endhasPermission
@endsection
@section('content')
<div class="card">
    <div class="card-body">
      <div data-render="app">
        <calendar-view
          slot-duration="{{ setting('slot_duration') }}"
          status="{{ json_encode($statusList) }}"
          :branch-id="{{ $selected_branch->id ?? 1 }}"
          date={{$date}}
          ></calendar-view>
      </div>
    </div>
</div>

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
            <small class="text-uppercase text-muted fw-bold d-block">Salon Sélectionné</small>
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
            <input type="text" readonly class="form-control form-control-sm bg-light text-primary fw-bold" id="share-link-input-blade-cal" value="{{ $publicShareUrl }}" />
            <a href="{{ $publicShareUrl }}" target="_blank" class="btn btn-outline-secondary btn-sm px-2" title="Tester le lien">
              <i class="fa-solid fa-external-link"></i>
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3" onclick="navigator.clipboard.writeText(document.getElementById('share-link-input-blade-cal').value); alert('Lien copié !');">
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
@endsection

@push ('after-scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="{{ mix("modules/booking/script.js") }}"></script>

@endpush
