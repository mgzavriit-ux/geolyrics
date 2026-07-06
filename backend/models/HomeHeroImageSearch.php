<?php

declare(strict_types=1);

namespace backend\models;

use common\models\HomeHeroImage;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class HomeHeroImageSearch extends Model
{
    public $id;
    public $artist_id;
    public $is_active;

    public function rules(): array
    {
        return [
            [['id', 'artist_id'], 'integer'],
            [['is_active'], 'boolean'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = HomeHeroImage::find()->with(['artist', 'mediaAsset']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'sort_order' => SORT_ASC,
                    'id' => SORT_ASC,
                ],
            ],
        ]);

        $this->load($params);

        if ($this->validate() === false) {
            return $dataProvider;
        }

        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['artist_id' => $this->artist_id]);
        $query->andFilterWhere(['is_active' => $this->is_active]);

        return $dataProvider;
    }
}
