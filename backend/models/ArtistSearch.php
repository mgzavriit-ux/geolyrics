<?php

declare(strict_types=1);

namespace backend\models;

use common\models\Artist;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class ArtistSearch extends Model
{
    public $id;
    public $slug;
    public $type;
    public $default_name;
    public $publication_status;

    public function rules(): array
    {
        return [
            [['id'], 'integer'],
            [['slug', 'type', 'default_name', 'publication_status'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Artist::find();

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
        $query->andFilterWhere(['type' => $this->type]);
        $query->andFilterWhere(['publication_status' => $this->publication_status]);
        $query->andFilterWhere(['ilike', 'slug', $this->slug]);
        $query->andFilterWhere(['ilike', 'default_name', $this->default_name]);

        return $dataProvider;
    }
}
