<?php

namespace AppBundle\Tools\Supply;

use AppBundle\Interfaces\ContentEntityMini;

class ContentStream {
    /** @var ContentEntityMini[] */
    private $listHistory = [];
    /** @var ContentEntityMini[] */
    private $listMain = [];
    /** @var int[] */
    private $listQueue = [];
    /** @var int[] */
    private $listShadow = [];

    private $lengthListHistory = 20;

    public function toContainer(): ContentStreamContainer {
        $object         = new ContentStreamContainer();
        $object->queue  = $this->listQueue;
        $object->shadow = $this->listShadow;
        foreach ($this->listMain as $item)
            if(method_exists($item,"toIndexedArray"))
                $object->main[] = $item->toIndexedArray();
        foreach ($this->listHistory as $item)
            if(method_exists($item,"toIndexedArray"))
                $object->history[] = $item->toIndexedArray();

        return $object;
    }

    public function parseContainer(ContentStreamContainer $container): ContentStream {
        $this->listShadow = $container->shadow;
        $this->listQueue  = $container->queue;
        foreach ($container->main as $item) {
            $a = new ContentStreamMiniEntity();
            $this->listMain[] = $a->constructFromArray($item);
        }
        foreach ($container->history as $item)
            $this->listHistory[] = (new ContentStreamMiniEntity())->constructFromArray($item);

        return $this;
    }

    public function hasShadowList(): bool {
        return count($this->listShadow) > 0;
    }
    public function getShadowListLength():int{
        return count($this->listShadow);
    }
    /**
     * @param $queue int[]
     * @return ContentStream
     */
    public function setQueueList(array $queue): ContentStream {
        $this->listQueue = $queue;

        return $this;
    }

    /** @return int */
    public function getQueueListLength(): int {
        return count($this->listQueue);
    }

    /**
     * @param int $numberOfEntries
     * @return int[]
     */
    public function getQueueList(int $numberOfEntries): array {
        $slices = min(count($this->listQueue), $numberOfEntries);

        if ($slices == 0)
            return [];

        return array_slice($this->listQueue, 0, $slices);
    }

    /** @param $entryID int */
    public function removeQueListEntry(int $entryID) {
        foreach ($this->listQueue as $k => $queue) {
            if ($queue == $entryID)
                unset($this->listQueue[$k]);
        }
        $this->listQueue = array_values($this->listQueue);
    }

    /** @param $entryIDArray int[] */
    public function removeQueListEntries(array $entryIDArray) {
        foreach ($this->listQueue as $k => $queue) {
            if ($queue == in_array($queue, $entryIDArray))
                unset($this->listQueue[$k]);
        }
        $this->listQueue = array_values($this->listQueue);
    }

    /**
     * @param $queue ContentEntityMini[]
     * @return ContentStream
     */
    public function setHistoryList(array $queue): ContentStream {
        $this->listQueue = $queue;

        return $this;
    }

    /**
     * @param $queue int[]
     * @return ContentStream
     */
    public function setShadowList(array $queue): ContentStream {
        $this->listShadow = $queue;

        return $this;
    }

    /**
     * @param int $numberOfEntries
     * @return int[]
     */
    public function getShadowList(int $numberOfEntries = 0) {
        if ($numberOfEntries <= 0)
            return $this->listShadow;

        $slices = min(count($this->listShadow), $numberOfEntries);

        if ($slices == 0)
            return [];

        return array_slice($this->listShadow, 0, $slices);
    }

    /**
     * @param $queue Content[]
     * @return ContentStream
     */
    public function setMainList(array $queue): ContentStream {
        $list = [];
        foreach ($queue as $item) {
            $a = new ContentStreamMiniEntity();
            $list[] = $a->constructFromEntity($item);
        }
        $this->listMain = $list;

        return $this;
    }

    /**
     * @param int $numberOfEntries
     * @return ContentEntityMini[]
     */
    public function getMainList(int $numberOfEntries): array {
        $slices = min(count($this->listMain), $numberOfEntries);

        if ($slices == 0)
            return [];

        return array_slice($this->listMain, 0, $slices);
    }

    /** @return int */
    public function getMainListLength(): int {
        return count($this->listMain);
    }

    /** @param $item ContentEntityMini */
    public function removeMainListEntry(ContentEntityMini $item) {
        foreach ($this->listMain as $key => $listItem) {
            if ($listItem->getID() == $item->getID()) {
                unset($this->listMain[$key]);
                break;
            }
        }
        $this->listMain = array_values($this->listMain);
    }

    /**
     * @param $pos int
     * @return ContentEntityMini
     */
    public function getHistoryLast(int $pos = 1): ContentEntityMini {
        if (count($this->listHistory) < 1 || $pos < 1)
            return new ContentStreamMiniEntity();

        return $this->listHistory[count($this->listHistory) - $pos] ?? new ContentStreamMiniEntity();
    }

    /**
     * @return ContentEntityMini[]
     */
    public function getHistoryStream() {
        return $this->listHistory;
    }

    public function removeHistoryEntryLast() {
        if (count($this->listHistory) > 0)
            unset($this->listHistory[count($this->listHistory) - 1]);
    }

    public function clearMainList() {
        $this->listMain = [];
    }

    public function clearQueueList() {
        $this->listQueue = [];
    }

    /** @param $newEntries ContentEntityMini[] */
    public function addMainEntries(array $newEntries) {
        $this->listMain = array_merge($this->listMain, $newEntries);
    }

    /** @param $entity ContentEntityMini */
    public function addMainEntryAtBegin(ContentEntityMini $entity) {
        $this->listMain = array_values(array_merge([$entity], $this->listMain));
    }

    /** @return bool */
    public function hasPreviousItem(): bool {
        return isset($this->listHistory[count($this->listHistory) - 2]);
    }

    public function getPreviousItem():?ContentEntityMini {
        return $this->listHistory[count($this->listHistory) - 2] ?? NULL;
    }

    /**
     * @param $content ContentStreamMiniEntity
     * @return ContentStream
     */
    public function addHistoryEntry(ContentStreamMiniEntity $content) {
        $this->stripHistoryList();
        if (count($this->listHistory) == 0 || $this->listHistory[count($this->listHistory) - 1]->getID() != $content->getID())
            $this->listHistory[] = $content;

        return $this;
    }

    private function stripHistoryList() {
        if (count($this->listHistory) >= $this->lengthListHistory) {
            $offset            = count($this->listHistory) - $this->lengthListHistory + 1;
            $this->listHistory = array_slice($this->listHistory, $offset, $this->lengthListHistory - 1);
        }
    }
}