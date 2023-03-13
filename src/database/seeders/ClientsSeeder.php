<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientsSeeder extends Seeder
{

	use WithoutModelEvents;
	/**
	 * Run the database seeds.
	 */
	public function run(): void {
		$file = (__DIR__ . '/Clientes - Dados.csv');
		$stream = fopen($file, 'r');
		$header = fgetcsv($stream, 0, ',');
		$data = [];

		while ($row = fgetcsv($stream, 0, ',')) {
			$value = array_combine($header, $row);
			$value['inclusao'] = array_reverse(explode('/', $value['inclusao']));
			$month = $value['inclusao'][1];
			$value['inclusao'][1] = $value['inclusao'][2];
			$value['inclusao'][2] = $month;
			$value['inclusao'] = implode('-', $value['inclusao']);
			$data[] = $value;
		}

		fclose($stream);

		Log::info('ClientsSeeder: ' . count($data) . ' clients found.');

		foreach (array_chunk($data, 1000) as $chunk) {
			DB::table('clientes')->insert($chunk);
		}

	}
}
