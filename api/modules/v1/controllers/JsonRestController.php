<?php

declare(strict_types=1);

namespace api\modules\v1\controllers;

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

        return $behaviors;
    }
}
