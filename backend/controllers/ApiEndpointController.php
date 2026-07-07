<?php

declare(strict_types=1);

namespace backend\controllers;

use Yii;
use yii\data\ArrayDataProvider;

final class ApiEndpointController extends AdminController
{
    private const string METHOD_ANY = 'ANY';

    public function actionIndex(): string
    {
        return $this->render('index', [
            'apiUrl' => $this->getApiUrl(),
            'dataProvider' => $this->createDataProvider(),
        ]);
    }

    private function createDataProvider(): ArrayDataProvider
    {
        return new ArrayDataProvider([
            'allModels' => $this->findEndpointRows(),
            'pagination' => false,
            'sort' => [
                'attributes' => [
                    'method',
                    'path',
                    'route',
                ],
                'defaultOrder' => [
                    'path' => SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array{method: string, path: string, route: string}>
     */
    private function findEndpointRows(): array
    {
        $rows = [];

        foreach ($this->findApiUrlRules() as $pattern => $route) {
            if (is_string($route) === false && is_array($route) === false) {
                continue;
            }

            $rule = $this->createRule((string) $pattern, $route);

            if ($rule !== null) {
                $rows[] = $rule;
            }
        }

        return $rows;
    }

    /**
     * @return array{method: string, path: string, route: string}|null
     */
    private function createRule(string $pattern, array|string $route): array|null
    {
        if (is_array($route)) {
            return $this->createRuleFromArray($pattern, $route);
        }

        return $this->createRuleRow($pattern, $route);
    }

    /**
     * @param array<string, mixed> $rule
     *
     * @return array{method: string, path: string, route: string}|null
     */
    private function createRuleFromArray(string $pattern, array $rule): array|null
    {
        if (isset($rule['route']) === false || is_string($rule['route']) === false) {
            return null;
        }

        if (isset($rule['pattern']) && is_string($rule['pattern'])) {
            $pattern = $rule['pattern'];
        }

        return $this->createRuleRow($pattern, $rule['route']);
    }

    /**
     * @return array{method: string, path: string, route: string}
     */
    private function createRuleRow(string $pattern, string $route): array
    {
        [$method, $path] = $this->parsePattern($pattern);

        return [
            'method' => $method,
            'path' => $path,
            'route' => $route,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parsePattern(string $pattern): array
    {
        $parts = preg_split('/\s+/', trim($pattern), 2);
        $method = self::METHOD_ANY;
        $path = $pattern;

        if (is_array($parts) && $parts !== [] && $this->isHttpMethodList($parts[0])) {
            $method = str_replace(',', ', ', $parts[0]);
            $path = $parts[1] ?? '';
        }

        return [
            $method,
            $this->normalizePath($path),
        ];
    }

    private function isHttpMethodList(string $value): bool
    {
        return preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)(,(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS))*$/', $value) === 1;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        return '/' . ltrim($path, '/');
    }

    /**
     * @return array<string|int, mixed>
     */
    private function findApiUrlRules(): array
    {
        $config = require Yii::getAlias('@api/config/main.php');
        $rules = $config['components']['urlManager']['rules'] ?? [];

        if (is_array($rules)) {
            return $rules;
        }

        return [];
    }

    private function getApiUrl(): string
    {
        $apiUrl = Yii::$app->params['apiUrl'] ?? '';

        if (is_string($apiUrl)) {
            return $apiUrl;
        }

        return '';
    }
}
