<?php

namespace App\Filament\Pages;

use App\Models\ProfilePerusahaan as ProfilePerusahaanModel;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProfilePerusahaan extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Profil Perusahaan';

    protected static ?int $navigationSort = 1;

    // View Default Filament
    protected static string $view = 'filament.pages.profile-perusahaan';

    // Property untuk menampung data form
    public ?array $data = [];

    public function mount(): void
    {
        // Ambil data pertama, atau buat array kosong jika belum ada
        $profile = ProfilePerusahaanModel::first();

        if ($profile) {
            $this->form->fill($profile->attributesToArray());
        }
    }

    protected function getForms(): array
    {
        return [
            'form',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Umum')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->required(),
                        TextInput::make('email')
                            ->email(),
                        TextInput::make('phone')
                            ->tel(),
                        Textarea::make('address')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Branding')
                    ->schema([
                        FileUpload::make('logo')
                            ->image()
                            ->directory('company-logo'),
                    ]),

                Section::make('Lokasi')
                    ->schema([
                        TextInput::make('lat_perusahaan')
                            ->label('Latitude'),
                        TextInput::make('long_perusahaan')
                            ->label('Longitude'),
                    ]),
            ])->statePath('data'); // sambungkan form ke property $data
    }

    // Definisikan action form save di header atau di form
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Simpan perubahan'))
                ->submit('save'),
        ];
    }

    // Logic Penyimpanan
    public function save(): void
    {
        $data = $this->form->getState();
        // Gunakan logic updateOrCreate atau firstOrNew
        // Kita paksa ID-nya 1 agar tetap single record
        $profile = ProfilePerusahaanModel::firstOrNew(['id' => 1]);
        $profile->fill($data);
        $profile->save();

        Notification::make()
            ->success()
            ->title('Berhasil disimpan')
            ->send();
    }
}
