<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Products;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $cluster = Products::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form

            ->schema([
                Grid::make(2)
                    ->schema([
                        Section::make('')
                            ->schema([
                                Select::make('category_id')
                                    ->label('Katagori Produk')
                                    ->options(
                                        Category::query()
                                            ->where('is_active', true)
                                            ->get()
                                            ->pluck('name', 'id')
                                    )
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required(),
                                Toggle::make('is_active')
                                    ->label('Status Produk Aktif?')
                                    ->default(true)
                            ])
                            ->columnSpan(1),

                        Section::make('')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Gambar')
                                    ->image()
                                    ->columnSpanFull()
                                    ->required()
                            ])
                            ->columnSpan(1),
                        Section::make('')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Harga')
                                    ->numeric()
                                    ->prefix('Rp.')
                                    ->required(),
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stok')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->sortable(),
                ImageColumn::make('image')
                    ->label('Gambar'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state > 50,
                        'warning' => fn($state) => $state <= 50 && $state > 10,
                        'danger' => fn($state) => $state <= 10,
                    ])
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->icon(fn(string $state): string => match ($state) {
                        '1' => 'heroicon-o-check-circle',
                        '0' => 'heroicon-o-x-circle',
                        default => 'heroicon-s-question-mark-circle',
                    })
                    //color icon
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                        default => 'text-gray-600',
                    })

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
