<template>
  <div class="booking-wizard">
    <div class="container-fluid p-3">
      <h2 class="fw-bold mb-4 text-center">Réservations</h2>
      <!-- BANDEAU SUPÉRIEUR VISIBLE : ÉVALUER NOS PRESTATIONS -->
      <div class="d-flex align-items-center justify-content-between p-3 mb-3 bg-white rounded-4 shadow-sm border">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold fs-6">
            <i class="fa-solid fa-star me-1 text-dark"></i> Avis Clients
          </span>
          <span class="text-dark fw-bold small d-none d-md-inline">
            Vous avez déjà effectué un rendez-vous chez nous ? Partagez votre avis !
          </span>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" @click="showReviewModal = true">
          <i class="fa-solid fa-star text-warning"></i> Évaluer nos prestations
        </button>
      </div>

      <div class="booking-container mx-auto">
        <!-- STEPPER HORIZONTAL / COMPACT -->
        <div class="stepper-wrapper mb-5 non-printable">
          <ul class="stepper-list d-flex justify-content-between align-items-center m-0 p-0">
            <template v-for="(item, index) in setupArray" :key="`items-${index}`">
              <li 
                v-if="item.is_vissible"
                :class="['stepper-item', activeCheck(item.id), doneCheck(item.id) ? 'completed' : '']"
                @click="doneCheck(item.id) || activeCheck(item.id) ? nextTabChange(item.id) : null"
              >
                <div class="stepper-icon-wrapper">
                  <div class="stepper-icon">
                    <i class="fa-solid fa-check" v-if="doneCheck(item.id)"></i>
                    <span v-else>{{ index + 1 }}</span>
                  </div>
                </div>
                <div class="stepper-text d-none d-md-block">
                  <h6 class="mb-0 fw-bold">{{ item.title }}</h6>
                </div>
              </li>
            </template>
          </ul>
        </div>

        <!-- ZONE DE CONTENU CENTRAL -->
        <div class="booking-content-area shadow-lg rounded-4 bg-white p-4 p-md-5">
          <div id="wizard-tab" class="tab-content">
            <template v-for="(item, index) in setupArray" :key="`panel-${index}`">
              <div
                :id="item.type"
                :class="`iq-fade iq-tab-pannel ${activeCheck(item.id)}`"
              >
                <div class="mb-4 text-center">
                  <h3 class="fw-bold text-dark">{{ item.title }}</h3>
                  <p class="text-muted" v-if="item.detail">{{ item.detail }}</p>
                </div>
                <TabPanel
                  :type="item.type"
                  :title="item.title"
                  :wizard-next="item.next"
                  :wizard-prev="item.prev"
                  @onClick="nextTabChange"
                />
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALE D'ÉVALUATION DES PRESTATIONS -->
    <div v-if="showReviewModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
          <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-star fs-4 text-warning"></i>
              <h5 class="modal-title fw-bold text-white mb-0">Évaluer nos prestations</h5>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="closeReviewModal"></button>
          </div>

          <div class="modal-body p-4">
            <!-- ÉTAPE 1 : Vérification par Téléphone -->
            <div v-if="reviewStep === 'check'" class="text-center py-3">
              <div class="mb-3">
                <i class="fa-solid fa-user-check fs-1 text-primary mb-2"></i>
                <h5 class="fw-bold text-dark">Évaluer nos prestations</h5>
                <p class="text-muted small">Saisissez votre numéro de téléphone pour accéder au formulaire d'évaluation.</p>
              </div>

              <div class="form-group max-width-400 mx-auto mb-3" style="max-width: 380px;">
                <input type="text" v-model="identifier" class="form-control form-control-lg text-center border-primary fw-bold" placeholder="ex: +22670000000" />
              </div>

              <div v-if="checkError" class="alert alert-warning alert-dismissible fade show max-width-500 mx-auto text-start mb-3" style="max-width: 480px;">
                <i class="fa-solid fa-circle-exclamation me-2 text-warning fs-5"></i>
                <strong>Accès non autorisé :</strong> {{ checkError }}
                <div class="mt-2 text-end">
                  <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="startNewBooking">
                    <i class="fa-solid fa-calendar-plus me-1"></i> Réserver une prestation
                  </button>
                </div>
              </div>

              <button type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold" :disabled="isChecking" @click="verifyEligibility">
                <span v-if="isChecking"><span class="spinner-border spinner-border-sm me-1"></span> Vérification...</span>
                <span v-else><i class="fa-solid fa-magnifying-glass me-1"></i> Vérifier mon éligibilité</span>
              </button>
            </div>

            <!-- ÉTAPE 2 : Formulaire de Saisie d'Avis (Image 1) -->
            <div v-else-if="reviewStep === 'form'">
              <!-- Header Image 1 -->
              <div class="text-center p-3 mb-4 rounded-3 text-white" style="background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);">
                <span class="badge bg-white text-primary rounded-pill px-3 py-1 fw-bold text-uppercase mb-2"><i class="fa-solid fa-scissors me-1"></i> Réservation Effectuée</span>
                <h4 class="text-white fw-bold mb-1">Votre Avis sur la Prestation</h4>
                <small class="text-white-50">Partagez votre expérience avec le salon et votre coiffeur</small>
              </div>

              <!-- Badge Client (Kabore Samuel / Client) -->
              <div class="p-3 mb-3 rounded-3 d-flex align-items-center justify-content-between" style="background: rgba(111,66,193,0.08); border: 1px dashed rgba(111,66,193,0.3);">
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar avatar-50 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5">
                    {{ eligibleUser.initials }}
                  </div>
                  <div>
                    <h6 class="mb-0 fw-bold text-dark">{{ eligibleUser.full_name }}</h6>
                    <small v-if="eligibleUser.is_verified" class="text-success fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i> Client vérifié - RDV Terminé</small>
                    <small v-else class="text-warning fw-bold"><i class="fa-solid fa-clock text-warning me-1"></i> Client non vérifié (En attente par le salon)</small>
                  </div>
                </div>
                <span class="badge bg-soft-success text-success fw-bold">RDV Honoré</span>
              </div>

              <!-- Formulaire de Saisie : Coiffeur Sélectionné de base (VERROUILLÉ) -->
              <div class="form-group mb-3">
                <label class="form-label fw-bold text-dark small mb-1"><i class="fa-solid fa-user-tie me-1 text-primary"></i> Coiffeur / Employé du Rendez-vous (Verrouillé) :</label>
                <input type="text" class="form-control border-primary bg-light fw-bold text-dark" :value="bookingEmployee.name" readonly disabled />
              </div>

              <!-- Note Étoiles -->
              <div class="form-group mb-3 text-center">
                <label class="form-label fw-bold text-dark small d-block mb-1"><i class="fa-solid fa-star me-1 text-warning"></i> Votre Note Globale :</label>
                <div class="d-flex justify-content-center gap-2 fs-2 text-warning cursor-pointer">
                  <i v-for="star in 5" :key="star" :class="star <= reviewRating ? 'fa-solid fa-star' : 'fa-regular fa-star text-muted'" @click="reviewRating = star" style="cursor: pointer;"></i>
                </div>
              </div>

              <!-- Commentaire -->
              <div class="form-group mb-4">
                <label class="form-label fw-bold text-dark small mb-1"><i class="fa-solid fa-comment-dots me-1 text-primary"></i> Votre Commentaire :</label>
                <textarea v-model="reviewMsg" class="form-control" rows="4" placeholder="Expliquez ce qui vous a plu lors de votre rendez-vous..."></textarea>
              </div>

              <div v-if="reviewSubmittedSuccess" class="alert alert-success mb-3">
                <i class="fa-solid fa-circle-check me-2"></i> <strong>Merci !</strong> Votre avis a été enregistré avec succès et mis à jour dans le tableau des avis du salon.
              </div>

              <button type="button" class="btn btn-primary w-100 py-3 rounded-pill fw-bold text-uppercase shadow" :disabled="isSubmittingReview" @click="submitReview">
                <span v-if="isSubmittingReview"><span class="spinner-border spinner-border-sm me-1"></span> Envoi en cours...</span>
                <span v-else><i class="fa-solid fa-paper-plane me-2"></i> Envoyer mon Avis</span>
              </button>
            </div>
          </div>

          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" @click="closeReviewModal">Fermer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import TabPanel from "./TabPanel.vue";
import { useQuickBooking } from "../store/quick-booking";

const store = useQuickBooking();

// Modal Évaluation
const showReviewModal = ref(false);
const reviewStep = ref('check'); // 'check' ou 'form'
const identifier = ref('samuel.kabore@salon.bf');
const isChecking = ref(false);
const checkError = ref('');

const eligibleUser = ref({ id: null, full_name: '', initials: '', is_verified: false });
const bookingEmployee = ref({ id: null, name: '' });
const reviewEmployeeId = ref(null);
const reviewRating = ref(5);
const reviewMsg = ref('');
const isSubmittingReview = ref(false);
const reviewSubmittedSuccess = ref(false);

const verifyEligibility = async () => {
  if (!identifier.value.trim()) {
    checkError.value = "Veuillez saisir votre numéro de téléphone ou votre adresse e-mail.";
    return;
  }
  checkError.value = "";
  isChecking.value = true;
  try {
    const res = await fetch(`/api/quick-booking/check-review-eligibility?identifier=${encodeURIComponent(identifier.value.trim())}`);
    const data = await res.json();
    if (data.can_review) {
      eligibleUser.value = data.user;
      bookingEmployee.value = data.booking_employee;
      reviewEmployeeId.value = data.booking_employee.id;
      reviewStep.value = 'form';
    } else {
      checkError.value = data.message;
    }
  } catch (e) {
    checkError.value = "Erreur lors de la vérification de l'éligibilité.";
  } finally {
    isChecking.value = false;
  }
};

const submitReview = async () => {
  if (!reviewEmployeeId.value || !reviewMsg.value.trim()) {
    alert("Veuillez rédiger un commentaire avant d'envoyer votre avis.");
    return;
  }
  isSubmittingReview.value = true;
  try {
    const res = await fetch('/public-save-review', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        user_id: eligibleUser.value.id,
        employee_id: reviewEmployeeId.value,
        rating: reviewRating.value,
        review_msg: reviewMsg.value
      })
    });
    const data = await res.json();
    if (data.status) {
      reviewSubmittedSuccess.value = true;
      setTimeout(() => {
        closeReviewModal();
      }, 2000);
    } else {
      alert(data.message || "Erreur lors de l'enregistrement de votre avis.");
    }
  } catch (e) {
    alert("Erreur lors de l'enregistrement de votre avis.");
  } finally {
    isSubmittingReview.value = false;
  }
};

const closeReviewModal = () => {
  showReviewModal.value = false;
  reviewStep.value = 'check';
  checkError.value = '';
  reviewSubmittedSuccess.value = false;
};

const startNewBooking = () => {
  closeReviewModal();
  currentindex.value = 1;
};

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const bId = urlParams.get('salon_id') || urlParams.get('branch_id');
  if (bId) {
    store.updateBookingValues({ key: 'branch_id', value: parseInt(bId) });
  } else {
    store.updateBookingValues({ key: 'branch_id', value: 1 });
  }
  currentindex.value = 1;
});

// Setup Array (Public Booking without Salon Selection)
const setupArray = reactive([
  {
    id: 1,
    title: "Sélectionner un service",
    type: "select-service",
    is_vissible: true,
    detail: "Sélectionnez le service souhaité parmi les options disponibles.",
    done: false,
    next: 2,
    prev: null,
  },
  {
    id: 2,
    title: "Sélectionner le personnel",
    type: "select-employee",
    is_vissible: true,
    detail: "Choisissez votre membre du personnel préféré pour le service.",
    done: false,
    next: 3,
    prev: 1,
  },
  {
    id: 3,
    title: "Sélectionner la date et l'heure",
    type: "select-date-time",
    is_vissible: true,
    detail: "Choisissez une date et une heure appropriées pour votre réservation.",
    done: false,
    next: 4,
    prev: 2,
  },
  {
    id: 4,
    title: "Détails du client",
    type: "customer-details",
    is_vissible: true,
    detail: "Entrez vos informations personnelles.",
    done: false,
    next: 5,
    prev: 3,
  },
  {
    id: 5,
    title: "Confirmation",
    type: "select-confirm",
    is_vissible: true,
    detail: "Confirmez votre réservation.",
    done: false,
    next: 6,
    prev: 4,
  },
  {
    id: 6,
    title: "Détails de la confirmation",
    type: "confirmation-detail",
    is_vissible: false,
    detail: "Confirmation des détails de votre réservation.",
    done: false,
    next: null,
    prev: null,
  },
]);
const currentindex = ref(1);
const activeCheck = (value) => (currentindex.value == value ? "active" : "");
const doneCheck = (value) => (currentindex.value > value ? true : false);
const nextTabChange = (val) => {
  currentindex.value = val;
};
</script>

<style scoped>
.booking-wizard {
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
}

.widget-layout {
  display: grid;
  grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
  gap: 1rem;
}

@media (max-width: 768px) {
  .widget-layout {
    grid-template-columns: 1fr;
  }

  .widget-tabs {
    overflow-x: auto;
  }

  .tab-list {
    display: flex;
    gap: .5rem;
    overflow-x: auto;
    white-space: nowrap;
    padding: .5rem;
  }

  .tab-item {
    flex: 0 0 auto;
  }

  .widget-pannel,
  .tab-content {
    min-width: 0;
  }

  .booking-wizard .container-fluid {
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
  }

  .booking-wizard .d-flex.align-items-center.justify-content-between.p-3 {
    flex-direction: column;
    align-items: stretch !important;
    gap: 0.75rem;
  }

  .booking-wizard .d-flex.align-items-center.justify-content-between.p-3 .btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 576px) {
  .booking-wizard {
    font-size: 13px;
  }

  .card-list-data {
    min-height: auto;
    max-height: none;
  }
}
</style>
