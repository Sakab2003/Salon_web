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
          $isAdmin = auth()->user()->hasRole('admin');
          $targetBranchId = auth()->user()->branch_id ?? 1;
          $publicShareUrl = url('/reservation-rapide?salon_id=' . $targetBranchId);
          $branches = \App\Models\Branch::all();
        @endphp
        <div class="p-3 mb-4 rounded-3" style="background: rgba(111, 66, 193, 0.05); border: 1px dashed rgba(111, 66, 193, 0.3);">
          @if($isAdmin)
            <div class="form-group mb-2">
              <label class="form-label small fw-bold text-dark">Sélectionnez le Salon à Partager :</label>
              <select id="admin-share-branch-select" class="form-select border-primary fw-bold" onchange="updateShareLinks(this.value)">
                @foreach($branches as $branch)
                  <option value="{{ $branch->id }}" {{ $branch->id == $targetBranchId ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
              </select>
            </div>
          @else
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <small class="text-uppercase text-muted fw-bold d-block">Salon Sélectionné</small>
                <strong class="fs-6 text-dark"><i class="fa-solid fa-store me-1 text-primary"></i> {{ auth()->user()->branch->name ?? 'Salon Principal' }}</strong>
              </div>
            </div>
          @endif
          <div class="text-end mt-2">
            <a href="{{ $publicShareUrl }}" target="_blank" id="btn-open-public-link" class="btn btn-sm btn-outline-primary rounded-pill px-3">
              <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ouvrir la Page Publique
            </a>
          </div>
        </div>

        <p class="small text-muted fw-semibold mb-3">Partager directement sur :</p>
        <div class="row g-3 mb-4 text-center">
          <div class="col-3">
            <a href="https://api.whatsapp.com/send?text={{ urlencode('Bonjour ! Réservez directement vos prestations en suivant ce lien : ' . $publicShareUrl) }}" id="btn-share-whatsapp" target="_blank" class="d-flex flex-column align-items-center p-2 text-decoration-none border-0 bg-transparent">
              <i class="fa-brands fa-whatsapp mb-1" style="color: #25D366; font-size: 2.3rem;"></i>
              <span class="small fw-bold text-dark" style="font-size:12px;">WhatsApp</span>
            </a>
          </div>
          <div class="col-3">
            <a href="javascript:void(0)" id="btn-share-tiktok" onclick="navigator.clipboard.writeText(document.getElementById('share-link-input-blade-cal').value); alert('Lien copié ! Collez-le dans votre profil ou vidéo TikTok.');" class="d-flex flex-column align-items-center p-2 text-decoration-none border-0 bg-transparent">
              <i class="fa-brands fa-tiktok mb-1" style="color: #000000; font-size: 2.3rem;"></i>
              <span class="small fw-bold text-dark" style="font-size:12px;">TikTok</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($publicShareUrl) }}" id="btn-share-facebook" target="_blank" class="d-flex flex-column align-items-center p-2 text-decoration-none border-0 bg-transparent">
              <i class="fa-brands fa-facebook-f mb-1" style="color: #1877F2; font-size: 2.3rem;"></i>
              <span class="small fw-bold text-dark" style="font-size:12px;">Facebook</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://twitter.com/intent/tweet?text={{ urlencode('Bonjour ! Réservez directement vos prestations en suivant ce lien : ' . $publicShareUrl) }}" id="btn-share-twitter" target="_blank" class="d-flex flex-column align-items-center p-2 text-decoration-none border-0 bg-transparent">
              <i class="fa-brands fa-x-twitter mb-1" style="color: #000000; font-size: 2.3rem;"></i>
              <span class="small fw-bold text-dark" style="font-size:12px;">X / Twitter</span>
            </a>
          </div>
          <div class="col-3">
            <a href="javascript:void(0)" id="btn-share-instagram" onclick="navigator.clipboard.writeText(document.getElementById('share-link-input-blade-cal').value); alert('Lien copié pour Instagram !');" class="d-flex flex-column align-items-center p-2 text-decoration-none border-0 bg-transparent">
              <i class="fa-brands fa-instagram mb-1" style="color: #E4405F; font-size: 2.3rem;"></i>
              <span class="small fw-bold text-dark" style="font-size:12px;">Instagram</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://www.threads.net/intent/post?text={{ urlencode('Bonjour ! Réservez vos prestations : ' . $publicShareUrl) }}" id="btn-share-threads" target="_blank" class="d-flex flex-column align-items-center p-2 text-decoration-none border-0 bg-transparent">
              <i class="fa-brands fa-threads mb-1" style="color: #000000; font-size: 2.3rem;"></i>
              <span class="small fw-bold text-dark" style="font-size:12px;">Threads</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://www.snapchat.com/scan?attachmentUrl={{ urlencode($publicShareUrl) }}" id="btn-share-snapchat" target="_blank" class="d-flex flex-column align-items-center p-2 text-decoration-none border-0 bg-transparent">
              <i class="fa-brands fa-snapchat mb-1" style="color: #e6c200; font-size: 2.3rem;"></i>
              <span class="small fw-bold text-dark" style="font-size:12px;">Snapchat</span>
            </a>
          </div>
          <div class="col-3">
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($publicShareUrl) }}" id="btn-share-linkedin" target="_blank" class="d-flex flex-column align-items-center p-2 text-decoration-none border-0 bg-transparent">
              <i class="fa-brands fa-linkedin-in mb-1" style="color: #0A66C2; font-size: 2.3rem;"></i>
              <span class="small fw-bold text-dark" style="font-size:12px;">LinkedIn</span>
            </a>
          </div>
        </div>

        <div class="form-group mb-2">
          <label class="form-label small fw-bold">Lien de réservation directe du salon (Page Inscription Client) :</label>
          <div class="input-group">
            <input type="text" readonly class="form-control form-control-sm bg-light text-primary fw-bold" id="share-link-input-blade-cal" value="{{ $publicShareUrl }}" />
            <a href="{{ $publicShareUrl }}" target="_blank" id="btn-test-link" class="btn btn-outline-secondary btn-sm px-2" title="Tester le lien">
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
<script>
  function updateShareLinks(branchId) {
    const baseUrl = '{{ url('/reservation-rapide') }}';
    const newUrl = baseUrl + '?salon_id=' + branchId;
    
    // Mettre à jour l'input
    document.getElementById('share-link-input-blade-cal').value = newUrl;
    
    // Mettre à jour "Ouvrir la Page Publique"
    document.getElementById('btn-open-public-link').href = newUrl;
    document.getElementById('btn-test-link').href = newUrl;
    
    // Mettre à jour les liens sociaux
    document.getElementById('btn-share-whatsapp').href = 'https://api.whatsapp.com/send?text=' + encodeURIComponent('Bonjour ! Réservez directement vos prestations en suivant ce lien : ' + newUrl);
    document.getElementById('btn-share-facebook').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(newUrl);
    document.getElementById('btn-share-twitter').href = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent('Bonjour ! Réservez directement vos prestations en suivant ce lien : ' + newUrl);
    document.getElementById('btn-share-linkedin').href = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(newUrl);
    document.getElementById('btn-share-snapchat').href = 'https://www.snapchat.com/scan?attachmentUrl=' + encodeURIComponent(newUrl);
    document.getElementById('btn-share-threads').href = 'https://www.threads.net/intent/post?text=' + encodeURIComponent('Bonjour ! Réservez vos prestations : ' + newUrl);
  }
</script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="{{ mix("modules/booking/script.js") }}"></script>

@endpush
