{{-- user info and avatar --}}
<div class="avatar av-l chatify-d-flex"></div>
<p class="info-name">{{ config('chatify.name') }}</p>
<div class="messenger-infoView-btns">
    <a href="#" class="danger delete-conversation">Delete Conversation</a>
</div>
{{-- group members --}}
<div class="messenger-infoView-members">
    <p class="messenger-title"><span>Members</span></p>
    <div class="group-members-list">
        <p class="message-hint center-el"><span>No members found.</span></p>
    </div>
</div>
{{-- shared photos --}}
<div class="messenger-infoView-shared">
    <p class="messenger-title"><span>Shared Photos</span></p>
    <div class="shared-photos-list"></div>
</div>
