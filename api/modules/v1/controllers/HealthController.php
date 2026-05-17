<?php

declare(strict_types=1);

namespace api\modules\v1\controllers;

final class HealthController extends JsonRestController
{
    public function actionIndex(): array
    {
        return [
            'version' => 'v1',
            'status' => 'ok',
            'time' => gmdate(DATE_ATOM),
        ];
    }
}
