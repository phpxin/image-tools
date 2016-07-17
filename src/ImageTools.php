<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/28 0028
 * Time: 上午 11:32
 */

namespace Phpxin\Tools ;
use \Intervention\Image\ImageManagerStatic as Image;

class ImageTools{

    /**
     * 给gif图片加水印
     * @param string $animation_buffer 二进制图片数据（可以通过 file_get_contents 获取）
     * @param string $water 二进制图片数据/图片路径
     * @param string $save_path 保存路径
     * @param string $position 水印位置，top-left (default)/top/top-right/left/center/right/bottom-left/bottom/bottom-right
     * @return bool 当save_path不为空时，返回true；当save_path 为空时返回处理后的图片二进制数据
     */
    public static function gifInsertWater ($animation_buffer, $water, $save_path='', $position="top-left"){


        $decoder = new \Intervention\Gif\Decoder();
        $decoder->initFromData($animation_buffer);

        $decoded = $decoder->decode();

        // 这些信息需要写入GIF头，否则图片头不完整会导致某些浏览器无法显示
        $width = $decoded->getCanvasWidth() ;
        $height = $decoded->getCanvasHeight();
        $frames_count = $decoded->countFrames();
        $frames_loop = $decoded->getLoops();


        if ($frames_count<=0){
            throw new \RuntimeException("至少要存在一帧") ;
        }

        $result_encoder = new \Intervention\Gif\Encoder() ;
        $result_encoder->setLoops($frames_loop);
        $result_encoder->setCanvas($width, $height);

        $frames = $decoded->getFrames();

        $operate_flag = true;

        foreach ($frames as $index=>$frame){


            $delay = $frame->getDelay();   //  当前帧延迟时间

            $encoder = new \Intervention\Gif\Encoder() ;

            // 使用这个设置头信息，比较稳定
            $encoder->setFromDecoded($decoded, $index);

            $gif = $encoder->encode();


            $gif_to_gd = imagecreatefromstring($gif) ;

            if (!$gif_to_gd){
                $operate_flag = false;
            }else{
                $bk_img = imagecreatetruecolor($width, $height) ;
                imagecopy($bk_img, $gif_to_gd, 0, 0, 0, 0, $width, $height) ;


                $img_water = Image::make($water) ;
                $img_src = Image::make($bk_img)->insert($img_water, $position) ;

                $_gif = imagecreatefromstring($img_src->encode('gif'));

                if (!$_gif){
                    $operate_flag = false;
                }else{
                    $result_encoder->createFrameFromGdResource($_gif, $delay) ;
                    imagedestroy($_gif);
                }

                // 释放图片内存
                // $img_src->destroy(); // 由于这个对象引用GD的对象，这里不需要释放
                $img_water->destroy();
                imagedestroy($bk_img);
                imagedestroy($gif_to_gd);

            }

            if (!$operate_flag){
                break;
            }

        }

        if (!$operate_flag){
            throw new \RuntimeException('帧错误，创建GD对象失败') ;
        }

        $result_gif = $result_encoder->encode();

        if ($save_path){
            $save_fp = fopen($save_path, 'wb+') ;  //  保存图片，需要以二进制写模式打开文件
            fwrite($save_fp, $result_gif) ;
            fclose($save_fp) ;

            return true;
        }else{

            return $result_gif ;

        }

    }


}