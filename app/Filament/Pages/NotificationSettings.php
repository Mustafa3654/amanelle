<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Http;
use UnitEnum;

class NotificationSettings extends Page
{
    protected string $view = 'filament.pages.notification-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Order alerts';

    protected static ?string $navigationLabel = 'Order alerts';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'contact_email' => Setting::get('contact_email'),
            'telegram_token' => Setting::getEncrypted('telegram_token'),
            'telegram_chat_id' => Setting::get('telegram_chat_id'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Contact enquiries')
                    ->description('Where messages from the contact form are sent.')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Send enquiries to')
                            ->email()
                            ->helperText('Replying to the email goes straight back to the customer. Every enquiry is also saved under Enquiries, so nothing is lost if mail is misconfigured.'),
                    ]),

                Section::make('Telegram')
                    ->description('The fastest way to hear about an order — it reaches your phone the moment someone checks out.')
                    ->schema([
                        TextInput::make('telegram_token')
                            ->label('Bot token')
                            // Treated as a credential: masked in the UI and
                            // encrypted at rest, so a database dump does not
                            // hand someone control of the bot.
                            ->password()
                            ->revealable()
                            ->autocomplete(false)
                            ->helperText('From @BotFather on Telegram: send /newbot and copy the token it gives you.'),

                        TextInput::make('telegram_chat_id')
                            ->label('Chat ID')
                            ->helperText('Message your new bot once, then open https://api.telegram.org/bot<TOKEN>/getUpdates and copy the "id" under "chat".'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::put('contact_email', $data['contact_email'] ?: null);
        Setting::putEncrypted('telegram_token', $data['telegram_token'] ?: null);
        Setting::put('telegram_chat_id', $data['telegram_chat_id'] ?: null);

        Notification::make()->title('Saved')->success()->send();
    }

    public function sendTest(): void
    {
        $data = $this->form->getState();

        if (blank($data['telegram_token']) || blank($data['telegram_chat_id'])) {
            Notification::make()->title('Fill in both fields first')->warning()->send();

            return;
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$data['telegram_token']}/sendMessage",
                [
                    'chat_id' => $data['telegram_chat_id'],
                    'text' => "✅ Amanelle is connected.\nOrder alerts will arrive here.",
                ]
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Could not reach Telegram')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($response->successful()) {
            Notification::make()->title('Sent — check Telegram')->success()->send();

            return;
        }

        // Telegram's own wording is genuinely useful here ("chat not found",
        // "Unauthorized"), so it is passed through rather than swallowed.
        Notification::make()
            ->title('Telegram refused it')
            ->body($response->json('description') ?? 'Check the token and chat ID.')
            ->danger()
            ->send();
    }
}
