<?php

declare(strict_types=1);

namespace common\models\auth;

final class GoogleIdentity
{
    public function __construct(
        private readonly string $subject,
        private readonly string $email,
        private readonly string $name,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }
}
