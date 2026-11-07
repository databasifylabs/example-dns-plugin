<?php

declare(strict_types=1);

namespace Databasify\ExampleDns;

use App\DTOs\Dns\DnsRecord;
use App\Enums\Dns\DnsRecordType;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final readonly class StoredRecord
{
    public function __construct(
        public string $id,
        public string $zone,
        public DnsRecordType $type,
        public string $name,
        public string $content,
        public ?string $comment,
    ) {}

    public static function created(string $zone, DnsRecord $record): self
    {
        return new self(Str::uuid()->toString(), $zone, $record->type, $record->name, $record->content, DnsRecord::MARKER);
    }

    /**
     * @param  array<mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        $comment = $row['comment'] ?? null;

        return new self(
            Arr::string($row, 'id'),
            Arr::string($row, 'zone'),
            DnsRecordType::from(Arr::string($row, 'type')),
            Arr::string($row, 'name'),
            Arr::string($row, 'content'),
            is_string($comment) ? $comment : null,
        );
    }

    /**
     * @return array{id: string, zone: string, type: string, name: string, content: string, comment: ?string}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'zone' => $this->zone, 'type' => $this->type->value, 'name' => $this->name, 'content' => $this->content, 'comment' => $this->comment];
    }

    public function matches(string $zone, DnsRecordType $type, string $name): bool
    {
        return $this->zone === $zone && $this->type === $type && $this->name === $name;
    }

    public function toDnsRecord(): DnsRecord
    {
        return new DnsRecord($this->type, $this->name, $this->content, $this->id, $this->comment === DnsRecord::MARKER);
    }
}
