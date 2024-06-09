<template>
  <form @submit="formSubmit">
    <div>
      <CardTitle :title="$t('setting_sidebar.lbl_payment')" icon="fa-solid fa-coins"></CardTitle>
    </div>
    <div class="form-group">
      <div class="d-flex justify-content-between align-items-center">
        <label class="form-label" for="payment_method_razorpay">{{ $t('setting_payment_method.lbl_razorpay') }} </label>
        <div class="form-check form-switch">
          <input class="form-check-input" :true-value="1" :false-value="0" :value="razor_payment_method"
            :checked="razor_payment_method == 1 ? true : false" name="razor_payment_method" id="payment_method_razorpay"
            type="checkbox" v-model="razor_payment_method" />
        </div>
      </div>
    </div>
    <div v-if="razor_payment_method == 1">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="razorpay_secretkey">{{ $t('setting_payment_method.lbl_secret_key') }}</label>
            <input type="text" class="form-control" v-model="razorpay_secretkey" id="razorpay_secretkey"
              name="razorpay_secretkey" :errorMessage="errors.razorpay_secretkey"
              :errorMessages="errorMessages.razorpay_secretkey" />
            <p class="text-danger" v-for="msg in errorMessages.razorpay_secretkey" :key="msg">{{ msg }}</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="razorpay_publickey">{{ $t('setting_payment_method.lbl_app_key') }}</label>
            <input type="text" class="form-control" v-model="razorpay_publickey" id="razorpay_publickey"
              name="razorpay_publickey" :errorMessage="errors.razorpay_publickey"
              :errorMessages="errorMessages.razorpay_publickey" />
            <p class="text-danger" v-for="msg in errorMessages.razorpay_publickey" :key="msg">{{ msg }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="d-flex justify-content-between align-items-center">
        <label class="form-label" for="payment_method_stripe">{{ $t('setting_payment_method.lbl_stripe') }}</label>
        <div class="form-check form-switch">
          <input class="form-check-input" :true-value="1" :false-value="0" :value="str_payment_method"
            :checked="str_payment_method == 1 ? true : false" name="str_payment_method" id="payment_method_stripe"
            type="checkbox" v-model="str_payment_method" />
        </div>
      </div>
    </div>
    <div v-if="str_payment_method == 1">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="stripe_secretkey">{{ $t('setting_payment_method.lbl_secret_key') }}</label>
            <input type="text" class="form-control" v-model="stripe_secretkey" id="stripe_secretkey"
              name="stripe_secretkey" :errorMessage="errors.stripe_secretkey"
              :errorMessages="errorMessages.stripe_secretkey" />
            <p class="text-danger" v-for="msg in errorMessages.stripe_secretkey" :key="msg">{{ msg }}</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="stripe_publickey">{{ $t('setting_payment_method.lbl_app_key') }}</label>
            <input type="text" class="form-control" v-model="stripe_publickey" id="stripe_publickey"
              name="stripe_publickey" :errorMessage="errors.stripe_publickey"
              :errorMessages="errorMessages.stripe_publickey" />
            <p class="text-danger" v-for="msg in errorMessages.stripe_publickey" :key="msg">{{ msg }}</p>
          </div>
        </div>
      </div>
    </div>
    
    <div class="form-group">
      <div class="d-flex justify-content-between align-items-center">
        <label class="form-label" for="payment_method_paystack">{{ $t('setting_payment_method.lbl_paystack') }}</label>
        <div class="form-check form-switch">
          <input class="form-check-input" :true-value="1" :false-value="0" :value="paystack_payment_method"
            :checked="paystack_payment_method == 1 ? true : false" name="paystack_payment_method" id="payment_method_paystack"
            type="checkbox" v-model="paystack_payment_method" />
        </div>
      </div>
    </div>
    <div v-if="paystack_payment_method == 1">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="paystack_secretkey">{{ $t('setting_payment_method.lbl_secret_key') }}</label>
            <input type="text" class="form-control" v-model="paystack_secretkey" id="paystack_secretkey"
              name="paystack_secretkey" :errorMessage="errors.paystack_secretkey"
              :errorMessages="errorMessages.paystack_secretkey" />
            <p class="text-danger" v-for="msg in errorMessages.paystack_secretkey" :key="msg">{{ msg }}</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="paystack_publickey">{{ $t('setting_payment_method.lbl_app_key') }}</label>
            <input type="text" class="form-control" v-model="paystack_publickey" id="paystack_publickey"
              name="paystack_publickey" :errorMessage="errors.paystack_publickey"
              :errorMessages="errorMessages.paystack_publickey" />
            <p class="text-danger" v-for="msg in errorMessages.paystack_publickey" :key="msg">{{ msg }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="d-flex justify-content-between align-items-center">
        <label class="form-label" for="payment_method_paypal">{{ $t('setting_payment_method.lbl_paypal') }}</label>
        <div class="form-check form-switch">
          <input class="form-check-input" :true-value="1" :false-value="0" :value="paypal_payment_method"
            :checked="paypal_payment_method == 1 ? true : false" name="paypal_payment_method" id="payment_method_paypal"
            type="checkbox" v-model="paypal_payment_method" />
        </div>
      </div>
    </div>
    <div v-if="paypal_payment_method == 1">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="paypal_secretkey">{{ $t('setting_payment_method.lbl_secret_key') }}</label>
            <input type="text" class="form-control" v-model="paypal_secretkey" id="paypal_secretkey"
              name="paypal_secretkey" :errorMessage="errors.paypal_secretkey"
              :errorMessages="errorMessages.paypal_secretkey" />
            <p class="text-danger" v-for="msg in errorMessages.paypal_secretkey" :key="msg">{{ msg }}</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="paypal_clientid">{{ $t('setting_payment_method.lbl_client_id') }}</label>
            <input type="text" class="form-control" v-model="paypal_clientid" id="paypal_clientid"
              name="paypal_clientid" :errorMessage="errors.paypal_clientid"
              :errorMessages="errorMessages.paypal_clientid" />
            <p class="text-danger" v-for="msg in errorMessages.paypal_clientid" :key="msg">{{ msg }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="form-group">
      <div class="d-flex justify-content-between align-items-center">
        <label class="form-label" for="flutterwave_method_paypal">{{ $t('setting_payment_method.lbl_flutterwave') }}</label>
        <div class="form-check form-switch">
          <input class="form-check-input" :true-value="1" :false-value="0" :value="flutterwave_payment_method"
            :checked="flutterwave_payment_method == 1 ? true : false" name="flutterwave_payment_method" id="flutterwave_method_paypal"
            type="checkbox" v-model="flutterwave_payment_method" />
        </div>
      </div>
    </div>
    <div v-if="flutterwave_payment_method == 1">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="flutterwave_secretkey">{{ $t('setting_payment_method.lbl_secret_key') }}</label>
            <input type="text" class="form-control" v-model="flutterwave_secretkey" id="flutterwave_secretkey"
              name="flutterwave_secretkey" :errorMessage="errors.flutterwave_secretkey"
              :errorMessages="errorMessages.flutterwave_secretkey" />
            <p class="text-danger" v-for="msg in errorMessages.flutterwave_secretkey" :key="msg">{{ msg }}</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="flutterwave_publickey">{{ $t('setting_payment_method.lbl_app_key') }}</label>
            <input type="text" class="form-control" v-model="flutterwave_publickey" id="flutterwave_publickey"
              name="flutterwave_publickey" :errorMessage="errors.flutterwave_publickey"
              :errorMessages="errorMessages.flutterwave_publickey" />
            <p class="text-danger" v-for="msg in errorMessages.flutterwave_publickey" :key="msg">{{ msg }}</p>
          </div>
        </div>
      </div>
    </div>
    <SubmitButton :IS_SUBMITED="IS_SUBMITED"></SubmitButton>
  </form>
</template>

<script setup>
import { ref, watch } from 'vue'
import CardTitle from '@/Setting/Components/CardTitle.vue'
import { useField, useForm } from 'vee-validate'
import { STORE_URL, GET_URL } from '@/vue/constants/setting'
//
import * as yup from 'yup';
import { useRequest } from '@/helpers/hooks/useCrudOpration'
import { onMounted } from 'vue'
import { createRequest } from '@/helpers/utilities'
import SubmitButton from './Forms/SubmitButton.vue'
const { storeRequest } = useRequest()
const IS_SUBMITED = ref(false)
//  Reset Form
const setFormData = (data) => {
  resetForm({
    values: {
      razor_payment_method: data.razor_payment_method || 0,
      razorpay_secretkey: data.razorpay_secretkey || '',
      razorpay_publickey: data.razorpay_publickey || '',
      str_payment_method: data.str_payment_method || 0,
      stripe_secretkey: data.stripe_secretkey || '',
      stripe_publickey: data.stripe_publickey || '',
      paystack_payment_method: data.paystack_payment_method || 0,
      paystack_secretkey: data.paystack_secretkey || '',
      paystack_publickey: data.paystack_publickey || '',
      paypal_payment_method: data.paypal_payment_method || 0,
      paypal_secretkey: data.paypal_secretkey || '',
      paypal_clientid: data.paypal_clientid || '',
      flutterwave_payment_method: data.flutterwave_payment_method || 0,
      flutterwave_secretkey: data.flutterwave_secretkey || '',
      flutterwave_publickey: data.flutterwave_publickey || '',
    }
  })
}
const validationSchema = yup.object({
  razorpay_secretkey: yup.string().test('razorpay_secretkey', 'Must be a valid RazorPay key', function (value) {
    if (this.parent.razor_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),
  razorpay_publickey: yup.string().test("Must be a valid RazorPay Publickey", function (value) {
    if (this.parent.razor_payment_method == '1' && !value) {
    
      return false;
    }
    return true
  }),
  stripe_secretkey: yup.string().test('stripe_secretkey', 'Must be a valid Stripe key', function (value) {
    if (this.parent.str_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),
  stripe_publickey: yup.string().test("Must be a valid Stripe Publickey", function (value) {
    if (this.parent.str_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),

  paystack_secretkey: yup.string().test('paystack_secretkey', 'Must be a valid Paystack key', function (value) {
    if (this.parent.paystack_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),
  paystack_publickey: yup.string().test("Must be a valid Paystack Publickey", function (value) {
    if (this.parent.paystack_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),

  paypal_secretkey: yup.string().test('paypal_secretkey', 'Must be a valid Paypal key', function (value) {
    if (this.parent.paypal_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),
  paypal_clientid: yup.string().test("Must be a valid Paypal Publickey", function (value) {
    if (this.parent.paypal_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),

  flutterwave_secretkey: yup.string().test('flutterwave_secretkey', 'Must be a valid Flutterwave key', function (value) {
    if (this.parent.flutterwave_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),
  flutterwave_publickey: yup.string().test("Must be a valid Flutterwave Publickey", function (value) {
    if (this.parent.flutterwave_payment_method == '1' && !value) {
      return false;
    }
    return true
  }),
})
const { handleSubmit, errors, resetForm } = useForm({ validationSchema })
const errorMessages = ref({})
const { value: razor_payment_method } = useField('razor_payment_method')
const { value: razorpay_secretkey } = useField('razorpay_secretkey')
const { value: razorpay_publickey } = useField('razorpay_publickey')
const { value: str_payment_method } = useField('str_payment_method')
const { value: stripe_secretkey } = useField('stripe_secretkey')
const { value: stripe_publickey } = useField('stripe_publickey')
const { value: paystack_payment_method } = useField('paystack_payment_method')
const { value: paystack_secretkey } = useField('paystack_secretkey')
const { value: paystack_publickey } = useField('paystack_publickey')
const { value: paypal_payment_method } = useField('paypal_payment_method')
const { value: paypal_secretkey } = useField('paypal_secretkey')
const { value: paypal_clientid } = useField('paypal_clientid')
const { value: flutterwave_payment_method } = useField('flutterwave_payment_method')
const { value: flutterwave_secretkey } = useField('flutterwave_secretkey')
const { value: flutterwave_publickey } = useField('flutterwave_publickey')

watch(() => razor_payment_method.value, (value) => {
  if(value == '0') {
    razorpay_secretkey.value = ''
    razorpay_publickey.value = ''
  }
}, {deep: true})
watch(() => str_payment_method.value, (value) => {
  if(value == '0') {
    stripe_secretkey.value = ''
    stripe_publickey.value = ''
  }
}, {deep: true})
watch(() => paystack_payment_method.value, (value) => {
  if(value == '0') {
    paystack_secretkey.value = ''
    paystack_publickey.value = ''
  }
}, {deep: true})
watch(() => paypal_payment_method.value, (value) => {
  if(value == '0') {
    paypal_secretkey.value = ''
    paypal_clientid.value = ''
  }
}, {deep: true})
watch(() => flutterwave_payment_method.value, (value) => {
  if(value == '0') {
    flutterwave_secretkey.value = ''
    flutterwave_publickey.value = ''
  }
}, {deep: true})
// message
const display_submit_message = (res) => {
  IS_SUBMITED.value = false
  if (res.status) {
    window.successSnackbar(res.message)
  } else {
    window.errorSnackbar(res.message)
    errorMessages.value = res.errors
  }
}

//fetch data
const data = 'razor_payment_method,razorpay_secretkey,razorpay_publickey,str_payment_method,stripe_secretkey,stripe_publickey,paystack_payment_method,paystack_secretkey,paystack_publickey,paypal_payment_method,paypal_secretkey,paypal_clientid,,flutterwave_payment_method,flutterwave_secretkey,flutterwave_publickey'
onMounted(() => {
  createRequest(GET_URL(data)).then((response) => {
    setFormData(response)
  })
})

//Form Submit
const formSubmit = handleSubmit((values) => {
  IS_SUBMITED.value = true
  const newValues = {}
  Object.keys(values).forEach((key) => {
    if(values[key] !== '') {
      newValues[key] = values[key] || ''
    }
  })
  storeRequest({
    url: STORE_URL,
    body: newValues
  }).then((res) => display_submit_message(res))
})

defineProps({
  label: { type: String, default: '' },
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  errorMessage: { type: String, default: '' },
  errorMessages: { type: Array, default: () => [] }
})
</script>
