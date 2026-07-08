<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

abstract class AdminController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => fn (): bool => $this->isAdminUser(),
                    ],
                ],
            ],
        ];
    }

    private function isAdminUser(): bool
    {
        $identity = Yii::$app->user->identity;

        return $identity instanceof User && $identity->isAdmin();
    }
}
