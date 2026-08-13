<?php

namespace App\Filament\Resources\Enquiries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        // Read-only throughout: an enquiry is a record of what someone sent.
        // Editing it would only ever falsify the record.
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label(__('Name')),
                    TextEntry::make('email')->label(__('Email'))->copyable(),
                    TextEntry::make('created_at')->label(__('Received'))->dateTime(),
                    TextEntry::make('emailed_at')
                        ->label(__('Emailed to you'))
                        ->dateTime()
                        ->placeholder(__('Not emailed')),
                ]),

            Section::make(__('Message'))
                ->schema([
                    TextEntry::make('message')->hiddenLabel()->prose(),
                ]),
        ]);
    }
}
