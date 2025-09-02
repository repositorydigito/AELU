<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.reports';
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?string $title = 'Reportes';
    protected static ?string $navigationGroup = 'Gestión';
    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return Auth::user()->can('page_Reports');
    }

}
