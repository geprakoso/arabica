<?php

namespace App\Filament\Resources\MasterData\MemberResource\Pages;

use App\Filament\Resources\MasterData\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;
}
