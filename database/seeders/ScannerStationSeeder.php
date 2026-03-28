<?php

namespace Database\Seeders;

use App\Models\ScannerStation;
use Illuminate\Database\Seeder;

class ScannerStationSeeder extends Seeder
{
    public function run(): void
    {
        $stations = [
            ['station_id' => 'north-a', 'scanner_id' => 'scanner-north-a', 'scanner_name' => 'North Gate A', 'gate_id' => 'north', 'gate_name' => 'North Gate'],
            ['station_id' => 'north-b', 'scanner_id' => 'scanner-north-b', 'scanner_name' => 'North Gate B', 'gate_id' => 'north', 'gate_name' => 'North Gate'],
            ['station_id' => 'east-a', 'scanner_id' => 'scanner-east-a', 'scanner_name' => 'East Gate A', 'gate_id' => 'east', 'gate_name' => 'East Gate'],
            ['station_id' => 'east-b', 'scanner_id' => 'scanner-east-b', 'scanner_name' => 'East Gate B', 'gate_id' => 'east', 'gate_name' => 'East Gate'],
            ['station_id' => 'south-a', 'scanner_id' => 'scanner-south-a', 'scanner_name' => 'South Gate A', 'gate_id' => 'south', 'gate_name' => 'South Gate'],
            ['station_id' => 'south-b', 'scanner_id' => 'scanner-south-b', 'scanner_name' => 'South Gate B', 'gate_id' => 'south', 'gate_name' => 'South Gate'],
        ];

        foreach ($stations as $station) {
            ScannerStation::updateOrCreate(
                ['scanner_id' => $station['scanner_id']],
                array_merge($station, ['is_active' => true]),
            );
        }
    }
}
