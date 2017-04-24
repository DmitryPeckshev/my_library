<?php

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;

class AppCache extends HttpCache
{
	return array(
            'default_ttl' => 0,
        );
}
