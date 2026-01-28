<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Absensi;
use App\Models\AkunTransaksi;
use App\Models\Brand;
use App\Models\ChatGroup;
use App\Models\Gudang;
use App\Models\Jasa;
use App\Models\Karyawan;
use App\Models\Kategori;
use App\Models\Lembur;
use App\Models\LiburCuti;
use App\Models\Member;
use App\Models\Pembelian;
use App\Models\PenjadwalanPengiriman;
use App\Models\PenjadwalanService;
use App\Models\PenjadwalanTugas;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\RequestOrder;
use App\Models\User;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use App\Models\Supplier;
use Spatie\Permission\Models\Role;
use TomatoPHP\FilamentMediaManager\Models\Folder;
use TomatoPHP\FilamentMediaManager\Models\Media;
use App\Policies\AbsensiPolicy;
use App\Policies\AkunTransaksiPolicy;
use App\Policies\BrandPolicy;
use App\Policies\ChatGroupPolicy;
use App\Policies\GudangPolicy;
use App\Policies\JasaPolicy;
use App\Policies\KaryawanPolicy;
use App\Policies\KategoriPolicy;
use App\Policies\LemburPolicy;
use App\Policies\LiburCutiPolicy;
use App\Policies\MediaPolicy;
use App\Policies\MemberPolicy;
use App\Policies\PembelianPolicy;
use App\Policies\PenjadwalanPengirimanPolicy;
use App\Policies\PenjadwalanServicePolicy;
use App\Policies\PenjadwalanTugasPolicy;
use App\Policies\PenjualanPolicy;
use App\Policies\ProdukPolicy;
use App\Policies\RequestOrderPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\StockAdjustmentPolicy;
use App\Policies\StockOpnamePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\FolderPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Absensi::class => AbsensiPolicy::class,
        AkunTransaksi::class => AkunTransaksiPolicy::class,
        Brand::class => BrandPolicy::class,
        ChatGroup::class => ChatGroupPolicy::class,
        Folder::class => FolderPolicy::class,
        Gudang::class => GudangPolicy::class,
        Jasa::class => JasaPolicy::class,
        Karyawan::class => KaryawanPolicy::class,
        Kategori::class => KategoriPolicy::class,
        Lembur::class => LemburPolicy::class,
        LiburCuti::class => LiburCutiPolicy::class,
        Media::class => MediaPolicy::class,
        Member::class => MemberPolicy::class,
        Pembelian::class => PembelianPolicy::class,
        PenjadwalanPengiriman::class => PenjadwalanPengirimanPolicy::class,
        PenjadwalanService::class => PenjadwalanServicePolicy::class,
        PenjadwalanTugas::class => PenjadwalanTugasPolicy::class,
        Penjualan::class => PenjualanPolicy::class,
        Produk::class => ProdukPolicy::class,
        RequestOrder::class => RequestOrderPolicy::class,
        Role::class => RolePolicy::class,
        User::class => UserPolicy::class,
        StockAdjustment::class => StockAdjustmentPolicy::class,
        StockOpname::class => StockOpnamePolicy::class,
        Supplier::class => SupplierPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Align super admin bypass with Shield config (falls back to Filament Spatie config for safety).
        Gate::before(function ($user, $ability) {
            // Godmode: Double Verification (Role + Email Whitelist)
            if ($user->hasRole('godmode')) {
                $godmodeEmailsEnv = config('godmode.emails');
                // Handle comma-separated string if provided
                $godmodeEmails = is_array($godmodeEmailsEnv) 
                    ? $godmodeEmailsEnv 
                    : array_filter(array_map('trim', explode(',', $godmodeEmailsEnv ?? '')));
                
                if (in_array($user->email, $godmodeEmails)) {
                    return true;
                }
            }

            $superAdminRole = config('filament-shield.super_admin.name')
                ?? config('filament-spatie-roles-permissions.super_admin_role_name')
                ?? 'super_admin';

            return $user->hasRole($superAdminRole) ? true : null;
        });
    }
}
