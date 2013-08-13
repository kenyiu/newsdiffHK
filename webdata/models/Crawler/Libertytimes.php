<?php

class Crawler_Libertytimes
{
    public static function crawl()
    {
        // http://www.libertytimes.com.tw/2013/new/aug/13/today-t3.htm
        // http://iservice.libertytimes.com.tw/liveNews/news.php?no=852779&type=%E7%A4%BE%E6%9C%83

        $categories = array('即時新聞', '政治', '社會', '科技', '國際', '財經', '生活', '體育', '影劇', '趣聞');

        $content = '';
        foreach ($categories as $category) {
            $url = 'http://iservice.libertytimes.com.tw/liveNews/list.php?type=' . urlencode($category);
            $content .= Crawler::getBody($url);
        }
        $url = 'http://iservice.libertytimes.com.tw/liveNews/?Slots=LiveMore';
        $content .= Crawler::getBody($url);

        preg_match_all('#news\.php?[^"]*#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $url = 'http://iservice.libertytimes.com.tw/liveNews/' . $link;
                News::insert(array(
                    'url' => $url,
                    'url_crc32' => crc32($url),
                    'created_at' => time(),
                    'last_fetch_at' => 0,
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
        }

        $base = 'http://www.libertytimes.com.tw/' . date('Y') . '/new/' . strtolower(date('M')) . '/' . date('d') . '/';
        $content = Crawler::getBody($base . 'menu2.js');

        preg_match_all('#today-.*\.htm#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $url = $base . $link;
                News::insert(array(
                    'url' => $url,
                    'url_crc32' => crc32($url),
                    'created_at' => time(),
                    'last_fetch_at' => 0,
                ));
            } catch (Pix_Table_DuplicateException $e) {
            }
        }

    }
}