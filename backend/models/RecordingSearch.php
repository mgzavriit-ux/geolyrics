<?php

declare(strict_types=1);

namespace backend\models;

use common\models\Recording;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class RecordingSearch extends Model
{
    public $id;
    public $song_id;
    public $release_year;
    public $slug;
    public $default_title;
    public $recording_type;
    public $publication_status;

    public function rules(): array
    {
        return [
            [['id', 'song_id', 'release_year'], 'integer'],
            [['slug', 'default_title', 'recording_type', 'publication_status'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Recording::find()->with(['song']);

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
        $query->andFilterWhere(['song_id' => $this->song_id]);
        $query->andFilterWhere(['release_year' => $this->release_year]);
        $query->andFilterWhere(['recording_type' => $this->recording_type]);
        $query->andFilterWhere(['publication_status' => $this->publication_status]);
        $query->andFilterWhere(['ilike', 'slug', $this->slug]);
        $query->andFilterWhere(['ilike', 'default_title', $this->default_title]);

        return $dataProvider;
    }
}
