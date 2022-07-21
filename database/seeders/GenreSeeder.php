<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\HotpepperService;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(HotpepperService $hotpepperService)
    {
        $genres = $hotpepperService->getGenreMaster();

        $apiVersion = $genres['api_version'];
        $records = $genres['genre'];

        $data = [];
        foreach($records as $record) {
            $data[] = [
                'api_version' => $apiVersion,
                'code' => $record['code'],
                'name' => $record['name']
            ];
        }

        DB::table('genres')->upsert($data, ['api_version', 'code'], ['name']);
    }
}
