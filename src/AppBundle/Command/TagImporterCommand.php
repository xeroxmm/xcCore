<?php
namespace AppBundle\Command;

use AppBundle\Entity\Tag;
use AppBundle\Entity\TagMeta;
use AppBundle\Safety\Content\ContentHarmonize;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagImporterCommand extends ContainerAwareCommand {
    private $pathToFile;

    public function __construct(?string $name = null) {
        parent::__construct($name);
        $this->pathToFile = dirname(__FILE__,4)."/web/data/tags.csv";
    }

    protected function configure() {
        parent::configure();

        $this
            ->setName('importer:tag:bundle')
            ->setDescription('Bundle Tags by file');
    }
    protected function execute(InputInterface $input, OutputInterface $output) {
        $lines = file($this->pathToFile, FILE_IGNORE_NEW_LINES);
        $output->writeln("Loading configFile...Done!");

        foreach($lines as $line){
            $temp = explode(";", $line, 99);
            if(empty($temp[0]))
                continue;
            $anz = 0; $tagsToChange = [];
            foreach($temp as $key => $value){
                if($key && !empty($value)){
                    $anz++;
                    $tagsToChange[] = ContentHarmonize::getSlugOfString($value);
                }
            }
            $output->writeln($temp[0]." -> has {$anz} child elements");

            /** @var $docCon Connection*/
            $docCon = $this->getContainer()->get('doctrine')->getConnection();
            /** @var $em EntityManagerInterface */
            $em = $this->getContainer()->get('doctrine')->getManager();
            /*$query = $docCon->prepare(
                "SELECT ID, label, slug, count FROM tag_index WHERE slug = '".ContentHarmonize::getSlugOfString($temp[0])."';"
            );*/

            $query = $em->createQueryBuilder()
                        ->select('t')
                        ->from('AppBundle:Tag','t')
                        ->where('t.slug = :param')
                        ->setParameter('param', ContentHarmonize::getSlugOfString($temp[0]))->getQuery()
                        ->getResult();

            /*try {
                $query->execute();
            } catch (DBALException $e) {
                die("could not read ".ContentHarmonize::getSlugOfString($temp[0])." because ".$e->getMessage());
            }
            $res = $query->fetchAll();*/

            /** @var $query Tag[] */
            $hasResults = count($query) > 0;

            //
            // Check if MainTag exists
            //
            $output->writeln("Check if mainTag '{$temp[0]}' exists...".($hasResults ?? FALSE ? "Yes!" : "Nope :("));
            if(($hasResults ?? FALSE) === FALSE){
                $tagEntity = $this->createNewTag($em, $temp[0], ContentHarmonize::getSlugOfString($temp[0]));
            } else {
                /** @var $tagEntity Tag*/
                $tagEntity = $query[0];
            }

            //
            // Loop through SubTags
            //
            foreach($tagsToChange as $subTagX){
                try {
                    $subTag = $em->createQueryBuilder()->select('t')->from('AppBundle:Tag', 't')->where('t.slug = :param')
                                 ->setParameter('param', $subTagX)->getQuery()->getOneOrNullResult();
                } catch (NonUniqueResultException $e) {
                    die("ONE or null for $subTagX not possible: ".$e->getMessage());
                }
                $mainTagCounter = 0;
                if($subTag){
                    /** @var $subTag Tag */
                    //
                    // UPDATE der der TAG-Nexus-DB by tag relation
                    //

                    $output->writeln("DELETE $subTagX");
                    // Load all cIDs with MainTagID and SubTagID -> delete candidates
                    $resSub = $docCon->executeQuery($sql = "SELECT cID FROM nexus_content_tag WHERE tID = {$subTag->getID()} AND 
                                cID IN (SELECT cID FROM nexus_content_tag WHERE tID = {$tagEntity->getID()});");
                    foreach($resSub as $deleteCID){
                        $docCon->executeQuery("DELETE FROM nexus_content_tag WHERE tID = {$subTag->getID()} AND cID = {$deleteCID['cID']}");
                        $output->write(".");
                    }

                    $output->writeln("\nUPDATE $subTagX");
                    // Load all Entries with subTag WITHOUT mainTAG -> update candidates
                    $resSub = $docCon->executeQuery($sql = "SELECT cID FROM nexus_content_tag WHERE tID = {$subTag->getID()} AND 
                                cID NOT IN (SELECT cID FROM nexus_content_tag WHERE tID = {$tagEntity->getID()});");
                    foreach($resSub as $deleteCID){
                        $mainTagCounter++;
                        $docCon->executeQuery("UPDATE nexus_content_tag SET tID = {$tagEntity->getID()} WHERE tID = {$subTag->getID()}");
                        $output->write(".");
                    }
                    $output->writeln("\nSubTag: ".$subTagX." update counter");
                    // Set counter of SubTag zero
                    $docCon->executeQuery('UPDATE tag_index SET `count` = 0 WHERE ID = '.$subTag->getID().';');
                    // update counter of mainTag
                    $sql = 'UPDATE tag_index SET `count` = `count` + '.$mainTagCounter.' WHERE ID = '.$tagEntity->getID().';';
                    $docCon->executeQuery($sql);
                }
                $output->writeln("SubTag: ".$subTagX." Tag update done...");

                $output->writeln("SubTag: ".$subTagX." Do title update...");

                //
                // UPDATE der der TAG-Nexus-DB by title relation
                //
                $resTitle = $docCon->executeQuery(
                    "SELECT ID FROM content_index WHERE title LIKE '%".strtolower($subTagX)."%' 
                    AND ID NOT IN (SELECT cID FROM nexus_content_tag WHERE tID = {$tagEntity->getID()});"
                );
                $mainTagCounter = 0;
                foreach($resTitle as $updateTitled){
                    $mainTagCounter++;
                    $docCon->executeQuery(
                        "INSERT INTO nexus_content_tag (cID, tID) VALUE ({$updateTitled['ID']},{$tagEntity->getID()});"
                    );
                    $output->write('.');
                }
                if($mainTagCounter)
                    $docCon->executeQuery('UPDATE tag_index SET `count` = `count` + '.$mainTagCounter.' WHERE ID = '.$tagEntity->getID().';');


                $output->writeln("\nDONE!");
            }
        }
    }

    private function createNewTag(EntityManagerInterface $em, string $tag, string $slug):Tag {
        $tag = new Tag($tag, $slug);
        $tag->initializeWithZeroCounter();

        $em->persist( $tag );
        try {
            $em->flush();
        } catch (OptimisticLockException $e) {
            die("could not write $tag because ".$e->getMessage());
        }

        return $tag;
    }

}