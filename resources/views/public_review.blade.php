<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Donner votre avis - Salon de Coiffure</title>
    <link rel="stylesheet" href="{{ asset('css/libs.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hope-ui.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 12px;
            margin: 0;
            overflow-x: hidden;
        }
        .review-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
            max-width: 580px;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        .review-header {
            background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 10px;
            margin: 15px 0;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            font-size: 2.2rem;
            color: #cbd5e1;
            cursor: pointer;
            transition: color 0.2s ease, transform 0.2s ease;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #f59e0b;
            transform: scale(1.1);
        }
        .client-badge {
            background: rgba(111, 66, 193, 0.08);
            border: 1px dashed rgba(111, 66, 193, 0.3);
            border-radius: 12px;
            padding: 12px 16px;
        }
    </style>
</head>
<body>

<div class="review-card">
    <div class="review-header text-center">
        <div class="mb-2">
            <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold text-uppercase">
                <i class="fa-solid fa-scissors me-1"></i> Réservation Effectuée
            </span>
        </div>
        <h3 class="text-white fw-bold mb-1">Votre Avis sur la Prestation</h3>
        <p class="mb-0 text-white-50 small">Partagez votre expérience avec le salon et votre coiffeur</p>
    </div>

    <div class="p-4">
        <!-- Badge Client -->
        <div class="client-badge mb-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-50 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5">
                    {{ substr($user->first_name ?? 'K', 0, 1) }}{{ substr($user->last_name ?? 'S', 0, 1) }}
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">{{ $user->full_name ?? 'Kabore Samuel' }}</h6>
                    <small class="text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i> Client vérifié - RDV Terminé</small>
                </div>
            </div>
            <span class="badge bg-soft-success text-success fw-bold">RDV Honoré</span>
        </div>

        <form id="publicReviewForm" action="{{ route('backend.employees.save_review') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id ?? 17 }}">

            <!-- Sélection de l'Employé / Coiffeur -->
            <div class="form-group mb-3">
                <label class="form-label fw-bold text-dark small mb-1">
                    <i class="fa-solid fa-user-tie me-1 text-primary"></i> Sélectionner votre Coiffeur / Employé :
                </label>
                <select name="employee_id" class="form-select border-primary" required>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->email ?? 'Staff Salon' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Note globale en étoiles -->
            <div class="form-group mb-3 text-center">
                <label class="form-label fw-bold text-dark small d-block mb-1">
                    <i class="fa-solid fa-star me-1 text-warning"></i> Votre Note Globale :
                </label>
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required />
                    <label for="star5" title="5 étoiles - Excellent"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star4" name="rating" value="4" />
                    <label for="star4" title="4 étoiles - Très bon"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star3" name="rating" value="3" />
                    <label for="star3" title="3 étoiles - Moyen"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star2" name="rating" value="2" />
                    <label for="star2" title="2 étoiles - Passable"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="star1" name="rating" value="1" />
                    <label for="star1" title="1 étoile - Mauvais"><i class="fa-solid fa-star"></i></label>
                </div>
            </div>

            <!-- Message / Commentaire -->
            <div class="form-group mb-4">
                <label class="form-label fw-bold text-dark small mb-1">
                    <i class="fa-solid fa-comment-dots me-1 text-primary"></i> Votre Commentaire :
                </label>
                <textarea name="review_msg" class="form-control" rows="4" placeholder="Expliquez ce qui vous a plu lors de votre rendez-vous..." required></textarea>
            </div>

            <!-- Message de succès Alerte -->
            <div id="alertSuccess" class="alert alert-success d-none mb-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <strong>Merci !</strong> Votre avis a été enregistré avec succès et mis à jour dans le tableau des avis du salon.
            </div>

            <button type="submit" id="btnSubmitReview" class="btn btn-primary w-100 py-3 rounded-pill fw-bold text-uppercase shadow">
                <i class="fa-solid fa-paper-plane me-2"></i> Envoyer mon Avis
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#publicReviewForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnSubmitReview');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Envoi en cours...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                $('#alertSuccess').removeClass('d-none');
                $btn.html('<i class="fa-solid fa-check me-2"></i> Avis Enregistré !').addClass('btn-success').removeClass('btn-primary');
                setTimeout(function() {
                    window.location.reload();
                }, 2500);
            },
            error: function(err) {
                alert("Erreur lors de l'enregistrement de l'avis.");
                $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-2"></i> Envoyer mon Avis');
            }
        });
    });
</script>

</body>
</html>
