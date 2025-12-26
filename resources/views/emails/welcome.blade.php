<x-mail::message>
    # Welcome to MRBS, {{ $user->name }}!

    Your account has been created for the Meeting Room Booking System.

    **Your Login Credentials:**
    - **Email:** {{ $user->email }}
    - **Temporary Password:** {{ $tempPassword }}

    <x-mail::button :url="$loginUrl">
        Login Now
    </x-mail::button>

    **Important:** For security, you will be required to change your password upon first login.

    If you have any questions, please contact your system administrator.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
