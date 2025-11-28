<?php

namespace Database\Seeders;

use App\Models\ChatGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChatGroupSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();

        if (! $admin) {
            return;
        }

        $group = ChatGroup::firstOrCreate(
            ['name' => 'Tim Operasional'],
            [
                'owner_id' => $admin->id,
                'description' => 'Group chat internal (seed).',
                'slug' => Str::slug('Tim Operasional') . '-seed',
                'settings' => ['allow_invite' => true],
            ]
        );

        $memberIds = User::whereIn('email', ['galih@example.com', 'huda@example.com'])
            ->pluck('id')
            ->push($admin->id)
            ->all();

        if ($memberIds) {
            $syncData = collect($memberIds)->mapWithKeys(fn ($id) => [$id => ['role' => $id === $admin->id ? 'owner' : 'member']]);
            $group->members()->syncWithoutDetaching($syncData);
        }
    }
}
