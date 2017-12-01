<?php
    /*
     * 遍历每个图集的照片，从中选择一张宽高比 height/width 最大的 作为第一张
     */
    require_once '../config_basic.php';
    require_once '../lib/function.php';

    $dbh = new PDO('mysql:host='.HOST_PORT.';dbname='.DB_NAME,DB_USER,DB_PSD,[
        PDO::ATTR_PERSISTENT => true,
    ]);
    if(intval(count($argv)) < 3)  {
        print "php scriptname start_seq,count".PHP_EOL;
        return;
    }
    $start_seq = $argv[1];
    $record_num = $argv[2];

    print 'start process:'.PHP_EOL;
    $album_info_list_stmt = $dbh->prepare("select * from album_info order by id desc limit ".$start_seq.",".$record_num);
    $album_info_list_stmt->execute();
    $album_info_list = $album_info_list_stmt->fetchAll();
    foreach ($album_info_list as $album_info_item) {
        $album_id = $album_info_item['id'];
        $album_pic_list = explode(',',trim($album_info_item['pic_list']));
        $max_h_w_rate = '';
        $new_pic_list = $album_pic_list; //array_unshift array_unique
        foreach ($album_pic_list as $album_pic_item) {
            $cur_h_w_rate = 1;
            $img_size = get_image_w_h($album_pic_item);
            $img_h = $img_size['h'];
            $img_w = $img_size['w'];
            $cur_h_w_rate = $img_h/$img_w;

            if($max_h_w_rate == '') {
                $max_h_w_rate = $cur_h_w_rate;
                continue;
            }
            if($cur_h_w_rate > $max_h_w_rate) {
               array_unshift($new_pic_list,$album_pic_item);
            }
        }
        $new_pic_list = array_unique($new_pic_list);
        if($album_pic_list != $new_pic_list) {
            $update_album_info_item_stmt =  $dbh->prepare("update album_info set pic_list=:pic_list where id=:album_id;");
            $update_album_info_item_stmt->bindParam(':pic_list',implode(',',$new_pic_list));
            $update_album_info_item_stmt->bindParam(':album_id',$album_id);
            $update_album_info_item_stmt->execute();
            print $album_info_item['id'].':need update album cover'.PHP_EOL;
        } else {
            print $album_info_item['id'].':not need update album cover'.PHP_EOL;
        }
    }

