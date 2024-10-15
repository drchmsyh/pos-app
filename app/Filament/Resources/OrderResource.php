<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Closure;
use Faker\Provider\ar_EG\Text;
use Filament\Forms\Components\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $products = Product::get();
        return
            $form
            ->schema([
                Grid::make(4)
                    ->schema([
                        Section::make('Produk dipesan')
                            ->schema([
                                Repeater::make('order_items')
                                    ->relationship('orderItems')
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Product')
                                            ->relationship('product', 'name')
                                            // Options are all products, but we have modified the display to show the price as well
                                            ->options(
                                                $products->filter(function (Product $product) {
                                                    return $product->is_active;
                                                })->mapWithKeys(function (Product $product) {
                                                    return [$product->id => sprintf('%s', $product->name)];
                                                })

                                            )
                                            ->afterStateUpdated(
                                                function (Get $get, Set $set, $state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $set('unit_price', $product->price);
                                                    } else {
                                                        $set('quantity', null);
                                                        $set('unit_price', null);
                                                    }
                                                }
                                            )
                                            // Disable options that are already selected in other rows
                                            ->disableOptionWhen(function ($value, $state, Get $get) {
                                                return collect($get('../*.product_id'))
                                                    ->reject(fn($id) => $id == $state)
                                                    ->filter()
                                                    ->contains($value);
                                            })
                                            ->searchable()
                                            ->required(),
                                        TextInput::make('quantity')
                                            ->label('Jumlah')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->default(1)
                                            ->required(),
                                        TextInput::make('unit_price')
                                            ->label('Harga Satuan')
                                            ->default(fn($get) => $get('unit_price'))
                                            ->disabled()
                                            ->prefix('Rp.')
                                            ->dehydrated()
                                            ->required(),
                                        // Hidden fields to ensure values are submitted
                                        Forms\Components\Hidden::make('unit_price')
                                            ->default(fn($get) => $get('unit_price')),
                                    ])
                                    ->columns(4)
                                    ->required()
                                    ->live()
                                    // After adding a new row, we need to update the totals
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateTotals($get, $set);
                                    })
                                    // After deleting a row, we need to update the totals
                                    ->deleteAction(
                                        fn(Action $action) => $action->after(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                    )
                                    // Disable reordering
                                    ->reorderable(false),

                            ])
                            ->columnSpan(3),
                        Section::make('Pembayaran')
                            ->schema([
                                TextInput::make('total_price')
                                    ->numeric()
                                    // Read-only, because it's calculated
                                    ->readOnly()
                                    ->prefix('Rp.')
                                    // This enables us to display the subtotal on the edit page load
                                    ->afterStateHydrated(function (Get $get, Set $set) {
                                        self::updateTotals($get, $set);
                                    }),
                                Select::make('payment_method')
                                    ->label('Metode Pembayaran')
                                    ->options([
                                        'cash' => 'Cash',
                                        'qris' => 'QRIS',
                                    ])
                                    ->required()
                                    ->reactive() // Enable reactive behavior on change
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state === 'qris') {
                                            // For QRIS, set the total price in the amount and set change to 0, disable amount input
                                            $totalPrice = $get('total_price');
                                            $set('amount', $totalPrice);
                                            $set('change', 0);
                                            $set('amount_disabled', true); // Disable amount input for QRIS
                                        } elseif ($state === 'cash') {
                                            // For Cash, enable the amount field for user input
                                            $set('amount', null); // Reset the amount field for input
                                            $set('change', null); // Reset the change field
                                            $set('amount_disabled', false); // Enable the amount field for input
                                        }
                                    }),

                                TextInput::make('amount')
                                    ->label('Jumlah Uang')
                                    ->numeric()
                                    ->prefix('Rp.')
                                    ->required()
                                    ->readOnly(fn(Get $get) => $get('amount_disabled')) // Disable conditionally based on the payment method
                                    ->reactive() // Make it reactive so the change is updated dynamically
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        // Only calculate change if cash is selected
                                        if ($get('payment_method') === 'cash') {
                                            $totalPrice = (float)$get('total_price');
                                            $amount = (float)$get('amount');
                                            $change = $amount - $totalPrice;
                                            $set('change', $change); // Ensure that change is not negative
                                        } elseif ($get('payment_method') === 'qris') {
                                            $set('amount', (float)$get('total_price'));
                                            $set('change', 0); // Change is always 0 for QRIS
                                        }
                                    }),

                                TextInput::make('change')
                                    ->label('Kembalian')
                                    ->numeric()
                                    ->prefix('Rp.')
                                    ->readOnly() // The change field will be read-only, as it's automatically calculated
                                    ->required()
                                    //buat rule jika kembalian tidak boleh kurang dari 0
                                    ->rules('min:0'),


                            ])
                            ->columnSpan(1),
                    ])
            ]);
    }




    public static function updateTotals(Get $get, Set $set): void
    {
        // Retrieve all selected products and remove empty rows
        $selectedProducts = collect($get('order_items'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));

        // Retrieve prices for all selected products
        $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');

        // Calculate subtotal based on the selected products and quantities
        $subtotal = $selectedProducts->reduce(function ($subtotal, $product) use ($prices) {
            return $subtotal + ($prices[$product['product_id']] * $product['quantity']);
        }, 0);

        // Update the state with the new values
        $set('total_price', number_format($subtotal, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Transaksi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total Bayar')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('change')
                    ->label('Total Kembalian')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('printInvoice')
                    ->label('Print Invoice')
                    ->icon('heroicon-o-printer')
                    ->action(function (Order $record) {

                        return redirect()->route('invoice', $record->id);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }





    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
