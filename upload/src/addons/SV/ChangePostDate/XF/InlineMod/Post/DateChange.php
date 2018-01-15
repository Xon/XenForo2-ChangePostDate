<?php

namespace SV\ChangePostDate\XF\InlineMod\Post;

use DateTime;
use DateTimeZone;
use XF\Http\Request;
use XF\Mvc\Controller;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Entity;
use XF\InlineMod\AbstractAction;


class DateChange extends AbstractAction
{
    public function getTitle()
    {
        return \XF::phrase('sv_change_post_date');
    }

    protected function canApplyInternal(AbstractCollection $entities, array $options, &$error)
    {
        $result = parent::canApplyInternal($entities, $options, $error);

        // Prevent user applying this action to multiple posts at once
        if ($result)
        {
            if (count($entities) > 1 || count($entities) == 0)
            {
                $error[] = 'sv_please_select_at_most_one_post';

                return false;
            }
        }

        return $result;
    }

    protected function applyInternal(AbstractCollection $entities, array $options)
    {
        // change post date
        foreach ($entities AS $entity)
        {
            /** @var \XF\Entity\Post $entity */
            $this->applyToEntity($entity, $options);
        }

        // rebuild thread
        if ($entities)
        {
            // Rebuild first/last post info
            /** @var \XF\Entity\Thread $thread */
            $thread = $entities->first()->Thread;
            $thread->rebuildFirstPostInfo();
            $thread->rebuildLastPostInfo();
            $thread->save();

            // Re-order the posts in the thread according to date
            /** @var \XF\Repository\Thread $threadRepo */
            $threadRepo = \XF::repository('XF:Thread');
            $threadRepo->rebuildThreadPostPositions($thread->thread_id);

            // Queue the search index updater
            $this->app()->jobManager()->enqueue(
                'XF:SearchIndex', [
                'content_type' => 'post',
                'content_ids'  => $thread->post_ids
            ]
            );
        }
    }

    protected function canApplyToEntity(Entity $entity, array $options, &$error = null)
    {
        /** @var \SV\ChangePostDate\XF\Entity\Post $entity */
        return $entity->canChangePostDate($error);
    }

    protected function applyToEntity(Entity $entity, array $options)
    {
        // change post date
        $newPostDate_ISO8601 = $options['datechange'];
        $newPostDate = strtotime($newPostDate_ISO8601);
        if (!$newPostDate)
        {
            throw new \InvalidArgumentException(\XF::phrase('sv_please_enter_valid_date_format'));
        }

        $entity->set('post_date', $newPostDate);
        $entity->save();
    }

    public function renderForm(AbstractCollection $entities, Controller $controller)
    {
        $post = $entities->first();
        $dt = new DateTime('@' . $post['post_date']);
        $dt->setTimezone(new DateTimeZone(\XF::visitor()->timezone));
        // ISO 8601
        $formatted_date = $dt->format('c');

        $viewParams = [
            'post'           => $post,
            'formatted_date' => $formatted_date,
            'posts'          => $entities,
        ];

        return $controller->view('XF:Public:InlineMod\Post\DateChange', 'inline_mod_post_datechange', $viewParams);
    }

    public function getFormOptions(AbstractCollection $entities, Request $request)
    {
        return [
            'datechange' => $request->filter('datechange', 'str'),
        ];
    }
}
