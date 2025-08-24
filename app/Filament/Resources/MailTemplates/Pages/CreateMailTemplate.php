<?php

namespace App\Filament\Resources\MailTemplates\Pages;

use App\Filament\Resources\MailTemplates\MailTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMailTemplate extends CreateRecord
{
    protected static string $resource = MailTemplateResource::class;
}
