<?php

declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property int $expires_at
 * @property int|null $revoked_at
 * @property int $created_at
 * @property int $updated_at
 * @property User $user
 */
final class UserRefreshToken extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%user_refresh_token}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'token_hash', 'expires_at'], 'required'],
            [['user_id', 'expires_at', 'revoked_at'], 'integer'],
            [['token_hash'], 'string', 'max' => 64],
            [['token_hash'], 'unique'],
            [['user_agent'], 'string', 'max' => 512],
            [['ip_address'], 'string', 'max' => 64],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function isExpired(): bool
    {
        return $this->expires_at < time();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function revoke(): void
    {
        $this->revoked_at = time();
    }
}
