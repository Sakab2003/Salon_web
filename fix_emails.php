<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

function removeAccents($str) {
    $from = ['à','â','ä','á','ã','å','è','é','ê','ë','ì','î','ï','ó','ô','ö','ò','õ','ù','û','ü','ú','ý','ÿ','ñ','ç',
             'À','Â','Ä','Á','Ã','Å','È','É','Ê','Ë','Ì','Î','Ï','Ó','Ô','Ö','Ò','Õ','Ù','Û','Ü','Ú','Ý','Ñ','Ç'];
    $to   = ['a','a','a','a','a','a','e','e','e','e','i','i','i','o','o','o','o','o','u','u','u','u','y','y','n','c',
             'A','A','A','A','A','A','E','E','E','E','I','I','I','O','O','O','O','O','U','U','U','U','Y','N','C'];
    return str_replace($from, $to, $str);
}

function generateUniqueEmail($firstName, $lastName, $mobile) {
    $first = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', removeAccents($firstName)));
    $last  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', removeAccents($lastName)));
    $phone = preg_replace('/[^0-9]/', '', $mobile ?? '');
    if (empty($first)) $first = 'user';
    if (empty($last)) $last = 'salon';

    $email = "{$first}.{$last}.{$phone}@salon.app";
    $i = 1;
    while (User::where('email', $email)->exists()) {
        $email = "{$first}.{$last}.{$phone}{$i}@salon.app";
        $i++;
    }
    return $email;
}

// Trouver tous les users sans email
$users = User::where(function($q) {
    $q->whereNull('email')->orWhere('email', '');
})->get();

echo "Utilisateurs sans email: " . $users->count() . "\n";

foreach ($users as $user) {
    $email = generateUniqueEmail($user->first_name, $user->last_name, $user->mobile);
    $user->email = $email;
    $user->save();
    echo "ID {$user->id} ({$user->first_name} {$user->last_name}) → {$email}\n";
}

echo "\nTerminé !\n";
