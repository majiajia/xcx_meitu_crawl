<?php
    header("Content-Type: text/html;charset=gbk");
    require_once 'simple_html_dom.php';
    require_once '../config_basic.php';

    $dbh = new PDO('mysql:host='.HOST_PORT.';dbname='.DB_NAME,DB_USER,DB_PSD,[
        PDO::ATTR_PERSISTENT => true,
    ]);

    $start_index = 2;
    $total_index = 126;
    $start_page_1st = 'http://m.5442.com/tag/meinv.html';
    $start_page_2nd_base = 'http://m.5442.com/tag/meinv/';

    get_page_content($start_page_1st,$dbh);
    for($index = $start_index;$index <= $total_index;$index++) {
        $url = $start_page_2nd_base .$index.'.html';
        get_page_content($url,$dbh);
    }
    /*
     * 输入图片集集合的url 返回该页面上所有图集的url
     */
    function get_page_content($url,$dbh) {
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

        $album_list = $html->find("#container li .libox a");

        $index = 1;
        foreach ($album_list as $album_item) {
            $album_url = $album_item->href;
            get_album_info($album_url,$dbh);
        }
    }
    /*
     * 获取相册的标签 $(".yxtagspic a").html() 有多个
     * $("#contents a img")
     * 获取相册所有页面的链接 $("#aplist ul li")
     *
     */
    function get_album_info($url,$dbh) {
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
        $album_title = $html->find(".title h1");
        //图集的标题
        $album_title = $album_title[0]->plaintext.PHP_EOL;
        //图集中图片的数量
        $album_total_pic_num_list = $html->find(".article_page ul li a"); //1/4

        $album_total_page_num = explode('/',$album_total_pic_num_list[0]->plaintext)[1];


        //相册的标签
        $album_label_name_list = [];
        $album_category_name_list = $html->find("#tag-list a");
        foreach ($album_category_name_list as $album_category_name_item) {
            array_push($album_label_name_list,$album_category_name_item->plaintext);
        }

        $base_url = rtrim($url,'.html');

        $album_pic_array = [];

        $album_pic_1st_array = get_album_pic_info($base_url.'.html');
        $album_pic_array = array_merge($album_pic_array,$album_pic_1st_array);
        for($index=2;$index <= $album_total_page_num;$index++) {
            $album_pic_other_array = get_album_pic_info($base_url.'_'.$index.'.html');
            $album_pic_array = array_merge($album_pic_array,$album_pic_other_array);
        }
        print $url.PHP_EOL;
        print $album_title.PHP_EOL;
        print implode(',',$album_pic_array).PHP_EOL;
        print '------'.PHP_EOL;
        if(implode(',',$album_pic_array) == '') {
            return;
        }
        $insert_album_info_stmt = $dbh->prepare("insert into album_info(label_name,title,pic_list,url)VALUE (:label_name,:title,:pic_list,:url)");
        $insert_album_info_stmt->bindParam(":label_name",implode(',',$album_label_name_list));
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
            print 'download page content error 1'."\n";
            return;
        }

        if($content == '') {
            print 'download page content empty'."\n";
            return;
        }
        $html = new simple_html_dom();
        $html->load($content);

        $album_pic_array = [];
        $album_pic_list = $html->find(".tal p a img");
        foreach ($album_pic_list as $album_pic_item) {
            array_push($album_pic_array,$album_pic_item->src);
        }
        return $album_pic_array;
    }