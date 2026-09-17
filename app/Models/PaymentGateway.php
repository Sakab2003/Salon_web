<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'code',
        'logo_url',
        'description',
        'driver',
        'config',
        'phone_prefixes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Retourne les credentials propres à ce gateway.
     * Utilise ceux de la BDD, ou ceux du .env en fallback pour PulseKango.
     */
    public function getCredentials(): array
    {
        $config = $this->config ?? [];

        if ($this->driver === 'pulse_kango') {
            return [
                'base_url' => $config['base_url'] ?? config('services.pulse_kango.base_url', 'https://sandbox.pulse-kango.com'),
                'api_key'  => $config['api_key']  ?? config('services.pulse_kango.api_key',  ''),
                'username' => $config['username']  ?? config('services.pulse_kango.username', ''),
                'secret'   => $config['secret']    ?? config('services.pulse_kango.secret',   ''),
            ];
        }

        return $config;
    }

    /**
     * Liste des préfixes téléphoniques autorisés pour ce réseau.
     */
    public function getPrefixesArray(): array
    {
        if (empty($this->phone_prefixes)) return [];
        return array_map('trim', explode(',', $this->phone_prefixes));
    }
}
