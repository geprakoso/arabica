<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'nama_member' => 'Adi Saputra',
                'email' => 'adi.saputra@example.com',
                'no_hp' => '08120001111',
                'alamat' => 'Jl. Melati No. 12, Bandung',
                'provinsi' => 'Jawa Barat',
                'kota' => 'Bandung',
                'kecamatan' => 'Coblong',
            ],
            [
                'nama_member' => 'Rina Laras',
                'email' => 'rina.laras@example.com',
                'no_hp' => '081233344455',
                'alamat' => 'Jl. Kenanga No. 8, Surabaya',
                'provinsi' => 'Jawa Timur',
                'kota' => 'Surabaya',
                'kecamatan' => 'Wonokromo',
            ],
            [
                'nama_member' => 'Dedi Mahendra',
                'email' => 'dedi.mahendra@example.com',
                'no_hp' => '081277788899',
                'alamat' => 'Jl. Diponegoro No. 21, Semarang',
                'provinsi' => 'Jawa Tengah',
                'kota' => 'Semarang',
                'kecamatan' => 'Banyumanik',
            ],
        ];

        foreach ($members as $member) {
            Member::updateOrCreate(
                ['no_hp' => $member['no_hp']],
                $member
            );
        }
    }
}
