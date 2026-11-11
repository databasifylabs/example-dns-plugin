<?php

declare(strict_types=1);

namespace Databasify\ExampleDns;

use App\DTOs\Forms\DynamicField;
use App\DTOs\Forms\DynamicForm;
use App\Plugins\AbstractPlugin;
use Illuminate\Support\Facades\File;

final class ExampleDnsPlugin extends AbstractPlugin
{
    public function boot(): void
    {
        $this->databasify()->dns(ExampleDns::id())
            ->label('Example DNS')
            ->handler(ExampleDns::class)
            ->form(DynamicForm::make([
                DynamicField::make('account')->text()->label('Account name')->placeholder('demo'),
                DynamicField::make('token')->password()->label('Any token'),
            ]))
            ->instructions([
                'This provider is fake: nothing reaches real DNS.',
                'Fill in any account name and token, for example `demo` and `secret`.',
                'Records go to `storage/app/example-dns/` on the Databasify server. See the [plugin README](https://github.com/databasifylabs/plugin-example-dns).',
            ])
            ->register();
    }

    public function uninstall(): void
    {
        File::deleteDirectory(ExampleDns::directory());
    }
}
