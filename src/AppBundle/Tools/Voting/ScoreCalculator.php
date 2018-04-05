<?php

namespace AppBundle\Tools\Voting;

use AppBundle\Entity\Content;
use AppBundle\Entity\ContentLikeDistiller;
use Doctrine\ORM\EntityManagerInterface;

class ScoreCalculator {
    private $em;
    private $timeWindowInSeconds;

    const SCORE_TYPE_TIME    = 1;
    const SCORE_TYPE_LIKE    = 2;
    const SCORE_TYPE_LOVE    = 3;
    const SCORE_TYPE_DISLIKE = -1.5;

    function __construct(EntityManagerInterface $em = NULL) {
        $this->em                  = $em;
        $this->timeWindowInSeconds = 1800;
    }

    public function calculateScoreOnContentAction(Content $contentObj, float $scoreAdd) {
        $this->calculateScoreOnContentID($contentObj, $scoreAdd);
    }

    public function calculateScoreForContentIDOnLikelihood(int $contentID, float $likelihood) {
        $random = mt_rand(0, 1000);
        if ($random > $likelihood * 1000)
            return;

        //$this->calculateScoreOnContentID($contentID);
    }

    public function calculateScoreOnDatabasePositionOnLikelihood(float $likelihood) {
        $random = mt_rand(0, 1000);
        if ($random > $likelihood * 1000)
            return;

        //$this->calculateScoreOnDatabasePosition();
    }

    private function calculateScoreOnContentID(Content $contentObj, float $score) {
        $now = time();
        $score = round($score,6);
        // timestamp has to be younger than 1 hour
        $qb = $this->em->createQueryBuilder()->select('d,c')
                       ->from('AppBundle:ContentLikeDistiller', 'd')
                       ->leftJoin('d.contentObj', 'c')
                       ->where('c.ID = ' . $contentObj->getID())
                       ->orderBy('d.timestamp', 'DESC')
                       ->setMaxResults(1)
                       ->getQuery()
                       ->getResult();

        if ($qb === FALSE)
            return;

        // Calculate new Score Value

        if (!$qb) {
            // new Entry
            $cObj = $contentObj;

            $distilled = new ContentLikeDistiller();
            $distilled->setTimestamp($now);
            $distilled->setContentObj($cObj);
            $distilled->setDeltaOldToNew($score);
            $distilled->setValueNew($score);
            $distilled->setValueOld(0);
            $distilled->setValueReal($score);
        } else {
            // check if the timestamp is okay
            $minTimestamp = ((int)($now / $this->timeWindowInSeconds)) * $this->timeWindowInSeconds;
            /** @var $distilled ContentLikeDistiller */
            $distilled = $qb[0];
            if ($distilled->getTimestamp() < $minTimestamp) {
                // We need a new Entry

                $cObj = $contentObj;

                // Calculate Score Delta
                $v    = $this->getNewScoreValuesFromOldOnes($distilled);
                $v[2] = $distilled->getValueNew();

                $distilled = new ContentLikeDistiller();
                $distilled->setTimestamp($now);
                $distilled->setContentObj($cObj);
                $distilled->setDeltaOldToNew($v[1]);
                $distilled->setValueNew(round(max($v[0] + $score, 0),6));
                $distilled->setValueOld(round($v[2],6));
                $distilled->setValueReal(round($v[0] + $score,6));

                $score += $v[0];
            } else {
                // Just UPDATE this entity
                $s = $distilled->getValueNew();

                $query = $this->em->createQuery(
                    'UPDATE AppBundle:ContentLikeDistiller cld 
                          SET cld.valueNew = cld.valueNew + '.$score.', 
                              cld.valueReal = cld.valueReal + '.$score.',
                              cld.timestamp = '.$now.'
                          WHERE cld.ID = '.$distilled->getID());
                $query->execute();

                $score += $s;

                $cObj = $distilled->getContentObj();
            }
        }

        $this->em->persist($distilled);

        $contentParameterObj = $cObj->getParameterObj();
        $contentParameterObj->setScore(round($score,6));

        $this->em->persist($contentParameterObj);

        $this->em->flush();
    }

    private function calculateScoreOnDatabasePosition(int $i) {

    }

    private function getNewScoreValuesFromOldOnes(ContentLikeDistiller $disOld): array {
        $now       = time();
        $timeDelta = $now - $disOld->getTimestamp();
        $times     = (int)($timeDelta / $this->timeWindowInSeconds);

        $growAbs  = $disOld->getValueNew() - $disOld->getValueOld();
        $growRate = $disOld->getValueOld() == 0 ? 1 : $growAbs / $disOld->getValueOld();

        $newScore = $disOld->getValueNew();

        for ($i = 0; $i < $times; $i++) {
            if ($i > 0)
                $growRate = 0;

            if ($growRate > 0.1)
                $newScore *= 1.1;
            else if ($growRate > 0.05)
                $newScore *= 1;
            else if ($growRate > 0.025)
                $newScore *= 0.85;
            else
                $newScore *= 0.7;
        }

        if ($newScore < 0) $newScore = 0;

        $newDelta = $newScore - $disOld->getValueNew();

        return [$newScore, $newDelta];
    }
}