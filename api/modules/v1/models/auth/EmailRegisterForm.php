<?php

declare(strict_types=1);

namespace api\modules\v1\models\auth;

use common\models\User;
use common\services\auth\UsernameGenerator;
use Yii;
use yii\base\Model;

final class EmailRegisterForm extends Model
{
    public $name;
    public $email;
    public $password;

    public function rules(): array
    {
        return [
            [['name', 'email', 'password'], 'required'],
            [['name', 'email'], 'trim'],
            ['name', 'string', 'min' => 2, 'max' => 255],
            ['email', 'email'],
            ['email', 'filter', 'filter' => 'mb_strtolower'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => User::class, 'message' => 'This email address has already been taken.'],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength']],
        ];
    }

    public function register(): User | null
    {
        if ($this->validate() === false) {
            return null;
        }

        $user = new User();
        $user->username = $this->getUsernameGenerator()->generateByEmail((string) $this->email);
        $user->name = (string) $this->name;
        $user->email = (string) $this->email;
        $user->role = User::ROLE_USER;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword((string) $this->password);
        $user->generateAuthKey();

        if ($user->save()) {
            return $user;
        }

        foreach ($user->getErrors() as $attribute => $errors) {
            foreach ($errors as $error) {
                $this->addError($attribute, $error);
            }
        }

        return null;
    }

    private function getUsernameGenerator(): UsernameGenerator
    {
        return new UsernameGenerator();
    }
}
