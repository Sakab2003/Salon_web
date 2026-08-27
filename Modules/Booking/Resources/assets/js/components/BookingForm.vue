<template>
  <form>
    <div :class="`offcanvas offcanvas-end`" data-bs-scroll="true" tabindex="-1" id="booking-form" aria-labelledby="offcanvasBookingForm">
      <template v-if="SINLGE_STEP == 'MAIN' && status == 'completed'">
        <InvoiceComponent :booking_id="id"></InvoiceComponent>
      </template>
      <template v-else-if="SINLGE_STEP == 'MAIN' && status != 'checkout'">
        <div class="offcanvas-header">
          <BookingHeader :booking_id="id" :status="status" :is_paid="is_paid" @statusUpdate="updateStatus" @openShareModal="handleOpenShareModal"></BookingHeader>
        </div>
        <BookingStatus v-if="id" :status="status" :booking_id="id" :status-list="statusList" :employee_id="employee_id" @statusUpdate="updateStatus"></BookingStatus>
        <div>
          <div class="d-flex text-center date-time">
            <div class="col-6 py-3">
              <i>Le</i> <strong v-if="start_date_time && start_date_time !== 'Invalid date'">{{ moment(start_date_time).locale('fr').format('D MMMM YYYY') }}</strong>
              <strong v-else> {{ moment(current_date).locale('fr').format('D MMMM YYYY') }}</strong>
            </div>
            <div class="col-6 py-3">
              <i>À</i> <strong v-if="start_date_time && start_date_time !== 'Invalid date'">{{ moment(start_date_time).locale('fr').format('HH:mm') }}</strong>
              <strong v-else>--:--</strong>
            </div>
          </div>
        </div>
        <div class="offcanvas-body border-top">
          <!-- Sélection du Salon -->
          <div class="form-group" v-if="bookingType !== 'CALENDER_BOOKING' && branch.options.length > 1 && !isManagerOnly">
            <Multiselect id="branch_id" placeholder="Selectionner un salon" v-model="branch_id" :disabled="is_paid || filterStatus(status).is_disabled" :value="branch_id" v-bind="singleSelectOption" :options="branch.options" @select="branchSelect" @change="removeBranch" class="form-group mb-0"></Multiselect>
            <span class="text-danger small" v-if="errors.branch_id">{{ errors.branch_id }}</span>
          </div>

          <!-- Sélection du Personnel -->
          <div class="form-group" v-if="bookingType !== 'CALENDER_BOOKING' && branch_id">
            <Multiselect id="employee_id" placeholder="Sélectionner le personnel" v-model="employee_id" :value="employee_id" :disabled="is_paid || filterStatus(status).is_disabled" v-bind="singleSelectOption" :options="employee.options" @select="employeeSelect" @change="removeEmployee" class="form-group mb-0"></Multiselect>
            <span class="text-danger small" v-if="errors.employee_id">{{ errors.employee_id }}</span>
          </div>

          <!-- Sélection Date et Heure -->
          <div class="row">
            <div class="form-group col-6" v-if="bookingType !== 'CALENDER_BOOKING' && employee_id">
              <div class="booking-datepicker">
                <flat-pickr v-model="current_date" :disabled="is_paid || filterStatus(status).is_disabled" placeholder="Sélectionner la date" @change="dateChange" :config="config" class="form-control" />
              </div>
            </div>
            <div class="form-group col-6" v-if="bookingType !== 'CALENDER_BOOKING' && current_date && employee_id">
              <Multiselect id="star_time" placeholder="Sélectionner l'heure" v-model="start_date_time" :disabled="is_paid || filterStatus(status).is_disabled" :value="start_date_time" v-bind="singleSelectOption" :options="slots" @select="slotSelect"  @change="removeSlot" class="form-group mb-0"></Multiselect>
              <span class="text-danger small" v-if="errors.start_date_time">{{ errors.start_date_time }}</span>
            </div>
          </div>

          <!-- Sélection du Client -->
          <div class="form-group border-bottom ">
            <div v-if="selectedCustomer">
              <div class="d-flex align-items-start gap-3 mb-2">
                <img :src="selectedCustomer.profile_image" alt="avatar" class="img-fluid avatar avatar-60 rounded-pill" />
                <div class="flex-grow-1">
                  <div class="gap-2">
                    <strong>{{ selectedCustomer.full_name }}</strong>
                    <p class="m-0">
                      <small>Client depuis {{ moment(selectedCustomer.created_at).locale('fr').format('MMMM YYYY') }}</small>
                    </p>
                  </div>
                </div>
                <button type="button" v-if="status !== 'check_in' && !is_paid" @click="removeCustomer()" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
              </div>
              <div class="row">
                <label class="col-4"><i>{{ $t('booking.lbl_phone') }}</i></label>
                <strong class="col text-primary">{{ selectedCustomer.mobile }}</strong>
              </div>
            </div>
            <div v-else>
              <Multiselect id="user_id" v-model="user_id" placeholder="Sélectionner un client" :disabled="is_paid || filterStatus(status).is_disabled" :value="user_id" v-bind="singleSelectOption" :options="customer.options" @select="customerSelect" class="form-group mb-0"></Multiselect>
              <span class="text-danger small" v-if="errors.user_id">{{ errors.user_id }}</span>
            </div>
          </div>

          <!-- Liste des Services Sélectionnés -->
          <ul class="form-group list-group list-group-flush">
            <div class="alert alert-warning m-3" v-if="service.options.length === 0 && employee_id && selectedService.length === 0">
              <i class="fa-solid fa-circle-info me-1"></i> Aucun service pré-enregistré dans la base de données. Vous pouvez ajouter directement un service personnalisé ci-dessous.
            </div>
            <li v-for="(service, index) in selectedService" :key="index" class="list-group-item py-3 px-1">
              <div class="d-flex flex-column gap-2">
                <div class="d-flex align-items-center justify-content-between">
                  <h6>{{ service.service_name }} ({{ formatCurrencyVue(service.service_price) }}) <span v-if="service.is_custom" class="badge bg-soft-info ms-1">Saisie libre</span></h6>
                  <button type="button" v-if="status !== 'check_in' && !is_paid" @click="removeService(service.service_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                </div>
                <p class="m-0">
                  <label><i>{{ $t('booking.lbl_with') }}</i></label> <strong>{{ service.employee?.full_name || selectedEmployee?.name || '' }}</strong>
                </p>
                <div>
                  <label><i>{{ $t('booking.lbl_at') }}</i></label> <strong v-if="service.start_date_time !== 'Invalid date'">{{ moment(service.start_date_time).locale('fr').format('HH:mm') }}</strong><strong v-else>--:--</strong> <span class="px-2">|</span> <label class="me-2"><i>Pour : </i></label><strong>{{ service.duration_min }} Min</strong>
                </div>
              </div>
            </li>
          </ul>

          <!-- Formulaire Créer Services (Identique à la section Services) -->
          <div v-if="showCustomServiceInput" class="card shadow-sm my-3 border rounded-3 bg-white">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
              <h6 class="fw-bold m-0 text-dark">Créer Services</h6>
              <button type="button" class="btn-close btn-sm" @click="showCustomServiceInput = false" aria-label="Close"></button>
            </div>
            <div class="card-body p-3">
              <!-- Upload Image Circle -->
              <div class="text-center mb-3">
                <div class="d-inline-block position-relative">
                  <div class="rounded-circle border d-flex align-items-center justify-content-center bg-light shadow-sm overflow-hidden" style="width: 120px; height: 120px; margin: 0 auto;">
                    <img v-if="custom_service_image_preview" :src="custom_service_image_preview" class="w-100 h-100" style="object-fit: cover;" alt="Aperçu image">
                    <span v-else class="text-muted small fw-bold">600 x 300</span>
                  </div>
                </div>
                <div class="mt-2">
                  <button type="button" class="btn btn-sm btn-info text-white px-3 py-1" @click="triggerImageSelect" style="background-color: #00b8c4; border: none; border-radius: 4px;">
                    Télécharger
                  </button>
                  <input type="file" ref="fileInputRef" accept="image/*" class="d-none" @change="onImageSelected">
                </div>
              </div>

              <!-- Nom * -->
              <div class="form-group mb-3">
                <label class="form-label small fw-semibold text-dark mb-1">Nom <span class="text-danger">*</span></label>
                <input type="text" v-model="custom_service_name" class="form-control form-control-sm" placeholder="Nom du service" required />
              </div>

              <!-- Durée du service (en minutes) * -->
              <div class="form-group mb-3">
                <label class="form-label small fw-semibold text-dark mb-1">Durée du service (en minutes) <span class="text-danger">*</span></label>
                <input type="number" v-model="custom_service_duration" class="form-control form-control-sm" placeholder="Ex: 30" required />
              </div>

              <!-- Prix par défaut (FCFA) * -->
              <div class="form-group mb-3">
                <label class="form-label small fw-semibold text-dark mb-1">Prix par défaut (FCFA) <span class="text-danger">*</span></label>
                <input type="number" v-model="custom_service_amount" class="form-control form-control-sm" placeholder="Ex: 5000" required />
              </div>

              <!-- Catégorie * -->
              <div class="form-group mb-3">
                <label class="form-label small fw-semibold text-dark mb-1">Catégorie <span class="text-danger">*</span></label>
                <select v-model="custom_service_category_id" class="form-select form-select-sm" required>
                  <option value="">Sélectionner une catégorie</option>
                  <option v-for="cat in categoryList" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>

              <!-- Description -->
              <div class="form-group mb-3">
                <label class="form-label small fw-semibold text-dark mb-1">Description</label>
                <textarea v-model="custom_service_description" class="form-control form-control-sm" rows="3" placeholder="Description du service..."></textarea>
              </div>

              <!-- Statut -->
              <div class="form-group mb-3 d-flex align-items-center justify-content-between">
                <label class="form-label small fw-semibold text-dark m-0">Statut</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" role="switch" v-model="custom_service_status" id="customServiceStatus" />
                </div>
              </div>

              <!-- Footer Buttons -->
              <div class="d-flex gap-2 pt-2 border-top">
                <button type="button" class="btn btn-sm text-white flex-fill py-2" :disabled="isSavingService" @click="saveCustomService" style="background-color: #9c27b0; border-color: #9c27b0; border-radius: 4px;">
                  <span v-if="isSavingService"><span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...</span>
                  <span v-else><i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill py-2" @click="showCustomServiceInput = false" style="border-radius: 4px;">
                  <i class="fa-solid fa-angles-left me-1"></i> Fermer
                </button>
              </div>
            </div>
          </div>

          <!-- Ajout de Service (Bases de données & Saisie libre) -->
          <div v-if="selectedCustomer && employee_id" class="text-center d-flex flex-column gap-2 mt-2">
            <Multiselect v-if="newService" :canClear="false" placeholder="Sélectionner un service" ref="serviceInput" class="" v-model="services_id" :value="services_id" v-bind="multipleSelectOption" :options="service.options" @select="serviceSelect" id="service_ids">
              <template v-slot:multiplelabel="{ values }">
                <div class="multiselect-multiple-label">Sélectionner un service</div>
              </template>
            </Multiselect>
            <div v-else class="d-flex justify-content-center gap-2">
              <a v-if="!filterStatus(status).is_disabled && !is_paid && start_date_time && service.options.length > 0" href="javascript:void(0)" @click="addNewService" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-circle-plus"></i> Liste des services</a>
              <a v-if="!filterStatus(status).is_disabled && !is_paid" href="javascript:void(0)" @click="showCustomServiceInput = true" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen"></i> Saisie libre de service</a>
            </div>
            <span class="text-danger small d-block mt-1" v-if="errors.services_id && selectedService.length === 0">{{ errors.services_id }}</span>
          </div>
        </div>

        <div class="offcanvas-footer">
          <div class="form-group px-3">
            <label class="form-label">{{ $t('booking.lbl_note') }}</label>
            <textarea name="note" :disabled="is_paid || filterStatus(status).is_disabled" v-model="note" cols="60" class="form-control"></textarea>
          </div>
          <div class="form-group m-0 p-3 d-flex justify-content-between border-top">
            <label for=""><strong>{{ $t('booking.lbl_sub_tot') }} </strong> </label>
            <span>{{ formatCurrencyVue(SUB_TOTAL_SERVICE_AMOUNT) }}</span>
          </div>

          <!-- Alertes de Validation avant soumission -->
          <div class="px-3">
            <div class="alert alert-danger py-2 mb-2" v-if="Object.keys(errors).length > 0 && selectedService.length === 0">
              <small><i class="fa-solid fa-circle-exclamation me-2"></i>Veuillez remplir tous les champs obligatoires mis en évidence ci-dessus.</small>
            </div>
            <small class="text-danger d-block text-center mb-2 fw-bold" v-if="errors.services_id && selectedService.length === 0">
              * Veuillez ajouter au moins un service pour pouvoir enregistrer.
            </small>
          </div>

          <div class="d-grid gap-3 px-3 pb-3" v-if="status !== 'check_in' && !is_paid">
            <button type="button" :disabled="selectedService.length > 0 && status !== 'cancelled' ? false : true" :class="`btn ${selectedService.length > 0 && status !== 'cancelled' ? 'btn-primary' : 'disabled btn-gray'} btn-lg rounded-0 d-block`" @click="formSubmit">
              <template v-if="IS_SUBMITED">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Chargement...
              </template>
              <span v-else><i class="fa-solid fa-floppy-disk me-2"></i>{{ $t('messages.save_appointment') }}</span>
            </button>
          </div>
        </div>
      </template>

      <!-- CHECKOUT TEMPLATE -->
      <template v-else-if="SINLGE_STEP == 'CHECK_OUT' && status == 'checkout'">
        <div class="offcanvas-header">
          <div class="d-flex gap-2 align-items-center">
            <h4 class="offcanvas-title" id="form-offcanvasLabel">Paiement / Encaisser</h4>
            <small class="badge bg-success" v-if="is_paid">{{ $t('booking.lbl_is_paid') }}</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body border-top">
          <div v-if="selectedCustomer" class="border-bottom">
            <div class="d-flex align-items-start gap-3 mb-3">
              <img :src="selectedCustomer.profile_image" alt="avatar" class="img-fluid avatar avatar-60 rounded-pill" />
              <div class="flex-grow-1">
                <div class="gap-2">
                  <strong>{{ selectedCustomer.full_name }}</strong>
                  <p class="m-0">
                    <small>Client since {{ moment(selectedCustomer.created_at).format('MMMM YYYY') }}</small>
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div v-if="selectedService.length > 0" :class="selectedProduct.length > 0 ? 'border-bottom' : ''">
            <h5 class="mt-3">Services</h5>
            <ul class="form-group list-group list-group-flush">
              <li v-for="(service, index) in selectedService" :key="index" class="list-group-item py-3 px-1">
                <div class="d-flex flex-column gap-2">
                  <div class="d-flex align-items-center justify-content-between">
                    <h6>{{ service.service_name }} ({{ formatCurrencyVue(service.service_price) }})</h6>
                    <button type="button" v-if="!is_paid" @click="removeService(service.service_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                  </div>
                  <p class="m-0">
                    <label><i>{{ $t('booking.lbl_with') }}</i></label> <strong>{{ service.employee?.full_name || selectedEmployee?.name || '' }}</strong>
                  </p>
                  <div>
                    <label><i>{{ $t('booking.lbl_at') }}</i></label> <strong>{{ moment(service.start_date_time).format('LT') }}</strong> <span class="px-2">|</span> <label class=" me-2"> <i>For:</i></label><strong> {{ service. duration_min }} Min</strong>
                  </div>
                </div>
              </li>
            </ul>
          </div>
          <div v-if="selectedProduct.length > 0">
            <h5 class="mt-3">Products</h5>
            <ul class="form-group list-group list-group-flush">
              <li v-for="(product, index) in selectedProduct" :key="index" class="list-group-item py-3 px-1">
                <div class="d-flex flex-column gap-2">
                  <div class="d-flex align-items-center justify-content-between">
                    <h6>{{ product.product_name }} </h6>
                    <button type="button" v-if="!is_paid" @click="removeProduct(product.product_variation_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                  </div>
                  <div>
                    <div class="d-flex">
                      <label>Price:</label>
                      <template v-if="product.discounted_price">
                        <h5 class="ms-2"><b>{{ formatCurrencyVue(product.discounted_price) }}</b></h5>
                        <h6 class=" text-secondary" v-if="product.product_price != product.discounted_price"><del class="me-2">{{ formatCurrencyVue(product.product_price) }}</del>
                        <small class=" text-success " v-if="product.discount_type=='percent'">{{ product.discount_value }}% OFF</small>
                        <small class=" text-success" v-else>{{ formatCurrencyVue(product.discount_value) }} OFF</small></h6>
                      </template>
                      <template v-else>
                        <h1>{{product.product_price}}</h1>
                        <h5 class="ms-2"><b>{{ formatCurrencyVue(product.product_price) }}</b></h5>
                      </template>

                  </div>
                    <label>Quantity: </label> <QtyButton v-model="product.product_qty" :max="product.max_qty"></QtyButton>
                  </div>
                  <p class="m-0">
                    <label><i>Sold By: </i></label> <strong>{{ product.employee?.full_name || selectedEmployee?.name || '' }}</strong>
                  </p>
                </div>
              </li>
            </ul>
          </div>
          <div class="d-flex gap-3 justify-content-between flex-column">
            <div v-if="services_id.length < service.options.length" class="text-center">
              <Multiselect v-if="newService" :canClear="false" placeholder="Selectionner un service" ref="serviceInput" class="" v-model="services_id" :value="services_id" v-bind="multipleSelectOption" :options="service.options" @select="serviceSelect" id="service_ids">
                <template v-slot:multiplelabel="{ values }">
                  <div class="multiselect-multiple-label">Selectionner un service</div>
                </template>
              </Multiselect>
              <template v-else>
                <a href="javascript:void(0)" v-if="!is_paid" @click="addNewService" class="btnw-100"><i class="fa-solid fa-circle-plus"></i> {{ $t('booking.lbl_add_service') }}</a>
              </template>
            </div>
           
            <div v-if="product_variation_id.length < products.options.length" class="text-center">
              <Multiselect v-if="newProduct" :canClear="false" placeholder="Selectionner le produit" ref="productInput" class="" v-model="product_variation_id" :value="product_variation_id" v-bind="multipleSelectOption" :options="products.options" @select="selectProduct" id="product_variation_ids">
                <template v-slot:multiplelabel="{ values }">
                  <div class="multiselect-multiple-label">Selectionner le produit</div>
                </template>
              </Multiselect>
              <template v-else>
                <a href="javascript:void(0)" v-if="!is_paid" @click="addNewProduct" class="btnw-100"><i class="fa-solid fa-circle-plus"></i> Add Product</a>
              </template>
            </div>
          </div>
        </div>
        <div class="offcanvas-footer border-top">
          <div class="form-group m-0 p-3 d-flex justify-content-between">
            <label for=""><strong>{{ $t('booking.lbl_sub_tot') }} </strong> </label>
            <span>{{ formatCurrencyVue(SUB_TOTAL_SERVICE_AMOUNT) }}</span>
          </div>
          <div class="d-grid gap-3">
            <button type="button" :disabled="IS_SUBMITED" v-if="services_id.length > 0" class="btn btn-primary btn-lg rounded-0 d-block" @click="formSubmitCheckout">
              <template v-if="IS_SUBMITED">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
              </template>
              <template v-else>
                <template v-if="is_paid">
                  <i class="fa-solid fa-floppy-disk mx-2"></i>{{ $t('booking.lbl_complete_now') }}
                </template>
                <template v-else>
                  <i class="fa-solid fa-floppy-disk mx-2"></i>{{ $t('booking.lbl_got_to_payment') }}
                </template>
              </template>
            </button>
          </div>
        </div>
      </template>

      <!-- PAYMENT TEMPLATE -->
      <template v-else-if="SINLGE_STEP == 'PAYMENT'">
        <div class="offcanvas-header">
          <h4 class="offcanvas-title" id="form-offcanvasLabel">{{ $t('booking.lbl_payment') }}</h4>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body border-top">
          <PaymentForm @updatePaymentData="updatePaymentData" :booking-id="id" :booking-status="status"></PaymentForm>
        </div>
        <div class="offcanvas-footer">
          <div class="d-grid gap-3">
            <button type="button" :disabled="IS_SUBMITED" class="btn btn-primary btn-lg rounded-0 d-block" @click="formSubmitPaynow">
              <template v-if="IS_SUBMITED">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
              </template>
              <template v-else><i class="fa-solid fa-floppy-disk"></i> {{ $t('booking.lbl_pay_now') }}</template>
            </button>
          </div>
        </div>
      </template>
    </div>
  </form>

  <CustomerCreate :data="newCustomerData" @submit="externalFormCreation"></CustomerCreate>
  <BookingShareModal :branch-id="branch_id" :branch-name="selectedBranchName"></BookingShareModal>
</template>
<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import FlatPickr from 'vue-flatpickr-component'
import { useBookingStore } from '../store/booking'
import { EDIT_URL, STORE_URL, UPDATE_URL, UPDATE_STATUS } from '../constant/booking'
import { PRODUCT_LIST } from '../constant/booking'
// Select Options List Request
import { EMPLOYEE_LIST, CUSTOMER_LIST, SERVICE_LIST, SLOT_LIST, PAYMENT_PUT_URL, UPDATE_PAYMENT_DATA, CHECKOUT_URL, STRIPE_PAYMENT_DATA } from '../constant/booking'
import { BRANCH_LIST } from '@/vue/constants/branch'

import { useField, useForm } from 'vee-validate'
import * as yup from 'yup'

import { useRequest, useOnOffcanvasHide, useOnOffcanvasShow } from '@/helpers/hooks/useCrudOpration'
import { XSRF_REQUEST_HEADER } from '@/helpers/utilities'

// Modals
import CustomerCreate from '@/vue/components/Modal/CustomerCreate.vue'

// Element Component
import BookingHeader from './BookingFormElements/BookingHeader.vue'
import BookingStatus from './BookingFormElements/BookingStatus.vue'
import PaymentForm from './Forms/PaymentForm.vue'
import InvoiceComponent from './Forms/InvoiceComponent.vue'
import BookingShareModal from './BookingFormElements/BookingShareModal.vue'

import QtyButton from '@/vue/components/form-elements/QtyButton.vue'
import { useSelect } from '@/helpers/hooks/useSelect'
import moment from 'moment'

// Custom Service State & Form matching "Créer Services"
const showCustomServiceInput = ref(false)
const categoryList = ref([])
const custom_service_name = ref('')
const custom_service_duration = ref(30)
const custom_service_amount = ref('')
const custom_service_category_id = ref('')
const custom_service_description = ref('')
const custom_service_status = ref(true)
const custom_service_file = ref(null)
const custom_service_image_preview = ref(null)
const fileInputRef = ref(null)
const isSavingService = ref(false)

const triggerImageSelect = () => {
  if (fileInputRef.value) {
    fileInputRef.value.click()
  }
}

const onImageSelected = (e) => {
  const file = e.target.files[0]
  if (file) {
    custom_service_file.value = file
    const reader = new FileReader()
    reader.onload = (evt) => {
      custom_service_image_preview.value = evt.target.result
    }
    reader.readAsDataURL(file)
  }
}

const fetchCategories = async () => {
  try {
    const response = await fetch('/app/services/category_list', {
      headers: {
        'Accept': 'application/json',
        ...XSRF_REQUEST_HEADER()
      }
    })
    const data = await response.json()
    if (data && Array.isArray(data)) {
      categoryList.value = data
    }
  } catch (err) {
    console.error('Erreur chargement catégories:', err)
  }
}

watch(showCustomServiceInput, (val) => {
  if (val && categoryList.value.length === 0) {
    fetchCategories()
  }
})

const saveCustomService = async () => {
  if (!custom_service_name.value || !custom_service_duration.value || !custom_service_amount.value || !custom_service_category_id.value) {
    if (window.errorSnackbar) {
      window.errorSnackbar('Veuillez remplir tous les champs obligatoires (*).')
    }
    return
  }

  isSavingService.value = true

  const formData = new FormData()
  formData.append('name', custom_service_name.value)
  formData.append('duration_min', custom_service_duration.value)
  formData.append('default_price', custom_service_amount.value)
  formData.append('category_id', custom_service_category_id.value)
  formData.append('description', custom_service_description.value || '')
  formData.append('status', custom_service_status.value ? 1 : 0)
  if (employee_id.value) {
    formData.append('employee_id', employee_id.value)
  }
  if (custom_service_file.value) {
    formData.append('feature_image', custom_service_file.value)
  }

  try {
    const response = await fetch('/app/services', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...XSRF_REQUEST_HEADER()
      },
      body: formData
    })

    const res = await response.json()
    isSavingService.value = false

    if (response.ok && res.status && res.data) {
      const createdService = res.data
      
      if (window.successSnackbar) {
        window.successSnackbar('Service créé et enregistré avec succès dans la section Services !')
      }

      // Add to service.options dropdown if not present
      if (service.value && service.value.options && !service.value.options.some(opt => opt.value == createdService.id)) {
        service.value.options.push({
          value: createdService.id,
          label: createdService.name + ' (' + formatCurrencyVue(createdService.default_price) + ')'
        })
      }

      // Add to current appointment selectedService
      const bookingService = {
        id: null,
        start_date_time: moment(start_date_time.value || new Date()).format('YYYY-MM-DD HH:mm:ss'),
        service_name: createdService.name,
        employee_id: employee_id.value || null,
        booking_id: id.value || null,
        service_id: createdService.id,
        branch_id: branch_id.value,
        service_price: parseFloat(createdService.default_price),
        duration_min: parseInt(createdService.duration_min),
        is_custom: false
      }
      selectedService.value.push(bookingService)
      if (!services_id.value.includes(createdService.id)) {
        services_id.value.push(createdService.id)
      }
      resetServiceTime()

      // Reset form
      custom_service_name.value = ''
      custom_service_amount.value = ''
      custom_service_duration.value = 30
      custom_service_category_id.value = ''
      custom_service_description.value = ''
      custom_service_status.value = true
      custom_service_file.value = null
      custom_service_image_preview.value = null
      showCustomServiceInput.value = false
    } else {
      const msg = res.message || 'Erreur lors de la création du service.'
      if (window.errorSnackbar) {
        window.errorSnackbar(msg)
      }
    }
  } catch (err) {
    isSavingService.value = false
    const msg = err?.response?.data?.message || err?.message || 'Erreur lors de la création du service.'
    if (window.errorSnackbar) {
      window.errorSnackbar(msg)
    }
  }
}

const selectedBranchName = computed(() => {
  const b = branch.value.list.find((item) => item.id == branch_id.value)
  return b ? b.name : ''
})

const isManagerOnly = computed(() => {
  try {
    const rolesMeta = document.querySelector('meta[name="auth_user_roles"]')?.getAttribute('content')
    const roles = rolesMeta ? (typeof rolesMeta === 'string' ? JSON.parse(rolesMeta) : rolesMeta) : []
    return roles.includes('manager') && !roles.includes('admin') && !roles.includes('super-admin')
  } catch (e) {
    return false
  }
})

const handleOpenShareModal = () => {
  const modalElem = document.getElementById('shareBookingModal')
  if (modalElem) {
    const modal = bootstrap.Modal.getOrCreateInstance(modalElem)
    modal.show()
  }
}

const { getRequest, storeRequest, updateRequest, listingRequest } = useRequest()
// Event Emits
const emit = defineEmits(['onSubmit'])

const formatCurrencyVue = (value) => {
  if(window.currencyFormat !== undefined) {
    return window.currencyFormat(value)
  }
  return value
}
// Props
const props = defineProps({
  statusList: { type: Object },
  bookingType: { type: String, default: 'GLOBAL_BOOKING' },
  bookingData: {
    default: () => {
      return {
        id: 0,
        employee_id: null,
        start_date_time: null,
        branch_id: null
      }
    }
  }
})
const IS_SUBMITED = ref(false)
const filterStatus = (value) => {
  if(props.statusList) {
    return props.statusList[value]
  }
  return {is_disabled: false}
}

const current_date = ref(moment().format('YYYY-MM-DD'))
const config = ref({
  dateFormat: 'Y-m-d',
  defaultDate: 'today',
  static: true
})

watch(
  () => props.bookingType,
  (value) => {
 
  }
)

watch(
  () => props.bookingData,
  (value) => {
    status.value = 'pending'
    store.updateStep('LOADER')
    if (value.id !== null && value.id !== undefined && value.id !== 0) {
      id.value = value.id
      getRequest({ url: EDIT_URL, id: id.value }).then((res) => {
        if (res.status) {
          store.updateStep('MAIN')
          setFormData(res.data)
          branchSelect(res.data.branch_id)
          employeeSelect(res.data.employee_id)
        }
      })
    } else {
      store.updateStep('MAIN')
      setFormData(defaultData())
      branch_id.value = value.branch_id
      employee_id.value = value.employee_id
      start_date_time.value = moment(value.start_date_time).format('YYYY-MM-DD HH:mm:ss')
      if(value.start_date_time) {
        current_date.value = moment(value.start_date_time).format('YYYY-MM-DD')
      } else {
        current_date.value = moment().format('YYYY-MM-DD')
      }
      branchSelect(value.branch_id)
      employeeSelect(employee_id.value)
    }
  },
  { deep: true }
)

// Vee-Validation Validations
const validationSchema = yup.object({
  start_date_time: yup.string().required('L\'heure et la date sont requises'),
  branch_id: yup.string().required('Le salon est requis'),
  employee_id: yup.string().required('L\'employé est requis'),
  services_id: yup.array().nullable(),
  user_id: yup.string().required('Le client est requis')
})

const { handleSubmit, errors, resetForm } = useForm({ validationSchema })
const { value: id } = useField('id')
const { value: note } = useField('note')
const { value: start_date_time } = useField('start_date_time')
const { value: employee_id } = useField('employee_id')
const { value: branch_id } = useField('branch_id')
const { value: user_id } = useField('user_id')
const { value: status } = useField('status')
const { value: services_id } = useField('services_id')
const { value: product_variation_id } = useField('product_variation_id')
const { value: is_paid } = useField('is_paid')

status.value = 'pending'
product_variation_id.value = []
services_id.value = []

const errorMessages = ref({})

// Default FORM DATA
const defaultData = () => {
  errorMessages.value = {}
  return {
    id: null,
    branch_id: props.bookingData.branch_id || null,
    note: '',
    start_date_time: null,
    employee_id: props.bookingData.employee_id || null,
    status: 'pending',
    services_id: [],
    product_variation_id: [],
    is_paid: 0
  }
}

//  Reset Form
const setFormData = (data) => {
  IS_SUBMITED.value = false;
  newService.value = false
  if (data.status == 'checkout') {
    store.updateStep('CHECK_OUT')
  }
  if(data.services !== undefined && data.services.length > 0) {
    selectedService.value = data.services
  } else {
    resetServices()
  }

  if(data.products !== undefined && data.products.length > 0) {
    selectedProduct.value = data.products
  } else {
    resetProducts()
  }

  resetForm({
    values: {
      id: data.id,
      branch_id: data.branch_id,
      note: data.note,
      start_date_time: data.start_date_time,
      employee_id: data.employee_id,
      user_id: data.user_id,
      status: data.status,
      services_id: data.services_id,
      is_paid: data.is_paid,
      product_variation_id: data.product_variation_id
    }
  })
}

// Emit Listner Functions
const externalFormCreation = (e) => {
  switch (e.type) {
    case 'create_customer':
      getCustomers(() => (user_id.value = e.value))
      break
  }
}

// Select Options
const singleSelectOption = ref({
  createOption: true,
  closeOnSelect: true,
  searchable: true
})

const multipleSelectOption = ref({
  mode: 'multiple',
  closeOnSelect: false,
  searchable: true
})

const branch = ref({ options: [], list: [] })
const employee = ref({ options: [], list: [] })
const customer = ref({ options: [], list: [] })
const service = ref({ options: [], list: [] })

const slots = ref([])

useOnOffcanvasHide('booking-form', () => setFormData(defaultData()))
useOnOffcanvasShow('booking-form', () => {
  useSelect({ url: BRANCH_LIST }, { value: 'id', label: 'name' }).then((data) => {
    branch.value = data
    if (!branch_id.value && data.options && data.options.length > 0) {
      branch_id.value = data.options[0].value
    }
    branchSelect(branch_id.value)
  })
  branch_id.value = props.bookingData.branch_id || branch_id.value
  getCustomers()
  branchSelect(branch_id.value)
  getProducts()
})

const getCustomers = (cb) =>
  useSelect({ url: CUSTOMER_LIST }, { value: 'id', label: 'full_name' }).then((data) => {
    customer.value = data
    if (typeof cb == 'function') {
      cb()
    }
  })

const dateChange = () => {
  getSlots()
  start_date_time.value = null
}

const getSlots = () => {
  if (!branch_id.value) return
  listingRequest({ url: SLOT_LIST, data: { branch_id: branch_id.value, date: current_date.value } }).then((res) => {
    if (res.status) {
      slots.value = res.data
    }
  })
}
// On Select
const branchSelect = (value) => {
  if (!value) return
  useSelect({ url: EMPLOYEE_LIST, data: { branch_id: value } }, { value: 'id', label: 'name' }).then((data) => {
    employee.value = data
    if (employee_id.value) {
      employeeSelect(employee_id.value)
    }
  })
  getSlots()
}
const removeBranch = (value) => {
  employee_id.value = null
  start_date_time.value = null
  user_id.value = null
  selectedCustomer.value = null
  resetServices()
}
const employeeSelect = (value) => {
  if (!value) return
  useSelect({ url: SERVICE_LIST, data: { id: value, branch_id: branch_id.value } }, { value: 'service_id', label: 'service_name' }).then((data) => (service.value = data))
}
const removeEmployee = () => {
  resetServices()
}
const newCustomerData = ref(null)
const customerSelect = (value) => {
 
  if(_.isString(value)) {
    newCustomerData.value = {
      first_name: value.split(" ")[0] || '',
      last_name: value.split(" ")[1] || ''
    }
    bootstrap.Modal.getOrCreateInstance($('#exampleModal')).show()
    user_id.value = null
  }
}
const slotSelect = () => {
  resetServiceTime()
}
const removeSlot = () => {
  resetServiceTime()
}

//  Customer Select & Unselect & Selected Values
const selectedCustomer = computed(() => customer.value.list.find((customer) => customer.id == user_id.value) ?? null)
const selectedEmployee = computed(() => employee.value.list.find((employee) => employee.id == employee_id.value) ?? null)

const removeCustomer = () => {
  user_id.value = null
  services_id.value = []
  selectedService.value = []
}

//------------------ Start:- Service Module Logic -----------------//
const selectedService =ref([])
const resetServices = () => {
  selectedService.value = []
  services_id.value = []
}
const removeService = (id) => {
  const servicesIds = services_id.value
  services_id.value = servicesIds.filter((serviceid) => serviceid !== id)
  selectedService.value = selectedService.value.filter((BKservice) => BKservice.service_id !== id)
  resetServiceTime()
}
const newService = ref(false)
const serviceInput = ref(null)
const addNewService = (value) => {
  newService.value = true
  setTimeout(() => {
    serviceInput.value.open()
  }, 100)
}
const serviceSelect = (value) => {
  const filteredService = service.value.list.find((ser) => ser.service_id == value)
  
  // Ajoute l'ID au tableau validé par Vee-Validate
  if (!services_id.value.includes(value)) {
    services_id.value.push(value)
  }

  const bookingService = {
    id: null,
    start_date_time: null,
    service_name: filteredService.service_name,
    employee_id: employee_id.value,
    booking_id: null,
    service_id: value,
    branch_id: branch_id.value,
    service_price: filteredService.service_price,
    duration_min: filteredService.duration_min,
  }
  selectedService.value.push(bookingService)
  resetServiceTime()
  newService.value = false
}
const resetServiceTime = () => {
  let startTime = moment(start_date_time.value)
  selectedService.value.forEach((bookingService, index) => {
    if(index > 0) {
      const lastService = selectedService.value[index - 1]
      startTime = moment(lastService.start_date_time)
      startTime = startTime.add(lastService.duration_min,'minutes')
    }
    bookingService.start_date_time = startTime.format('YYYY-MM-DD HH:mm:ss')
    selectedService.value[index] = bookingService
  })
}

//------------------ End:- Service Module Logic -----------------//

//------------------ Start:- Product Module Logic -----------------//

const newProduct = ref(false)
const productInput = ref(null)
const addNewProduct = (value) => {

  newProduct.value = true
  setTimeout(() => {
    productInput.value.open()
  }, 100)
}

const resetProducts = () => {
  selectedProduct.value = []
  product_variation_id.value = []
}

const products = ref({ options: [], list: [] })
const getProducts = () => {
  // useSelect({ url: PRODUCT_LIST }, { value: 'id', label: 'text' }).then((data) => (products.value = data))
  useSelect({ url: PRODUCT_LIST }, { value: 'id', label: 'text' }).then((data) => {
  
    products.value = data;

    // Now you can log the data
    console.log(data);
}).catch((error) => {
    // Handle errors if needed
    console.error(error);
});
}

const selectedProduct = ref([])

const selectProduct = (value) => {

  const filteredProduct = products.value.list.find((pr) => pr.id == value)

  const product_variation = JSON.parse(filteredProduct.extra_data)


  const bookingProduct = {
    id: null,
    product_name: filteredProduct.text,
    booking_id: id.value,
    employee_id: employee_id.value,
    order_id: null,
    product_id: product_variation.product_id,
    product_variation_id: value,
    product_price: product_variation.price,
    discounted_price: product_variation.discounted_price,
    discount_type: product_variation.discount_type,
    discount_value: product_variation.discount_value,
    product_qty: 1,
    variation_name: product_variation.variation_name,
    max_qty: product_variation.qty
  }

  selectedProduct.value.push(bookingProduct)



  newProduct.value = false
}

const removeProduct = (id) => {
  const productsIds = product_variation_id.value
  product_variation_id.value = productsIds.filter((productid) => productid !== id)
  selectedProduct.value = selectedProduct.value.filter((BKproduct) => BKproduct.product_variation_id !== id)
}



//------------------ End:- Product Module Logic -----------------//

const payment_data = ref(null)
const stripe_payment_data = ref(null)
const store = useBookingStore()
const SINLGE_STEP = computed(() => store.singleStep)
const SUB_TOTAL_SERVICE_AMOUNT = computed(() => selectedService.value.reduce((total, service) => total + service.service_price, 0) + selectedProduct.value.reduce((total, product) => total + ((product.discounted_price ? product.discounted_price : product.product_price) * product.product_qty), 0))
const formSubmit = handleSubmit((values) => {
  if(!IS_SUBMITED.value) {
    IS_SUBMITED.value = true;
    values['services'] = selectedService.value
    values['products'] = selectedProduct.value
    if (id.value > 0) {
      
      updateRequest({ url: UPDATE_URL, id: id.value, body: values }).then((res) => {
        submiting_booking(res)
      })
    } else {

      storeRequest({ url: STORE_URL, body: values }).then((res) => {
        submiting_booking(res)
      })
    }
  }
})
const updateStatus = (data) => {
  setFormData(data)
  emit('onSubmit')
}
const submiting_booking = (res) => {
  IS_SUBMITED.value = false;
  if (res.status) {
    window.successSnackbar(res.message)
    if(props.bookingType == 'CALENDER_BOOKING') {
      setFormData(res.data)
    } else {
      setFormData(defaultData())
      const elem = document.getElementById('booking-form')
      const form = bootstrap.Offcanvas.getOrCreateInstance(elem)
      form.hide()
      if(document.getElementById('booking-datatable') != null) {
        window.renderedDataTable.ajax.reload(null, false)
      }
    }
  } else {
    window.errorSnackbar(res.message)
  }
  emit('onSubmit')
}
const formSubmitCheckout = () => {
  if(!IS_SUBMITED.value) {
    const values = {services: selectedService.value, products: selectedProduct.value}
    IS_SUBMITED.value = true;
    if(is_paid.value) {
      const data = {
        status: 'completed',
      }
      updateRequest({ url: UPDATE_STATUS, id: id.value, body: data }).then((res) => {
        if(res.status) {
          store.updateStep('MAIN')
          window.successSnackbar(res.message)
          updateStatus(res.data)
        }
      })
    } else {
      updateRequest({ url: CHECKOUT_URL, id: id.value, body: values }).then((res) => {
        if (res.status) {
          setFormData(res.data)
          submiting_booking(res)
          store.updateStep('PAYMENT')
        }
      })
    }
  }
}
const updatePaymentData = (data) => {
  payment_data.value = data
}
const formSubmitPaynow = () => {
  if(!IS_SUBMITED.value) {
    IS_SUBMITED.value = true;
    updateRequest({ url: PAYMENT_PUT_URL, id: id.value, body: payment_data.value }).then((res) => {

      switch(res.data.payment_method) {
        case 'razorpay':

          if(res.data.public_key !='') {

          openRazorpay(res.data);
          }else{

            window.errorSnackbar('Razorpay key does not exist')
            errorMessages.value = 'Razorpay key does not exist'
          }
          break;

        case 'stripe':
          stripe_payment_data.value = {
            booking_transaction_id: res.data.booking_transaction_id,
            currency: res.data.currency,
            payment_method: res.data.payment_method,
            total_amount: res.data.total_amount,
          };

          if(res.data.public_key !=''){

            openStripe(stripe_payment_data.value);

            }else{

              window.errorSnackbar('Stripe Secret key does not exist')
              errorMessages.value = 'Stripe Secret key does not exist'
          }

          break;

        default:
          submiting_booking(res.data);
          setFormData(res.data.data);
          store.updateStep('MAIN');
          break;
      }
    })
  }
}

const openRazorpay = (data) => {

  var options = {
    key: data.public_key, // Enter the Key ID generated from the Dashboard
    amount: data.total_amount * 100, // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
    currency: data.currency,
    name: 'Acme Corp', //your business name
    description: 'Test Transaction',
    image: 'https://example.com/your_logo',

    handler: function (response) {
      response.razorpay_payment_id = response.razorpay_payment_id
      response.total_amount = data.total_amount
      response.currency = data.currency

      updateRequest({ url: UPDATE_PAYMENT_DATA, id: data.booking_transaction_id, body: { response } }).then((res) => {
        submiting_booking(res.data)
        setFormData(res.data.booking)
        store.updateStep('MAIN')
      })
    },

    notes: {
      address: 'Razorpay Corporate Office'
    },
    theme: {
      color: '#3399cc'
    }
  }
  var rzp1 = new Razorpay(options)
  rzp1.on('payment.failed', function (response) {
    window.errorSnackbar(response.error.description)
    errorMessages.value = response.error.description
  })

  rzp1.open()
}

const openStripe = (data) => {

  storeRequest({ url: STRIPE_PAYMENT_DATA, body: { data } }).then((res) => {

    if(res.status == true){

      var newWindow = window.open(res.data_url, '_blank');

    }else{

      window.errorSnackbar(res.data.message)
      errorMessages.value = res.data.message

    }

  })
}
</script>

<style scoped>
.offcanvas {
  box-shadow: none;
}
.service-duration {
  position: absolute;
  /* padding: 2px 8px; */
  bottom: -16px;
  border-radius: 0;
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 4px;
  right: 0;
}

.border-br-radius-0 {
  border-bottom-right-radius: 0;
}

[dir='rtl'] .border-br-radius-0 {
  border-bottom-left-radius: 0;
}
.date-time {
  border-top: 1px solid var(--bs-border-color);
}
.date-time > div:not(:first-child) {
  border-left: 1px solid var(--bs-border-color);
}
.list-group-flush > .list-group-item {
  color: var(--bs-body-color);
}

/* Vous pouvez modifier la couleur et la taille des textes d'erreur ici si besoin */
.text-danger.small {
  font-size: 0.85em;
  margin-top: 4px;
  display: inline-block;
}
</style>