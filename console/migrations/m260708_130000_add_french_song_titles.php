<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260708_130000_add_french_song_titles extends Migration
{
    public function safeUp(): void
    {
        $languageId = $this->findLanguageIdByCode('fr');
        $titles = $this->getSongTitles();
        $songIdsBySlug = $this->findSongIdsBySlug(array_keys($titles));
        $translationsBySongId = $this->findFrenchTranslationsBySongId(
            array_values($songIdsBySlug),
            $languageId,
        );
        $timestamp = time();

        foreach ($titles as $slug => $title) {
            $songId = $songIdsBySlug[$slug] ?? null;

            if ($songId === null) {
                throw new RuntimeException('Song "' . $slug . '" was not found.');
            }

            $row = [
                'song_id' => $songId,
                'language_id' => $languageId,
                'title' => $title,
                'subtitle' => null,
                'description' => null,
                'history' => null,
                'translation_source' => 'manual',
                'provider' => null,
                'model' => null,
                'review_status' => 'approved',
                'updated_at' => $timestamp,
            ];
            $translation = $translationsBySongId[$songId] ?? null;

            if ($translation === null) {
                $row['created_at'] = $timestamp;
                $this->insert('{{%song_translation}}', $row);

                continue;
            }

            if (trim($translation['title']) !== '') {
                continue;
            }

            $this->update(
                '{{%song_translation}}',
                [
                    'title' => $title,
                    'updated_at' => $timestamp,
                ],
                ['id' => $translation['id']],
            );
        }
    }

    public function safeDown(): void
    {
        echo "m260708_130000_add_french_song_titles cannot be reverted.\n";
    }

    private function findLanguageIdByCode(string $code): int
    {
        $languageId = (new Query())
            ->select(['id'])
            ->from('{{%language}}')
            ->andWhere(['code' => $code])
            ->scalar();

        if ($languageId === false || $languageId === null) {
            throw new RuntimeException('Language "' . $code . '" was not found.');
        }

        return (int) $languageId;
    }

    /**
     * @param string[] $slugs
     * @return array<string, int>
     */
    private function findSongIdsBySlug(array $slugs): array
    {
        $query = (new Query())
            ->select(['id', 'slug'])
            ->from('{{%song}}')
            ->andWhere(['slug' => $slugs]);
        $songIdsBySlug = [];

        foreach ($query->each() as $row) {
            $songIdsBySlug[(string) $row['slug']] = (int) $row['id'];
        }

        return $songIdsBySlug;
    }

    /**
     * @param int[] $songIds
     * @return array<int, array{id:int, title:string}>
     */
    private function findFrenchTranslationsBySongId(array $songIds, int $languageId): array
    {
        $query = (new Query())
            ->select(['id', 'song_id', 'title'])
            ->from('{{%song_translation}}')
            ->andWhere(['song_id' => $songIds])
            ->andWhere(['language_id' => $languageId]);
        $translationsBySongId = [];

        foreach ($query->each() as $row) {
            $translationsBySongId[(int) $row['song_id']] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
            ];
        }

        return $translationsBySongId;
    }

    /**
     * @return array<string, string>
     */
    private function getSongTitles(): array
    {
        return [
            'alilo' => 'Alilo',
            'diplipito' => 'Diplipito',
            'kartuli-disko' => 'Disco géorgien',
            'georgia' => 'La Géorgie',
            'zgva-gelavs' => 'Le vent de l’amour a soufflé',
            'chemi-mta-da-chemi-buneba' => 'Ma montagne et ma nature',
            'otsnebis-kalaki' => 'La ville des rêves',
            'chemi-siqvarulit-shen-ar-shegatsuxeb' => 'Je ne te dérangerai pas avec mon amour',
            'terapia' => 'Thérapie',
            'shen-anateb' => 'Tu brilles',
            'herio-bichebo' => 'Allez, les gars !',
            'me-da-shen' => 'Toi et moi',
            'ertad-viarot' => 'Marchons ensemble',
            'nu-geshinia' => 'N’aie pas peur',
            'shevechvie-landebs' => 'Je me suis habitué aux ombres',
            'isev-shen-da-isev-shen' => 'Encore toi, encore toi',
            'me-rom-lamazi-tvalebi-mkondes' => 'Si j’avais de beaux yeux',
            'chamovukroleb-chem-sakartveloshi' => 'Je filerai à travers ma Géorgie',
            'nasakhlarebze' => 'Sur les ruines',
            'tsiteli-basanoshkebi' => 'Les sandales rouges',
            'dzveli-simghera' => 'La vieille chanson',
            'shatilis-daughter' => 'La fille de Shatili',
            'shatilis-asulo' => 'La fille de Shatili',
            'damchknari-iebi' => 'Les violettes fanées',
            'shentvis-midseria' => 'C’est écrit pour toi',
            'akhunis-goro' => 'Le mont Akhoun',
            'acharuli-gandagana' => 'Gandagana adjarien',
            'ra-damadzinebs' => 'Comment pourrais-je dormir ?',
            'darial-mtashi' => 'Dans les montagnes de Darial',
            'erti-shekhedvit-shenze-vtitineb' => 'Dès le premier regard, je parle de toi',
            'simghera-megobrobaze' => 'Chanson sur l’amitié',
            'vedreba' => 'Prière (le passé a tout emporté)',
            'cota-kidev-moitmine-gulo' => 'Patiente encore un peu, mon cœur',
            'ghighilo' => 'Bleuet',
            'shen-rom-tskhovrobde-zghvastan' => 'Si tu vivais au bord de la mer',
            'satrpialo' => 'Chanson d’amour',
            'gogo-romelsac-uqvarda-tsvima' => 'La fille qui aimait la pluie',
            'me-shentan-dsvimad-movida' => 'Je viendrai vers toi comme la pluie',
            'chemi-aghara-kxar-morcha' => 'Tu n’es plus à moi, c’est fini',
            'miqvarkxar-tavdavitsqebit' => 'Je t’aime à la folie',
            'meskhuri' => 'Meskhuri',
            'iqideba-sakartvelo' => 'Géorgie à vendre',
            'sno' => 'Sno',
            'bindisperi-sopeli' => 'Le village couleur crépuscule',
            'lamazi-dghe' => 'Belle journée',
            'ocneba' => 'Rêve',
            'zetsa-tiris' => 'Le ciel pleure',
            'netav-isev-viyo-patara' => 'Si seulement j’étais encore enfant',
            'gamiprindi' => 'Envole-toi',
            'samshoblov-shen-xar' => 'Patrie, c’est toi',
            'dros-sheviqvareb' => 'J’aimerai le temps',
            'giqidi-giqidi-pachuchebs' => 'Je t’achèterai des chaussons',
            'iagundi' => 'Rubis',
            'daigvianes' => 'Ils sont en retard',
            'ra-kargi-khar' => 'Comme tu es belle !',
            'chemi-khalkhi' => 'Mon peuple',
            'mtidan-movqveba-bilikebs' => 'Je descends les sentiers depuis la montagne',
            'me-shen-miyvarxar' => 'Je t’aime',
            'chveno-tbilis-kalako' => 'Notre ville de Tbilissi',
            'mshvidobit' => 'Adieu',
            'rogor-miqvarkhar' => 'Comme je t’aime',
            'tsvima-modis' => 'Il pleut',
            'chemo-peria' => 'Ma fée',
            'iavnana' => 'Berceuse',
            'ghimili-varigot' => 'Offrons des sourires',
            'apareka' => 'Apareka',
            'krizantemebi' => 'Chrysanthèmes',
            'gulis-trfialo' => 'Amour du cœur',
            'dedi' => 'Maman',
            'tkha-da-venakhi' => 'La chèvre et la vigne',
            'samshoblo' => 'Patrie',
            'me-mholod-siqvaruli-minda' => 'Je ne veux que l’amour',
            'ar-meko' => 'Cela ne m’a pas suffi',
            'lomisa' => 'Lomisa',
            'dro' => 'Le temps',
            'nami-vels' => 'La rosée au champ',
            'grubeli-kari-niavi' => 'Nuage, vent, brise',
            'maia' => 'Maia',
            'tsitsinatela' => 'Luciole',
            'churchuli' => 'Chuchotement',
            'mahindzhi-var' => 'Je suis laid',
            'tbiliso' => 'Tbiliso',
            'khma-gulisa' => 'Voix du cœur',
            'garet' => 'Dehors',
            'caucasian-ballad' => 'Ballade caucasienne',
            'sad-gedzeba' => 'Où te chercher',
            'ar-minda-gulis-gatkino' => 'Je ne veux pas te briser le cœur',
            'dzveli-iasamani' => 'Le vieux lilas',
            'alal-me' => 'Sans péché',
            'dont-want-to-loose-time' => 'Je ne veux pas perdre de temps',
            'hari-harale' => 'Hari Harale',
            'dumaya-o-more' => 'Mer de pensées',
            'kals-mshvenier-kals' => 'À une belle femme',
            'sikhvaruli-ar-ikhneba' => 'Il n’y aura pas d’amour',
            'mikhvarkhar' => 'Je t’aime',
            'an-raga-unda-giambo' => 'Que pourrais-je encore te raconter',
            'ramdeni-malodine' => 'Combien de temps m’as-tu fait attendre',
            'qvavilebis-kveqana' => 'Le pays des fleurs',
            'uto' => 'Fer à repasser',
            'gazaphulda' => 'Le printemps est arrivé',
            'vazha' => 'Vaja',
            'ra-chemi-bralia' => 'En quoi est-ce ma faute ?',
            'turpav' => 'Beauté',
            'kuchashi-ertkhel' => 'Un jour dans la rue',
            'violets' => 'Violettes',
            'song-is-all-i-have' => 'Il ne me reste que la chanson',
            'serenada' => 'Sérénade',
            'siqvarulis-ghame' => 'Nuit d’amour',
            'imeritinskaya-oda' => 'Oda imérétienne',
            'kekela-da-maro' => 'Kekela et Maro',
            'ah-turpav-turpav' => 'Ah, beauté, beauté',
            'shen-makhvel' => 'Tu es venue',
            'kakhuri-nana' => 'Berceuse kakhetienne',
            'danama-danama' => 'Berceuse (danama danama)',
            'suliko' => 'Suliko',
            'gzebi' => 'Routes',
            'trialebs' => 'La roue du destin tourne',
            'metevzis-simghera' => 'Chanson du pêcheur',
            'chito-gvrito' => 'Petit oiseau',
            'zurgchanta' => 'Sac à dos',
            'velvet' => 'Velvet',
            'in-vino-veritas' => 'In vino veritas',
            'domino' => 'Domino',
            'am-dros-nugar-eli' => 'N’attends plus ce temps',
            'relado' => 'Relado',
            'panduri' => 'Panduri',
            'iasamani' => 'Lilas',
            'gamodi' => 'Sors !',
            'ero' => 'Peuple',
            'erti-simi' => 'Une corde',
            'geo' => 'Geo',
            'capela-53' => 'Capella, 53',
            'damina' => 'Damina',
            'arsad' => 'Nulle part',
            'vatman' => 'Vatman',
            'rembo' => 'Rambo',
            'iavnana-rasats-axla' => 'Berceuse',
            'ananke' => 'Ananké',
            'tu-gamomitsvdi-hels' => 'Si tu me tends la main',
            'piramidebi' => 'Pyramides',
            'shemikhvardi' => 'Je suis tombé amoureux',
            'bachia' => 'Petit lapin',
            'gala' => 'Gala',
            'gegis-leksze' => 'Sur les vers de Gegi',
            'or-alublad' => 'Comme deux cerises',
            'mdzhera' => 'Je crois',
            'tu-gamova-mze' => 'Si le soleil se lève',
            'ramdeni-dghea-ramdeni-ghame' => 'Combien de jours, combien de nuits',
            'shansi-ar-aris' => 'Aucune chance',
            'me-movigone' => 'J’ai inventé',
            'kalakuri' => 'Chanson urbaine',
            'vazi' => 'Vigne',
            'tango' => 'Tango',
            'tsremlebs-tuchebze' => 'Des larmes sur les lèvres',
            'gazapkhulis-khandzari' => 'Incendie de printemps',
            'damiskhi-damalevine' => 'Verse-moi à boire !',
            'shav-ghvinoshi' => 'Les cerises nagent dans le vin noir',
            'botlshi-chamtsxvdeul-fikrebs' => 'Pensées enfermées dans une bouteille',
            'acharuli' => 'Adjarien',
            'shopenis-karma' => 'Le vent de Chopin',
            'mgzavruli' => 'Chanson de voyage',
            'arada-miqvarhar' => 'Et pourtant je t’aime',
            'me-mkholod' => 'Rien, simplement',
            'gaigvidza-bunebam' => 'La nature s’est réveillée',
            'dzaghli-qephda' => 'Le chien aboyait',
        ];
    }
}
