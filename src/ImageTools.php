<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/28 0028
 * Time: 上午 11:32
 */

namespace lixin ;


class ImageTools{

    public static function gifInsertWater ($animation_buffer, $water, $savePath, $position="top-left"){


        $decoder = new \Intervention\Gif\Decoder();
        $decoder->initFromData($animation_buffer);

        $decoded = $decoder->decode();

        // 这些信息需要写入GIF头，否则图片头不完整会导致某些浏览器无法显示
        $width = $decoded->getCanvasWidth() ;
        $height = $decoded->getCanvasHeight();
        $frames_count = $decoded->countFrames();
        $frames_loop = $decoded->getLoops();

        //var_dump($width); var_dump($height); var_dump($frames_loop); var_dump($frames_count); exit();

        if ($frames_count<=0){
            return ['status'=>false, 'info'=>'至少要存在一帧'];
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

            //$encoder->setFrames([$frame]) ;
            //$encoder->setLoops($frames_loop);
            //$encoder->setCanvas($width, $height);

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
            return ['status'=>false, 'info'=>'帧错误，创建GD对象失败'];
        }

        $result_gif = $result_encoder->encode();

        /*
        $save_fp = fopen($savePath, 'wb+') ;  //  保存图片，需要以二进制写模式打开文件
        fwrite($save_fp, $result_gif) ;
        fclose($save_fp) ;
        */

        header("content-type: image/gif");
        echo $result_gif ;


        return ['status'=>true, 'info'=>'ok'];
    }


}