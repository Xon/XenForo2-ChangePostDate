<?php

namespace SV\ChangePostDate\XF\Entity;

class Post extends XFCP_Post
{
    /**
     * @param \XF\Phrase|string|null $error
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function canChangePostDate(&$error = null): bool
    {
        $thread = $this->Thread;
        if (!$thread)
        {
            return false;
        }

        $visitor = \XF::visitor();
        if (!$visitor->user_id)
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
