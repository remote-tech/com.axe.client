<?php

namespace RemoteTech\ComAxe\Client\Oauth\Model;

class TokenIntrospect
{
    public string $status;
    public string $error;
    public bool $validEncoding;
    public bool $validSignature;
    public bool $expired;
    public bool $revoked;
    public ?string $expiresOn;

    public static function fromArray(array $array): TokenIntrospect
    {
        $self = new TokenIntrospect();

        $self->status = $array['status'];
        $self->error = $array['error'];
        $self->validEncoding = $array['validEncoding'];
        $self->validSignature = $array['validSignature'];
        $self->expired = $array['expired'];
        $self->revoked = $array['revoked'];
        $self->expiresOn = $array['expiresOn'];

        return $self;
    }
}