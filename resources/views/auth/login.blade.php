<x-auth-layout>
  <x-slot name="title">
    @lang('Login')
  </x-slot>

  <x-auth-card>
    <x-slot name="logo">
      <a href="/">
        <x-application-logo />
      </a>
      <h2 class="mt-4 mb-2">Bienvenue</h2>
      <p class="text-gray-600 mb-0">Veuillez vous connecter à votre compte</p>
    </x-slot>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Social Login -->
    <x-auth-social-login />

    <!-- Validation Errors -->
    <x-auth-validation-errors class="mb-4" :errors="$errors" />

    <form method="POST" action="{{ $url ?? route('login') }}">
      @csrf

      <!-- Email Address -->
      <div class="mb-4">
        <x-label for="email" :value="__('Adresse Email')" class="mb-2 fw-semibold" />
        <div class="position-relative">
          <span class="position-absolute top-50 translate-middle-y ms-3 opacity-50" style="left: 0; z-index: 10;">
            <i class="fas fa-envelope"></i>
          </span>
          <x-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="exemple@salon.com" class="ps-5" style="padding-left: 2.5rem !important;" />
        </div>
      </div>

      <!-- Password -->
      <div class="mb-4">
        <x-label for="password" :value="__('Mot de passe')" class="mb-2 fw-semibold" />
        <div class="position-relative">
          <span class="position-absolute top-50 translate-middle-y ms-3 opacity-50" style="left: 0; z-index: 10;">
            <i class="fas fa-lock"></i>
          </span>
          <x-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="ps-5" style="padding-left: 2.5rem !important;" />
        </div>
      </div>

      <!-- Remember Me -->
      <div class="mb-4 d-flex align-items-center">
        <div class="form-check">
          <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
          <label for="remember_me" class="form-check-label ms-2">
            {{ __('Se souvenir de moi') }}
          </label>
        </div>
      </div>

      <div class="d-grid mt-5">
        <x-button class="w-100 py-3">
          {{ __('Connexion') }}
        </x-button>
      </div>
    </form>

    @if(env('IS_DEMO'))
    <div class="mt-5 pt-4 border-top" style="border-color: rgba(0,0,0,0.1) !important;">
      <h6 class="text-center mb-4 opacity-75">Comptes de Démo</h6>
      <div class="row g-3">
        <div class="col-6">
          <div class="p-3 rounded text-center cursor-pointer demo-account-box" onclick="setLoginCredentials('admin')">
            <p class="mb-1 fw-bold text-sm" id="admin_email">admin@salon.com</p>
            <p class="mb-0 opacity-50 text-xs" id="admin_password">12345678</p>
            <div class="mt-2 text-primary text-xs"><i class="fas fa-copy me-1"></i>Copier Admin</div>
          </div>
        </div>
        <div class="col-6">
          <div class="p-3 rounded text-center cursor-pointer demo-account-box" onclick="setLoginCredentials('employee')">
            <p class="mb-1 fw-bold text-sm" id="employee_email">manager@salon.com</p>
            <p class="mb-0 opacity-50 text-xs" id="employee_password">12345678</p>
            <div class="mt-2 text-primary text-xs"><i class="fas fa-copy me-1"></i>Copier Manager</div>
          </div>
        </div>
      </div>
    </div>
    <style>
      .demo-account-box {
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
      }
      .demo-account-box:hover {
        background: rgba(0, 0, 0, 0.06);
        transform: translateY(-2px);
      }
      .cursor-pointer { cursor: pointer; }
      .text-xs { font-size: 0.75rem; }
    </style>
    @endif

    <x-slot name="extra">
      @if (Route::has('register'))
      <p class="text-center text-gray-600 mb-0">
        Vous n'avez pas de compte ? <a href="{{ route('register') }}" class="fw-bold">Inscrivez-vous</a>
      </p>
      @endif
    </x-slot>
  </x-auth-card>

  <script type="text/javascript">
    function domId (name) {
      return document.getElementById(name)
    }
    function setLoginCredentials(type) {
      domId('email').value = domId(type+'_email').textContent
      domId('password').value = domId(type+'_password').textContent
    }
  </script>
</x-auth-layout>
