<?php
namespace App\DTOs;

use Illuminate\Support\Str;
use Spatie\DataTransferObject\DataTransferObject;

class CustomJobData extends DataTransferObject
{
    public string $id;
    public array $data;

    public static function fromData($data): self
    {
        return new self(
            id: Str::uuid(),
            data: $data
        );
    }
}
