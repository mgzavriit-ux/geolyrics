<?php

declare(strict_types=1);

namespace backend\controllers;

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
                    ],
                ],
            ],
        ];
    }
}
