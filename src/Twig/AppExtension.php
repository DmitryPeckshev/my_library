<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return array(
            new TwigFunction('showImg', array($this, 'makeImg'), array('is_safe' => array('html'))),
        );
    }

    public function makeImg(string $link, int $width, int $height)
    {
        return '<img src="'.$link.'" width="'.$width.'" height="'.$height.'" />';
    }
}
