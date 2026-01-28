<?php

use App\Models\Member;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('md_members', function (Blueprint $table) {
            $table->string('kode_member')->nullable()->unique()->after('id');
        });

        $members = DB::table('md_members')
            ->select(['id', 'nama_member', 'created_at'])
            ->orderBy('id')
            ->get();

        Member::withoutEvents(function () use ($members): void {
            foreach ($members as $member) {
                $date = $member->created_at ? Carbon::parse($member->created_at) : now();
                $nama = $member->nama_member ?: 'MEM';
                $kode = Member::generateKode($nama, $date);

                DB::table('md_members')
                    ->where('id', $member->id)
                    ->update(['kode_member' => $kode]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('md_members', function (Blueprint $table) {
            $table->dropUnique(['kode_member']);
            $table->dropColumn('kode_member');
        });
    }
};
