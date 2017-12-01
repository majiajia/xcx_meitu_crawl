<?php
    require_once '../config_basic.php';
    $dbh = new PDO('mysql:host='.HOST_PORT.';dbname='.DB_NAME,DB_USER,DB_PSD,[
        PDO::ATTR_PERSISTENT => true,
    ]);
    $start_id = 1;
    $end_id = 100;

    $select_album_pic_list_stmt = $dbh->prepare("select id,pic_list from album_info where pic_list!='' and id>=:start_id and id <=:end_id order by id desc;");
    $select_album_pic_list_stmt->bindParam(':start_id',$start_id);
    $select_album_pic_list_stmt->bindParam(':end_id',$end_id);
    $select_album_pic_list_stmt->execute();
    $pic_list = $select_album_pic_list_stmt->fetchAll();

    foreach ($pic_list as $pic_item) {
        $pic_list = explode(',',trim($pic_item['pic_list'])) ;
        $item_id = $pic_item['id'];

        foreach ($pic_list as $pic_item) {
            $pic_size_array = getimagesize($pic_item);
            if(!is_array($pic_size_array)) {
                print 'not array---'.$item_id.PHP_EOL;
                break;
            }
            $pic_size_w = intval($pic_size_array[0]);
            $pic_size_h = intval($pic_size_array[1]);

            if($pic_size_w > $pic_size_h) {
                print 'width and height error---'.$item_id.PHP_EOL;
                break;
            }
        }
    }
    print '~~~~~~~~~~~~~~';

