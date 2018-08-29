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

            $newPostDate_ISO8601 = isset($options['datechange']) ? $options['datechange'] : '';
            $newPostDate = @strtotime($newPostDate_ISO8601);
            if (!$newPostDate)
            {
                $error[] ='sv_please_enter_valid_date_format';
            }
        }

        return $result;
    }

    protected function applyInternal(AbstractCollection $entities, array $options)
    {
        // change post date
        $newPostDate_ISO8601 = isset($options['datechange']) ? $options['datechange'] : '';
        $newPostDate = @strtotime($newPostDate_ISO8601);
        if (!$newPostDate)
        {
            throw new \InvalidArgumentException(\XF::phrase('sv_please_enter_valid_date_format'));
        }

        /** @var \SV\ChangePostDate\Service\Post\DateChanger $dateChanger */
        $dateChanger = $this->app()->service('SV\ChangePostDate:Post\DateChanger', $entities);

        $dateChanger->changeDate($newPostDate);
    }

    protected function canApplyToEntity(Entity $entity, array $options, &$error = null)
    {
        /** @var \SV\ChangePostDate\XF\Entity\Post $entity */
        return $entity->canChangePostDate($error);
    }

    protected function applyToEntity(Entity $entity, array $options)
    {
        throw new \LogicException("applyToEntity should not be called on change post date");
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
