<template>
  <div class="modal fade" id="shareBookingModal" tabindex="-1" aria-labelledby="shareBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content glass-share-card">
        <div class="modal-header border-0 pb-0">
          <div class="d-flex align-items-center gap-2">
            <div class="share-icon-wrapper">
              <i class="fa-solid fa-share-nodes"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-gradient mb-0" id="shareBookingModalLabel">Inviter des Clients au Salon</h5>
              <small class="text-muted">Partagez la page de réservation publique directement pour votre salon</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-4">
          <!-- Selection Obligatoire du Salon pour l'Administrateur -->
          <div v-if="branches.length > 1 || !isManager" class="salon-selection-card p-3 mb-3 rounded-3 border bg-light">
            <label class="form-label small fw-bold text-primary mb-1">
              <i class="fa-solid fa-store me-1"></i> Sélectionner le salon à partager (Obligatoire pour l'Administrateur) :
            </label>
            <select v-model="selectedBranchId" @change="onBranchChange" class="form-select form-select-sm fw-bold border-primary">
              <option v-for="b in branches" :key="b.id" :value="b.id">
                {{ b.name }} {{ b.contact_number ? '(' + b.contact_number + ')' : '' }}
              </option>
            </select>
            <small class="text-muted d-block mt-1">
              <i class="fa-solid fa-circle-info me-1"></i> Le lien généré ci-dessous bloquera automatiquement la réservation sur le salon sélectionné.
            </small>
          </div>

          <!-- Multi-vendor Salon info badge -->
          <div class="salon-badge-info p-3 mb-3 rounded-3 d-flex align-items-center justify-content-between">
            <div>
              <small class="text-uppercase tracking-wider text-muted fw-bold d-block">Salon Sélectionné</small>
              <strong class="fs-6 text-dark"><i class="fa-solid fa-store me-1 text-primary"></i> {{ selectedBranchName || 'Salon Principal' }}</strong>
            </div>
            <a :href="shareUrl" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
              <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ouvrir la Page Publique
            </a>
          </div>

          <!-- Network Search Filter & Domain Customizer -->
          <div class="row g-2 mb-3">
            <div class="col-md-7">
              <label class="form-label small text-muted mb-1">Rechercher un réseau social :</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" v-model="searchQuery" class="form-control bg-light border-start-0" placeholder="ex: TikTok, WhatsApp, X..." />
              </div>
            </div>
            <div class="col-md-5">
              <label class="form-label small text-muted mb-1">Domaine / Domaine personnalisé :</label>
              <div class="input-group input-group-sm">
                <input type="text" v-model="customHost" class="form-control bg-light" placeholder="ex: mon-salon.com" />
              </div>
            </div>
          </div>

          <!-- Social Media Grid -->
          <p class="small text-muted fw-semibold mb-2">Partager sur vos réseaux sociaux :</p>
          <div class="row g-3 mb-4">
            <div v-for="net in filteredNetworks" :key="net.id" class="col-3 col-md-3 text-center">
              <a :href="net.url" @click="handleSocialClick(net, $event)" target="_blank" :class="['social-btn', net.cssClass]">
                <i :class="net.icon + ' fs-3'"></i>
                <span class="d-block small mt-1 font-weight-bold">{{ net.name }}</span>
              </a>
            </div>
          </div>

          <!-- Native Web Share Button -->
          <div class="mb-3 text-center" v-if="canNativeShare">
            <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-4" @click="nativeShare">
              <i class="fa-solid fa-mobile-screen-button me-1"></i> Partager via mes applications mobiles
            </button>
          </div>

          <!-- Direct Link Section -->
          <div class="form-group">
            <label class="form-label small fw-bold">Lien de réservation directe du salon (Page client) :</label>
            <div class="input-group">
              <input type="text" readonly class="form-control form-control-sm bg-light text-primary fw-bold" :value="shareUrl" />
              <a :href="shareUrl" target="_blank" class="btn btn-outline-secondary btn-sm px-2" title="Tester le lien">
                <i class="fa-solid fa-external-link"></i>
              </a>
              <button type="button" class="btn btn-primary btn-sm px-3" @click="copyLink">
                <i class="fa-regular fa-copy me-1" v-if="!copied"></i>
                <i class="fa-solid fa-check me-1 text-white" v-else></i>
                {{ copied ? 'Copié !' : 'Copier le lien' }}
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
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const props = defineProps({
  branchId: { type: [Number, String], default: null },
  branchName: { type: String, default: '' },
  isManager: { type: Boolean, default: false }
})

const copied = ref(false)
const searchQuery = ref('')
const customHost = ref('')
const canNativeShare = ref(false)

const branches = ref([])
const selectedBranchId = ref(props.branchId || 1)
const selectedBranchName = ref(props.branchName || '')

const fetchBranches = async () => {
  try {
    const res = await fetch('/api/quick-booking/branch-list')
    if (res.ok) {
      const data = await res.json()
      if (data && data.data && Array.isArray(data.data)) {
        branches.value = data.data
        if (!selectedBranchId.value || !branches.value.some(b => b.id == selectedBranchId.value)) {
          if (branches.value.length > 0) {
            selectedBranchId.value = branches.value[0].id
            selectedBranchName.value = branches.value[0].name
          }
        } else {
          const current = branches.value.find(b => b.id == selectedBranchId.value)
          if (current) selectedBranchName.value = current.name
        }
      }
    }
  } catch (e) {
    console.error('Erreur chargement des salons', e)
  }
}

const onBranchChange = () => {
  const found = branches.value.find(b => b.id == selectedBranchId.value)
  if (found) {
    selectedBranchName.value = found.name
  }
}

watch(() => props.branchId, (newVal) => {
  if (newVal) {
    selectedBranchId.value = newVal
    if (props.branchName) selectedBranchName.value = props.branchName
  }
})

onMounted(() => {
  if (navigator.share) {
    canNativeShare.value = true
  }
  fetchBranches()
})

const shareUrl = computed(() => {
  let origin = window.location.origin
  if (customHost.value && customHost.value.trim()) {
    let h = customHost.value.trim()
    if (!h.startsWith('http://') && !h.startsWith('https://')) {
      h = 'https://' + h
    }
    origin = h.replace(/\/$/, '')
  }
  const bId = selectedBranchId.value || 1
  return `${origin}/quick-booking?branch_id=${bId}`
})

const shareMessage = computed(() => {
  return encodeURIComponent(`Bonjour ! Prenez rendez-vous directement au salon ${selectedBranchName.value || ''} en suivant ce lien : ${shareUrl.value}`)
})

const networks = computed(() => [
  { id: 'whatsapp', name: 'WhatsApp', icon: 'fa-brands fa-whatsapp', cssClass: 'whatsapp', url: `https://api.whatsapp.com/send?text=${shareMessage.value}` },
  { id: 'tiktok', name: 'TikTok', icon: 'fa-brands fa-tiktok', cssClass: 'tiktok', url: shareUrl.value, copyFirst: true, notice: 'Lien copié ! Collez-le dans votre bio ou vidéo TikTok.' },
  { id: 'facebook', name: 'Facebook', icon: 'fa-brands fa-facebook-f', cssClass: 'facebook', url: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}` },
  { id: 'x', name: 'X / Twitter', icon: 'fa-brands fa-x-twitter', cssClass: 'x-twitter', url: `https://twitter.com/intent/tweet?text=${shareMessage.value}` },
  { id: 'instagram', name: 'Instagram', icon: 'fa-brands fa-instagram', cssClass: 'instagram', url: 'https://www.instagram.com/', copyFirst: true, notice: 'Lien copié ! Collez-le dans votre Story Instagram ou Bio.' },
  { id: 'threads', name: 'Threads', icon: 'fa-brands fa-threads', cssClass: 'threads', url: `https://www.threads.net/intent/post?text=${shareMessage.value}` },
  { id: 'snapchat', name: 'Snapchat', icon: 'fa-brands fa-snapchat', cssClass: 'snapchat', url: `https://www.snapchat.com/scan?attachmentUrl=${encodeURIComponent(shareUrl.value)}` },
  { id: 'linkedin', name: 'LinkedIn', icon: 'fa-brands fa-linkedin-in', cssClass: 'linkedin', url: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl.value)}` }
])

const filteredNetworks = computed(() => {
  if (!searchQuery.value.trim()) return networks.value
  const q = searchQuery.value.toLowerCase()
  return networks.value.filter(n => n.name.toLowerCase().includes(q))
})

const handleSocialClick = (net, event) => {
  if (net.copyFirst) {
    event.preventDefault()
    navigator.clipboard.writeText(shareUrl.value)
    if (window.successSnackbar) {
      window.successSnackbar(net.notice)
    } else {
      alert(net.notice)
    }
  }
}

const copyLink = () => {
  navigator.clipboard.writeText(shareUrl.value).then(() => {
    copied.value = true
    if (window.successSnackbar) {
      window.successSnackbar('Lien de réservation du salon copié !')
    }
    setTimeout(() => {
      copied.value = false
    }, 3000)
  })
}

const nativeShare = () => {
  if (navigator.share) {
    navigator.share({
      title: `Réservation ${selectedBranchName.value || 'Salon'}`,
      text: `Réservez votre prestation en ligne au salon ${selectedBranchName.value || ''}`,
      url: shareUrl.value
    }).catch(() => {})
  }
}
</script>

<style scoped>
.glass-share-card {
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(14px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.4);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.share-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.25rem;
}

.text-gradient {
  background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.salon-badge-info {
  background: rgba(111, 66, 193, 0.05);
  border: 1px dashed rgba(111, 66, 193, 0.25);
}

.social-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px 6px;
  border-radius: 14px;
  text-decoration: none;
  transition: all 0.25s ease;
  color: #fff;
}

.social-btn:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.18);
  color: #fff;
}

.social-btn.whatsapp { background: linear-gradient(135deg, #25D366, #128C7E); }
.social-btn.tiktok { background: linear-gradient(135deg, #000000, #25F4EE 50%, #FE2C55); }
.social-btn.facebook { background: linear-gradient(135deg, #1877F2, #0d52ab); }
.social-btn.x-twitter { background: linear-gradient(135deg, #14171A, #000000); }
.social-btn.instagram { background: linear-gradient(135deg, #f09433, #dc2743 50%, #bc1888); }
.social-btn.threads { background: linear-gradient(135deg, #101010, #333333); }
.social-btn.snapchat { background: linear-gradient(135deg, #FFFC00, #E0DC00); color: #000 !important; }
.social-btn.linkedin { background: linear-gradient(135deg, #0A66C2, #004182); }
</style>

