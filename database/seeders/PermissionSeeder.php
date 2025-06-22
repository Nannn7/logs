<?php

    namespace Modules\Logs\Database\Seeders;

    use Illuminate\Database\Seeder;
    use Illuminate\Support\Str;
    use Modules\Usermanagement\Models\PermissionGroup;

    class PermissionSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         */
        public function run()
        {
            $data = $this->data();

            foreach ($data as $value) {
                PermissionGroup::updateOrCreate([
                    'name'       => $value['name'],
                    'slug'       => Str::slug($value['name'])
                ]);
            }
        }

        public function data()
        {
            return [
                ['name' => 'system-logs'],
                ['name' => 'audit-logs'],
            ];
        }
    }
