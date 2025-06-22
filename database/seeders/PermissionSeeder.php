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
                $group = PermissionGroup::updateOrCreate([
                    'name'       => $value['name'],
                    'slug'       => Str::slug($value['name'])
                ]);

                foreach ($this->crudActions($group->name) as $action) {
                    $data[] = ['name' => $action, 'group' => $group->id];
                }
            }
        }

        public function data()
        {
            return [
                ['name' => 'system-logs'],
                ['name' => 'audit-logs'],
            ];
        }

        public function crudActions($name)
        {
            $actions = [];
            // list of permission actions
            $crud = ['create', 'read', 'update', 'delete','export', 'authorize', 'report','restore'];


            foreach ($crud as $value) {
                $actions[] = $name . '.' . $value;
            }

            return $actions;
        }
    }
