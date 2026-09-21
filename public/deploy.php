<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║        KUILINGA — Script de déploiement automatique         ║
 * ║  Appel : https://salon.kuilinga.tech/deploy.php?key=SECRET  ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * NE PAS SUPPRIMER — Permet de déployer sans accès SSH.
 */

// ─── Clé secrète (ne jamais partager) ────────────────────────────────────────
define('DEPLOY_SECRET', 'kuilinga_deploy_2026_$4m');

// ─── Sécurité ────────────────────────────────────────────────────────────────
header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? $_POST['key'] ?? '';
if ($key !== DEPLOY_SECRET) {
    http_response_code(403);
    die("❌ Accès refusé. Clé invalide.\n");
}

ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "🚀 Déploiement Kuilinga — " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('─', 60) . "\n\n";

$appRoot = dirname(__DIR__);
chdir($appRoot);

echo "📁 Répertoire : $appRoot\n\n";

// ─── Fonctions utilitaires sécurisées ────────────────────────────────────────
function run(string $cmd): void {
    echo "▶ $cmd\n";
    if (!function_exists('exec')) {
        echo "  ℹ️  exec() désactivé par l'hébergeur (normal sur mutualisé)\n\n";
        return;
    }
    $output = [];
    $return = 0;
    try {
        @exec($cmd . ' 2>&1', $output, $return);
        foreach ($output as $line) {
            echo "  $line\n";
        }
        echo ($return === 0 ? "  ✅ OK\n" : "  ⚠️  Code retour : $return\n") . "\n";
    } catch (\Throwable $e) {
        echo "  ⚠️  Erreur exec: " . $e->getMessage() . "\n\n";
    }
}

// ─── Étape 1 : Git Pull (si possible via exec) ────────────────────────────────
echo "ÉTAPE 1 — Récupération du code depuis GitHub\n";
echo str_repeat('─', 40) . "\n";
run('git pull origin master');

// ─── Étape 2 : Initialisation de Laravel en mémoire ──────────────────────────
echo "ÉTAPE 2 — Bootstrap Laravel\n";
echo str_repeat('─', 40) . "\n";
try {
    require_once $appRoot . '/vendor/autoload.php';
    $app = require_once $appRoot . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "  ✅ Laravel initialisé avec succès\n\n";
} catch (\Throwable $e) {
    echo "  ⚠️ Erreur bootstrap: " . $e->getMessage() . "\n\n";
}

// ─── Étape 3 : Migrations de base de données ──────────────────────────────────
echo "ÉTAPE 3 — Migrations base de données\n";
echo str_repeat('─', 40) . "\n";
try {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $migrationOutput = \Illuminate\Support\Facades\Artisan::output();
    echo $migrationOutput ?: "  ✅ Migrations terminées\n";
} catch (\Throwable $e) {
    echo "  ❌ Erreur migration: " . $e->getMessage() . "\n";
}
echo "\n";

// ─── Étape 4 : Seeders (Passerelles & Plans) ─────────────────────────────────
echo "ÉTAPE 4 — Seeders (Passerelles de paiement et Plans)\n";
echo str_repeat('─', 40) . "\n";
try {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'PaymentGatewaySeeder', '--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();
} catch (\Throwable $e) {
    echo "  ⚠️ PaymentGatewaySeeder: " . $e->getMessage() . "\n";
}

try {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'TestPlanSeeder', '--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();
} catch (\Throwable $e) {
    echo "  ⚠️ TestPlanSeeder: " . $e->getMessage() . "\n";
}
echo "  ✅ Seeders terminés\n\n";

// ─── Étape 5 : Nettoyage des caches ──────────────────────────────────────────
echo "ÉTAPE 5 — Nettoyage des caches\n";
echo str_repeat('─', 40) . "\n";
try {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "  ✅ Caches vidés avec succès\n\n";
} catch (\Throwable $e) {
    echo "  ⚠️ Caches: " . $e->getMessage() . "\n\n";
}

// ─── Fin ──────────────────────────────────────────────────────────────────────
echo str_repeat('═', 60) . "\n";
echo "✅ Déploiement terminé — " . date('Y-m-d H:i:s') . "\n";
echo "🌐 Site : https://salon.kuilinga.tech\n";
