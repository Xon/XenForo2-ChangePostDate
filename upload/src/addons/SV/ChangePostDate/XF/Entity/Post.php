<?php

namespace SV\ChangePostDate\XF\Entity;

class Post extends XFCP_Post
{
    /**
     * @param string|null $error
     * @return bool
     */
    public function canChangePostDate(/** @noinspection PhpUnusedParameterInspection */ &$error = null)
    {
        $thread = $this->Thread;
        $visitor = \XF::visitor();
        if (!$visitor->user_id || !$thread)
        {
            return false;
        }

        if ($visitor->hasNodePermission($thread->node_id, 'SV_ChangePostDate'))
        {
            return true;
        }

        return false;
    }
}
