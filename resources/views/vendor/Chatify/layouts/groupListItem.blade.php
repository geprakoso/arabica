@forelse($groups as $group)
<table class="messenger-list-item" data-contact="{{ $group->id }}" data-type="group">
    <tr data-action="0">
        {{-- Avatar side --}}
        <td style="position: relative">
            <div class="avatar av-m group-avatar"
                @if ($group->avatar)
                    style="background-image: url('{{ Chatify::getUserAvatarUrl($group->avatar) }}');"
                @endif
            >
                @unless ($group->avatar)
                    <span class="fas fa-users"></span>
                @endunless
            </div>
        </td>
        {{-- center side --}}
        <td>
            <p data-id="{{ $group->id }}" data-type="group">
                {{ strlen($group->name) > 18 ? trim(substr($group->name,0,18)).'..' : $group->name }}
                <span class="contact-item-time">{{ $group->members_count ?? $group->members()->count() }} members</span>
            </p>
            <span>{{ strlen($group->description ?? '') > 40 ? substr($group->description,0,40).'..' : ($group->description ?? 'No description yet') }}</span>
        </td>
    </tr>
</table>
@empty
<p class="message-hint center-el"><span>No groups available.</span></p>
@endforelse
