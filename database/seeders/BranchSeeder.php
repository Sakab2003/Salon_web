<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Modules\BussinessHour\Models\BussinessHour;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('IS_DUMMY_DATA')) {
            $days = [
                ['day' => 'monday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'tuesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'wednesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'thursday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'friday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'saturday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
                ['day' => 'sunday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => true, 'breaks' => []],
            ];
            $branches = [
                [
                    'address' => [
                        'postal_code' => '00000',
                        'address_line_1' => '12 Avenue de la Paix',
                        'address_line_2' => 'Gombe',
                        'city' => 'Kinshasa',
                        'state' => 'Kinshasa',
                        'country' => 'CD',
                        'latitude' => '-4.3056',
                        'longitude' => '15.2974',
                    ],
                    'name' => 'L\'Atelier Beauté',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/branches/1.png'),
                    'contact_email' => 'contact@latelier-beaute.cd',
                    'contact_number' => '+243810000001',
                    'payment_method' => ['cash', 'debit_card', 'mobile_money'],
                    'branch_for' => 'unisex',
                ],
                [
                    'address' => [
                        'postal_code' => '00000',
                        'address_line_1' => '45 Avenue Nguma',
                        'address_line_2' => 'Ngaliema',
                        'city' => 'Kinshasa',
                        'state' => 'Kinshasa',
                        'country' => 'CD',
                        'latitude' => '-4.3317',
                        'longitude' => '15.2500',
                    ],
                    'name' => 'Espace Coiffure',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/branches/2.png'),
                    'contact_email' => 'hello@espace-coiffure.cd',
                    'contact_number' => '+243810000002',
                    'payment_method' => ['cash', 'mobile_money'],
                    'branch_for' => 'male',
                ],
                [
                    'address' => [
                        'postal_code' => '00000',
                        'address_line_1' => '78 Boulevard Lumumba',
                        'address_line_2' => 'Limete',
                        'city' => 'Kinshasa',
                        'state' => 'Kinshasa',
                        'country' => 'CD',
                        'latitude' => '-4.3542',
                        'longitude' => '15.3421',
                    ],
                    'name' => 'Le Salon de Paris',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/branches/3.png'),
                    'contact_email' => 'info@salon-paris.cd',
                    'contact_number' => '+243810000003',
                    'payment_method' => ['cash', 'debit_card'],
                    'branch_for' => 'male',
                ],
                [
                    'address' => [
                        'postal_code' => '00000',
                        'address_line_1' => '32 Avenue Kasa-Vubu',
                        'address_line_2' => 'Bandalungwa',
                        'city' => 'Kinshasa',
                        'state' => 'Kinshasa',
                        'country' => 'CD',
                        'latitude' => '-4.3381',
                        'longitude' => '15.2922',
                    ],
                    'name' => 'Beauté d\'Afrique',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/branches/4.png'),
                    'contact_email' => 'hello@beaute-afrique.cd',
                    'contact_number' => '+243810000004',
                    'payment_method' => ['cash', 'mobile_money'],
                    'branch_for' => 'female',
                ],
                [
                    'address' => [
                        'postal_code' => '00000',
                        'address_line_1' => '65 Rond Point',
                        'address_line_2' => 'Lemba',
                        'city' => 'Kinshasa',
                        'state' => 'Kinshasa',
                        'country' => 'CD',
                        'latitude' => '-4.3794',
                        'longitude' => '15.3136',
                    ],
                    'name' => 'Kin\' Beauté',
                    'manager_id' => null,
                    'feature_image' => public_path('/dummy-images/branches/5.png'),
                    'contact_email' => 'info@kin-beaute.cd',
                    'contact_number' => '+243810000005',
                    'payment_method' => ['cash', 'mobile_money'],
                    'branch_for' => 'unisex',
                ],
            ];

            foreach ($branches as $branch) {
                $address = $branch['address'];
                $featureImage = $branch['feature_image'] ?? null;
                $branchData = Arr::except($branch, ['feature_image', 'address']);
                $br = Branch::create($branchData);
                $this->attachFeatureImage($br, $featureImage);
                $br->address()->save(new Address($address));
                foreach ($days as $key => $val) {
                    $val['branch_id'] = $br->id;
                    BussinessHour::create($val);
                }
            }
        }
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
