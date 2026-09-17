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

echo "🚀 Déploiement Kuilinga — " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('─', 60) . "\n\n";

// ─── Détection du répertoire racine de l'application ─────────────────────────
// Ce fichier est dans /public/, la racine est un niveau au-dessus
$appRoot = dirname(__DIR__);
chdir($appRoot);

echo "📁 Répertoire : $appRoot\n\n";

// ─── Fonctions utilitaires ────────────────────────────────────────────────────
function run(string $cmd): void {
    echo "▶ $cmd\n";
    $output = [];
    $return = 0;
    exec($cmd . ' 2>&1', $output, $return);
    foreach ($output as $line) {
        echo "  $line\n";
    }
    echo ($return === 0 ? "  ✅ OK\n" : "  ⚠️  Code retour : $return\n") . "\n";
}

// ─── Étape 1 : Git Pull ───────────────────────────────────────────────────────
echo "ÉTAPE 1 — Récupération du code depuis GitHub\n";
echo str_repeat('─', 40) . "\n";
run('git pull origin master');

// ─── Étape 2 : Composer (si composer.json a changé) ──────────────────────────
echo "ÉTAPE 2 — Dépendances Composer\n";
echo str_repeat('─', 40) . "\n";
run('composer install --no-dev --optimize-autoloader --no-interaction');

// ─── Étape 3 : Migrations ─────────────────────────────────────────────────────
echo "ÉTAPE 3 — Migrations base de données\n";
echo str_repeat('─', 40) . "\n";
run('php artisan migrate --force');

// ─── Étape 4 : Vider tous les caches ─────────────────────────────────────────
echo "ÉTAPE 4 — Nettoyage des caches\n";
echo str_repeat('─', 40) . "\n";
run('php artisan config:clear');
run('php artisan route:clear');
run('php artisan cache:clear');
run('php artisan view:clear');

// ─── Fin ──────────────────────────────────────────────────────────────────────
echo str_repeat('═', 60) . "\n";
echo "✅ Déploiement terminé — " . date('Y-m-d H:i:s') . "\n";
echo "🌐 Site : https://salon.kuilinga.tech\n";
