<?php

class Crawler_Chinatimes
{
    public static function crawl()
    {
        $content = Crawler::getBody('http://www.chinatimes.com');
        $content = Crawler::getBody('http://www.chinatimes.com/newspapers/'); // 日報精選
        $content = Crawler::getBody('http://www.chinatimes.com/newspapers/2601'); // 中國時報
        $content = Crawler::getBody('http://www.chinatimes.com/newspapers/2602'); // 工商時報
        $content = Crawler::getBody('http://www.chinatimes.com/newspapers/2603'); // 旺報
        $content = Crawler::getBody('http://www.chinatimes.com/newspapers/ctw'); // 時周精選
        $content .= Crawler::getBody('http://www.chinatimes.com/rss/focus.xml');

        preg_match_all('#/(newspapers|realtimenews)/([^"\#<]*-)?\d+-\d+["<]?#', $content, $matches);
        foreach (array_unique($matches[0]) as $link) {
            $url = Crawler::standardURL('http://www.chinatimes.com' . rtrim($link, '"<'));
            News::addNews($url, 2);
        }
    }

    public static function parse($body)
    {
        if (preg_match('/抱歉！您所查詢的資料，目前無法找到任何頁面/', $body)) {
            $ret = new StdClass;
            $ret->title = '404';
            $ret->body = '404';
            return $ret;
        }
        $doc = new DOMDocument;
        @$doc->loadHTML($body);
        $article_dom = $doc->getElementsByTagName('article')->item(0);
        $header_dom = $article_dom->getElementsByTagName('header')->item(0);
        $ret = new StdClass;
        $ret->title = trim($header_dom->getElementsByTagName('h1')->item(0)->nodeValue);
        $article_dom = $doc->getElementsByTagName('article')->item(1);

        // 有時候可能會有 div, 有的話就要跳過
        if ($div_pic_dom = $article_dom->getElementsByTagName('div')->item(0)) {
            $dom = $div_pic_dom->nextSibling;
        } else {
            $dom = $article_dom->childNodes->item(0);
        }
        $content = '';
        do {
            $content .= $dom->nodeValue. "\n";
        } while ($dom = $dom->nextSibling);

        $ret->body = trim($content);
        return $ret;
    }
}
