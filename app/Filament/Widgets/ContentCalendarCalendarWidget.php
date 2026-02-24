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
                    'action' => 'viewEvent',
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
                'end' => 'listWeek dayGridMonth prev today next',
            ],
            'buttonText' => [
                'today' => 'Hari ini',
                'dayGridMonth' => 'Bulanan',
                'listWeek' => 'Daftar',
            ],
            'eventDisplay' => 'block',
            'height' => 'auto',
            'contentHeight' => 'auto',
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

    public function viewEventAction(): Action
    {
        return Action::make('viewEvent')
            ->modalHeading('Detail Konten')
            ->slideOver()
            ->modalSubmitAction(false)
            ->modalCancelAction(fn($action) => $action->label('Tutup'))
            ->infolist(function (\Filament\Infolists\Infolist $infolist, array $arguments) {
                $recordId = data_get($arguments, 'event.extendedProps.key');
                if ($recordId) {
                    $infolist->record(ContentCalendar::find($recordId));
                }
                return ContentCalendarResource::infolist($infolist);
            });
    }
}
