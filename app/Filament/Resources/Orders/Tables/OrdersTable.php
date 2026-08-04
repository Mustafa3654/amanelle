<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('number')
                    ->label('Order')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Order $record) => $record->placed_at?->diffForHumans()),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Order $record) => $record->customer_phone),

                TextColumn::make('city')->toggleable(),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Lines')
                    ->alignEnd(),

                TextColumn::make('total')
                    ->money('USD')
                    ->alignEnd()
                    // What the customer actually saw, when it was not dollars.
                    ->description(fn (Order $record) => $record->display_currency !== 'USD'
                        ? number_format((float) $record->total * (float) $record->display_rate).' '.$record->display_currency
                        : null),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('reservation_expires_at')
                    ->label('Stock held until')
                    ->since()
                    ->placeholder('—')
                    ->toggleable()
                    ->tooltip('Pending orders hold stock. Past this it goes back on sale automatically.'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(array_combine(Order::STATUSES, array_map('ucfirst', Order::STATUSES)))
                    ->multiple(),
            ])
            ->recordActions([
                /*
                 * Advancing status is the whole job, so it is one click rather
                 * than opening the record. Delivering confirms first because
                 * it is the one transition that changes the shelf count.
                 */
                Action::make('advance')
                    ->label(fn (Order $record) => match ($record->status) {
                        'pending' => 'Start processing',
                        'processing' => 'Mark shipped',
                        'shipped' => 'Mark delivered',
                        default => 'Done',
                    })
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color(fn (Order $record) => $record->status === 'shipped' ? 'success' : 'gray')
                    ->visible(fn (Order $record) => in_array($record->status, ['pending', 'processing', 'shipped'], true))
                    ->requiresConfirmation(fn (Order $record) => $record->status === 'shipped')
                    ->modalHeading('Mark as delivered?')
                    ->modalDescription('This removes the items from your shelf count and clears the reservation for this order.')
                    ->action(function (Order $record) {
                        $next = match ($record->status) {
                            'pending' => 'processing',
                            'processing' => 'shipped',
                            'shipped' => 'delivered',
                            default => $record->status,
                        };

                        $record->update([
                            'status' => $next,
                            'delivered_at' => $next === 'delivered' ? now() : $record->delivered_at,
                        ]);

                        Notification::make()
                            ->title("{$record->number} is now {$next}")
                            ->body($next === 'delivered' ? 'Stock has been deducted.' : null)
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record) => ! in_array($record->status, ['delivered', 'cancelled'], true))
                    ->requiresConfirmation()
                    ->modalDescription('The reserved items go back on sale immediately.')
                    ->action(function (Order $record) {
                        $record->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                        Notification::make()
                            ->title("{$record->number} cancelled")
                            ->body('Stock released back on sale.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ]);
    }
}
