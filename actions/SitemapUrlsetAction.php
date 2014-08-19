<?php

namespace xj\sitemap\actions;

use Yii;
use yii\base\Action;
use xj\sitemap\models\Url;
use xj\sitemap\formaters\UrlsetResponseFormatter;

class SitemapUrlsetAction extends Action {

    /**
     * dataProvider
     * @var ActiveDataProvider
     */
    public $dataProvider;

    /**
     * remap type
     * @var bool
     */
    private $isClosure;

    /**
     * Remap Data to Url
     * @var Closure | []
     */
    public $remap;

    /**
     * gzip package.
     * @var bool
     */
    public $gzip = false;

    public function init() {

        if (is_array($this->remap)) {
            $this->isClosure = false;
        } elseif (is_callable($this->remap)) {
            $this->isClosure = true;
        } else {
            throw new \yii\base\ErrorException('remap is wrong type!.');
        }

        return parent::init();
    }

    /**
     * execute run()
     * @return []Url
     */
    public function run() {
        $remap = $this->remap;
        $models = $this->dataProvider->getModels();
        $oModels = [];
        foreach ($models as $model) {
            if ($this->isClosure) {
                //function($model)
                //return Url
                $oModels[] = call_user_func($remap, $model);
            } else {
                $oModels[] = $this->remapModel($model, $this->remap);
            }
        }

        $response = Yii::$app->response;
        $response->formatters[UrlsetResponseFormatter::FORMAT_URLSET] = new UrlsetResponseFormatter([
            'gzip' => $this->gzip,
            'gzipFilename' => 'sitemap.' . $this->dataProvider->getPagination()->getPage() . '.xml.gz',
        ]);
        $response->format = UrlsetResponseFormatter::FORMAT_URLSET;
        return $oModels;
    }

    /**
     * SourceModel Remap to SitemapModel
     * @param Model $model SourceModel
     * @param [] $remap Remap Table
     * @reutrn Url
     */
    private function remapModel($model, $remap) {
        $oModel = new Url();
        foreach ($remap as $dst => $src) {
            if (is_callable($src)) {
                //function($model)
                //return xj\sitemap\models\Sitemap
                $oModel->$dst = call_user_func($src, $model);
            } else {
                $oModel->$dst = $model->$src;
            }
        }
        return $oModel;
    }

}
