<?php
namespace Core2\Mod\Sources;

use Phpml\Classification\NaiveBayes;


/**
 *
 */
class SentimentAnalysis {

    /**
     * @var NaiveBayes
     */
    protected NaiveBayes $classifier;

    /**
     *
     */
    public function __construct() {
        $this->classifier = new NaiveBayes();
    }


    /**
     * @param $samples
     * @param $labels
     * @return void
     */
    public function train(array $samples, array $labels): void {

        $this->classifier->train($samples, $labels);
    }


    /**
     * @param array $samples
     * @return array|mixed
     */
    public function predict(array $samples) {

        return $this->classifier->predict($samples);
    }
}