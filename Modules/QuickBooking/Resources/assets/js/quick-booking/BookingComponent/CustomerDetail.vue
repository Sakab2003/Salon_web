<template>
  <div class="card-list-data">
    <!-- Image de profil / Télécharger -->
    <div class="form-group text-center mb-4">
      <div class="mb-2">
        <img :src="profile_image_preview || defaultImage" alt="profile-image" class="avatar avatar-90 rounded-circle border p-1 shadow-sm" style="object-fit: cover;" />
      </div>
      <div>
        <label for="public_profile_image_input" class="btn btn-sm btn-info text-white rounded-pill px-3">
          <i class="fa-solid fa-cloud-arrow-up me-1"></i> Télécharger
        </label>
        <input type="file" id="public_profile_image_input" accept="image/*" class="d-none" @change="onImageSelect" />
      </div>
    </div>

    <div class="row">
      <InputField class="col-md-6" :is-required="true" :label="$t('quick_booking.lbl_first_name')" placeholder="Prénom" v-model="first_name" :error-message="errors.first_name" :error-messages="errorMessages['first_name']"></InputField>
      <InputField class="col-md-6" :is-required="true" :label="$t('quick_booking.lbl_last_name')" placeholder="Nom de famille" v-model="last_name" :error-message="errors['last_name']" :error-messages="errorMessages['last_name']"></InputField>
    </div>

    <div class="form-group">
      <label class="form-label">{{ $t('quick_booking.lbl_phone_number') }}<span class="text-danger">*</span> </label>
      <vue-tel-input :value="mobile" @input="handleInput" v-bind="{ mode: 'international', maxLen: 15 }"></vue-tel-input>
      <span class="text-danger small" v-if="errors['mobile']">{{ errors['mobile'] }}</span>
    </div>


    <!-- Genre -->
    <div class="form-group col-md-12 mt-4">
      <label for="" class="w-100 font-weight-bold mb-3 text-muted">Genre / Sexe</label>
      <div class="d-flex align-items-center gap-3">
        <div class="iq-time-slot">
          <input type="radio" name="gender" v-model="gender" id="male" value="male" class="btn-check" />
          <label class="btn d-block py-2 px-4 rounded-pill" for="male"><i class="fa-solid fa-mars me-2"></i>Homme</label>
        </div>
        <div class="iq-time-slot">
          <input type="radio" name="gender" v-model="gender" id="female" value="female" class="btn-check" />
          <label class="btn d-block py-2 px-4 rounded-pill" for="female"><i class="fa-solid fa-venus me-2"></i>Femme</label>
        </div>
        <div class="iq-time-slot">
          <input type="radio" name="gender" v-model="gender" id="intersex" value="intersex" class="btn-check" />
          <label class="btn d-block py-2 px-4 rounded-pill" for="intersex"><i class="fa-solid fa-genderless me-2"></i>Autre</label>
        </div>
      </div>
    </div>
  </div>

  <div class="card-footer d-flex justify-content-between">
    <button type="button" class="btn btn-secondary iq-text-uppercase" v-if="wizardPrev" @click="prevTabChange(wizardPrev)">
      <i class="fa-solid fa-angles-left me-1"></i> Retour
    </button>
    <button :disabled="IS_SUBMITED" class="btn btn-primary iq-text-uppercase" name="submit" v-if="wizardNext" @click="formSubmit">
      <template v-if="IS_SUBMITED">
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Enregistrement...
      </template>
      <template v-else> <i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer</template>
    </button>
  </div>
</template>
<script setup>
import { ref, watch } from 'vue'
import { useField, useForm } from 'vee-validate'
import { VueTelInput } from 'vue3-tel-input'
import InputField from '@/vue/components/form-elements/InputField.vue'
import * as yup from 'yup'
import { useQuickBooking } from '../../store/quick-booking'

const props = defineProps({
  wizardNext: {
    default: '',
    type: [String, Number]
  },
  wizardPrev: {
    default: '',
    type: [String, Number]
  }
})

const defaultImage = '/images/user/user.png'
const profile_image_preview = ref('')

const onImageSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      profile_image_preview.value = e.target.result
      store.updateUserValues({ key: 'profile_image', value: e.target.result })
    }
    reader.readAsDataURL(file)
  }
}

// Default FORM DATA
const defaultData = () => {
  errorMessages.value = {}
  return {
    first_name: '',
    last_name: '',
    mobile: '',
    password: '',
    password_confirmation: '',
    gender: 'male'
  }
}

const numberRegex = /^\d+$/
let EMAIL_REGX = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
const validationSchema = yup.object({
  first_name: yup
    .string()
    .required('Le prénom est obligatoire')
    .test('is-string', 'Seules les lettres sont autorisées', (value) => {
      const specialCharsRegex = /[!@#$%^&*(),.?":{}|<>\-_;'\/+=\[\]\\]/
      return !specialCharsRegex.test(value) && !numberRegex.test(value)
    }),
  last_name: yup
    .string()
    .required('Le nom de famille est obligatoire')
    .test('is-string', 'Seules les lettres sont autorisées', (value) => {
      const specialCharsRegex = /[!@#$%^&*(),.?":{}|<>\-_;'\/+=\[\]\\]/
      return !specialCharsRegex.test(value) && !numberRegex.test(value)
    }),
  mobile: yup.string().required('Le numéro de téléphone est obligatoire'),
  password: yup.string().nullable().notRequired(),
  password_confirmation: yup.string().nullable().notRequired()
})

const { handleSubmit, errors, resetForm } = useForm({
  validationSchema,
  initialValues: {
    gender: 'male'
  }
})
const { value: first_name } = useField('first_name')
const { value: last_name } = useField('last_name')
const { value: password } = useField('password')
const { value: password_confirmation } = useField('password_confirmation')
const { value: gender } = useField('gender')
const { value: mobile } = useField('mobile')

const errorMessages = ref({})
const IS_SUBMITED = ref(false)

const handleInput = (phone, phoneObject) => {
  if (phoneObject?.formatted) {
    mobile.value = phoneObject.formatted
  }
}

const emit = defineEmits(['tab-change', 'onReset'])
const prevTabChange = (val) => (emit('tab-change', val))
const formSubmit = handleSubmit((values) => {
  IS_SUBMITED.value = true
  emit('tab-change', props.wizardNext)
})
const store = useQuickBooking()

watch(() => store.bookingResponse, () => {
  IS_SUBMITED.value = false
  resetForm(defaultData())
}, {deep: true})

watch(() => mobile.value, (value) => { store.updateUserValues({ key: 'mobile', value: value }) }, { deep: true })
watch(() => first_name.value, (value) => { store.updateUserValues({ key: 'first_name', value: value }) }, { deep: true })
watch(() => last_name.value, (value) => { store.updateUserValues({ key: 'last_name', value: value }) }, { deep: true })
watch(() => password.value, (value) => { store.updateUserValues({ key: 'password', value: value }) }, { deep: true })
watch(() => gender.value, (value) => { store.updateUserValues({ key: 'gender', value: value }) }, { deep: true })
</script>

