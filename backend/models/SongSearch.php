<?php

declare(strict_types=1);

namespace backend\models;

use common\models\Song;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;

final class SongSearch extends Model
{
    public $id;
    public $artist_id;
    public $original_language_id;
    public $slug;
    public $default_title;
    public $publication_status;
    public $published_at;

    public function rules(): array
    {
        return [
            [['id', 'artist_id', 'original_language_id', 'published_at'], 'integer'],
            [['slug', 'default_title', 'publication_status'], 'safe'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function attributeLabels(): array
    {
        return [
            'artist_id' => 'Исполнители',
            'published_at' => 'Опубликована',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Song::find()
            ->alias('song')
            ->with([
                'originalLanguage',
                'recordings.recordingArtists.artist',
            ])
            ->addSelect([
                'song.*',
                'published_at_is_empty' => new Expression('song.published_at IS NULL'),
                'recording_artist_names_sort' => $this->createRecordingArtistNamesSortExpression(),
            ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'published_at' => SORT_DESC,
                ],
                'attributes' => [
                    'id',
                    'default_title',
                    'slug',
                    'original_language_id',
                    'publication_status',
                    'published_at' => [
                        'asc' => [
                            'published_at_is_empty' => SORT_ASC,
                            'song.published_at' => SORT_ASC,
                            'id' => SORT_ASC,
                        ],
                        'desc' => [
                            'published_at_is_empty' => SORT_ASC,
                            'song.published_at' => SORT_DESC,
                            'id' => SORT_DESC,
                        ],
                        'label' => 'Опубликована',
                    ],
                    'artist_id' => [
                        'asc' => [
                            'recording_artist_names_sort' => SORT_ASC,
                            'id' => SORT_ASC,
                        ],
                        'desc' => [
                            'recording_artist_names_sort' => SORT_DESC,
                            'id' => SORT_DESC,
                        ],
                        'label' => 'Исполнители',
                    ],
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

        if ($this->artist_id !== null && $this->artist_id !== '') {
            $query->andWhere([
                'id' => $this->createSongIdsByArtistFilterQuery((int) $this->artist_id),
            ]);
        }

        return $dataProvider;
    }

    private function createRecordingArtistNamesSortExpression(): Expression
    {
        $artistNamesQuery = (new Query())
            ->select(['artist_name' => 'artist.default_name'])
            ->from(['recording' => '{{%recording}}'])
            ->innerJoin(
                ['recording_artist' => '{{%recording_artist}}'],
                'recording_artist.recording_id = recording.id',
            )
            ->innerJoin(
                ['artist' => '{{%artist}}'],
                'artist.id = recording_artist.artist_id',
            )
            ->andWhere('recording.song_id = song.id')
            ->groupBy(['artist.id', 'artist.default_name'])
            ->orderBy(['artist.default_name' => SORT_ASC]);

        $artistNamesSql = $artistNamesQuery->createCommand()->rawSql;

        return new Expression(
            'COALESCE((SELECT string_agg(artist_name, \', \') FROM (' . $artistNamesSql . ') AS artist_names), \'\')',
        );
    }

    private function createSongIdsByArtistFilterQuery(int $artistId): Query
    {
        return (new Query())
            ->select(['recording.song_id'])
            ->from(['recording' => '{{%recording}}'])
            ->innerJoin(
                ['recording_artist' => '{{%recording_artist}}'],
                'recording_artist.recording_id = recording.id',
            )
            ->andWhere(['recording_artist.artist_id' => $artistId]);
    }
}
