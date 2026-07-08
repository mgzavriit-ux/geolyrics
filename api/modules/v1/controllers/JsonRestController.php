<?php

declare(strict_types=1);

namespace api\modules\v1\controllers;

use api\components\RequestJwtAuth;
use yii\web\Response;

abstract class JsonRestController extends \yii\rest\Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = [
            'application/json' => Response::FORMAT_JSON,
            'application/*+json' => Response::FORMAT_JSON,
            'text/json' => Response::FORMAT_JSON,
            'text/html' => Response::FORMAT_JSON,
        ];
        $requestJwtAuth = \Yii::$app->params['apiRequestJwtAuth'] ?? [];

        if (is_array($requestJwtAuth)) {
            $behaviors['requestJwtAuth'] = array_merge(
                [
                    'class' => RequestJwtAuth::class,
                ],
                $requestJwtAuth,
            );
        }

        return $behaviors;
    }
}
