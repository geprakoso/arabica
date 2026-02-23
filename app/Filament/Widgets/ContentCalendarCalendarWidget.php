<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Forms\Form;
use App\Models\ContentCalendar;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Guava\Calendar\Widgets\CalendarWidget;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Resources\KontenSosmed\ContentCalendarResource;

class ContentCalendarCalendarWidget extends CalendarWidget
{
    use InteractsWithForms;

    protected string | \Closure | \Illuminate\Support\HtmlString | null $heading = 'Kalender Konten';

    protected int | string | array $columnSpan = 'full';
    protected bool $dayMaxEvents = true;
    protected ?string $icon = 'heroicon-o-calendar-days';
    protected ?string $locale = 'id';
    protected bool $eventClickEnabled = true;
    protected bool $dateSelectEnabled = true;

    protected static string $view = 'filament.widgets.content-calendar-widget';

    protected string | \Closure | \Illuminate\Support\HtmlString | null $description = 'Jadwal konten sosial media';

    public function getDescription(): string | \Illuminate\Support\HtmlString | null
    {
        return $this->description;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public ?int $month = null;
    public ?int $year = null;

    public function mount(): void
    {
        $now = Carbon::now();
        $this->month = $this->month ?? $now->month;
        $this->year = $this->year ?? $now->year;
        $this->setOption('date', sprintf('%04d-%02d-01', $this->year, $this->month));
    }

    public function updatedMonth(): void
    {
        $this->updateCalendarView();
    }

    public function updatedYear(): void
    {
        $this->updateCalendarView();
    }

    public function updateCalendarView(): void
    {
        $this->setOption('date', sprintf('%04d-%02d-01', $this->year, $this->month));
    }

    public function getEvents(array $fetchInfo = []): Collection | array
    {
        $events = [];

        $pillarLabels = [
            'edukasi' => 'Edukasi',
            'promo' => 'Promo',
            'branding' => 'Branding',
            'engagement' => 'Engagement',
            'testimoni' => 'Testimoni',
        ];

        $tipeLabels = [
            'feed' => 'Feed',
            'story' => 'Story',
            'reels' => 'Reels',
            'carousel' => 'Carousel',
            'video' => 'Video',
            'talking_head' => 'Talking Head',
        ];

        $contents = ContentCalendar::query()
            ->whereNotNull('tanggal_publish')
            ->get();

        foreach ($contents as $content) {
            $className = $this->pillarClass($content->content_pillar);

            $events[] = [
                'title' => $content->judul ?: 'Tanpa Judul',
                'start' => $content->tanggal_publish->toIso8601String(),
                'end' => $content->tanggal_publish->copy()->addHour()->toIso8601String(),
                'allDay' => false,
                'classNames' => [$className, 'cc-rich-event'],
                'className' => $className,
                'extendedProps' => [
                    'model' => $content::class,
                    'key' => $content->getKey(),
                    'action' => 'view',
                    'url' => ContentCalendarResource::getUrl('view', ['record' => $content], panel: Filament::getCurrentPanel()?->getId()),
                    'url_target' => '_self',
                    'status' => $content->status,
                    'statusLabel' => ucfirst($content->status),
                    'pillar' => $content->content_pillar,
                    'pillarLabel' => $pillarLabels[$content->content_pillar] ?? '',
                    'tipeKonten' => $tipeLabels[$content->tipe_konten] ?? '',
                    'platforms' => $content->platform ?? [],
                    'time' => $content->tanggal_publish->format('g:i A'),
                ],
            ];
        }

        return $events;
    }


    public function getOptions(): array
    {
        return [
            'dayMaxEvents' => 4,
            'headerToolbar' => [
                'start' => 'title',
                'center' => '',
                'end' => 'today prev,next dayGridMonth,dayGridWeek,listWeek',
            ],
            'buttonText' => [
                'today' => 'Hari ini',
                'month' => 'Bulanan',
                'week' => 'Mingguan',
                'list' => 'Daftar',
            ],
            'eventDisplay' => 'block',
        ];
    }

    public function getHeaderActions(): array
    {
        return [
            $this->buildCreateAction('createContentHeader', 'Buat Konten'),
        ];
    }

    public function getDateSelectContextMenuActions(): array
    {
        return [
            $this->buildCreateAction('createContentRange', 'Buat Konten'),
        ];
    }

    private function pillarClass(?string $pillar): string
    {
        return match ($pillar) {
            'edukasi' => 'event-info',
            'promo' => 'event-danger',
            'branding' => 'event-warning',
            'engagement' => 'event-success',
            'testimoni' => 'event-info',
            default => 'event-gray',
        };
    }

    private function buildCreateAction(string $name, string $label): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-m-plus')
            ->modalHeading('Buat Konten Baru')
            ->form($this->contentFormSchema())
            ->mountUsing(function (Form $form, array $arguments): void {
                $startStr = data_get($arguments, 'startStr') ?? data_get($arguments, 'dateStr');
                $start = $startStr ? Carbon::parse($startStr) : Carbon::now();

                $form->fill([
                    'status' => 'draft',
                    'tanggal_publish' => $start,
                ]);
            })
            ->action(function (array $data): void {
                ContentCalendar::create([
                    ...$data,
                    'created_by' => Filament::auth()->id(),
                ]);

                $this->refreshRecords();

                Notification::make()
                    ->title('Konten berhasil dibuat')
                    ->success()
                    ->send();
            });
    }

    private function contentFormSchema(): array
    {
        return [
            \Filament\Forms\Components\TextInput::make('judul')
                ->label('Judul Konten')
                ->required()
                ->maxLength(255),
            \Filament\Forms\Components\Select::make('content_pillar')
                ->label('Content Pillar')
                ->options([
                    'edukasi' => '📚 Edukasi',
                    'promo' => '🔥 Promo',
                    'branding' => '🏷️ Branding',
                    'engagement' => '💬 Engagement',
                    'testimoni' => '⭐ Testimoni',
                ])
                ->native(false)
                ->required(),
            \Filament\Forms\Components\Select::make('tipe_konten')
                ->label('Tipe Konten')
                ->options([
                    'feed' => 'Feed',
                    'story' => 'Story',
                    'reels' => 'Reels',
                    'carousel' => 'Carousel',
                    'video' => 'Video',
                    'talking_head' => 'Talking Head',
                ])
                ->native(false),
            \Filament\Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => '📝 Draft',
                    'waiting' => '⏳ Waiting',
                    'scheduled' => '📅 Scheduled',
                    'published' => '✅ Published',
                ])
                ->default('draft')
                ->native(false)
                ->required(),
            \Filament\Forms\Components\CheckboxList::make('platform')
                ->label('Platform')
                ->options([
                    'instagram' => 'Instagram',
                    'tiktok' => 'TikTok',
                    'facebook' => 'Facebook',
                    'twitter' => 'Twitter / X',
                    'youtube' => 'YouTube',
                    'linkedin' => 'LinkedIn',
                ])
                ->columns(3),
            \Filament\Forms\Components\DateTimePicker::make('tanggal_publish')
                ->label('Tanggal Publish')
                ->native(false)
                ->seconds(false)
                ->required(),
            \Filament\Forms\Components\Select::make('pic')
                ->label('PIC')
                ->relationship('assignee', 'name')
                ->searchable()
                ->preload()
                ->native(false),
        ];
    }
}
