<?php

declare(strict_types=1);

namespace api\modules\v1\models\auth;

use common\models\User;
use common\services\auth\GoogleIdentityVerifier;
use common\services\auth\UsernameGenerator;
use Throwable;
use Yii;
use yii\base\Model;

final class GoogleLoginForm extends Model
{
    public $idToken;

    public function __construct(
        private readonly GoogleIdentityVerifier $googleIdentityVerifier,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            ['idToken', 'required'],
            ['idToken', 'string'],
        ];
    }

    public function login(): User | null
    {
        if ($this->validate() === false) {
            return null;
        }

        try {
            $identity = $this->googleIdentityVerifier->verify((string) $this->idToken);
        } catch (Throwable $exception) {
            $this->addError('idToken', $exception->getMessage());

            return null;
        }

        $user = User::findByGoogleSubject($identity->getSubject());

        if ($user instanceof User) {
            return $user;
        }

        $user = User::findAnyByEmail($identity->getEmail());

        if ($user instanceof User) {
            $user->google_subject = $identity->getSubject();

            if (trim((string) $user->name) === '') {
                $user->name = $identity->getName();
            }

            if ($user->status !== User::STATUS_ACTIVE) {
                $user->status = User::STATUS_ACTIVE;
            }

            if ($user->save()) {
                return $user;
            }

            $this->addUserErrors($user);

            return null;
        }

        return $this->createUser($identity->getName(), $identity->getEmail(), $identity->getSubject());
    }

    private function createUser(string $name, string $email, string $googleSubject): User | null
    {
        $user = new User();
        $user->username = $this->getUsernameGenerator()->generateByEmail($email);
        $user->name = $name;
        $user->email = $email;
        $user->google_subject = $googleSubject;
        $user->role = User::ROLE_USER;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword(Yii::$app->security->generateRandomString(64));
        $user->generateAuthKey();

        if ($user->save()) {
            return $user;
        }

        $this->addUserErrors($user);

        return null;
    }

    private function addUserErrors(User $user): void
    {
        foreach ($user->getErrors() as $attribute => $errors) {
            foreach ($errors as $error) {
                $this->addError($attribute, $error);
            }
        }
    }

    private function getUsernameGenerator(): UsernameGenerator
    {
        return new UsernameGenerator();
    }
}
