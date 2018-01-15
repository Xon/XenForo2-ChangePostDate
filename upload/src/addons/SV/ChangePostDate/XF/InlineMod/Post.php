<?php

namespace SV\ChangePostDate\XF\InlineMod;

use XF\Mvc\Entity\Entity;

/*
 * Extends \XF\Pub\Controller\InlineMod\Post
 */
class Post extends XFCP_Post
{
	public function getPossibleActions()
	{
		$actions = parent::getPossibleActions();

		$actions['datechange'] = $this->getActionHandler('\SV\ChangePostDate\XF:Post\DateChange');

		return $actions;
	}
}
