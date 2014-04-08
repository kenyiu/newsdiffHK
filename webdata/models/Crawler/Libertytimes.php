<?php

class Crawler_Libertytimes
{
    public static function crawl($insert_limit)
    {
        // http://www.libertytimes.com.tw/2013/new/aug/13/today-t3.htm
        // http://iservice.libertytimes.com.tw/liveNews/news.php?no=852779&type=%E7%A4%BE%E6%9C%83

        $categories = array('即時新聞', '政治', '社會', '科技', '國際', '財經', '生活', '體育', '影劇', '趣聞');

        $content = '';
        foreach ($categories as $category) {
            $url = 'http://iservice.libertytimes.com.tw/liveNews/list.php?type=' . urlencode($category);
            $content .= Crawler::getBody($url, 0.5, false);
        }
        $url = 'http://iservice.libertytimes.com.tw/liveNews/?Slots=LiveMore';
        $content .= Crawler::getBody($url, 0.5, false);

        preg_match_all('#news\.php?[^"]*#', $content, $matches);
        $insert = $update = 0;
        foreach ($matches[0] as $link) {
            $update = 0;
            $url = Crawler::standardURL('http://iservice.libertytimes.com.tw/liveNews/' . $link);
            $insert += News::addNews($url, 5);
            if ($insert_limit <= $insert) {
                break;
            }
        }

        $base = 'http://www.libertytimes.com.tw/' . date('Y') . '/new/' . strtolower(date('M')) . '/' . intval(date('d')) . '/';
        $content = Crawler::getBody($base . 'menu2.js', 0.5, false);

        preg_match_all('#today-.*\.htm#', $content, $matches);
        foreach ($matches[0] as $link) {
            try {
                $update ++;
                $url = $base . $link;
                $insert += News::addNews($url, 5);
                if ($insert_limit <= $insert) {
                    break;
                }
            } catch (Pix_Table_DuplicateException $e) {
            }
        }
        return array($update, $insert);
    }

    public static function parse($body)
    {
        if (strpos($body, '<div class="newsbox"><ul><li>網址錯誤</li></ul></div>')) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        if ("<script>alert('無這則新聞');location='index.php';</script>" == trim($body)) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        if (strpos($body, '<div class="newsbox"><ul><li>無這則新聞</li></ul></div>')) {
            $ret = new StdClass;
            $ret->title = $ret->body = 404;
            return $ret;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        if (!$doc->getElementById('newsti')){
            // 新版
            if (strpos($body, '無此則新聞') and $doc->getElementsByTagName('title')->item(0)->nodeValue == '自由時報電子報') {
                $ret = new StdClass;
                $ret->title = $ret->body = 404;
                return $ret;
            }

            if ($doc->getElementById('newstext') and $doc->getElementsByTagName('h1')->length == 1) {
                $body = '';
                foreach ($doc->getElementById('newstext')->childNodes as $child_node) {
                    if ($child_node->nodeName == 'p') {
                        $body = $body . "\n" . $child_node->nodeValue;
                    }
                }
                $title = $doc->getElementsByTagName('h1')->item(0)->nodeValue;
                $ret->title = $title;
                $ret->body = $body;
                return $ret;
            }
            throw new Exception('newsti not found');
        }
        $ret->title = trim($doc->getElementById('newsti')->childNodes->item(0)->nodeValue);

        foreach ($doc->getElementsByTagName('div') as $div_dom) {
            if ($div_dom->getAttribute('class') == 'news_content') {
                $ret->body = trim(Crawler::getTextFromDom($div_dom));
            }
        }

        return $ret;
    }

    public static function parse2($body)
    {
        $body = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=big5" />', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">', $body);
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($body);
        $ret = new StdClass;
        $ret->title = trim($doc->getElementById('newtitle')->nodeValue);

        foreach ($doc->getElementById('newsContent')->childNodes as $node) {
            if ($node->nodeName == 'span' and $node->getAttribute('id') != 'newtitle') {
                $ret->body = trim(Crawler::getTextFromDom($node));
                break;
            }
        }

        if (!$ret->title) {
            $ret->title = $ret->body = '';
            // http://www.libertytimes.com.tw/2013/new/aug/13/today-o13.htm
            foreach ($doc->getElementById('newsContent')->childNodes as $node) {
                if ($node->nodeName == 'span' and $node->getAttribute('class') == 'insubject1') {
                    $ret->title = $node->nodeValue;
                }
                if ($node->nodeName == 'table') {
                    $ret->body = trim(Crawler::getTextFromDom($node));
                }
            }
        }

        return $ret;
    }
}
