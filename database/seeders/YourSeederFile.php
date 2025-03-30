<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorkingDay;
use Carbon\Carbon;


class YourSeederFile extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
          // Hindu festivals (must be updated yearly)
          $hinduFestivals = [
            '2025-01-14' => 'Makar Sankranti',
            '2025-03-17' => 'Holika Dahan',
            '2025-03-18' => 'Holi',
            '2025-04-08' => 'Ram Navami',
            '2025-05-12' => 'Akshaya Tritiya',
            '2025-08-26' => 'Raksha Bandhan',
            '2025-09-07' => 'Ganesh Chaturthi',
            '2025-10-02' => 'Navaratri Begins',
            '2025-10-11' => 'Dussehra',
            '2025-10-29' => 'Karva Chauth',
            '2025-11-09' => 'Diwali',
            '2025-11-10' => 'Govardhan Puja',
            '2025-11-12' => 'Bhai Dooj',
        ];

        $year = now()->year;
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        while ($startDate <= $endDate) {
            $dateString = $startDate->format('Y-m-d');
            
            if ($startDate->dayOfWeek === Carbon::SUNDAY) {
                $type = 'Weekend'; // Only Sundays are weekends
            } elseif (array_key_exists($dateString, $hinduFestivals)) {
                $type = 'Holiday';
                $remark = $hinduFestivals[$dateString]; // Festival name
            } else {
                $type = 'Working Day';
                $remark = null;
            }
        
            WorkingDay::updateOrCreate([
                'date' => $dateString,
            ], [
                'type' => $type,
                'remark' => $remark,
            ]);
        
            $startDate->addDay();
        }
    }
}
