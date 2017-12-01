<?php
    header("Content-Type: text/html;charset=gbk");
    require_once 'simple_html_dom.php';
    require_once '../config_basic.php';

    $dbh = new PDO('mysql:host='.HOST_PORT.';dbname='.DB_NAME,DB_USER,DB_PSD,[
        PDO::ATTR_PERSISTENT => true,
    ]);

    $html = new simple_html_dom();
    $html->load_file('mplmmcomcn.html');
    $album_list = $html->find('.mt-img-wrap-1 a');
    $index = 1;
    foreach ($album_list as $album_item) {
        $album_url = $album_item->href;
        $index ++ ;
        get_album_info($album_url,$dbh);
    }

    /*
     * 获取相册的标签 $(".yxtagspic a").html() 有多个
     * $("#contents a img")
     * 获取相册所有页面的链接 $("#aplist ul li")
     *
     */
    function get_album_info($url,$dbh) {
        $base_url = $url;
        $url = rtrim($url,"/").'.html';

        $ch = curl_init();

        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.131 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $content = curl_exec($ch);
        $error = curl_error($ch);

        if($error != '') {
            print 'download page content error'."\n";
            return;
        }
        if($content == '') {
            print 'download page content empty'."\n";
            return;
        }
        $html = new simple_html_dom();
        $html->load($content);
        $album_title = $html->find(".mt-tit");
        //图集的标题
        $album_title = $album_title[0]->plaintext;

        //图集中图片的数量
        $album_total_pic_num_list = $html->find(".ax-avg-sm-3 li");
        $album_total_pic_num = intval(count($album_total_pic_num_list));

        //相册的标签
        $album_label_name = '美女';
//        http://m.plmm.com.cn/xinggan/177/
//        http://m.plmm.com.cn/xinggan/177/2.html

        $album_pic_array = [];

        $album_pic_1st_item = get_album_pic_info($base_url);
        array_push($album_pic_array,$album_pic_1st_item);
        for($index=2;$index <= $album_total_pic_num;$index++) {
            $album_pic_item = get_album_pic_info($base_url.$index.'.html');
            array_push($album_pic_array,$album_pic_item);
        }
        print $url.PHP_EOL;
        print $album_title.PHP_EOL;
        print implode(',',$album_pic_array).PHP_EOL;
        print '@@@@@@@@'.PHP_EOL;

        $insert_album_info_stmt = $dbh->prepare("insert into album_info(label_name,title,pic_list,url)VALUE (:label_name,:title,:pic_list,:url)");
        $insert_album_info_stmt->bindParam(":label_name",$album_label_name);
        $insert_album_info_stmt->bindParam(":title",$album_title);
        $insert_album_info_stmt->bindParam(":pic_list",implode(',',$album_pic_array));
        $insert_album_info_stmt->bindParam(":url",$url);
        $insert_album_info_stmt->execute();
    }

    function get_album_pic_info($album_pic_url) {

        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt($ch, CURLOPT_URL,$album_pic_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.131 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $content = curl_exec($ch);
        $error = curl_error($ch);

        if($error != '') {
            print 'download page content error 1:'.$error."\n";
            return;
        }

        if($content == '') {
            print 'download page content empty'."\n";
            return;
        }
        $html = new simple_html_dom();
        $html->load($content);

        $album_pic_list = $html->find(".mt-page-card-bd img");
        return $album_pic_list[0]->src;
    }