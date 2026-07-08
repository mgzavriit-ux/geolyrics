<?php

declare(strict_types=1);

namespace api\modules\v1\controllers;

use api\modules\v1\models\auth\EmailLoginForm;
use api\modules\v1\models\auth\EmailRegisterForm;
use api\modules\v1\models\auth\GoogleLoginForm;
use api\modules\v1\presenters\AuthPresenter;
use common\models\User;
use common\services\auth\AuthTokenManager;
use common\services\auth\GoogleIdentityVerifier;
use common\services\auth\JwtTokenService;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\BadRequestHttpException;
use yii\web\Request;
use yii\web\UnauthorizedHttpException;

final class AuthController extends JsonRestController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['userBearerAuth'] = [
            'class' => HttpBearerAuth::class,
            'only' => [
                'me',
            ],
        ];

        return $behaviors;
    }

    public function actionRegister(): array
    {
        $form = new EmailRegisterForm();
        $form->load($this->getRequestBodyParams(), '');
        $user = $form->register();

        if ($user === null) {
            throw new BadRequestHttpException($this->formatFirstError($form->getFirstErrors()));
        }

        return $this->getAuthPresenter()->presentSession($user, $this->getAuthTokenManager()->createTokenPair($user));
    }

    public function actionLogin(): array
    {
        $form = new EmailLoginForm();
        $form->load($this->getRequestBodyParams(), '');
        $user = $form->login();

        if ($user === null) {
            throw new UnauthorizedHttpException($this->formatFirstError($form->getFirstErrors()));
        }

        return $this->getAuthPresenter()->presentSession($user, $this->getAuthTokenManager()->createTokenPair($user));
    }

    public function actionGoogle(): array
    {
        $form = new GoogleLoginForm($this->getGoogleIdentityVerifier());
        $form->load($this->getRequestBodyParams(), '');
        $user = $form->login();

        if ($user === null) {
            throw new UnauthorizedHttpException($this->formatFirstError($form->getFirstErrors()));
        }

        return $this->getAuthPresenter()->presentSession($user, $this->getAuthTokenManager()->createTokenPair($user));
    }

    public function actionRefresh(): array
    {
        $refreshToken = $this->findRequestString('refreshToken');

        if ($refreshToken === '') {
            throw new BadRequestHttpException('Refresh token is required.');
        }

        $tokenPair = $this->getAuthTokenManager()->refreshTokenPair($refreshToken);
        $user = $this->getUserByAccessToken($tokenPair->toArray()['accessToken']);

        return $this->getAuthPresenter()->presentSession($user, $tokenPair);
    }

    public function actionLogout(): array
    {
        $refreshToken = $this->findRequestString('refreshToken');

        if ($refreshToken === '') {
            throw new BadRequestHttpException('Refresh token is required.');
        }

        return [
            'revoked' => $this->getAuthTokenManager()->revokeRefreshToken($refreshToken),
        ];
    }

    public function actionMe(): array
    {
        $identity = Yii::$app->user->identity;

        if ($identity instanceof User === false) {
            throw new UnauthorizedHttpException('User is required.');
        }

        return $this->getAuthPresenter()->presentUser($identity);
    }

    /**
     * @param array<string, string> $errors
     */
    private function formatFirstError(array $errors): string
    {
        $error = reset($errors);

        if (is_string($error) && $error !== '') {
            return $error;
        }

        return 'Request is invalid.';
    }

    /**
     * @return array<string, mixed>
     */
    private function getRequestBodyParams(): array
    {
        $params = $this->getRequest()->getBodyParams();

        return is_array($params) ? $params : [];
    }

    private function findRequestString(string $name): string
    {
        $value = $this->getRequestBodyParams()[$name] ?? null;

        if (is_string($value)) {
            return trim($value);
        }

        return '';
    }

    private function getUserByAccessToken(string $accessToken): User
    {
        $user = User::findIdentityByAccessToken($accessToken);

        if ($user instanceof User) {
            return $user;
        }

        throw new UnauthorizedHttpException('User is inactive.');
    }

    private function getAuthPresenter(): AuthPresenter
    {
        return new AuthPresenter();
    }

    private function getAuthTokenManager(): AuthTokenManager
    {
        $params = Yii::$app->params['userJwtAuth'] ?? [];
        $secret = is_array($params) ? (string) ($params['secret'] ?? '') : '';
        $issuer = is_array($params) ? (string) ($params['issuer'] ?? 'geolyrics-api') : 'geolyrics-api';
        $audience = is_array($params) ? (string) ($params['audience'] ?? 'geolyrics-client') : 'geolyrics-client';
        $accessTokenTtl = is_array($params) ? (int) ($params['accessTokenTtl'] ?? 900) : 900;
        $refreshTokenTtl = is_array($params) ? (int) ($params['refreshTokenTtl'] ?? 2592000) : 2592000;

        return new AuthTokenManager(
            new JwtTokenService($secret, $issuer, $audience),
            $accessTokenTtl,
            $refreshTokenTtl,
        );
    }

    private function getGoogleIdentityVerifier(): GoogleIdentityVerifier
    {
        $params = Yii::$app->params['googleAuth'] ?? [];
        $clientIds = is_array($params) && is_array($params['clientIds'] ?? null) ? $params['clientIds'] : [];
        $certsUrl = is_array($params) ? (string) ($params['certsUrl'] ?? '') : '';

        return new GoogleIdentityVerifier($clientIds, $certsUrl);
    }

    private function getRequest(): Request
    {
        /** @var Request $request */
        $request = Yii::$app->request;

        return $request;
    }
}
