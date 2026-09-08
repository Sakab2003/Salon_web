<form action="{{$url ?? ''}}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-stretch flex-md-row flex-column">
  @csrf
  {{$slot}}
  <input type="hidden" name="message_change-is_featured" value="Êtes-vous sûr de vouloir effectuer cette action ?">
  <input type="hidden" name="message_change-status" value="Êtes-vous sûr de vouloir modifier le statut ?">
  <input type="hidden" name="message_delete" value="Êtes-vous sûr de vouloir supprimer les éléments sélectionnés ?">
  <button class="btn btn-gray" id="quick-action-apply">{{ __('messages.apply') }}</button>
</form>
