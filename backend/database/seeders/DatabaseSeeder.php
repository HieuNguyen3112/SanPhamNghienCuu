<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
{
    $this->call([
        LookupSeeder::class,
        RolesPermissionsSeeder::class,
        UsersDemoSeeder::class,
        LecturerProfileDemoSeeder::class,
        ResearchLookupSeeder::class,
        ResearchHoursCatalogSeeder::class,
        ResearchActivityDemoSeeder::class,
        LecturerPersonalHoursOverviewSeeder::class,
        ResearchHoursUsageSeeder::class,
        LecturerHourApprovalDemoSeeder::class,
        WorkCatalogSeeder::class,
        AuditLogSeeder::class,
        FacultyHoursApprovalSeeder::class,
        NotificationIconDemoSeeder::class,
    ]);
}

}
