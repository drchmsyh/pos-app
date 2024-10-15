<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    // Override untuk redirect setelah penyimpanan
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
