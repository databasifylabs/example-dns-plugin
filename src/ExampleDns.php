<?php

declare(strict_types=1);

namespace Databasify\ExampleDns;

use App\DnsProviders\Drivers\AbstractDnsDriver;
use App\DTOs\Dns\DnsRecord;
use App\DTOs\Dns\DnsZoneData;
use App\Enums\Dns\DnsRecordType;
use App\Exceptions\Providers\ProviderRequestFailed;
use Illuminate\Support\Facades\File;

final readonly class ExampleDns extends AbstractDnsDriver
{
    private const array ZONES = ['example-dns.test', 'demo.test'];

    public static function id(): string
    {
        return 'example-dns';
    }

    public static function directory(): string
    {
        return storage_path('app/example-dns');
    }

    public function verify(): void
    {
        if ($this->credential('account') === '' || $this->credential('token') === '') {
            throw ProviderRequestFailed::from('Example DNS', 422, 'Fill in any account name and token.');
        }
    }

    public function zones(): array
    {
        return array_map(fn (string $zone): DnsZoneData => new DnsZoneData($zone, $zone), self::ZONES);
    }

    public function records(string $zoneId, DnsRecordType $type, string $name): array
    {
        $matching = array_filter($this->load(), fn (StoredRecord $row): bool => $row->matches($zoneId, $type, $name));

        return array_values(array_map(fn (StoredRecord $row): DnsRecord => $row->toDnsRecord(), $matching));
    }

    public function createRecord(string $zoneId, DnsRecord $record): void
    {
        $this->save([...$this->load(), StoredRecord::created($zoneId, $record)]);
    }

    public function deleteRecord(string $zoneId, DnsRecord $record): void
    {
        $this->save(array_filter($this->load(), fn (StoredRecord $row): bool => $row->id !== $record->id));
    }

    /**
     * @param  array<StoredRecord>  $rows
     */
    private function save(array $rows): void
    {
        $json = json_encode(array_values(array_map(fn (StoredRecord $row): array => $row->toArray(), $rows)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        File::ensureDirectoryExists(self::directory());
        File::put($this->file(), $json, lock: true);
    }

    /**
     * @return list<StoredRecord>
     */
    private function load(): array
    {
        $rows = File::exists($this->file()) ? json_decode(File::get($this->file(), lock: true), true) : [];

        return array_values(array_map(StoredRecord::fromArray(...), array_filter(is_array($rows) ? $rows : [], is_array(...))));
    }

    private function file(): string
    {
        return self::directory().'/'.sha1("{$this->credential('account')}:{$this->credential('token')}").'.json';
    }

    private function credential(string $key): string
    {
        return trim($this->dnsProvider->credentials[$key] ?? '');
    }
}
