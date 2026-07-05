<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Вход';
?>
<div class="login-box">
    <div class="login-logo">
        <?= Html::a('<b>GeoLyrics</b> Admin', ['/site/index']) ?>
    </div>

    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Войдите в админку каталога</p>

            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

                <?= $form->field($model, 'username', [
                    'template' => '<div class="input-group mb-3">{input}<div class="input-group-text"><span class="bi bi-person" aria-hidden="true"></span></div>{error}</div>',
                ])->textInput([
                    'autofocus' => true,
                    'class' => 'form-control',
                    'placeholder' => 'Логин',
                ])->label(false) ?>

                <?= $form->field($model, 'password', [
                    'template' => '<div class="input-group mb-3">{input}<div class="input-group-text"><span class="bi bi-lock-fill" aria-hidden="true"></span></div>{error}</div>',
                ])->passwordInput([
                    'class' => 'form-control',
                    'placeholder' => 'Пароль',
                ])->label(false) ?>

                <?= $form->field($model, 'rememberMe')->checkbox([], false)->label('Запомнить меня') ?>

                <div class="d-grid">
                    <?= Html::submitButton('Войти', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
