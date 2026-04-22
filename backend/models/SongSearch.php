<?php

declare(strict_types=1);

namespace backend\models;

use common\models\Song;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class SongSearch extends Model
{
    public $id;
    public $original_language_id;
    public $slug;
    public $default_title;
    public $publication_status;

    public function rules(): array
    {
        return [
            [['id', 'original_language_id'], 'integer'],
            [['slug', 'default_title', 'publication_status'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Song::find()->with(['originalLanguage']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'updated_at' => SORT_DESC,
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if ($this->validate() === false) {
            return $dataProvider;
        }

        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['original_language_id' => $this->original_language_id]);
        $query->andFilterWhere(['publication_status' => $this->publication_status]);
        $query->andFilterWhere(['ilike', 'slug', $this->slug]);
        $query->andFilterWhere(['ilike', 'default_title', $this->default_title]);

        return $dataProvider;
    }
}
