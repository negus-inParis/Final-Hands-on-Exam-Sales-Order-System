<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $calculations = function (Get $get, Set $set) {
            $items = $get('../../orderItems') ?? [];
            $total = 0;
            foreach ($items as $item) {
                $price = (float) ($item['purchase_price'] ?? 0);
                $quantity = (float) ($item['quantity'] ?? 0);
                $total += $price * $quantity;
            }
            $total = round($total, 2);
            $set('../../total', $total);
            $tax = $total > 1000 ? round($total * 0.12, 2) : 0;
            $set('../../tax', $tax);
            $grandTotal = round($total + $tax, 2);
            $set('../../grand_total', $grandTotal);
        };

        return $form
            ->schema([
                Section::make('Customer\'s Detail')
                    ->schema([
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->compact(),

                Section::make('Ordering Section')
                    ->schema([
                        Repeater::make('orderItems')
                            ->columnSpanFull()
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) use ($calculations) {
                                        $product = Product::find($state);
                                        $set('purchase_price', $product?->cost_price ?? 0);
                                        $calculations($get, $set);
                                    })
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Please select a product',
                                    ])
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),

                                TextInput::make('purchase_price')
                                    ->disabled()
                                    ->dehydrated()
                                    ->numeric(),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->live(debounce: 1000)
                                    ->disabled(fn (Get $get) => $get('product_id') === null)
                                    ->afterStateUpdated($calculations)
                                    ->required(),
                            ]),

                        TextInput::make('total')
                            ->label('Expected Total')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('tax')
                            ->label('Tax (12% if total exceeds 1000)')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('grand_total')
                            ->label('Grand Total')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('amount_tendered')
                            ->numeric()
                            ->live(debounce: 1000)
                            ->disabled(fn (Get $get) => $get('total') === null || (float) $get('total') === 0.0)
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $grandTotal = (float) $get('grand_total');
                                    $amountTendered = (float) $value;
                                    if ($grandTotal > $amountTendered) {
                                        $fail('Amount tendered should be greater than or equal to total.');
                                    }
                                },
                            ])
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $grandTotal = (float) $get('grand_total');
                                $amountTendered = (float) $state;
                                if ($amountTendered < $grandTotal) {
                                    $set('change', 'Invalid');
                                } else {
                                    $set('change', number_format($amountTendered - $grandTotal, 2));
                                }
                            })
                            ->required(),

                        TextInput::make('change')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),

                TextColumn::make('orderItems.product.name')
                    ->listWithLineBreaks()
                    ->label('Product Display'),

                TextColumn::make('orderItems.purchase_price')
                    ->listWithLineBreaks()
                    ->money('PHP')
                    ->label('Product Price'),

                TextColumn::make('orderItems.quantity')
                    ->listWithLineBreaks()
                    ->label('Qty'),

                TextColumn::make('sub_total')
                    ->state(fn ($record) => $record->orderItems->map(
                        fn ($item) => $item->purchase_price * $item->quantity
                    ))
                    ->listWithLineBreaks()
                    ->money('PHP')
                    ->label('Sub Total Display'),

                TextColumn::make('tax')
                    ->state(function ($record) {
                        $sum = $record->orderItems->sum(
                            fn ($item) => $item->purchase_price * $item->quantity
                        );
                        return $sum > 1000 ? $sum * 0.12 : 0;
                    })
                    ->money('PHP')
                    ->label('Tax (12%)'),

                TextColumn::make('grand_total')
                    ->state(function ($record) {
                        $sum = $record->orderItems->sum(
                            fn ($item) => $item->purchase_price * $item->quantity
                        );
                        $tax = $sum > 1000 ? $sum * 0.12 : 0;
                        return $sum + $tax;
                    })
                    ->money('PHP')
                    ->label('Grand Total'),

                TextColumn::make('created_at')
                    ->date('m/d/y'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
