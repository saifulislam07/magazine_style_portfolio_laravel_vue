<x-mail::message>
# New message from your website

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})

@if ($contactMessage->subject)
**Subject:** {{ $contactMessage->subject }}
@endif

<x-mail::panel>
{{ $contactMessage->message }}
</x-mail::panel>

Reply to this email to answer {{ $contactMessage->name }} directly.

<x-mail::button :url="route('admin.dashboard')">
Open the studio inbox
</x-mail::button>
</x-mail::message>
