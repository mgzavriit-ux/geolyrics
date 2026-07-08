<?php

declare(strict_types=1);

namespace api\modules\v1\models\auth;

use common\models\User;
use yii\base\Model;

final class EmailLoginForm extends Model
{
    public $email;
    public $password;

    private User | null $user = null;

    public function rules(): array
    {
        return [
            [['email', 'password'], 'required'],
            ['email', 'trim'],
            ['email', 'email'],
            ['email', 'filter', 'filter' => 'mb_strtolower'],
            ['password', 'validatePassword'],
        ];
    }

    public function login(): User | null
    {
        if ($this->validate() === false) {
            return null;
        }

        return $this->getUser();
    }

    public function validatePassword(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();

        if ($user === null || $user->validatePassword((string) $this->password) === false) {
            $this->addError($attribute, 'Incorrect email or password.');
        }
    }

    private function getUser(): User | null
    {
        if ($this->user === null) {
            $this->user = User::findByEmail((string) $this->email);
        }

        return $this->user;
    }
}
