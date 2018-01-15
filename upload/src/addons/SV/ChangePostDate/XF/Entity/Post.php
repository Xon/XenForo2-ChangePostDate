<?php

namespace SV\ChangePostDate\XF\Entity;

class Post extends XFCP_Post
{
    public function canChangePostDate(&$error = null)
    {
        $thread = $this->Thread;
        $visitor = \XF::visitor();
        if (!$visitor->user_id || !$thread)
        {
            return false;
        }

        $nodeId = $thread->node_id;

        if ($visitor->hasNodePermission($nodeId, 'SV_ChangePostDate'))
        {
            return true;
        }

        return false;
    }
}
