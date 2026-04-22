<?php

declare(strict_types=1);

namespace backend\models;

use common\models\Language;
use yii\base\Model;
use yii\data\ActiveDataProvider;

final class LanguageSearch extends Model
{
    public $id;
    public $code;
    public $locale;
    public $name;
    public $native_name;
    public $is_active;
    public $is_default;
    public $sort_order;

    public function rules(): array
    {
        return [
            [['id', 'sort_order'], 'integer'],
            [['code', 'locale', 'name', 'native_name'], 'safe'],
            [['is_active', 'is_default'], 'boolean'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Language::find();

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
        $query->andFilterWhere(['is_active' => $this->is_active]);
        $query->andFilterWhere(['is_default' => $this->is_default]);
        $query->andFilterWhere(['sort_order' => $this->sort_order]);
        $query->andFilterWhere(['ilike', 'code', $this->code]);
        $query->andFilterWhere(['ilike', 'locale', $this->locale]);
        $query->andFilterWhere(['ilike', 'name', $this->name]);
        $query->andFilterWhere(['ilike', 'native_name', $this->native_name]);

        return $dataProvider;
    }
}
