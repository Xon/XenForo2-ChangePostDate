<?php

namespace SV\ChangePostDate\Service\Post;

use XF\App;
use XF\Entity\Post;
use XF\Mvc\Entity\AbstractCollection;
use XF\Service\AbstractService;

class DateChanger extends AbstractService
{
    /** @var Post[]|AbstractCollection */
    protected $targets;
    /** @var int[] */
    protected $oldDates = [];
    /** @var bool */
    protected $log = true;
    /** @var \XF\Entity\Thread[] */
    protected $targetThreads = [];
    /** @var int */
    protected $newPostDate = 0;

    public function __construct(App $app, AbstractCollection $targets)
    {
        parent::__construct($app);

        $this->targets = $targets;
    }

    /**
     * @param bool $log
     */
    public function setLog($log)
    {
        $this->log = (bool)$log;
    }

    public function changeDate($newPostDate)
    {
        $db = $this->db();

        /** @var \XF\Entity\Thread[] $sourceThreads */
        $targetThreads = [];

        foreach ($this->targets as $post)
        {
            $this->oldDates[$post->post_id] = $post->post_date;
            $post->setOption('log_moderator', false);
            $thread = $post->Thread;
            if (!isset($targetThreads[$thread->thread_id]))
            {
                $thread->setOption('log_moderator', false);
                $targetThreads[$thread->thread_id] = $thread;
            }
        }

        $this->targetThreads = $targetThreads;
        $this->newPostDate = $newPostDate;

        $db->beginTransaction();

        $this->moveDataToTarget();
        $this->rebuildTargetThreads();
        $this->finalActions();

        $db->commit();

        return true;
    }

    protected function moveDataToTarget()
    {
        foreach ($this->targets as $post)
        {
            $post->post_date = $this->newPostDate;
            $post->save(true, false);
        }
    }

    protected function rebuildTargetThreads()
    {
        /** @var \XF\Repository\Thread $threadRepo */
        $threadRepo = \XF::repository('XF:Thread');

        foreach ($this->targetThreads as $threadId => $thread)
        {
            // Rebuild first/last post info
            $thread->rebuildFirstPostInfo();
            $thread->rebuildLastPostInfo();
            $thread->save(true, false);
            // Re-order the posts in the thread according to date
            $threadRepo->rebuildThreadPostPositions($threadId);
        }
    }

    protected function finalActions()
    {
        foreach ($this->targetThreads as $threadId => $thread)
        {
            $this->app->jobManager()->enqueue(
                'XF:SearchIndex',
                [
                    'content_type' => 'post',
                    'content_ids'  => $thread->post_ids,
                ]
            );
        }

        if ($this->log)
        {
            foreach ($this->targets as $post)
            {
                $oldDate = isset($this->oldDates[$post->post_id]) ? $this->oldDates[$post->post_id] : 0;
                $dt = new \DateTime('@' . $oldDate);
                $dt->setTimezone(new \DateTimeZone(\XF::visitor()->timezone));
                // ISO 8601
                $oldDateISO8601 = $dt->format('c');

                $dt = new \DateTime('@' . $post->post_date);
                $dt->setTimezone(new \DateTimeZone(\XF::visitor()->timezone));
                // ISO 8601
                $newDateISO8601 = $dt->format('c');

                $this->app->logger()->logModeratorAction(
                    'post', $post, 'change_date',
                    [
                        'old_date' => $oldDateISO8601,
                        'new_date' => $newDateISO8601,
                    ]
                );
            }
        }
    }
}
