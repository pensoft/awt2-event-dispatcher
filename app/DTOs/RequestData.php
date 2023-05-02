<?php

namespace App\DTOs;

use App\Authentication\User;
use Dingo\Api\Http\Request;
use Spatie\DataTransferObject\DataTransferObject;

class RequestData extends DataTransferObject
{
    public ?User $user;

    public $data;

    public static function fromRequest(Request $request): self
    {
        return new self(
            user: $request->has('user')? new User($request->get('user')) : null,
            data: $request->has('data')? $request->get('data') : $request->all()
        );
    }
}
