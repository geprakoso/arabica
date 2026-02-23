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

    public function getEventContent(): null | string | array
    {
        return <<<'HTML'
        <div style="padding:4px 6px;line-height:1.3;cursor:pointer;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:4px;">
                <span style="font-weight:600;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;" x-text="event?.title || ''"></span>
                <span style="font-size:10px;color:#9ca3af;white-space:nowrap;" x-text="event?.extendedProps?.time || ''"></span>
            </div>
            <div style="display:flex;align-items:center;gap:4px;margin-top:2px;">
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;flex-shrink:0;" x-bind:style="'background:' + ({published:'#16a34a',scheduled:'#2563eb',waiting:'#d97706',draft:'#6b7280'}[event?.extendedProps?.status] || '#6b7280')"></span>
                <span style="font-size:10px;color:#9ca3af;" x-text="event?.extendedProps?.statusLabel || ''"></span>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:3px;">
                <template x-if="event?.extendedProps?.pillarLabel">
                    <span style="display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;color:#fff;" x-text="event?.extendedProps?.pillarLabel" x-bind:style="'display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;color:#fff;background:' + ({edukasi:'#3b82f6',promo:'#ef4444',branding:'#f59e0b',engagement:'#22c55e',testimoni:'#6366f1'}[event?.extendedProps?.pillar] || '#6b7280')"></span>
                </template>
                <template x-if="event?.extendedProps?.tipeKonten">
                    <span style="display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:500;color:#374151;background:#e5e7eb;" x-text="event?.extendedProps?.tipeKonten"></span>
                </template>
            </div>
            <template x-if="event?.extendedProps?.platforms?.length > 0">
                <div style="display:flex;gap:3px;margin-top:4px;">
                    <template x-for="p in (event?.extendedProps?.platforms || [])" :key="p">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;color:#fff;font-size:9px;font-weight:700;" x-bind:style="'background:' + ({instagram:'linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)',tiktok:'#000',facebook:'#1877f2',twitter:'#000',youtube:'#ff0000',linkedin:'#0a66c2'}[p] || '#6b7280')" x-text="{instagram:'IG',tiktok:'TT',facebook:'f',youtube:'▶',linkedin:'in'}[p] || p"></span>
                    </template>
                </div>
            </template>
        </div>
        HTML;
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
