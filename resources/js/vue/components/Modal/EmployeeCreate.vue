<template>
    <!-- Modal Créer Nouveau Manager -->
    <form @submit="formSubmit" class="">
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">{{ $t('employee.lbl_create_manager') }}</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row" id="form-offcanvas">
                          <InputField class="col-md-6" :is-required="true" :label="$t('customer.lbl_first_name')" placeholder="" v-model="first_name" :error-message="errors['first_name']" :error-messages="errorMessages['first_name']"></InputField>
                          <InputField class="col-md-6" :is-required="true" :label="$t('customer.lbl_last_name')" placeholder="" v-model="last_name" :error-message="errors['last_name']" :error-messages="errorMessages['last_name']"></InputField>

                          <!-- Nom du salon obligatoire pour un manager -->
                          <InputField class="col-md-12" :is-required="true" label="Nom du salon" placeholder="Entrez le nom du salon" v-model="salon_name" :error-message="errors['salon_name']" :error-messages="errorMessages['salon_name']"></InputField>

                          <div class="form-group col-md-6">
                            <label class="form-label">{{ $t('branch.lbl_contact_number') }}<span class="text-danger">*</span> </label>
                            <vue-tel-input :value="mobile" @input="handleInput" v-bind="{mode: 'international',maxLen: 15}"></vue-tel-input>
                            <span class="text-danger">{{ errors['mobile'] }}</span>
                          </div>

                          <!-- Note: l'email sera généré automatiquement par le serveur -->
                          <div class="col-md-6 form-group">
                            <div class="alert alert-info py-2 px-3 mt-2" style="font-size:0.85rem;">
                              <i class="fa-solid fa-circle-info me-1"></i>
                              Un email sera généré automatiquement pour ce manager.
                            </div>
                          </div>

                            <InputField type="password" class="col-md-6" :is-required="true" :label="$t('employee.lbl_password')" placeholder="" v-model="password" :error-message="errors['password']" :error-messages="errorMessages['password']"></InputField>

                            <InputField type="password" class="col-md-6" :is-required="true" :label="$t('employee.lbl_confirm_password')" placeholder="" v-model="confirm_password" :error-message="errors['confirm_password']" :error-messages="errorMessages['confirm_password']"></InputField>
                            <div class="form-group col-md-12">
                              <label for="" class="w-100">{{ $t('customer.lbl_gender') }}</label>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="gender" v-model="gender" id="male" value="male">
                                  <label class="form-check-label" for="male">
                                    {{ $t('messages.male') }}
                                  </label>
                                </div>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="gender" v-model="gender" id="female" value="female">
                                  <label class="form-check-label" for="female">
                                    {{ $t('messages.female') }}
                                  </label>
                                </div>

                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="gender" v-model="gender" id="other" value="other">
                                  <label class="form-check-label" for="other">
                                    {{ $t('messages.intersex') }}
                                  </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" class="btn btn-primary">{{ $t('messages.save_changes') }}</button>
                        <button type="button" class="btn btn-outline-primary d-block"  @click="resetform()"  data-bs-dismiss="modal"><i class="fa-solid fa-angles-left"></i>{{ $t('messages.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>
<script setup>
import { ref, onMounted } from 'vue'

import { useRequest } from '@/helpers/hooks/useCrudOpration'
import InputField from '@/vue/components/form-elements/InputField.vue'
import { useField, useForm } from 'vee-validate'
import { VueTelInput } from 'vue3-tel-input'
import * as yup from 'yup'

import { EMPLOYEE_STORE } from '@/vue/constants/users'

const emit = defineEmits(['submit'])

const { storeRequest } = useRequest()


/*
 * Form Data & Validation & Handeling
 */
// Default FORM DATA
const defaultData = () => {
  errorMessages.value = {}
  return {
    first_name: '',
    last_name: '',
    salon_name: '',
    mobile: '',
    password: '',
    confirm_password: '',
    gender: 'male',
    show_in_calender: 1,
    is_manager: 1,
    confirmed: 1
  }
}

onMounted(() => {
  setFormData(defaultData())
})

//  Reset Form
const setFormData = (data) => {
  resetForm({
    values: {
      first_name: data.first_name,
      last_name: data.last_name,
      salon_name: data.salon_name,
      mobile: data.mobile,
      password: data.password,
      confirm_password: data.confirm_password,
      gender: data.gender,
      show_in_calender: data.show_in_calender,
      is_manager: data.is_manager,
      confirmed: data.confirmed,
    }
  })
}

// Validations
const validationSchema = yup.object({
    first_name: yup.string().required('Le prénom est obligatoire'),
    last_name: yup.string().required('Le nom est obligatoire'),
    salon_name: yup.string().required('Le nom du salon est obligatoire'),
    mobile: yup.string().required('Le numéro de téléphone est obligatoire'),
    password : yup.string().required('Le mot de passe est obligatoire')
    .min(8, 'Le mot de passe doit comporter au moins 8 caractères'),
      confirm_password : yup.string().required('La confirmation du mot de passe est obligatoire')
      .oneOf([yup.ref('password')], 'Les mots de passe ne correspondent pas')
})

const { handleSubmit, errors, resetForm } = useForm({
  validationSchema
})

const { value: first_name } = useField('first_name')
const { value: last_name } = useField('last_name')
const { value: salon_name } = useField('salon_name')
const { value: password } = useField('password')
const { value: confirm_password } = useField('confirm_password')
const { value: gender } = useField('gender')
const { value: mobile } = useField('mobile')
const { value: show_in_calender } = useField('show_in_calender')
const { value: is_manager } = useField('is_manager')
const { value: confirmed } = useField('confirmed')
confirmed.value = 1
show_in_calender.value = 1
is_manager.value = 1
const errorMessages = ref({})

// phone number
const handleInput = (phone, phoneObject) => {
  // Handle the input event
  if (phoneObject?.formatted) {
    mobile.value = phoneObject.formatted
  }
};

const resetform = () => {
  setFormData(defaultData())
      bootstrap.Modal.getInstance(document.getElementById("exampleModal")).hide()
};

const formSubmit = handleSubmit((value) => {
  storeRequest({ url: EMPLOYEE_STORE, body: value }).then((res) => {
    if(res.status) {
      emit('submit', {type: 'create_manager', value: res.data.id})
      setFormData(defaultData())
      bootstrap.Modal.getInstance(document.getElementById("exampleModal")).hide()
    }
  })
})

</script>
