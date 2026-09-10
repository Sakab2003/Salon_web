<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountAutoEmailCreated extends Notification
{
    use Queueable;

    protected string $generatedEmail;
    protected string $plainPassword;
    protected string $role;

    /**
     * @param string $generatedEmail   Email généré automatiquement
     * @param string $plainPassword    Mot de passe en clair (pour notification uniquement)
     * @param string $role             'manager' ou 'employee'
     */
    public function __construct(string $generatedEmail, string $plainPassword, string $role = 'employee')
    {
        $this->generatedEmail = $generatedEmail;
        $this->plainPassword  = $plainPassword;
        $this->role           = $role;
    }

    /**
     * Canaux de livraison : uniquement in-app (base de données).
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Données stockées dans la table notifications (visibles dans la section Notifications du web).
     */
    public function toDatabase($notifiable): array
    {
        $roleLabel = $this->role === 'manager' ? 'Manager' : 'Personnel';

        return [
            'type'    => 'account_created',
            'title'   => "Compte {$roleLabel} créé avec succès",
            'message' => "Votre compte a été créé. Email : {$this->generatedEmail} | Mot de passe : {$this->plainPassword}. "
                       . "Vous pouvez utiliser ces identifiants pour vous connecter sur la version web.",
            'email'   => $this->generatedEmail,
            'role'    => $this->role,
        ];
    }
}
