<?php namespace SmallTeam\ImageBundle\Engines;

use Symfony\Component\DependencyInjection\ContainerInterface;

set_time_limit(0);
error_reporting(0);

/**
 * IMagickImageEngine
 * */
class IMagickImageEngine extends ImageEngine
{

    private $_imagick_executable    = 'convert';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->_imagick_executable = $container->getParameter('smallteam_image.imagick_path') . '/' . $this->_imagick_executable;
    }

    public function crop($from, $to, $dimension, $gravity = 'center')
    {
        exec($this->_imagick_executable.' "'.$from.'" -resize '.$dimension.'^ -gravity '.$gravity.' -crop '.$dimension.'+0+0 +repage -unsharp 0x0.5 "'.$to.'"');
    }

    public function fitIn($from, $to, $dimensions, $gravity = 'center')
    {
        $size = explode('x', $dimensions);
        $command = $this->_imagick_executable.' "'.$from.'"';
        $size_opt   = '';
        if ($size[0] && $size[1]) {
            $resize_opt = $dimensions;
            $size_opt = ' -size '.$dimensions;
        } elseif ($size[0]) {
            $resize_opt = $size[0]. 'x';
        } else {
            $resize_opt = 'x' . $size[1];
        }
        $command.= ' -resize '.$resize_opt.$size_opt.' -unsharp  0x0.5 -gravity center "'.$to.'"';
        exec($command);
    }

    public function fitInBigger($from, $to, $dimensions, $gravity = 'center')
    {
        $size = explode('x', $dimensions);
        $command = $this->_imagick_executable.' "'.$from.'"';
        $size_opt   = '';
        if ($size[0] && $size[1]) {
            $resize_opt = $dimensions;
            $size_opt = ' -size '.$dimensions;
        } elseif ($size[0]) {
            $resize_opt = $size[0]. 'x';
        } else {
            $resize_opt = 'x' . $size[1];
        }
        $command.= ' -resize "'.$resize_opt.'>" '.$size_opt.' -unsharp  0x0.5 -gravity center "'.$to.'"';
        exec($command);
    }

    public function fitOut($from, $to, $dimensions, $gravity = 'center')
    {
        $size = explode('x', $dimensions);
        $command = $this->_imagick_executable.' "'.$from.'"';

        exec('identify -format "%[fx:w]x%[fx:h]" '.$from, $isizes);
        $isize = explode('x', $isizes[0]);

        $to_ratio = $size[0]/$size[1]; //viewport ratio

        $dwp = round($isize[0] / $size[0],5); //diff by width in percents
        $dhp = round($isize[1] / $size[1],5); //diff by height in percents

        if ($dwp > $dhp) {
            $new_w = $isize[1] * $to_ratio;
            $isize[0] = $new_w;
        } else if ( $dwp < $dhp){
            $new_h = $isize[0] / $to_ratio;
            $isize[1] = $new_h;
        }
        $command.= ' -gravity center -crop '.$isize[0].'x'.$isize[1].'+0+0 +repage -resize '.$dimensions.' -unsharp  0x0.5 "'.$to.'"';

        exec($command);
    }

    public function fitInFull($from, $to, $dimensions, $gravity = 'center', $additional_process = array())
    {
        $size = explode('x', $dimensions);
        $command = $this->_imagick_executable.' "'.$from.'"';
        $size_opt   = '';
        if ($size[0] && $size[1]) {
            $resize_opt = $dimensions;
            $size_opt = ' -size '.$dimensions;
        } elseif ($size[0]) {
            $resize_opt = $size[0]. 'x';
        } else {
            $resize_opt = 'x' . $size[1];
        }

        $color = isset($additional_process['color']) ? $additional_process['color'] : 'white';
        $bigger = isset($additional_process['bigger']) ? $additional_process['bigger'] : false;
        $mask = isset($additional_process['mask']) ? $additional_process['mask'] : null;

        $command .= ' -resize "'.$dimensions.($bigger ? '>' : '').'" -size '.$dimensions.' xc:'.$color.' +swap -unsharp  0x0.5 -gravity center -composite "'.$to.'"';
        exec($command);

        if( $mask ) {
            $mask = $this->base_dir.'/'.$mask;
            $command = 'composite -compose Dst_Out  -gravity center "'.$mask.'" "'.$to.'" -alpha Set "'.substr($to,0,strlen($to)-4).'.png"';
            exec($command);
        }
    }
}