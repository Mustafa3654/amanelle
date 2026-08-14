<x-mail::message>
<div style="text-align: center; margin-bottom: 24px;">
    <img src="{{ asset('images/logo.jpeg') }}" alt="{{ config('app.name') }}" style="display: inline-block; width: 150px; height: auto; border: 0;">
</div>

# New enquiry

**{{ $enquiry->name }}**
{{ $enquiry->email }}

<x-mail::panel>
{{ $enquiry->message }}
</x-mail::panel>

Hit reply to answer them directly.

<x-mail::button :url="url('/admin/enquiries')">
Open in admin
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
