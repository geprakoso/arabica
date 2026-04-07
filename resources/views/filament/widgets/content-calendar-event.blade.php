{{-- Loomly-style event card for Content Calendar --}}
{{-- Data comes from x-data JSON attribute set by guava/calendar --}}
<div
    style="padding:4px 6px;line-height:1.3;cursor:pointer;font-family:inherit;"
    x-show="true"
>
    {{-- Title + Time --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:4px;">
        <span
            style="font-weight:600;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;"
            x-text="event?.title || ''"
        ></span>
        <span
            style="font-size:10px;color:#9ca3af;white-space:nowrap;"
            x-text="event?.extendedProps?.time || ''"
        ></span>
    </div>

    {{-- Status dot + label --}}
    <div style="display:flex;align-items:center;gap:4px;margin-top:2px;">
        <span
            style="display:inline-block;width:7px;height:7px;border-radius:50%;flex-shrink:0;"
            x-bind:style="'background:' + ({published:'#16a34a',scheduled:'#2563eb',waiting:'#d97706',draft:'#6b7280'}[event?.extendedProps?.status] || '#6b7280')"
        ></span>
        <span
            style="font-size:10px;color:#9ca3af;"
            x-text="event?.extendedProps?.statusLabel || ''"
        ></span>
    </div>

    {{-- Pillar + Tipe badges --}}
    <template x-if="event?.extendedProps?.pillarLabel || event?.extendedProps?.tipeKonten">
        <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:3px;">
            <template x-if="event?.extendedProps?.pillarLabel">
                <span
                    style="display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;color:#fff;"
                    x-text="event?.extendedProps?.pillarLabel"
                    x-bind:style="'display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;color:#fff;background:' + ({edukasi:'#3b82f6',promo:'#ef4444',branding:'#f59e0b',engagement:'#22c55e',testimoni:'#6366f1'}[event?.extendedProps?.pillar] || '#6b7280')"
                ></span>
            </template>
            <template x-if="event?.extendedProps?.tipeKonten">
                <span
                    style="display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:500;color:#374151;background:#e5e7eb;"
                    x-text="event?.extendedProps?.tipeKonten"
                ></span>
            </template>
        </div>
    </template>

    {{-- Platform icons --}}
    <template x-if="event?.extendedProps?.platforms?.length > 0">
        <div style="display:flex;gap:3px;margin-top:4px;">
            <template x-for="p in (event?.extendedProps?.platforms || [])" :key="p">
                <span
                    style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;color:#fff;font-size:9px;font-weight:700;"
                    x-bind:style="'display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;color:#fff;font-size:9px;font-weight:700;background:' + ({instagram:'linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888)',tiktok:'#000',facebook:'#1877f2',twitter:'#000',youtube:'#ff0000',linkedin:'#0a66c2'}[p] || '#6b7280')"
                    x-text="{instagram:'IG',tiktok:'TT',facebook:'f',twitter:'𝕏',youtube:'▶',linkedin:'in'}[p] || p"
                ></span>
            </template>
        </div>
    </template>
</div>
