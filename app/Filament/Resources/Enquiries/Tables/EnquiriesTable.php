<?php

namespace App\Filament\Resources\Enquiries\Tables;

use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Received'))
                    ->since()
                    ->sortable()
                    // Unread ones should be findable at a glance.
                    ->weight(fn (Enquiry $record) => $record->read_at ? null : 'bold'),

                TextColumn::make('name')->label(__('Name'))
                    ->searchable()
                    ->description(fn (Enquiry $record) => $record->email)
                    ->weight(fn (Enquiry $record) => $record->read_at ? null : 'bold'),

                TextColumn::make('message')->label(__('Message'))
                    ->limit(70)
                    ->wrap()
                    ->searchable(),

                IconColumn::make('emailed_at')
                    ->label(__('Emailed'))
                    ->boolean()
                    // A false here means mail is misconfigured, and the only
                    // copy of the message is this row.
                    ->tooltip(fn (Enquiry $record) => $record->emailed_at
                        ? 'Sent to your contact address'
                        : 'Not emailed — check the contact address and mail settings'),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label(__('Read'))
                    ->nullable()
                    ->placeholder(__('All'))
                    ->trueLabel('Read')
                    ->falseLabel('Unread'),
            ])
            ->recordActions([
                ViewAction::make()
                    // Opening it is what "read" means; making staff tick a box
                    // as well would just leave the flag permanently wrong.
                    ->after(fn (Enquiry $record) => $record->read_at
                        ?: $record->update(['read_at' => now()])),

                Action::make('reply')
                    ->label(__('Reply'))
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->url(fn (Enquiry $record) => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: your message to Amanelle'))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No enquiries yet'));
    }
}
