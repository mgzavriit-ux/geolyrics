<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\User;
use yii\console\Controller;

final class UserController extends Controller
{
    public function actionCreateAdmin(string $username, string $email, string $password): int
    {
        if (User::find()->andWhere(['username' => $username])->exists() === true) {
            $this->stderr("User with username \"{$username}\" already exists.\n");

            return self::EXIT_CODE_ERROR;
        }

        if (User::find()->andWhere(['email' => $email])->exists() === true) {
            $this->stderr("User with email \"{$email}\" already exists.\n");

            return self::EXIT_CODE_ERROR;
        }

        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();

        if ($user->save() === false) {
            $this->stderr("Failed to create admin user.\n");
            $this->stderr(print_r($user->getErrors(), true));

            return self::EXIT_CODE_ERROR;
        }

        $this->stdout("Admin user \"{$username}\" created successfully.\n");

        return self::EXIT_CODE_NORMAL;
    }
}
