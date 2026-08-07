<x-mail::message>
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
