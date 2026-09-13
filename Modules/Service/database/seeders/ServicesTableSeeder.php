<?php

namespace Modules\Service\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\Service\Models\ServiceEmployee;

class ServicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks!
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        /*
         * Services Seed
         * ------------------
         */

        // DB::table('services')->truncate();
        // echo "Truncate: services \n";
        if (env('IS_DUMMY_DATA')) {
            $data = [
                // Hair Category Services
                [
                    'slug' => 'taille-barbe',
                    'name' => 'Taille de Barbe',
                    'description' => 'Taille de barbe avec soin hydratant',
                    'duration_min' => 30,
                    'default_price' => 15.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 12.webp'),
                    'category' => 'grooming',
                    'sub_category' => 'shaving',
                ],
                [
                    'slug' => 'coupe-homme',
                    'name' => 'Coupe Homme',
                    'description' => 'Coupe homme classique ou dégradé',
                    'duration_min' => 30,
                    'default_price' => 20.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 10.webp'),
                    'category' => 'hair',
                    'sub_category' => 'haircuts',
                ],
                [
                    'slug' => 'tresses-africaines',
                    'name' => 'Tresses Africaines',
                    'description' => 'Tresses plaquées ou nattes',
                    'duration_min' => 120,
                    'default_price' => 50.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 24.webp'),
                    'category' => 'hair-extension',
                    'sub_category' => 'temporary-extension',
                ],
                [
                    'slug' => 'degrade-americain',
                    'name' => 'Dégradé Américain',
                    'description' => 'Coupe courte avec dégradé à blanc',
                    'duration_min' => 45,
                    'default_price' => 25.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 19.webp'),
                    'category' => 'hair',
                    'sub_category' => 'haircuts',
                ],
                [
                    'slug' => 'coupe-enfant',
                    'name' => 'Coupe Enfant',
                    'description' => 'Coupe de cheveux pour enfants (-12 ans)',
                    'duration_min' => 30,
                    'default_price' => 15.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 25.webp'),
                    'category' => 'hair',
                    'sub_category' => 'haircuts',
                ],
                [
                    'slug' => 'boucles',
                    'name' => 'Mise en plis / Boucles',
                    'description' => 'Création de boucles au fer',
                    'duration_min' => 50,
                    'default_price' => 35.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 40.webp'),
                    'category' => 'hair',
                    'sub_category' => 'hairstyling',
                ],
                [
                    'slug' => 'chignon',
                    'name' => 'Chignon Élégant',
                    'description' => 'Chignon pour mariages ou événements',
                    'duration_min' => 60,
                    'default_price' => 60.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 3.webp'),
                    'category' => 'hair',
                    'sub_category' => 'hairstyling',
                ],
                [
                    'slug' => 'brushing',
                    'name' => 'Brushing',
                    'description' => 'Brushing lisse ou souple',
                    'duration_min' => 45,
                    'default_price' => 30.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 8.webp'),
                    'category' => 'hair',
                    'sub_category' => 'hairstyling',
                ],
                [
                    'slug' => 'coupe-femme-long',
                    'name' => 'Coupe Femme Cheveux Longs',
                    'description' => 'Coupe sur cheveux longs avec shampooing',
                    'duration_min' => 60,
                    'default_price' => 45.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 35.webp'),
                    'category' => 'hair',
                    'sub_category' => 'haircuts',
                ],
                [
                    'slug' => 'coupe-carre',
                    'name' => 'Coupe Carré',
                    'description' => 'Coupe carré court ou plongeant',
                    'duration_min' => 50,
                    'default_price' => 40.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 8.webp'),
                    'category' => 'hair',
                    'sub_category' => 'haircuts',
                ],
                [
                    'slug' => 'coupe-degradee',
                    'name' => 'Coupe Dégradée Femme',
                    'description' => 'Coupe dégradée pour plus de volume',
                    'duration_min' => 60,
                    'default_price' => 45.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 40.webp'),
                    'category' => 'hair',
                    'sub_category' => 'haircuts',
                ],
                // Grooming Services Packages
                [
                    'slug' => 'rasage-traditionnel',
                    'name' => 'Rasage Traditionnel',
                    'description' => 'Rasage à l\'ancienne avec serviette chaude',
                    'duration_min' => 45,
                    'default_price' => 25.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 12.webp'),
                    'category' => 'grooming',
                    'sub_category' => 'shaving',
                ],
                [
                    'slug' => 'soin-hydratant-homme',
                    'name' => 'Soin Hydratant Visage',
                    'description' => 'Soin visage spécifique pour hommes',
                    'duration_min' => 45,
                    'default_price' => 35.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 4.webp'),
                    'category' => 'grooming',
                    'sub_category' => 'facial',
                ],
                [
                    'slug' => 'nettoyage-profond',
                    'name' => 'Nettoyage de Peau Profond',
                    'description' => 'Extraction des comédons et masque',
                    'duration_min' => 60,
                    'default_price' => 50.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 1.webp'),
                    'category' => 'skin',
                    'sub_category' => 'facials',
                ],

                [
                    'slug' => 'massage-dos',
                    'name' => 'Massage Dos et Nuque',
                    'description' => 'Massage relaxant pour soulager les tensions',
                    'duration_min' => 30,
                    'default_price' => 40.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 30.webp'),
                    'category' => 'massage',
                    'sub_category' => 'relaxation',
                ],
                [
                    'slug' => 'massage-corps',
                    'name' => 'Massage Corps Complet',
                    'description' => 'Massage relaxant de tout le corps',
                    'duration_min' => 60,
                    'default_price' => 80.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 21.webp'),
                    'category' => 'massage',
                    'sub_category' => 'relaxation',
                ],

                // Makeup Category Services
                [
                    'slug' => 'pose-vernis-semi-permanent',
                    'name' => 'Pose de Vernis Semi-Permanent',
                    'description' => 'Tenue 2 à 3 semaines',
                    'duration_min' => 45,
                    'default_price' => 35.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 34.webp'),
                    'category' => 'nail-care',
                    'sub_category' => 'manicure',
                ],
                [
                    'slug' => 'soin-pieds',
                    'name' => 'Pédicure Complète',
                    'description' => 'Soin des pieds avec pose de vernis classique',
                    'duration_min' => 60,
                    'default_price' => 45.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 43.webp'),
                    'category' => 'nail-care',
                    'sub_category' => 'pedicure',
                ],
                // Scalp Category Services
                [
                    'slug' => 'massage-cranien',
                    'name' => 'Massage Crânien',
                    'description' => 'Massage stimulant du cuir chevelu',
                    'duration_min' => 20,
                    'default_price' => 25.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 41.webp'),
                    'category' => 'hair-treatments',
                    'sub_category' => 'scalp-treatments',
                ],
                [
                    'slug' => 'soin-profond-cheveux',
                    'name' => 'Soin Réparateur Profond',
                    'description' => 'Masque à la kératine et chaleur',
                    'duration_min' => 45,
                    'default_price' => 50.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 39.webp'),
                    'category' => 'hair-treatments',
                    'sub_category' => 'hair-repair-treatments',
                ],

                // Package Category Services
                [
                    'slug' => 'forfait-visage-eclat',
                    'name' => 'Forfait Visage Éclat',
                    'description' => 'Gommage, massage et masque du visage',
                    'duration_min' => 90,
                    'default_price' => 80.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 44.webp'),
                    'category' => 'spa-packages',
                    'sub_category' => 'relaxation-package',
                ],
                [
                    'slug' => 'journee-detente',
                    'name' => 'Journée Détente Complète',
                    'description' => 'Massage corps + Soin visage + Manucure',
                    'duration_min' => 180,
                    'default_price' => 150.00,
                    'status' => 1,
                    'feature_image' => public_path('/dummy-images/common/Service 27.webp'),
                    'category' => 'spa-packages',
                    'sub_category' => 'detox-package',
                ],
            ];
            foreach ($data as $key => $value) {
                $categroy = Category::where('slug', $value['category'])->first();
                $sub_category = $value['sub_category'];
                $featureImage = $value['feature_image'] ?? null;
                $serviceData = Arr::except($value, ['sub_category', 'category', 'feature_image']);

                if (isset($sub_category)) {
                    $sub_category = Category::where('slug', $value['sub_category'])->first();
                }

                $service = [
                    'slug' => $value['slug'],
                    'name' => $value['name'],
                    'category_id' => $categroy->id,
                    'sub_category_id' => $sub_category->id ?? null,
                    'description' => $value['description'],
                    'duration_min' => $value['duration_min'],
                    'default_price' => $value['default_price'],
                    'status' => $value['status'],
                ];
                $service = Service::create($service);
                if (isset($featureImage)) {
                    $this->attachFeatureImage($service, $featureImage);
                }
                for ($i = 1; $i <= 5; $i++) {
                    ServiceBranches::create([
                        'service_id' => $service->id,
                        'branch_id' => $i,
                        'service_price' => $service->default_price ?? 0,
                        'duration_min' => $service->duration_min,
                    ]);
                }
                for ($i = 43; $i <= 73; $i++) {
                    ServiceEmployee::create([
                        'service_id' => $service->id,
                        'employee_id' => $i,
                    ]);
                }
            }
        }
        // Enable foreign key checks!
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function attachFeatureImage($model, $publicPath)
    {
        if (! env('IS_DUMMY_DATA_IMAGE')) {
            return false;
        }

        $file = new \Illuminate\Http\File($publicPath);

        $media = $model->addMedia($file)->preservingOriginal()->toMediaCollection('feature_image');

        return $media;
    }
}
