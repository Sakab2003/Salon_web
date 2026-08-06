{{-- Footer Section Start --}}
<footer class="footer pr-hide sticky {{ getCustomizationSetting('footer_style') }}">
  <div class="footer-body">
      <div class="left-panel">
          <a href="{{ route('backend.home') }}">{{ setting('app_name', 'Salon') }}</a>. {{ setting('copyright_text', 'Droits d\'auteur © 2026') }}
      </div>
      <div class="center-panel">
          {!! setting('footer_text', 'Conçu avec ♥ par Kuilinga Technologies.') !!}
      </div>
      <div class="end-panel">
        {!! setting('ui_text', 'Propulsé par Kuilinga Technologies') !!}
      </div>
  </div>
</footer>
