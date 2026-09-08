<template>
  <form @submit="formSubmit">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel">
      <FormHeader :currentId="currentId" :editTitle="editTitle" :createTitle="createTitle"></FormHeader>
      <div class="offcanvas-body">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body">
            <!-- Photo du produit -->
            <div class="text-center mb-4">
              <div class="position-relative d-inline-block">
                <img :src="ImageViewer || defaultImage" alt="feature-image" class="img-fluid rounded-4 shadow-sm border" style="width: 140px; height: 140px; object-fit: cover;" />
                <div class="mt-2 d-flex align-items-center justify-content-center gap-2">
                  <input type="file" ref="profileInputRef" class="form-control d-none" id="feature_image" name="feature_image" @change="fileUpload" accept=".jpeg, .jpg, .png, .gif, .webp" />
                  <label class="btn btn-sm btn-primary rounded-pill px-3" for="feature_image">
                    <i class="fa-solid fa-camera me-1"></i> {{ $t('messages.upload') || 'Photo' }}
                  </label>
                  <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" @click="removeLogo()" v-if="ImageViewer && ImageViewer !== defaultImage">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Champs Produit (Identiques au Mobile) -->
            <div class="row g-3">
              <!-- Nom du produit -->
              <div class="col-12">
                <label class="form-label fw-bold">Nom du produit <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-tag text-primary"></i></span>
                  <input type="text" class="form-control border-start-0" placeholder="Ex: Huile à barbe Premium" v-model="name" required />
                </div>
                <span class="text-danger small" v-if="errors['name']">{{ errors['name'] }}</span>
              </div>

              <!-- Quantité en stock -->
              <div class="col-md-12">
                <label class="form-label fw-bold">Quantité en stock <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-boxes-stacked text-primary"></i></span>
                  <input type="number" min="0" step="1" class="form-control border-start-0" placeholder="Ex: 50" v-model="stock" required />
                </div>
                <span class="text-danger small" v-if="errors['stock']">{{ errors['stock'] }}</span>
              </div>

              <!-- Prix d'achat (FCFA) -->
              <div class="col-md-6">
                <label class="form-label fw-bold">Prix d'achat (FCFA) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-money-bill-wave text-secondary"></i></span>
                  <input type="number" min="0" step="1" class="form-control border-start-0" placeholder="Ex: 3000" v-model="purchase_price" required />
                </div>
                <span class="text-danger small" v-if="errors['purchase_price']">{{ errors['purchase_price'] }}</span>
              </div>

              <!-- Prix de vente (FCFA) -->
              <div class="col-md-6">
                <label class="form-label fw-bold">Prix de vente (FCFA) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-coins text-success"></i></span>
                  <input type="number" min="0" step="1" class="form-control border-start-0" placeholder="Ex: 5000" v-model="price" required />
                </div>
                <span class="text-danger small" v-if="errors['price']">{{ errors['price'] }}</span>
              </div>

              <!-- Description -->
              <div class="col-12">
                <label class="form-label fw-bold">Description</label>
                <textarea class="form-control" rows="3" placeholder="Détails du produit (optionnel)..." v-model="description"></textarea>
              </div>

              <!-- Statut & Mise en avant -->
              <div class="col-12 pt-2">
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                  <div class="d-flex align-items-center gap-2">
                    <div class="form-check form-switch mb-0">
                      <input class="form-check-input" :value="status" :checked="status == 1" :true-value="1" :false-value="0" id="product_status" type="checkbox" v-model="status" />
                    </div>
                    <label class="form-check-label fw-bold mb-0 cursor-pointer" for="product_status">
                      {{ status == 1 ? 'Actif (Disponible)' : 'Inactif' }}
                    </label>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <div class="form-check form-switch mb-0">
                      <input class="form-check-input" :value="is_featured" :checked="is_featured == 1" :true-value="1" :false-value="0" id="product_featured" type="checkbox" v-model="is_featured" />
                    </div>
                    <label class="form-check-label small mb-0 cursor-pointer" for="product_featured">
                      Mettre en vedette
                    </label>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
      <FormFooter :IS_SUBMITED="IS_SUBMITED"></FormFooter>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { EDIT_URL, STORE_URL, UPDATE_URL } from '../constant/product'
import { useField, useForm } from 'vee-validate'
import { useModuleId, useRequest, useOnOffcanvasHide } from '@/helpers/hooks/useCrudOpration'
import * as yup from 'yup'
import { readFile } from '@/helpers/utilities'
import FormHeader from '@/vue/components/form-elements/FormHeader.vue'
import FormFooter from '@/vue/components/form-elements/FormFooter.vue'

// Props
const props = defineProps({
  createTitle: { type: String, default: 'Ajouter un produit' },
  editTitle: { type: String, default: 'Modifier le produit' },
  defaultImage: { type: String, default: 'https://dummyimage.com/600x300/cfcfcf/000000.png' }
})

const { getRequest, storeRequest, updateRequest } = useRequest()

// Validations
const validationSchema = yup.object({
  name: yup.string().required('Le nom du produit est requis').max(190),
  price: yup.number().typeError('Prix de vente requis').required('Prix de vente requis').min(0),
  purchase_price: yup.number().typeError('Prix d\'achat requis').required('Prix d\'achat requis').min(0),
  stock: yup.number().typeError('Quantité en stock requise').required('Quantité en stock requise').min(0)
})

const { handleSubmit, errors, resetForm } = useForm({
  validationSchema
})

const { value: name } = useField('name')
const { value: price } = useField('price')
const { value: purchase_price } = useField('purchase_price')
const { value: stock } = useField('stock')
const { value: description } = useField('description')
const { value: status } = useField('status')
const { value: is_featured } = useField('is_featured')
const { value: feature_image } = useField('feature_image')

const ImageViewer = ref(null)
const profileInputRef = ref(null)
const errorMessages = ref({})
const IS_SUBMITED = ref(false)

const fileUpload = async (e) => {
  let file = e.target.files[0]
  if (file) {
    await readFile(file, (fileB64) => {
      ImageViewer.value = fileB64
      profileInputRef.value.value = ''
    })
    feature_image.value = file
  }
}

const removeLogo = () => {
  ImageViewer.value = props.defaultImage
  feature_image.value = null
}

const defaultData = () => {
  errorMessages.value = {}
  ImageViewer.value = props.defaultImage
  return {
    name: '',
    price: '',
    purchase_price: '',
    stock: 1,
    description: '',
    status: 1,
    is_featured: 0,
    feature_image: null
  }
}

const setFormData = (data) => {
  ImageViewer.value = data.feature_image || props.defaultImage
  resetForm({
    values: {
      name: data.name || '',
      price: data.price !== undefined ? data.price : (data.max_price || ''),
      purchase_price: data.purchase_price !== undefined ? data.purchase_price : (data.min_price || ''),
      stock: data.stock !== undefined ? data.stock : (data.stock_qty !== undefined ? data.stock_qty : 0),
      description: data.short_description || data.description || '',
      status: data.status !== undefined ? data.status : 1,
      is_featured: data.is_featured !== undefined ? data.is_featured : 0,
      feature_image: data.feature_image || null
    }
  })
}

const currentId = useModuleId(() => {
  if (currentId.value > 0) {
    getRequest({ url: EDIT_URL, id: currentId.value }).then((res) => {
      if (res.status && res.data) {
        setFormData(res.data)
      }
    })
  } else {
    setFormData(defaultData())
  }
})

useOnOffcanvasHide('form-offcanvas', () => {
  setFormData(defaultData())
})

const reset_datatable_close_offcanvas = (res) => {
  IS_SUBMITED.value = false
  if (res.status) {
    if (window.successSnackbar) window.successSnackbar(res.message)
    if (window.renderedDataTable) window.renderedDataTable.ajax.reload(null, false)
    const offcanvasEl = document.querySelector('#form-offcanvas')
    if (offcanvasEl && window.bootstrap) {
      const offcanvasInstance = window.bootstrap.Offcanvas.getInstance(offcanvasEl)
      if (offcanvasInstance) offcanvasInstance.hide()
    }
    setFormData(defaultData())
  } else {
    if (window.errorSnackbar) window.errorSnackbar(res.message)
    errorMessages.value = res.all_message || {}
  }
}

const formSubmit = handleSubmit((values) => {
  if (IS_SUBMITED.value) return false
  IS_SUBMITED.value = true

  const formData = {
    name: values.name,
    price: values.price,
    selling_price: values.price,
    purchase_price: values.purchase_price,
    min_price: values.purchase_price,
    max_price: values.price,
    stock: values.stock,
    stock_qty: values.stock,
    description: values.description,
    short_description: values.description,
    status: values.status,
    is_featured: values.is_featured,
    has_variation: 0,
    combinations: '[]',
    category_ids: '[]',
    tags: '[]'
  }

  if (feature_image.value instanceof File) {
    formData.feature_image = feature_image.value
  }

  if (currentId.value > 0) {
    updateRequest({ url: UPDATE_URL, id: currentId.value, body: formData, type: 'file' })
      .then((res) => reset_datatable_close_offcanvas(res))
      .catch(() => { IS_SUBMITED.value = false })
  } else {
    storeRequest({ url: STORE_URL, body: formData, type: 'file' })
      .then((res) => reset_datatable_close_offcanvas(res))
      .catch(() => { IS_SUBMITED.value = false })
  }
})

onMounted(() => {
  if (currentId.value <= 0) {
    setFormData(defaultData())
  }
})
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
