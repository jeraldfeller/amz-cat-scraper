<?php
require '/var/www/html/amazon-cat-scraper/Model/Init.php';
require '/var/www/html/amazon-cat-scraper/Model/Scraper.php';
require '/var/www/html/amazon-cat-scraper/simple_html_dom.php';
//require 'Model/Init.php';
//require 'Model/Scraper.php';
//require 'simple_html_dom.php';


$scraper = new Scraper();

// get next available category
$cat = $scraper->getCategory();
if(count($cat) > 0){
    $catName = $cat[0]['name'];
    $url = $cat[0]['url'];
    $htmlData = $scraper->curlTo($url);
    if ($htmlData['html']) {

        $html = str_get_html($htmlData['html']);

        // find total results first and get 1%
        $sResultCount = $html->find('#s-result-count', 0);
        if($sResultCount){
            $sResultCount = $sResultCount->plaintext;
            $sResultCount = substr($sResultCount, strpos($sResultCount, "over") + 1);
            $totalCount = trim(str_replace($letters, '', $sResultCount));
            $total1Percent = (1 * $totalCount) / 100;
            $listCounter = 0;

            // list results, 1st page
            $resultsContainer = $html->find('#mainResults', 0);
            if($resultsContainer){
                $results = $resultsContainer->find('.s-result-item');
                for($x = 0; $x < count($results); $x++){
                    $asin = $results[$x]->getAttribute('data-asin');
                    $rank = $results[$x]->getAttribute('data-result-rank');
                    $aLink = $results[$x]->find('.a-link-normal', 0);
                    $price1 = $results[$x]->find('.sx-price-whole', 0);
                    if (!$price1) {
                        $price1 = $results[$x]->find('.a-size-base', 0);
                        if (trim(str_replace($letters, '', $price1->plaintext)) == '') {
                            $price1 = $results[$x]->find('.a-normal', 0);
                        }
                    }
                    if ($price1) {
                        $price2 = $results[$x]->find('.sx-price-fractional', 0);
                        $price = $price1->plaintext;
                        $price = trim(str_replace($letters, '', $price));
                        if($aLink){
                            $prodLink = $aLink->getAttribute('href');
                            $productName = $aLink->plaintext;
                            $scraper->insertProductLink($catName, $prodLink, $asin, $price, $rank+1);
                        }
                    }
                    $listCounter++;
                }
            }


            sleep(mt_rand(1, 3));

//            // proceed next page
            $pg = 2;

            while($listCounter <= $total1Percent){
                $urlPg = $url.'&page='.$pg;
                $htmlData = $scraper->curlTo($urlPg);
                if ($htmlData['html']) {
                    $html = str_get_html($htmlData['html']);
                    // list results, 1st page
                    $resultsContainer = $html->find('#atfResults', 0);
                    if($resultsContainer){
                        $results = $html->find('.celwidget');
                        for($x = 0; $x < count($results); $x++){
                            $asin = $results[$x]->getAttribute('data-asin');
                            $rank = $results[$x]->getAttribute('data-result-rank');
                            $aLink = $results[$x]->find('.a-link-normal', 0);
                            $price1 = $results[$x]->find('.sx-price-whole', 0);
                            if (!$price1) {
                                $price1 = $results[$x]->find('.a-size-base', 0);
                                if($price1){
                                    if (trim(str_replace($letters, '', $price1->plaintext)) == '') {
                                        $price1 = $results[$x]->find('.a-normal', 0);
                                    }
                                }

                            }
                            if ($price1) {
                                $price2 = $results[$x]->find('.sx-price-fractional', 0);
                                $price = $price1->plaintext;
                                $price = trim(str_replace($letters, '', $price));
                                if($aLink){
                                    $prodLink = $aLink->getAttribute('href');
                                    $scraper->insertProductLink($catName, $prodLink, $asin, $price, $rank+1);
                                }
                            }
                            $listCounter++;
                        }
                    }
                }
                $pg++;
                sleep(mt_rand(1, 3));
            }
        }
    }

}
