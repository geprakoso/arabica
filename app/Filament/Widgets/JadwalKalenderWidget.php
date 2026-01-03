<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\User;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\LiburCuti;
use App\Enums\StatusTugas;
use Livewire\Attributes\On;
use Filament\Actions\Action;
use App\Models\KalenderEvent;
use App\Enums\StatusPengajuan;
use Filament\Facades\Filament;
use App\Models\PenjadwalanTugas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Guava\Calendar\Widgets\CalendarWidget;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\Absensi\LiburCutiResource;
use App\Filament\Resources\Penjadwalan\KalenderEventResource;
use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;

class JadwalKalenderWidget extends CalendarWidget
{
    protected string | \Closure | \Illuminate\Support\HtmlString | null $heading = 'Kalender Jadwal';

    protected int | string | array $columnSpan = 'full';
    protected bool $dayMaxEvents = true;
    protected ?string $locale = 'id';
    protected bool $eventClickEnabled = true;
    protected bool $dateSelectEnabled = true;

    #[On('calendar-date-set')]
    public function setCalendarDate(string $date): void
    {
        $this->setOption('date', $date);
    }

    public function getEvents(array $fetchInfo = []): Collection | array
    {
        $events = [];

        $liburCutis = LiburCuti::query()
            ->select([
                'id',
                'keperluan',
                'mulai_tanggal',
                'sampai_tanggal',
                'status_pengajuan',
                'keterangan',
            ])
            ->where('status_pengajuan', StatusPengajuan::Diterima)
            ->get();

        foreach ($liburCutis as $libur) {

            if ($libur->user_id !== Auth::user()->id) {
                continue;
            }
            $rawMulai = $libur->getRawOriginal('mulai_tanggal');
            $rawSampai = $libur->getRawOriginal('sampai_tanggal');

            if (! $rawMulai && ! $rawSampai) {
                continue;
            }

            [$start, $end] = $this->normalizeAllDayRange(
                $rawMulai ?: $rawSampai,
                $rawSampai ?: $rawMulai,
            );

            $events[] = [
                'title' => $this->buildLiburTitle($libur),
                'start' => $start,
                'end' => $end,
                'allDay' => true,
                'backgroundColor' => $this->liburColor($libur->status_pengajuan),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'model' => $libur::class,
                    'key' => $libur->getKey(),
                    'action' => 'view',
                    'url' => LiburCutiResource::getUrl('view', ['record' => $libur], panel: Filament::getCurrentPanel()?->getId()),
                    'url_target' => '_self',
                ],
            ];
        }

        $tugas = PenjadwalanTugas::query()
            ->select([
                'id',
                'judul',
                'tanggal_mulai',
                'deadline',
                'status',
            ])
            ->get();

        foreach ($tugas as $task) {
            $rawMulai = $task->getRawOriginal('tanggal_mulai');
            $rawDeadline = $task->getRawOriginal('deadline');

            if (! $rawMulai && ! $rawDeadline) {
                continue;
            }

            [$start, $end] = $this->normalizeAllDayRange(
                $rawMulai ?: $rawDeadline,
                $rawDeadline ?: $rawMulai,
            );

            $events[] = [
                'title' => $this->prefixTitle('Tugas', $task->judul ?: 'Tanpa Judul'),
                'start' => $start,
                'end' => $end,
                'allDay' => true,
                'backgroundColor' => $this->tugasColor($task->status),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'model' => $task::class,
                    'key' => $task->getKey(),
                    'action' => 'view',
                    'url' => PenjadwalanTugasResource::getUrl('view', ['record' => $task], panel: Filament::getCurrentPanel()?->getId()),
                    'url_target' => '_self',
                ],
            ];
        }

        $kalenderEvents = KalenderEvent::query()
            ->select([
                'id',
                'judul',
                'tipe',
                'mulai',
                'selesai',
                'all_day',
                'lokasi',
            ])
            ->get();

        foreach ($kalenderEvents as $event) {
            $payload = $this->buildKalenderEventPayload($event);

            if ($payload) {
                $events[] = $payload;
            }
        }

        return $events;
    }

    public function getOptions(): array
    {
        return [
            'dayMaxEvents' => 3,
        ];
    }

    public function getHeaderActions(): array
    {
        return [
            $this->buildTaskAction('createTaskHeader', 'Buat Tugas'),
            $this->buildCalendarEventAction('createEventHeader', 'Buat Event'),
        ];
    }

    public function getDateSelectContextMenuActions(): array
    {
        return [
            $this->buildTaskAction('createTaskRange', 'Buat Tugas'),
            $this->buildCalendarEventAction('createEventRange', 'Buat Event'),
        ];
    }

    private function buildLiburTitle(LiburCuti $libur): string
    {
        $parts = array_filter([
            $libur->keperluan?->getLabel(),
            $libur->keterangan,
        ]);

        return empty($parts) ? 'Libur/Cuti' : implode(' - ', $parts);
    }

    private function liburColor(?StatusPengajuan $status): string
    {
        return match ($status) {
            StatusPengajuan::Diterima => '#16a34a',
            StatusPengajuan::Pending => '#f59e0b',
            StatusPengajuan::Ditolak => '#ef4444',
            default => '#6b7280',
        };
    }

    private function tugasColor(?StatusTugas $status): string
    {
        return match ($status) {
            StatusTugas::Selesai => '#16a34a',
            StatusTugas::Proses => '#0ea5e9',
            StatusTugas::Pending => '#f59e0b',
            StatusTugas::Batal => '#ef4444',
            default => '#6b7280',
        };
    }

    private function kalenderEventColor(string $type): string
    {
        return match ($type) {
            'libur' => '#ef4444',
            'meeting' => '#0ea5e9',
            'event' => '#22c55e',
            'catatan' => '#f59e0b',
            default => '#6b7280',
        };
    }

    private function buildKalenderEventTitle(KalenderEvent $event): string
    {
        $typeLabel = match ($event->tipe) {
            'libur' => 'Libur',
            'meeting' => 'Meeting',
            'event' => 'Event',
            'catatan' => 'Catatan',
            default => 'Event',
        };

        return $this->prefixTitle($typeLabel, $event->judul ?: 'Tanpa Judul');
    }

    private function prefixTitle(string $prefix, string $title): string
    {
        $normalized = trim($title);
        $startsWith = str_starts_with(mb_strtolower($normalized), mb_strtolower($prefix . ' -'));

        return $startsWith ? $normalized : ($prefix . ' - ' . $normalized);
    }

    private function buildKalenderEventPayload(KalenderEvent $event): array
    {
        $rawMulai = $event->getRawOriginal('mulai');
        $rawSelesai = $event->getRawOriginal('selesai');

        if (! $rawMulai || ! $rawSelesai) {
            return [];
        }

        $eventTitle = $this->buildKalenderEventTitle($event);

        if ($event->all_day) {
            [$start, $end] = $this->normalizeAllDayRange(
                Carbon::parse($rawMulai)->toDateString(),
                Carbon::parse($rawSelesai)->toDateString(),
            );

            return [
                'title' => $eventTitle,
                'start' => $start,
                'end' => $end,
                'allDay' => true,
                'backgroundColor' => $this->kalenderEventColor($event->tipe),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'model' => $event::class,
                    'key' => $event->getKey(),
                    'action' => 'view',
                    'url' => KalenderEventResource::getUrl('view', ['record' => $event], panel: Filament::getCurrentPanel()?->getId()),
                    'url_target' => '_self',
                ],
            ];
        }

        return [
            'title' => $eventTitle,
            'start' => Carbon::parse($rawMulai)->toIso8601String(),
            'end' => Carbon::parse($rawSelesai)->toIso8601String(),
            'allDay' => false,
            'backgroundColor' => $this->kalenderEventColor($event->tipe),
            'textColor' => '#ffffff',
            'extendedProps' => [
                'model' => $event::class,
                'key' => $event->getKey(),
                'action' => 'view',
                'url' => KalenderEventResource::getUrl('view', ['record' => $event], panel: Filament::getCurrentPanel()?->getId()),
                'url_target' => '_self',
            ],
        ];
    }

    private function normalizeAllDayRange(Carbon | string $start, Carbon | string $end): array
    {
        $startDate = $start instanceof Carbon ? $start->copy() : Carbon::make($start);
        $endDate = $end instanceof Carbon ? $end->copy() : Carbon::make($end);

        if ($startDate && $endDate && $endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // All-day events are end-exclusive in the underlying calendar.
        if ($endDate) {
            $endDate = $endDate->copy()->addDay();
        }

        // Use date-only strings to avoid timezone shifts.
        return [
            $startDate?->toDateString(),
            $endDate?->toDateString(),
        ];
    }

    private function buildTaskAction(string $name, string $label): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-m-plus')
            ->modalHeading('Buat Tugas Baru')
            ->form($this->taskFormSchema())
            ->mountUsing(function (Form $form, array $arguments): void {
                [$startDate, $endDate] = $this->resolveDateRangeFromArguments($arguments);

                $form->fill([
                    'status' => StatusTugas::Pending,
                    'prioritas' => 'sedang',
                    'tanggal_mulai' => $startDate,
                    'deadline' => $endDate,
                ]);
            })
            ->action(function (array $data): void {
                $this->authorize('create', PenjadwalanTugas::class);

                PenjadwalanTugas::create([
                    ...$data,
                    'created_by' => Filament::auth()->id(),
                ]);

                $this->refreshRecords();

                Notification::make()
                    ->title('Tugas berhasil dibuat')
                    ->success()
                    ->send();
            });
    }

    private function buildCalendarEventAction(string $name, string $label): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-m-plus')
            ->modalHeading('Buat Event Baru')
            ->form($this->calendarEventFormSchema())
            ->mountUsing(function (Form $form, array $arguments): void {
                [$startDate, $endDate, $allDay] = $this->resolveDateRangeForEvent($arguments);

                $form->fill([
                    'tipe' => 'meeting',
                    'all_day' => $allDay,
                    'mulai' => $startDate,
                    'selesai' => $endDate,
                ]);
            })
            ->action(function (array $data): void {
                $this->authorize('create', KalenderEvent::class);

                KalenderEvent::create([
                    ...$data,
                    'created_by' => Filament::auth()->id(),
                ]);

                $this->refreshRecords();

                Notification::make()
                    ->title('Event berhasil dibuat')
                    ->success()
                    ->send();
            });
    }

    private function taskFormSchema(): array
    {
        return [
            TextInput::make('judul')
                ->label('Judul Tugas')
                ->required()
                ->maxLength(255),
            RichEditor::make('deskripsi')
                ->label('Deskripsi')
                ->required(),
            Select::make('status')
                ->label('Status')
                ->options(StatusTugas::class)
                ->native(false)
                ->required(),
            ToggleButtons::make('prioritas')
                ->label('Prioritas')
                ->options([
                    'rendah' => 'Rendah',
                    'sedang' => 'Sedang',
                    'tinggi' => 'Tinggi',
                ])
                ->colors([
                    'rendah' => 'success',
                    'sedang' => 'info',
                    'tinggi' => 'danger',
                ])
                ->icons([
                    'rendah' => 'heroicon-o-arrow-down',
                    'sedang' => 'heroicon-o-minus',
                    'tinggi' => 'heroicon-o-arrow-up',
                ])
                ->inline()
                ->required(),
            Select::make('karyawan_id')
                ->label('Ditugaskan Kepada')
                ->options(fn() => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),
            DatePicker::make('tanggal_mulai')
                ->label('Tanggal Mulai')
                ->native(false)
                ->displayFormat('d M Y')
                ->required(),
            DatePicker::make('deadline')
                ->label('Tenggat Waktu')
                ->native(false)
                ->displayFormat('d M Y')
                ->required(),
        ];
    }

    private function calendarEventFormSchema(): array
    {
        return [
            TextInput::make('judul')
                ->label('Judul')
                ->required()
                ->maxLength(255),
            Select::make('tipe')
                ->label('Tipe')
                ->options([
                    'meeting' => 'Meeting',
                    'event' => 'Event',
                    'catatan' => 'Catatan',
                ])
                ->native(false)
                ->required(),
            RichEditor::make('deskripsi')
                ->label('Deskripsi')
                ->columnSpanFull(),
            TextInput::make('lokasi')
                ->label('Lokasi')
                ->maxLength(255),
            Toggle::make('all_day')
                ->label('Sepanjang Hari')
                ->reactive(),
            DateTimePicker::make('mulai')
                ->label('Mulai')
                ->native(false)
                ->seconds(false)
                ->required(),
            DateTimePicker::make('selesai')
                ->label('Selesai')
                ->native(false)
                ->seconds(false)
                ->required()
                ->minDate(fn(Get $get) => $get('mulai')),
        ];
    }

    private function resolveDateRangeFromArguments(array $arguments): array
    {
        $startStr = data_get($arguments, 'startStr') ?? data_get($arguments, 'dateStr');
        $endStr = data_get($arguments, 'endStr') ?? $startStr;
        $allDay = (bool) data_get($arguments, 'allDay', true);

        $start = $this->normalizeDateString($startStr);
        $end = $this->normalizeDateString($endStr);

        if ($allDay && $end) {
            $end = Carbon::parse($end)->subDay()->toDateString();
        }

        if ($start && $end && Carbon::parse($end)->lessThan(Carbon::parse($start))) {
            [$start, $end] = [$end, $start];
        }

        $today = Carbon::today()->toDateString();

        return [
            $start ?? $today,
            $end ?? $start ?? $today,
        ];
    }

    private function resolveDateRangeForEvent(array $arguments): array
    {
        $startStr = data_get($arguments, 'startStr') ?? data_get($arguments, 'dateStr');
        $endStr = data_get($arguments, 'endStr') ?? $startStr;
        $allDay = (bool) data_get($arguments, 'allDay', true);

        $start = $this->normalizeDateString($startStr);
        $end = $this->normalizeDateString($endStr);

        if ($allDay && $end) {
            $end = Carbon::parse($end)->subDay()->toDateString();
        }

        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::now();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : $startDate->copy()->endOfDay();

        return [
            $startDate,
            $endDate,
            $allDay,
        ];
    }

    private function normalizeDateString(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_contains($value, 'T')) {
            return Carbon::parse($value)->toDateString();
        }

        return $value;
    }
}
